<?php
/**
 * Wangari Referral Program Widget
 * 
 * Embeds in the dashboard to show:
 * - User's referral code + share link
 * - Progress toward free month (X/5)
 * - Recent referrals
 * - Leaderboard
 * 
 * Usage: <?php include __DIR__ . '/includes/referral_widget.php'; ?>
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDB();
$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) return;

// Get referral data
$stmt = $pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$referral_code = $user['referral_code'] ?? null;

// Generate code if not exists
if (!$referral_code) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    $base = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $u['username'] ?? 'FARMER'), 0, 5));
    $referral_code = $base . random_int(1000, 9999);
    $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?")->execute([$referral_code, $userId]);
}

// Count referrals
$stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?");
$stmt->execute([$userId]);
$total_referrals = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = 'active'");
$stmt->execute([$userId]);
$active_referrals = (int) $stmt->fetchColumn();

$free_months = intdiv($active_referrals, 5);
$progress = $active_referrals % 5;
$needed = 5 - $progress;

// Recent referrals
$stmt = $pdo->prepare("SELECT u.full_name, r.created_at, r.status FROM referrals r JOIN users u ON r.referred_id = u.id WHERE r.referrer_id = ? ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="background: linear-gradient(135deg, rgba(34,197,94,0.08), rgba(34,197,94,0.02)); border: 1px solid rgba(34,197,94,0.2); border-radius: 16px; padding: 24px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(34,197,94,0.15); display: flex; align-items: center; justify-content: center;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <h3 style="font-size: 1rem; font-weight: 700; margin: 0;">🎁 Refer & Earn Free Months</h3>
            <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5); margin: 0;">Bring 5 farmers, get 1 month free. Forever.</p>
        </div>
    </div>
    
    <!-- Referral Code -->
    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
        <div style="flex: 1; padding: 12px 16px; background: rgba(0,0,0,0.3); border-radius: 8px; font-family: monospace; font-size: 1.1rem; font-weight: 700; color: #22C55E; letter-spacing: 0.1em; display: flex; align-items: center;">
            <?php echo htmlspecialchars($referral_code); ?>
        </div>
        <button onclick="copyCode()" style="padding: 12px 20px; background: #22C55E; color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem; white-space: nowrap;">
            Copy Code
        </button>
    </div>
    
    <!-- Progress Bar -->
    <div style="margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="font-size: 0.85rem; color: rgba(255,255,255,0.7);">
                <?php if ($free_months > 0): ?>
                    🎉 You've earned <strong style="color: #22C55E;"><?php echo $free_months; ?> free month(s)</strong>!
                <?php else: ?>
                    <strong style="color: #22C55E;"><?php echo $progress; ?>/5</strong> referrals toward your first free month
                <?php endif; ?>
            </span>
            <span style="font-size: 0.8rem; color: rgba(255,255,255,0.4);">
                Need <?php echo $needed; ?> more
            </span>
        </div>
        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; width: <?php echo ($progress / 5) * 100; ?>%; background: linear-gradient(90deg, #22C55E, #16A34A); border-radius: 4px; transition: width 0.3s;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 4px;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div style="width: 16px; height: 16px; border-radius: 50%; background: <?php echo $i <= $progress ? '#22C55E' : 'rgba(255,255,255,0.1)'; ?>; display: flex; align-items: center; justify-content: center; font-size: 0.6rem;">
                    <?php echo $i <= $progress ? '✓' : ''; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- Share Buttons -->
    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
        <a href="https://wa.me/?text=<?php echo urlencode("Join Wangari — the free farm management system! 🌱\n\nI've been using it to track my farm profits. Try it free:\nhttps://wangari.imeantech.com/Frontend/pages/register.php?ref=" . $referral_code); ?>" 
           target="_blank" 
           style="flex: 1; padding: 10px; background: #25D366; color: #fff; border-radius: 8px; text-align: center; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
            📱 Share on WhatsApp
        </a>
        <button onclick="shareSMS()" style="flex: 1; padding: 10px; background: rgba(255,255,255,0.1); color: #fff; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
            💬 Share via SMS
        </button>
    </div>
    
    <!-- Recent Referrals -->
    <?php if (!empty($recent)): ?>
        <div style="border-top: 1px solid rgba(255,255,255,0.08); padding-top: 12px;">
            <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-bottom: 8px;">Recent referrals:</p>
            <?php foreach ($recent as $r): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 0.85rem;">
                    <span style="color: rgba(255,255,255,0.7);"><?php echo htmlspecialchars($r['full_name'] ?? 'Someone'); ?></span>
                    <span style="color: <?php echo $r['status'] === 'active' ? '#22C55E' : '#F59E0B'; ?>; font-size: 0.75rem; font-weight: 600;">
                        <?php echo $r['status'] === 'active' ? '✓ Active' : '⏳ Pending'; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function copyCode() {
    const code = '<?php echo htmlspecialchars($referral_code); ?>';
    navigator.clipboard.writeText(code).then(() => {
        alert('Referral code copied! Share it with farmer friends.');
    }).catch(() => {
        // Fallback
        const el = document.createElement('textarea');
        el.value = code;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        alert('Referral code copied!');
    });
}

function shareSMS() {
    const text = encodeURIComponent("Join Wangari — free farm management! Track profits, stop theft, know your numbers. Try free: https://wangari.imeantech.com/Frontend/pages/register.php?ref=<?php echo $referral_code; ?>");
    window.location.href = `sms:?body=${text}`;
}
</script>
