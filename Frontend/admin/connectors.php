<?php
/**
 * Connectors — hub with submodules. Plug the system into external services.
 *
 * Research-backed design (Aug 2026):
 *   - 46% of farmers want automatic push/SMS/email alerts
 *   - 31% reject farm software because it "doesn't integrate with other tools"
 *   - 48% want offline + poor-network operation (local engine always works first)
 *
 * Each submodule = a connector: status, config form, implementation guide.
 * Free connectors (Weather via Open-Meteo, AI hybrid routing) are functional now;
 * paid ones (Google OAuth, M-Pesa Daraja, WhatsApp Cloud API) store credentials
 * and show the exact steps when you're ready to switch them on.
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../includes/config.php';
}
$page_title = 'Connectors - Admin';
$pdo = getDB();

$tab = $_GET['tab'] ?? 'ai';
$validTabs = ['ai','google','mpesa','whatsapp','weather','roadmap'];
if (!in_array($tab, $validTabs, true)) $tab = 'ai';

/* ── Small settings helper ── */
$saveSetting = function (string $key, string $value, string $group = 'connectors') use ($pdo): void {
    if (!$pdo) return;
    try {
        $stmt = $pdo->prepare('SELECT id FROM settings WHERE setting_key=?');
        $stmt->execute([$key]);
        if ($stmt->fetchColumn()) {
            $pdo->prepare('UPDATE settings SET setting_value=?, setting_group=? WHERE setting_key=?')->execute([$value, $group, $key]);
        } else {
            $pdo->prepare('INSERT INTO settings (setting_key, setting_value, setting_group, description) VALUES (?,?,?,?)')
                ->execute([$key, $value, $group, 'Connector credential']);
        }
    } catch (Exception $e) { /* ignore */ }
};

/* ── Save connector settings ── */
$saved = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';
    /* Generic multi-key save (every connector form posts settings[key]=value) */
    if ($action === 'save_multi' && $pdo && isset($_POST['settings']) && is_array($_POST['settings'])) {
        foreach ($_POST['settings'] as $k => $v) {
            $k = trim((string)$k); $v = trim((string)$v);
            if ($k === '') continue;
            $saveSetting($k, $v);
        }
        /* "Test provider" button = save + live test in one tap */
        if (isset($_POST['do_test'])) {
            $provider = trim($_POST['settings']['ai_provider'] ?? 'gemini');
            $key      = trim($_POST['settings']['ai_api_key'] ?? '');
            $model    = trim($_POST['settings']['ai_model'] ?? '');
            $err = testAIConnection($provider, $key, $model);
            if ($err === null) $saved = 'Connected ✓ — the model answered.';
            else { $saved = ''; $error = $err; }
        } else {
            $saved = 'Saved ✓';
        }
    }
    if ($action === 'disconnect' && $pdo) {
        $k = trim($_POST['disconnect_key'] ?? '');
        if ($k !== '') {
            $saveSetting($k, '');
            $saved = 'Disconnected. You can reconnect any time.';
        }
    }
    if ($action === 'test_weather' && $pdo) {
        $lat  = trim($_POST['weather_lat'] ?? '0.5214');
        $lon  = trim($_POST['weather_lon'] ?? '35.2697');
        $saveSetting('weather_lat', $lat);
        $saveSetting('weather_lon', $lon);
        $res  = fetchWeather($lat, $lon);
        if ($res) $saved = 'Weather OK — ' . $res['summary'];
        else      $error = 'Could not reach Open-Meteo. Check internet connection.';
    }
}

include __DIR__ . '/includes/admin_header.php';

/* admin_sidebar.php clobbers $tab to '' — re-assert our default after it. */
if (!in_array($tab, $validTabs, true)) $tab = 'ai';

/* ── Load saved connector settings ── */
$getSetting = function (string $key) use ($pdo): string {
    if (!$pdo) return '';
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=?');
        $stmt->execute([$key]);
        return (string)$stmt->fetchColumn();
    } catch (Exception $e) { return ''; }
};
$cfg = [
    'ai_provider'     => $getSetting('ai_provider'),
    'ai_api_key'      => $getSetting('ai_api_key'),
    'ai_model'        => $getSetting('ai_model'),
    'google_client_id'=> $getSetting('google_client_id'),
    'google_secret'   => $getSetting('google_client_secret'),
    'mpesa_ck'        => $getSetting('mpesa_consumer_key'),
    'mpesa_cs'        => $getSetting('mpesa_consumer_secret'),
    'mpesa_shortcode' => $getSetting('mpesa_shortcode'),
    'wa_phone_id'     => $getSetting('wa_phone_number_id'),
    'wa_token'        => $getSetting('wa_access_token'),
    'weather_lat'     => $getSetting('weather_lat') ?: '0.5214',
    'weather_lon'     => $getSetting('weather_lon') ?: '35.2697',
];
/* Default provider is Gemini Flash latest — the free, current model. */
if (empty($cfg['ai_provider'])) $cfg['ai_provider'] = 'gemini';
/* Recommended model per provider — auto-filled, but editable later */
$defaultModels = [
    'openai'   => 'gpt-4o-mini',
    'gemini'   => 'gemini-flash-latest',
    'deepseek' => 'deepseek-chat',
    'ollama'   => 'llama3.2',
];
if (empty($cfg['ai_model'])) $cfg['ai_model'] = $defaultModels[$cfg['ai_provider']] ?? 'gpt-4o-mini';
$aiConnected = $cfg['ai_api_key'] !== '';
$goConnected = $cfg['google_client_id'] !== '';
$mpConnected = $cfg['mpesa_ck'] !== '';
$waConnected = $cfg['wa_phone_id'] !== '';
$liveCount   = ($aiConnected ? 1 : 0) + ($goConnected ? 1 : 0) + ($mpConnected ? 1 : 0) + ($waConnected ? 1 : 0) + 1; /* weather always live */
/* Full redirect URI for Google OAuth (must be an absolute http(s) URL) */
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$googleRedirect = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/Frontend/admin/connectors.php?tab=google&google=callback';

/**
 * Test an AI provider with a tiny prompt. Returns error string or null on success.
 */
function testAIConnection(string $provider, string $key, string $model = ''): ?string
{
    if ($key === '') return 'Enter an API key first.';
    if ($provider === 'gemini') {
        $model = $model !== '' ? $model : 'gemini-flash-latest';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($key);
        $body = json_encode(['contents' => [['parts' => [['text' => 'Reply with exactly: OK']]]]]);
        $res = httpPostJson($url, $body, ['Content-Type: application/json']);
        if ($res === null) return 'Network error — check internet or key.';
        if (isset($res['error'])) return 'Gemini error: ' . ($res['error']['message'] ?? 'unknown');
        return null;
    }
    // OpenAI-compatible (OpenAI / DeepSeek / local Ollama)
    $base = 'https://api.openai.com/v1';
    if ($provider === 'deepseek') $base = 'https://api.deepseek.com/v1';
    if ($provider === 'ollama')   $base = 'http://localhost:11434/v1';
    if ($model === '') $model = ($provider === 'deepseek') ? 'deepseek-chat' : 'gpt-4o-mini';
    $res = httpPostJson($base . '/chat/completions', json_encode([
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => 'Reply with exactly: OK']],
        'max_tokens' => 5,
    ]), ['Content-Type: application/json', 'Authorization: Bearer ' . $key]);
    if ($res === null) return 'Network error — check internet or key.';
    if (isset($res['error'])) return ($res['error']['message'] ?? 'API error');
    return null;
}

function httpPostJson(string $url, string $body, array $headers): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $out = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($out === false || $code >= 400) return null;
    $j = json_decode((string)$out, true);
    return is_array($j) ? $j : null;
}

/**
 * Open-Meteo: free, no API key, 7–16 day forecasts. Returns summary or null.
 */
function fetchWeather(string $lat, string $lon): ?array
{
    $url = 'https://api.open-meteo.com/v1/forecast?latitude=' . urlencode($lat) . '&longitude=' . urlencode($lon)
         . '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=auto&forecast_days=3';
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $out = curl_exec($ch);
    curl_close($ch);
    $j = json_decode((string)$out, true);
    if (!is_array($j) || empty($j['daily'])) return null;
    $d = $j['daily'];
    $today = [
        'high' => $d['temperature_2m_max'][0] ?? '?',
        'low'  => $d['temperature_2m_min'][0] ?? '?',
        'rain' => $d['precipitation_probability_max'][0] ?? 0,
    ];
    $code = $d['weather_code'][0] ?? 0;
    $desc = ['☀️ Clear','🌤 Mostly clear','⛅ Partly cloudy','☁️ Overcast','🌫 Fog','🌦 Drizzle','🌧 Rain','❄️ Snow','⛈ Thunderstorm'][$code] ?? '🌤';
    return [
        'summary' => sprintf('%s %s°C–%s°C, %d%% chance of rain today', $desc, $today['low'], $today['high'], $today['rain']),
        'raw' => $today,
    ];
}
?>
<style>
/* ═══ Connectors V2 — scoped design ═══ */
.cn-wrap{max-width:880px;margin:0 auto;padding:30px 26px 70px;}
.cn-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap;}
.cn-head h1{margin:0;font-family:'Outfit',sans-serif;font-size:1.45rem;color:var(--w2-heading,#0f172a);letter-spacing:-0.02em;display:flex;align-items:center;gap:10px;}
.cn-head p{margin:6px 0 0;color:#64748b;font-size:0.88rem;max-width:600px;line-height:1.5;}
.cn-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--w2-border,#e7eaf0);border-radius:999px;padding:8px 16px;font-size:0.78rem;font-weight:700;color:#475569;box-shadow:var(--w2-shadow,0 8px 28px rgba(15,23,42,.05));white-space:nowrap;}
.cn-chip b{color:var(--w2-primary,#166534);font-size:0.92rem;}

/* tab bar */
.cn-tabs{display:flex;gap:4px;background:#F1F5F9;border:1px solid #E7EAF0;padding:5px;border-radius:12px;margin-bottom:26px;overflow-x:auto;scrollbar-width:none;}
.cn-tab{display:flex;align-items:center;gap:7px;padding:9px 15px;border-radius:9px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.85rem;color:#64748b;transition:all .2s;}
.cn-tab:hover{color:#334155;background:rgba(255,255,255,.6);}
.cn-tab.active{background:#fff;color:var(--w2-primary,#166534);box-shadow:0 2px 10px rgba(15,23,42,.08);font-weight:700;}
.cn-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}

/* card */
.cn-card{background:var(--w2-card,#fff);border:1px solid var(--w2-border,#e7eaf0);border-radius:var(--w2-radius,14px);box-shadow:var(--w2-shadow,0 8px 28px rgba(15,23,42,.05));overflow:hidden;}
.cn-top{display:flex;align-items:center;gap:14px;padding:20px 24px;border-bottom:1px solid #EEF1F6;}
.cn-icon{width:48px;height:48px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;box-shadow:inset 0 0 0 1px rgba(15,23,42,.06);}
.cn-top h2{margin:0;font-size:1.02rem;color:var(--w2-heading,#0f172a);letter-spacing:-0.01em;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.cn-value{font-size:0.86rem;color:#64748b;margin:4px 0 0;line-height:1.55;max-width:640px;}
.cn-body{padding:22px 24px;}

/* badges — green live, amber needs setup, gray inactive */
.cn-badge{display:inline-flex;align-items:center;gap:6px;font-size:0.72rem;font-weight:700;padding:4px 11px;border-radius:999px;letter-spacing:.01em;white-space:nowrap;}
.cn-badge.live{background:#DCFCE7;color:#166534;}
.cn-badge.warn{background:#FEF3C7;color:#92400E;}
.cn-badge.off{background:#F1F5F9;color:#64748B;}
.cn-badge.info{background:#EEF2FF;color:#4338CA;}

/* forms */
.cn-form{display:flex;flex-direction:column;gap:0;}
.cn-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.cn-field label{display:block;font-weight:600;font-size:0.8rem;color:var(--w2-heading,#0f172a);margin-bottom:6px;}
.cn-field .cn-input{width:100%;padding:10px 13px;border:1.5px solid #D8DEE8;border-radius:var(--w2-radius-sm,9px);font-size:0.9rem;color:var(--w2-text,#1e293b);background:#fff;transition:border-color .2s,box-shadow .2s;box-sizing:border-box;outline:none;font-family:inherit;}
.cn-field .cn-input:focus{border-color:var(--w2-primary,#166534);box-shadow:0 0 0 4px rgba(22,101,52,.12);}
select.cn-input{cursor:pointer;appearance:auto;}
.cn-hint{font-size:0.74rem;color:#94A3B8;margin-top:5px;line-height:1.45;}
.cn-actions{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;}
.cn-actions .btn{margin:0;}

/* connected notice + disconnect */
.cn-on{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:11px;padding:13px 16px;font-size:0.85rem;color:#166534;display:flex;align-items:center;gap:11px;margin-bottom:18px;line-height:1.45;}
.cn-on strong{font-size:0.88rem;}
.cn-on .cn-dot{background:#16A34A;}

/* banners */
.cn-banner{background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;border-radius:11px;padding:12px 16px;font-size:0.85rem;margin-bottom:16px;display:flex;align-items:center;gap:9px;font-weight:600;}
.cn-banner.err{background:#FEF2F2;border-color:#FECACA;color:#B91C1C;}
.cn-banner.ok::before{content:"✓";font-weight:800;}

/* needs chips */
.cn-need{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 14px;}
.cn-need span{display:inline-flex;align-items:center;gap:7px;background:#F8FAFC;border:1px solid #E7EAF0;border-radius:999px;padding:6px 12px;font-size:0.75rem;font-weight:600;color:#475569;}

/* copy buttons + link rows */
.cn-copy{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #D8DEE8;color:#166534;font-weight:700;font-size:0.73rem;padding:5px 12px;border-radius:8px;cursor:pointer;transition:all .15s;vertical-align:middle;font-family:inherit;}
.cn-copy:hover{background:#F0FDF4;border-color:#86EFAC;}
.cn-copy.copied{background:#DCFCE7;border-color:#16A34A;}
.cn-link-row{display:flex;align-items:center;gap:8px;background:#F8FAFC;border:1px solid #E7EAF0;border-radius:8px;padding:7px 10px;margin:6px 0;}
.cn-link-row code{flex:1;font-size:0.74rem;color:#334155;overflow-x:auto;white-space:nowrap;}

/* done marker + sub-headings */
.cn-done{display:flex;align-items:center;gap:8px;background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;border-radius:9px;padding:10px 13px;font-size:0.8rem;font-weight:600;margin-top:12px;}
.cn-sub{font-size:0.8rem;font-weight:700;color:#334155;margin:14px 0 6px;}
.cn-guide-body a{color:var(--w2-primary,#166534);font-weight:600;}
.cn-guide-body ol.cn-ol{margin:6px 0 10px;padding-left:2px;list-style:none;}
.cn-guide-body ol.cn-ol li{display:flex;gap:12px;margin:9px 0;line-height:1.55;}
.cn-psteps{border:1px dashed #D8DEE8;border-radius:10px;padding:14px 16px;margin:10px 0 6px;background:#fff;}
.cn-psteps[hidden]{display:none;}
.cn-psteps .cn-pick{display:inline-flex;align-items:center;gap:6px;background:#E7F5EC;color:#166534;font-weight:700;font-size:0.75rem;padding:4px 11px;border-radius:999px;margin-bottom:10px;}
.cn-autobadge{display:inline-block;background:#E7F5EC;color:#166534;font-size:0.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;padding:2px 7px;border-radius:999px;vertical-align:1px;margin-left:5px;}

/* collapsed technical guide */
.cn-guide{margin-top:20px;border:1px solid #E7EAF0;border-radius:11px;background:#FBFCFE;overflow:hidden;}
.cn-guide summary{cursor:pointer;padding:13px 17px;font-weight:600;font-size:0.85rem;color:#334155;display:flex;align-items:center;gap:9px;list-style:none;user-select:none;}
.cn-guide summary::-webkit-details-marker{display:none;}
.cn-guide summary:hover{background:#F6F8FB;}
.cn-guide[open] summary{border-bottom:1px solid #E7EAF0;color:var(--w2-primary,#166534);}
.cn-guide-body{padding:17px 20px 20px;font-size:0.84rem;color:#475569;line-height:1.6;}
.cn-guide-body h4{margin:16px 0 8px;font-size:0.76rem;text-transform:uppercase;letter-spacing:.05em;color:#94A3B8;}
.cn-guide-body h4:first-child{margin-top:0;}
.cn-step{display:flex;gap:12px;margin:9px 0;}
.cn-step-num{width:22px;height:22px;border-radius:50%;background:#E7F5EC;color:#166534;font-size:0.72rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;border:1px solid #BBF0CC;}
.cn-code{background:#0B1220;color:#A7F3D0;border-radius:8px;padding:11px 14px;font-size:0.74rem;font-family:Consolas,Menlo,monospace;overflow-x:auto;white-space:pre;margin:10px 0;line-height:1.5;}
.cn-note{background:#FFFBEB;border:1px solid #FDE68A;color:#92400E;border-radius:9px;padding:10px 13px;font-size:0.8rem;margin-top:12px;line-height:1.5;}

@media (max-width:640px){
    .cn-grid{grid-template-columns:1fr;}
    .cn-top{flex-wrap:wrap;}
    .cn-actions .btn{flex:1;}
    .cn-wrap{padding:22px 16px 50px;}
}
</style>

<div class="admin-content">
<div class="cn-wrap">

    <div class="cn-head">
        <div>
            <h1><i data-lucide="plug" style="width:24px;height:24px;color:var(--w2-primary,#166534);"></i> Connectors</h1>
            <p>Plug Wangari into AI, Google, M-Pesa, WhatsApp and weather. Each connector is a switch <em>you</em> control — nothing leaves your farm until you turn it on.</p>
        </div>
        <div class="cn-chip"><span class="cn-dot" style="background:#16A34A;"></span><b><?= $liveCount ?></b> of 5 live</div>
    </div>

    <?php if ($saved): ?><div class="cn-banner ok"><?= htmlspecialchars($saved) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="cn-banner err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Submodule tab bar -->
    <div class="cn-tabs">
        <?php
        $tabs = [
            'ai'       => ['plug', 'AI', $aiConnected ? '#16A34A' : '#F59E0B'],
            'google'   => ['cloud', 'Google', $goConnected ? '#16A34A' : '#CBD5E1'],
            'mpesa'    => ['smartphone', 'M-Pesa', $mpConnected ? '#16A34A' : '#CBD5E1'],
            'whatsapp' => ['message-circle', 'WhatsApp & SMS', $waConnected ? '#16A34A' : '#CBD5E1'],
            'weather'  => ['cloud-sun', 'Weather', '#16A34A'],
            'roadmap'  => ['map', 'Roadmap', '#818CF8'],
        ];
        foreach ($tabs as $key => $t): ?>
            <a href="?tab=<?= $key ?>" class="cn-tab <?= $tab === $key ? 'active' : '' ?>">
                <i data-lucide="<?= $t[0] ?>" style="width:15px;height:15px;"></i>
                <?= $t[1] ?>
                <span class="cn-dot" style="background:<?= $t[2] ?>;"></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($tab === 'ai'): ?>
        <!-- ═══ AI PROVIDERS ═══ -->
        <div class="cn-card">
            <div class="cn-top">
                <div class="cn-icon" style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5);">🤖</div>
                <div style="flex:1;">
                    <h2>AI Providers
                        <?php if ($aiConnected): ?><span class="cn-badge live">● Connected</span>
                        <?php else: ?><span class="cn-badge warn">● Setup needed</span><?php endif; ?>
                    </h2>
                    <p class="cn-value">Ask Wangari <strong>anything</strong>. Connect a provider to unlock Thinking mode in Ask Wangari AI — open-ended reasoning, with your farm's numbers as context.</p>
                </div>
            </div>
            <div class="cn-body">
                <?php if ($aiConnected): ?>
                    <div class="cn-on">
                        <span class="cn-dot"></span>
                        <div>Connected to <strong><?= htmlspecialchars(ucfirst($cfg['ai_provider'])) ?></strong>
                            <?= $cfg['ai_model'] !== '' ? '· <code>' . htmlspecialchars($cfg['ai_model']) . '</code>' : '' ?><br>
                            <span style="font-weight:500;">Thinking mode is now available — flip the switch in <strong>Ask Wangari AI</strong>.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" class="cn-form">
                    <input type="hidden" name="_action" value="save_multi">
                    <div class="cn-grid">
                        <div class="cn-field">
                            <label for="ai-provider">Provider</label>
                            <select id="ai-provider" name="settings[ai_provider]" class="cn-input">
                                <option value="openai" <?= $cfg['ai_provider']==='openai'?'selected':'' ?>>OpenAI</option>
                                <option value="gemini" <?= $cfg['ai_provider']==='gemini'?'selected':'' ?>>Google Gemini</option>
                                <option value="deepseek" <?= $cfg['ai_provider']==='deepseek'?'selected':'' ?>>DeepSeek</option>
                                <option value="ollama" <?= $cfg['ai_provider']==='ollama'?'selected':'' ?>>Local Ollama (free, offline)</option>
                            </select>
                            <p class="cn-hint">Ollama runs on your own machine — zero cost, data never leaves the farm.</p>
                        </div>
                        <div class="cn-field">
                            <label for="ai-model">Model <span class="cn-autobadge">auto</span></label>
                            <input id="ai-model" name="settings[ai_model]" class="cn-input" placeholder="<?= htmlspecialchars($defaultModels[$cfg['ai_provider']] ?? '') ?>" value="<?= htmlspecialchars($cfg['ai_model']) ?>" data-default="<?= htmlspecialchars($defaultModels[$cfg['ai_provider']] ?? '') ?>">
                            <p class="cn-hint" id="ai-model-hint">Auto-filled with the recommended model for <?= htmlspecialchars(ucfirst($cfg['ai_provider'])) ?> — you can edit it anytime.</p>
                        </div>
                    </div>
                    <div class="cn-field" style="margin-top:14px;">
                        <label for="ai-key">API key</label>
                        <input id="ai-key" name="settings[ai_api_key]" type="password" class="cn-input" placeholder="sk-…" value="<?= htmlspecialchars($cfg['ai_api_key']) ?>" autocomplete="off">
                        <p class="cn-hint">Get one free: <a href="https://platform.openai.com" target="_blank" rel="noopener">OpenAI</a> · <a href="https://aistudio.google.com" target="_blank" rel="noopener">Google AI Studio</a> · <a href="https://platform.deepseek.com" target="_blank" rel="noopener">DeepSeek</a></p>
                    </div>
                    <div class="cn-actions">
                        <button class="btn btn-primary" type="submit">Save connection</button>
                        <button class="btn btn-outline" type="submit" name="do_test" value="1">Test provider</button>
                        <?php if ($aiConnected): ?>
                            <button class="btn btn-trans" type="submit" name="disconnect_ai" value="1" formaction="?tab=ai" onclick="this.form._action.value='disconnect';this.form.appendChild(Object.assign(document.createElement('input'),{type:'hidden',name:'disconnect_key',value:'ai_api_key'}));return true;">Disconnect</button>
                        <?php endif; ?>
                    </div>
                </form>

                <details class="cn-guide">
                    <summary>📖 How to connect — step by step (no tech skills needed)</summary>
                    <div class="cn-guide-body">
                        <h4>What you'll need</h4>
                        <div class="cn-need">
                            <span>🆓 A free account at one AI provider (or nothing at all if you use Ollama)</span>
                            <span>⏱️ About 5 minutes</span>
                        </div>
                        <p>An <strong>API key</strong> is just a secret password that lets Wangari talk to the AI service. You create it on the provider's website, copy it, and paste it below. That's all. <strong>The steps below change to match the provider you pick.</strong></p>

                        <h4>Step 1 — Get your free key for <span id="cn-current-provider" style="color:var(--w2-primary,#166534);"><?= htmlspecialchars(ucfirst($cfg['ai_provider'])) ?></span></h4>

                        <?php
                        /* Per-provider steps — the visible one follows the saved provider; JS swaps on change */
                        $aiSteps = [
                            'openai' => [
                                'label' => 'OpenAI (simplest)',
                                'icon'  => '☁️',
                                'lines' => [
                                    'Open the link, sign up or log in:',
                                    'https://platform.openai.com/api-keys',
                                    'Click <strong>+ Create new secret key</strong>, give it any name (e.g. "Wangari").',
                                    'Click <strong>Create secret key</strong> — copy the key that appears (starts with <code>sk-</code>). It is <strong>only shown once</strong>, so copy it right away.',
                                ],
                                'copy'  => 'https://platform.openai.com/api-keys',
                            ],
                            'gemini' => [
                                'label' => 'Google Gemini (free tier)',
                                'icon'  => '☁️',
                                'lines' => [
                                    'Open the link and sign in with your Google account:',
                                    'https://aistudio.google.com/app/apikey',
                                    'Click <strong>Create API key</strong>, pick a project (or "Create project"), then copy the key it shows.',
                                ],
                                'copy'  => 'https://aistudio.google.com/app/apikey',
                            ],
                            'deepseek' => [
                                'label' => 'DeepSeek (very cheap)',
                                'icon'  => '☁️',
                                'lines' => [
                                    'Open the link, sign up:',
                                    'https://platform.deepseek.com/api_keys',
                                    'Click <strong>Create new API key</strong>, then copy it.',
                                ],
                                'copy'  => 'https://platform.deepseek.com/api_keys',
                            ],
                            'ollama' => [
                                'label' => 'Local Ollama (free & fully offline)',
                                'icon'  => '🏠',
                                'lines' => [
                                    'Download and install Ollama for your computer:',
                                    'https://ollama.com/download',
                                    'Open a terminal (Windows: search "cmd") and run <code>ollama pull llama3.2</code> — this downloads a free AI that runs on your own machine.',
                                    '<strong>No key needed</strong> — leave the API key box empty, choose <strong>Local Ollama (free)</strong> above, and tap Save.',
                                ],
                                'copy'  => 'https://ollama.com/download',
                            ],
                        ];
                        foreach ($aiSteps as $pKey => $p): ?>
                            <div class="cn-psteps" data-provider="<?= $pKey ?>" <?= $cfg['ai_provider'] === $pKey ? '' : 'hidden' ?>>
                                <span class="cn-pick"><?= $p['icon'] ?> Showing steps for <?= $p['label'] ?></span>
                                <?php $n = 1; foreach ($p['lines'] as $i => $line): ?>
                                    <?php if ($i === 1): /* the link line gets a copy row */ ?>
                                        <div class="cn-step"><span class="cn-step-num"><?= $n++ ?></span><span><?= $line ?></span></div>
                                        <div class="cn-link-row"><code><?= htmlspecialchars($p['copy']) ?></code><button type="button" class="cn-copy" data-copy="<?= htmlspecialchars($p['copy']) ?>">Copy link</button></div>
                                    <?php else: ?>
                                        <div class="cn-step"><span class="cn-step-num"><?= $n++ ?></span><span><?= $line ?></span></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>

                        <h4>Step 2 — Paste your key</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>Paste the key you copied into the <strong>API key</strong> box above.</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Tap <strong>Test provider</strong>. You'll see ✓ if it works, or a plain-language message if something's wrong.</span></div>

                        <h4>Step 3 — Turn on Thinking</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>Open <strong>Ask Wangari AI</strong> in the menu.</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Flip the <strong>Thinking</strong> switch on. Now the assistant can answer any question with full reasoning.</span></div>

                        <div class="cn-done">✅ You're done when: <strong>Test provider</strong> shows "Connected ✓". If it fails, double-check you copied the whole key.</div>
                    </div>
                </details>
            </div>
        </div>

    <?php elseif ($tab === 'google'): ?>
        <!-- ═══ GOOGLE WORKSPACE ═══ -->
        <div class="cn-card">
            <div class="cn-top">
                <div class="cn-icon" style="background:linear-gradient(135deg,#EFF6FF,#DBEAFE);">🔗</div>
                <div style="flex:1;">
                    <h2>Google Workspace
                        <?php if ($goConnected): ?><span class="cn-badge live">● Connected</span>
                        <?php else: ?><span class="cn-badge off">○ Not connected</span><?php endif; ?>
                    </h2>
                    <p class="cn-value">Reminders land in Google Calendar, reports export to Sheets, invoices send from Gmail. Meet the farmer where they already live.</p>
                </div>
            </div>
            <div class="cn-body">
                <form method="post" class="cn-form">
                    <input type="hidden" name="_action" value="save_multi">
                    <div class="cn-grid">
                        <div class="cn-field">
                            <label for="g-client">OAuth Client ID</label>
                            <input id="g-client" name="settings[google_client_id]" class="cn-input" placeholder="….apps.googleusercontent.com" value="<?= htmlspecialchars($cfg['google_client_id']) ?>">
                        </div>
                        <div class="cn-field">
                            <label for="g-secret">Client Secret</label>
                            <input id="g-secret" name="settings[google_client_secret]" type="password" class="cn-input" placeholder="GOCSPX-…" value="<?= htmlspecialchars($cfg['google_secret']) ?>" autocomplete="off">
                        </div>
                    </div>
                    <div class="cn-actions">
                        <button class="btn btn-primary" type="submit">Save credentials</button>
                        <?php if ($goConnected): ?>
                            <button class="btn btn-trans" type="submit" onclick="this.form._action.value='disconnect';this.form.appendChild(Object.assign(document.createElement('input'),{type:'hidden',name:'disconnect_key',value:'google_client_id'}));return true;">Disconnect</button>
                        <?php endif; ?>
                    </div>
                </form>

                <details class="cn-guide">
                    <summary>📖 How to connect — step by step (2026 Google setup)</summary>
                    <div class="cn-guide-body">
                        <h4>What you'll need</h4>
                        <div class="cn-need">
                            <span>🆓 A Google (Gmail) account</span>
                            <span>💻 A computer — this takes about 15 minutes</span>
                        </div>
                        <p>This creates a "bridge" between Wangari and your Google account. <strong>Important:</strong> Google redesigned its setup screens in 2026 — most older guides show old menus that no longer exist. Follow these steps exactly.</p>

                        <h4>Step 1 — Create a Google project</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>Open this link (it takes you straight to the project screen):</span></div>
                        <div class="cn-link-row"><code>https://console.cloud.google.com/projectcreate</code><button type="button" class="cn-copy" data-copy="https://console.cloud.google.com/projectcreate">Copy link</button></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Sign in with your Google account, type any project name (e.g. "Wangari Farm") and click <strong>Create</strong>.</span></div>
                        <div class="cn-step"><span class="cn-step-num">3</span><span>After it's created, make sure the new project is selected in the top-left dropdown.</span></div>

                        <h4>Step 2 — Open the "Get started" wizard</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>In the top search bar of the console, type <code>OAuth</code> and click the <strong>OAuth consent screen</strong> result.</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Click <strong>Get started</strong> (bottom right) to open a 4-section wizard.</span></div>

                        <h4>Step 3 — Pick "External" (the most common mistake!)</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>In the wizard, fill in an <strong>App name</strong> (e.g. "Wangari Farm App") and your email.</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>For <strong>Audience / User type</strong>, choose <strong>External</strong>. <em>Do not choose Internal — it can't be changed later and your app won't work.</em></span></div>
                        <div class="cn-step"><span class="cn-step-num">3</span><span>Fill in the contact email, accept the terms, and click <strong>Create</strong>.</span></div>

                        <h4>Step 4 — Add yourself as a test user</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>In the <strong>Audience</strong> tab, click <strong>Add users</strong> and add the Gmail address(es) you'll sign in with. (External apps start in "testing" mode — only listed test users can connect until you publish.)</span></div>

                        <h4>Step 5 — Create the Web client + copy the redirect URI</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>Open the <strong>Clients</strong> tab → <strong>Create client</strong>.</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span><strong>Application type:</strong> choose <strong>Web application</strong>. Give it any name.</span></div>
                        <div class="cn-step"><span class="cn-step-num">3</span><span>In <strong>Authorized redirect URIs</strong>, paste this exactly (use the Copy button — one wrong character breaks it):</span></div>
                        <div class="cn-link-row"><code><?= htmlspecialchars($googleRedirect) ?></code><button type="button" class="cn-copy" data-copy="<?= htmlspecialchars($googleRedirect) ?>">Copy</button></div>
                        <div class="cn-step"><span class="cn-step-num">4</span><span>Click <strong>Create</strong>.</span></div>

                        <h4>Step 6 — Copy the Client ID and Secret immediately</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>After creating, a dialog shows your <strong>Client ID</strong> (ends in <code>.apps.googleusercontent.com</code>) and <strong>Client Secret</strong> (starts with <code>GOCSPX-</code>).</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span><strong>The secret is shown only once.</strong> Copy both now — then click <strong>Download JSON</strong> and keep the file somewhere safe.</span></div>
                        <div class="cn-step"><span class="cn-step-num">3</span><span>Paste the Client ID and Client Secret into the boxes above and click <strong>Save credentials</strong>.</span></div>

                        <div class="cn-done">✅ You're done when: the badge above turns green "● Connected".</div>
                        <div class="cn-note">ℹ️ Good to know: reminders (vaccinations due, credit follow-ups) will be pushable to Google Calendar, and reports will export to Google Sheets. Going live for many customers later needs Google's app verification — fine for you and early users in testing mode.</div>
                    </div>
                </details>
            </div>
        </div>

    <?php elseif ($tab === 'mpesa'): ?>
        <!-- ═══ M-PESA ═══ -->
        <div class="cn-card">
            <div class="cn-top">
                <div class="cn-icon" style="background:linear-gradient(135deg,#FEFCE8,#FEF9C3);">💳</div>
                <div style="flex:1;">
                    <h2>M-Pesa (Daraja)
                        <?php if ($mpConnected): ?><span class="cn-badge live">● Connected</span>
                        <?php else: ?><span class="cn-badge off">○ Not connected</span><?php endif; ?>
                    </h2>
                    <p class="cn-value">Customers tap "Pay invoice" → the M-Pesa prompt pops on their phone → the payment lands in the Credit module automatically. No more chasing money.</p>
                </div>
            </div>
            <div class="cn-body">
                <form method="post" class="cn-form">
                    <input type="hidden" name="_action" value="save_multi">
                    <div class="cn-grid">
                        <div class="cn-field">
                            <label for="m-key">Consumer Key</label>
                            <input id="m-key" name="settings[mpesa_consumer_key]" class="cn-input" placeholder="Consumer Key…" value="<?= htmlspecialchars($cfg['mpesa_ck']) ?>">
                        </div>
                        <div class="cn-field">
                            <label for="m-secret">Consumer Secret</label>
                            <input id="m-secret" name="settings[mpesa_consumer_secret]" type="password" class="cn-input" placeholder="Secret…" value="<?= htmlspecialchars($cfg['mpesa_cs']) ?>" autocomplete="off">
                        </div>
                        <div class="cn-field">
                            <label for="m-short">Business Shortcode</label>
                            <input id="m-short" name="settings[mpesa_shortcode]" class="cn-input" placeholder="e.g. 174379 (sandbox)" value="<?= htmlspecialchars($cfg['mpesa_shortcode']) ?>">
                            <p class="cn-hint">Paybill / till / buy-goods shortcode.</p>
                        </div>
                    </div>
                    <div class="cn-actions">
                        <button class="btn btn-primary" type="submit">Save credentials</button>
                        <?php if ($mpConnected): ?>
                            <button class="btn btn-trans" type="submit" onclick="this.form._action.value='disconnect';this.form.appendChild(Object.assign(document.createElement('input'),{type:'hidden',name:'disconnect_key',value:'mpesa_consumer_key'}));return true;">Disconnect</button>
                        <?php endif; ?>
                    </div>
                </form>

                <details class="cn-guide">
                    <summary>📖 How to connect — step by step</summary>
                    <div class="cn-guide-body">
                        <h4>What you'll need</h4>
                        <div class="cn-need">
                            <span>🆓 An email address + phone number (to register)</span>
                            <span>📱 A Safaricom number for testing</span>
                            <span>💼 For live payments: a registered paybill / till number</span>
                        </div>
                        <p>This lets customers pay you directly from their phones — the M-Pesa prompt pops up, and the payment records itself in your Credit module. <strong>You can set it up in testing mode for free first.</strong></p>

                        <h4>Step 1 — Create a Safaricom developer account</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>Open the Daraja portal:</span></div>
                        <div class="cn-link-row"><code>https://developer.safaricom.co.ke</code><button type="button" class="cn-copy" data-copy="https://developer.safaricom.co.ke">Copy link</button></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Click <strong>Register</strong> and create an account with your email and phone. Verify the code they text/email you, then log in.</span></div>

                        <h4>Step 2 — Create an app to get your keys</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>In the dashboard, open <strong>My Apps</strong> → <strong>Create New App</strong>.</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Give it a name (e.g. "Wangari Farm") and tick the <strong>Lipa na M-Pesa Online (STK Push)</strong> API.</span></div>
                        <div class="cn-step"><span class="cn-step-num">3</span><span>Submit — you'll get your <strong>Consumer Key</strong> and <strong>Consumer Secret</strong>. Copy both into the boxes above.</span></div>

                        <h4>Step 3 — Get your test shortcode</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>In <strong>Sandbox</strong> tools, find the test till / shortcode (commonly <code>174379</code>) and the test passkey shown with your app.</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Paste the shortcode into the <strong>Business Shortcode</strong> box and tap <strong>Save credentials</strong>.</span></div>

                        <div class="cn-done">✅ You're done when: the badge turns green "● Connected" (testing mode). You can test a payment with the Safaricom test number.</div>
                        <div class="cn-note">⚠️ Going live: to accept real money you need a business paybill/till shortcode from Safaricom and a callback address reachable from the internet. Everything you've set up stays valid — only the shortcode changes.</div>
                    </div>
                </details>
            </div>
        </div>

    <?php elseif ($tab === 'whatsapp'): ?>
        <!-- ═══ WHATSAPP & SMS ═══ -->
        <div class="cn-card">
            <div class="cn-top">
                <div class="cn-icon" style="background:linear-gradient(135deg,#F0FDF4,#DCFCE7);">💬</div>
                <div style="flex:1;">
                    <h2>WhatsApp & SMS
                        <?php if ($waConnected): ?><span class="cn-badge live">● Connected</span>
                        <?php else: ?><span class="cn-badge off">○ Not connected</span><?php endif; ?>
                    </h2>
                    <p class="cn-value">Farmers live on WhatsApp. Send reminders as messages — "Deworming due for 12 cows tomorrow" — with SMS as the fallback for feature phones.</p>
                </div>
            </div>
            <div class="cn-body">
                <form method="post" class="cn-form">
                    <input type="hidden" name="_action" value="save_multi">
                    <div class="cn-grid">
                        <div class="cn-field">
                            <label for="w-phone">WhatsApp Phone Number ID</label>
                            <input id="w-phone" name="settings[wa_phone_number_id]" class="cn-input" placeholder="Phone Number ID…" value="<?= htmlspecialchars($cfg['wa_phone_id']) ?>">
                        </div>
                        <div class="cn-field">
                            <label for="w-token">Access Token</label>
                            <input id="w-token" name="settings[wa_access_token]" type="password" class="cn-input" placeholder="EAA…" value="<?= htmlspecialchars($cfg['wa_token']) ?>" autocomplete="off">
                        </div>
                    </div>
                    <div class="cn-actions">
                        <button class="btn btn-primary" type="submit">Save credentials</button>
                        <?php if ($waConnected): ?>
                            <button class="btn btn-trans" type="submit" onclick="this.form._action.value='disconnect';this.form.appendChild(Object.assign(document.createElement('input'),{type:'hidden',name:'disconnect_key',value:'wa_phone_number_id'}));return true;">Disconnect</button>
                        <?php endif; ?>
                    </div>
                </form>

                <details class="cn-guide">
                    <summary>📖 How to connect — step by step</summary>
                    <div class="cn-guide-body">
                        <h4>What you'll need</h4>
                        <div class="cn-need">
                            <span>🆓 A Facebook account (for Meta for Developers)</span>
                            <span>📱 A phone that receives WhatsApp</span>
                            <span>⏱️ About 15 minutes</span>
                        </div>
                        <p>This sends reminders as WhatsApp messages — "Deworming due for 12 cows tomorrow" lands right in your customers' WhatsApp.</p>

                        <h4>Step 1 — Become a Meta developer</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>Open the developer page and click <strong>Get Started</strong>:</span></div>
                        <div class="cn-link-row"><code>https://developers.facebook.com</code><button type="button" class="cn-copy" data-copy="https://developers.facebook.com">Copy link</button></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Follow the registration (verify your phone or email).</span></div>

                        <h4>Step 2 — Create the app</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>Open the App Dashboard and click <strong>Create App</strong>.</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Choose the <strong>WhatsApp</strong> use case ("Connect with customers through WhatsApp").</span></div>
                        <div class="cn-step"><span class="cn-step-num">3</span><span>Give the app a name and click <strong>Create App</strong>.</span></div>

                        <h4>Step 3 — Start using the API</h4>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>Click <strong>Start using the API</strong> and connect it to a <strong>WhatsApp Business account</strong> (create one if asked).</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>On the API Setup page, click <strong>Generate access token</strong> — copy the token into the <strong>Access Token</strong> box above.</span></div>
                        <div class="cn-step"><span class="cn-step-num">3</span><span>Copy the <strong>Phone Number ID</strong> shown there into the <strong>WhatsApp Phone Number ID</strong> box above.</span></div>
                        <div class="cn-step"><span class="cn-step-num">4</span><span>Tap <strong>Save credentials</strong>.</span></div>

                        <div class="cn-done">✅ You're done when: the badge turns green "● Connected".</div>
                        <div class="cn-note">ℹ️ First message to a new customer must be an approved template (Meta reviews it); after they reply, you can message freely for 24 hours. The temporary token works for testing — for daily use, create a permanent token under <strong>System users</strong> in the app dashboard.</div>
                    </div>
                </details>
            </div>
        </div>

    <?php elseif ($tab === 'weather'): ?>
        <!-- ═══ WEATHER ═══ -->
        <div class="cn-card">
            <div class="cn-top">
                <div class="cn-icon" style="background:linear-gradient(135deg,#EFF6FF,#E0E7FF);">🌦</div>
                <div style="flex:1;">
                    <h2>Weather (Open-Meteo) <span class="cn-badge live">● Free · always on</span></h2>
                    <p class="cn-value">57% of farmers want weather alerts. Free, no API key, no signup — rain and frost warnings feed the Reminders & Weather module.</p>
                </div>
            </div>
            <div class="cn-body">
                <form method="post" class="cn-form">
                    <input type="hidden" name="_action" value="test_weather">
                    <div class="cn-grid">
                        <div class="cn-field">
                            <label for="w-lat">Latitude</label>
                            <input id="w-lat" name="weather_lat" class="cn-input" placeholder="e.g. 0.5214" value="<?= htmlspecialchars($cfg['weather_lat']) ?>">
                        </div>
                        <div class="cn-field">
                            <label for="w-lon">Longitude</label>
                            <input id="w-lon" name="weather_lon" class="cn-input" placeholder="e.g. 35.2697" value="<?= htmlspecialchars($cfg['weather_lon']) ?>">
                        </div>
                    </div>
                    <p class="cn-hint">Your farm's coordinates — find them on Google Maps (right-click → copy coordinates).</p>
                    <div class="cn-actions">
                        <button class="btn btn-primary" type="submit">Check today's weather</button>
                    </div>
                </form>

                <details class="cn-guide">
                    <summary>📖 How it works — nothing to set up</summary>
                    <div class="cn-guide-body">
                        <h4>What you'll need</h4>
                        <div class="cn-need">
                            <span>🆓 Nothing — it's free and always on</span>
                            <span>🌍 Internet connection when you check</span>
                        </div>
                        <p>Weather is the easiest connector: it needs <strong>no account, no key, no signup</strong>. It's already live — that's why the badge is green.</p>
                        <div class="cn-step"><span class="cn-step-num">1</span><span>Enter your farm's <strong>latitude</strong> and <strong>longitude</strong> (find them on Google Maps: right-click any spot → <strong>copy coordinates</strong> → paste the two numbers).</span></div>
                        <div class="cn-step"><span class="cn-step-num">2</span><span>Tap <strong>Check today's weather</strong> — you'll see the day's temperature and rain chance for your farm.</span></div>
                        <div class="cn-step"><span class="cn-step-num">3</span><span>Coordinates are remembered, so rain/frost alerts can feed the Reminders & Weather module automatically.</span></div>
                        <div class="cn-done">✅ You're done when: you see "Weather OK — …" with your farm's forecast.</div>
                    </div>
                </details>
            </div>
        </div>

    <?php else: ?>
        <!-- ═══ ROADMAP ═══ -->
        <div class="cn-card">
            <div class="cn-top">
                <div class="cn-icon" style="background:linear-gradient(135deg,#FDF2F8,#FCE7F3);">🗺</div>
                <div style="flex:1;">
                    <h2>Roadmap <span class="cn-badge info">● Priorities</span></h2>
                    <p class="cn-value">Where each connector sits by value vs effort — and the privacy rule that protects the offline product.</p>
                </div>
            </div>
            <div class="cn-body">
                <div class="cn-guide-body" style="padding:0;">
                    <h4>Build order (value / effort)</h4>
                    <div class="cn-step"><span class="cn-step-num">1</span><span><strong>AI Provider + Weather</strong> — done, both functional today.</span></div>
                    <div class="cn-step"><span class="cn-step-num">2</span><span><strong>Google Calendar + Sheets</strong> — OAuth, moderate effort, huge daily value.</span></div>
                    <div class="cn-step"><span class="cn-step-num">3</span><span><strong>M-Pesa STK Push</strong> — needs a business shortcode, kills credit friction.</span></div>
                    <div class="cn-step"><span class="cn-step-num">4</span><span><strong>WhatsApp reminders</strong> — needs a Meta app + template approval.</span></div>
                    <div class="cn-step"><span class="cn-step-num">5</span><span><strong>SMS gateway</strong> — cheapest fallback, ~KES 0.40/msg.</span></div>
                    <h4>Offline & privacy rule</h4>
                    <p>The local engine and weather stay <strong>offline-safe</strong>. Any connector that sends data out (AI LLM, Google, M-Pesa) is <strong>opt-in per connector</strong> — nothing leaves the farm without the farmer switching it on. This matches the downloadable-first product: the app works alone; connectors upgrade it.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

<script>window.WangariAdmin && window.WangariAdmin.initIcons && window.WangariAdmin.initIcons();
/* Swap AI guide steps when the provider changes */
(function () {
    var sel = document.getElementById('ai-provider');
    var names = { openai: 'OpenAI', gemini: 'Google Gemini', deepseek: 'DeepSeek', ollama: 'Local Ollama' };
    var defaults = <?= json_encode($defaultModels) ?>;
    if (sel) {
        sel.addEventListener('change', function () {
            var v = sel.value;
            document.querySelectorAll('.cn-psteps').forEach(function (el) {
                el.hidden = (el.getAttribute('data-provider') !== v);
            });
            var head = document.getElementById('cn-current-provider');
            if (head) head.textContent = names[v] || v;
            /* Ollama needs no key — nudge the API key field */
            var keyField = document.getElementById('ai-key');
            if (keyField) keyField.placeholder = (v === 'ollama') ? 'Not needed — leave empty for local AI' : 'sk-…';
            var keyHint = keyField && keyField.nextElementSibling;
            if (keyHint) keyHint.style.display = (v === 'ollama') ? 'none' : '';
            /* Auto-fill the model with the provider's recommended default —
               but only if the user hasn't typed their own model yet. */
            var modelField = document.getElementById('ai-model');
            var def = defaults[v] || '';
            if (modelField) {
                var cur = modelField.value.trim();
                var isUntouched = (cur === '' || Object.values(defaults).indexOf(cur) !== -1);
                if (isUntouched && def) modelField.value = def;
                modelField.placeholder = def;
                modelField.setAttribute('data-default', def);
            }
            var modelHint = document.getElementById('ai-model-hint');
            if (modelHint) modelHint.textContent = (v === 'ollama')
                ? 'Auto-filled with llama3.2 — runs locally and free. Edit anytime.'
                : 'Auto-filled with the recommended model for ' + (names[v] || v) + ' — you can edit it anytime.';
        });
    }
})();

/* Copy-to-clipboard buttons for links & URIs (beginner-friendly) */
(function () {
    document.querySelectorAll('.cn-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy') || '';
            var done = function () {
                btn.classList.add('copied');
                var old = btn.textContent;
                btn.textContent = 'Copied ✓';
                setTimeout(function () { btn.classList.remove('copied'); btn.textContent = old; }, 1600);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
            } else { fallbackCopy(text); done(); }
            function fallbackCopy(t) {
                var ta = document.createElement('textarea');
                ta.value = t; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta);
            }
        });
    });
})();</script>
<?php include __DIR__ . '/includes/admin_footer.php'; ?>
