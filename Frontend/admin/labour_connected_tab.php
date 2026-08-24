<?php
/**
 * Connected Workers Tab
 * Shows workers connected to this farm
 */
?>

<div class="admin-card">
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Connected Workers</h3>
    <p style="color:#64748B;font-size:0.85rem;margin-bottom:16px;">Workers who have connected to your farm using a code.</p>
    
    <?php if (empty($connectedWorkers)): ?>
        <div style="text-align:center;padding:32px;color:#94A3B8;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:0.5;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <p>No workers connected yet. Share a code with your workers to connect.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Worker</th>
                        <th>Email</th>
                        <th>Connected</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($connectedWorkers as $worker): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#22C55E,#16A34A);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;">
                                        <?= strtoupper(substr($worker['full_name'] ?? 'W', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;color:#0F172A;"><?= htmlspecialchars($worker['full_name'] ?? 'Unknown') ?></div>
                                        <div style="font-size:0.8rem;color:#64748B;">@<?= htmlspecialchars($worker['username'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($worker['email'] ?? '-') ?></td>
                            <td><?= date('M j, Y', strtotime($worker['connected_at'])) ?></td>
                            <td>
                                <?php if ($worker['is_active']): ?>
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#D1FAE5;color:#065F46;border-radius:20px;font-size:0.8rem;font-weight:600;">
                                        <span style="width:6px;height:6px;background:#22C55E;border-radius:50%;"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#FEE2E2;color:#991B1B;border-radius:20px;font-size:0.8rem;font-weight:600;">
                                        <span style="width:6px;height:6px;background:#DC2626;border-radius:50%;"></span> Disconnected
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($worker['is_active']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Disconnect this worker?');">
                                        <input type="hidden" name="_action" value="disconnect_worker">
                                        <input type="hidden" name="id" value="<?= (int)$worker['id'] ?>">
                                        <button type="submit" style="padding:6px 12px;background:#FEE2E2;color:#991B1B;border:none;border-radius:6px;font-size:0.8rem;font-weight:600;cursor:pointer;">Disconnect</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#94A3B8;font-size:0.85rem;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
