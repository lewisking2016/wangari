<?php
/**
 * Worker Connection Codes Tab
 * Shows generated codes and connected workers
 */
$farmUserId = (int)($_SESSION['user_id'] ?? 0);
?>

<div class="admin-card" style="margin-bottom: 20px;">
    <h3 style="margin:0 0 12px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Generate New Code</h3>
    <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;">
        <input type="hidden" name="_action" value="generate_code">
        <div style="flex:1;min-width:120px;max-width:150px;">
            <label style="display:block;font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:4px;">Max Uses</label>
            <input type="number" name="max_uses" value="10" min="1" max="100" style="width:100%;padding:8px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:0.9rem;">
        </div>
        <div style="flex:1;min-width:120px;max-width:180px;">
            <label style="display:block;font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:4px;">Expires After (days)</label>
            <input type="number" name="expires_days" value="30" min="1" max="365" style="width:100%;padding:8px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:0.9rem;">
        </div>
        <button type="submit" class="btn btn-primary" style="padding:10px 24px;white-space:nowrap;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:4px;"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
            Generate Code
        </button>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Active Codes</h3>
    <p style="color:#64748B;font-size:0.85rem;margin-bottom:16px;">Share these codes with your workers. They enter them when logging in to connect to your farm.</p>
    
    <?php if (empty($workerCodes)): ?>
        <div style="text-align:center;padding:32px;color:#94A3B8;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:0.5;"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
            <p>No codes generated yet. Click "Generate Code" above to create one.</p>
        </div>
    <?php else: ?>
        <div style="display:grid;gap:12px;">
            <?php foreach ($workerCodes as $code): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:16px;background:#F8FAFC;border-radius:12px;border:1px solid #E5E7EB;">
                    <div>
                        <div style="font-size:1.5rem;font-weight:800;color:#0F172A;font-family:monospace;letter-spacing:2px;"><?= htmlspecialchars($code['code']) ?></div>
                        <div style="font-size:0.8rem;color:#64748B;margin-top:4px;">
                            Used <?= $code['uses_count'] ?>/<?= $code['max_uses'] ?> times
                            <?php if ($code['expires_at']): ?>
                                · Expires <?= date('M j, Y', strtotime($code['expires_at'])) ?>
                            <?php endif; ?>
                            <?php if (!$code['is_active']): ?>
                                · <span style="color:#DC2626;">Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($code['code']) ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)" style="padding:8px 16px;background:#22C55E;color:#fff;border:none;border-radius:8px;font-size:0.85rem;font-weight:600;cursor:pointer;">Copy</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this code?');">
                            <input type="hidden" name="_action" value="delete_code">
                            <input type="hidden" name="id" value="<?= (int)$code['id'] ?>">
                            <button type="submit" style="padding:8px 12px;background:#FEE2E2;color:#991B1B;border:none;border-radius:8px;cursor:pointer;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
