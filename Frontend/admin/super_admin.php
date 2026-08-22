<?php
/**
 * Super Admin Control Center
 * Platform management — users, accounts, system health, analytics
 * ONLY accessible by super_admin role
 */
declare(strict_types=1);
session_start();
$page_title = 'Platform Control Center — Wangari';

// Must be super_admin
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header('Location: /Frontend/admin/login.php');
    exit;
}

include __DIR__ . '/includes/admin_header.php';
$pdo = getDB();

// Load data
$stats = [];
try {
    $stats['total_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['active_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1 OR is_active IS NULL")->fetchColumn();
    $stats['users_by_role'] = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
    $stats['new_today'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $stats['new_week'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $stats['new_month'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    $stats['total_products'] = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['total_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['total_animals'] = (int) $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn();
    $stats['revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND MONTH(transaction_date)=MONTH(CURDATE())")->fetchColumn();

    // Recent users
    $recentUsers = $pdo->query("SELECT id, username, email, first_name, last_name, role, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // Activity log
    $activityLog = $pdo->query("SELECT al.*, u.username FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<style>
.sa-stat { display:flex;align-items:center;gap:16px;padding:22px;background:#fff;border:1px solid var(--w2-border);border-radius:14px;box-shadow:var(--w2-shadow);transition:all .25s; }
.sa-stat:hover { transform:translateY(-3px);box-shadow:0 8px 24px rgba(15,23,42,0.08); }
.sa-stat-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.sa-stat h3 { margin:0;font-size:1.8rem;font-family:'Outfit',sans-serif;font-weight:700;color:var(--w2-heading); }
.sa-stat p { margin:2px 0 0;font-size:0.8rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em; }
.sa-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:28px; }
.sa-tab-bar { display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;overflow-x:auto; }
.sa-tab { display:flex;align-items:center;gap:7px;padding:9px 14px;border-radius:7px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.84rem;transition:all .18s;cursor:pointer;border:none;background:none;color:#64748b;font-family:inherit; }
.sa-tab.active { background:#fff;color:var(--w2-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08); }
.sa-tab:hover:not(.active) { color:var(--w2-heading); }
.sa-tab-content { display:none; }
.sa-tab-content.active { display:block; }
.sa-user-row { display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid #F0F2F6;transition:background .15s; }
.sa-user-row:last-child { border-bottom:none; }
.sa-user-row:hover { background:#FAFBFD; }
.sa-user-avatar { width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0; }
.sa-toggle { position:relative;width:40px;height:22px;cursor:pointer; }
.sa-toggle input { opacity:0;width:0;height:0; }
.sa-toggle .slider { position:absolute;inset:0;background:#cbd5e1;border-radius:22px;transition:.3s; }
.sa-toggle .slider:before { content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;left:3px;top:3px;transition:.3s; }
.sa-toggle input:checked + .slider { background:#22C55E; }
.sa-toggle input:checked + .slider:before { transform:translateX(18px); }
</style>

<!-- Top Bar -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;font-weight:800;color:var(--w2-heading);">
            <i data-lucide="shield" style="width:22px;height:22px;vertical-align:middle;color:var(--w2-primary);"></i>
            Platform Control Center
        </h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Manage users, monitor system health, and control your platform.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" onclick="openUserModal()">
            <i data-lucide="user-plus" style="width:15px;height:15px;"></i> New User
        </button>
        <button class="btn btn-outline" onclick="location.reload()">
            <i data-lucide="refresh-cw" style="width:15px;height:15px;"></i> Refresh
        </button>
    </div>
</div>

<!-- Stats Grid -->
<div class="sa-grid">
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:rgba(22,101,52,0.08);color:var(--w2-primary);">
            <i data-lucide="users" style="width:22px;height:22px;"></i>
        </div>
        <div>
            <h3><?= number_format($stats['total_users']) ?></h3>
            <p>Total Users</p>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:rgba(34,197,94,0.1);color:#16A34A;">
            <i data-lucide="user-check" style="width:22px;height:22px;"></i>
        </div>
        <div>
            <h3><?= number_format($stats['active_users']) ?></h3>
            <p>Active Accounts</p>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:rgba(59,130,246,0.1);color:#2563EB;">
            <i data-lucide="trending-up" style="width:22px;height:22px;"></i>
        </div>
        <div>
            <h3><?= $stats['new_today'] ?> / <?= $stats['new_week'] ?></h3>
            <p>New Today / This Week</p>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:rgba(245,158,11,0.1);color:#D97706;">
            <i data-lucide="banknote" style="width:22px;height:22px;"></i>
        </div>
        <div>
            <h3>KES <?= number_format($stats['revenue']) ?></h3>
            <p>Revenue This Month</p>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:rgba(168,85,247,0.1);color:#7C3AED;">
            <i data-lucide="package" style="width:22px;height:22px;"></i>
        </div>
        <div>
            <h3><?= number_format($stats['total_products']) ?></h3>
            <p>Products</p>
        </div>
    </div>
    <div class="sa-stat">
        <div class="sa-stat-icon" style="background:rgba(239,68,68,0.1);color:#DC2626;">
            <i data-lucide="heart" style="width:22px;height:22px;"></i>
        </div>
        <div>
            <h3><?= number_format($stats['total_animals']) ?></h3>
            <p>Animals Tracked</p>
        </div>
    </div>
</div>

<!-- Role Breakdown -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:28px;">
    <?php foreach ($stats['users_by_role'] as $role => $count): ?>
    <div style="text-align:center;padding:16px;background:#fff;border:1px solid var(--w2-border);border-radius:12px;">
        <div style="font-size:1.5rem;font-weight:800;font-family:'Outfit',sans-serif;color:var(--w2-heading);"><?= $count ?></div>
        <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.06em;font-weight:600;"><?= ucwords(str_replace('_', ' ', $role)) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabs -->
<div class="sa-tab-bar">
    <button class="sa-tab active" onclick="switchSaTab('users')">
        <i data-lucide="users" style="width:15px;height:15px;"></i> User Management
    </button>
    <button class="sa-tab" onclick="switchSaTab('health')">
        <i data-lucide="activity" style="width:15px;height:15px;"></i> System Health
    </button>
    <button class="sa-tab" onclick="switchSaTab('activity')">
        <i data-lucide="scroll" style="width:15px;height:15px;"></i> Activity Log
    </button>
</div>

<!-- ══════ USERS TAB ══════ -->
<div id="sa-tab-users" class="sa-tab-content active">
    <div class="admin-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="text" id="sa-search" placeholder="Search users..." oninput="searchUsers()" style="padding:9px 14px;border:1.5px solid #D8DEE8;border-radius:10px;font-size:0.9rem;width:240px;font-family:inherit;">
                <select id="sa-role-filter" onchange="searchUsers()" style="padding:9px 14px;border:1.5px solid #D8DEE8;border-radius:10px;font-size:0.9rem;font-family:inherit;">
                    <option value="">All Roles</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="farm_manager">Farm Manager</option>
                    <option value="stock_manager">Stock Manager</option>
                    <option value="sales_staff">Sales Staff</option>
                    <option value="customer">Customer</option>
                </select>
            </div>
            <span id="sa-count" style="font-size:0.85rem;color:#64748b;"></span>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="sa-users-body">
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;">Loading users...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════ HEALTH TAB ══════ -->
<div id="sa-tab-health" class="sa-tab-content">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
        <div class="admin-card" id="sa-health-db">
            <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">
                <i data-lucide="database" style="width:18px;height:18px;vertical-align:middle;"></i> Database
            </h3>
            <p style="color:#94a3b8;">Loading...</p>
        </div>
        <div class="admin-card" id="sa-health-redis">
            <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">
                <i data-lucide="zap" style="width:18px;height:18px;vertical-align:middle;"></i> Redis Cache
            </h3>
            <p style="color:#94a3b8;">Loading...</p>
        </div>
        <div class="admin-card" id="sa-health-opcache">
            <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">
                <i data-lucide="cpu" style="width:18px;height:18px;vertical-align:middle;"></i> OPcache
            </h3>
            <p style="color:#94a3b8;">Loading...</p>
        </div>
        <div class="admin-card" id="sa-health-php">
            <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">
                <i data-lucide="code" style="width:18px;height:18px;vertical-align:middle;"></i> PHP
            </h3>
            <p style="color:#94a3b8;">Loading...</p>
        </div>
    </div>
</div>

<!-- ══════ ACTIVITY TAB ══════ -->
<div id="sa-tab-activity" class="sa-tab-content">
    <div class="admin-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Details</th></tr>
                </thead>
                <tbody>
                <?php if (empty($activityLog)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">No activity recorded yet.</td></tr>
                <?php else: foreach ($activityLog as $log): ?>
                    <tr>
                        <td style="white-space:nowrap;font-size:0.8rem;color:#64748b;"><?= htmlspecialchars($log['created_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= htmlspecialchars($log['username'] ?? 'System', ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($log['action'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($log['module'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-size:0.82rem;color:#64748b;"><?= htmlspecialchars($log['details'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════ USER MODAL ══════ -->
<div id="sa-user-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:14px;width:100%;max-width:560px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:92vh;overflow-y:auto;">
        <h3 id="sa-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;font-size:1.2rem;">New User</h3>
        <form id="sa-user-form" onsubmit="saveUser(event)">
            <input type="hidden" name="id" id="sa-form-id" value="0">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group">
                    <label class="admin-form-label">First Name</label>
                    <input class="admin-form-control" name="first_name" id="sa-f-first" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Last Name</label>
                    <input class="admin-form-control" name="last_name" id="sa-f-last">
                </div>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Username</label>
                <input class="admin-form-control" name="username" id="sa-f-username" required>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Email</label>
                <input class="admin-form-control" type="email" name="email" id="sa-f-email" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group">
                    <label class="admin-form-label">Phone</label>
                    <input class="admin-form-control" name="phone_number" id="sa-f-phone">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Farm Name</label>
                    <input class="admin-form-control" name="farm_name" id="sa-f-farm">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group">
                    <label class="admin-form-label">Role</label>
                    <select class="admin-form-control" name="role" id="sa-f-role">
                        <option value="customer">Customer</option>
                        <option value="sales_staff">Sales Staff</option>
                        <option value="stock_manager">Stock Manager</option>
                        <option value="farm_manager">Farm Manager</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Password</label>
                    <input class="admin-form-control" type="password" name="password" id="sa-f-pass" placeholder="Leave blank to keep current">
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                <label class="sa-toggle"><input type="checkbox" name="is_active" id="sa-f-active" checked><span class="slider"></span></label>
                <span style="font-size:0.9rem;font-weight:600;">Account Active</span>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save" style="width:15px;height:15px;"></i> Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
const SA_API = '/api/super_admin.php';
const ROLE_COLORS = {
    'super_admin': '#DC2626',
    'farm_manager': '#16A34A',
    'stock_manager': '#2563EB',
    'sales_staff': '#D97706',
    'customer': '#6B7280'
};

function switchSaTab(tab) {
    document.querySelectorAll('.sa-tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.sa-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('sa-tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
    if (tab === 'health') loadHealth();
}

async function loadUsers() {
    const search = document.getElementById('sa-search').value;
    const role = document.getElementById('sa-role-filter').value;
    try {
        const res = await fetch(`${SA_API}?endpoint=users&search=${encodeURIComponent(search)}&role=${role}`);
        const data = await res.json();
        const tbody = document.getElementById('sa-users-body');
        document.getElementById('sa-count').textContent = `${data.total} user(s)`;
        if (!data.users || data.users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;">No users found.</td></tr>';
            return;
        }
        tbody.innerHTML = data.users.map(u => {
            const initial = (u.first_name || u.username || 'U').charAt(0).toUpperCase();
            const color = ROLE_COLORS[u.role] || '#6B7280';
            const name = [u.first_name, u.last_name].filter(Boolean).join(' ') || u.username;
            const isActive = u.is_active == 1 || u.is_active === null;
            return `<tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="sa-user-avatar" style="background:${color}20;color:${color};">${initial}</div>
                        <div><strong>${esc(name)}</strong><br><small style="color:#94a3b8;">@${esc(u.username)}</small></div>
                    </div>
                </td>
                <td style="font-size:0.85rem;">${esc(u.email || '-')}</td>
                <td><span class="badge-pill" style="background:${color}15;color:${color};">${esc(u.role.replace(/_/g, ' '))}</span></td>
                <td>
                    <label class="sa-toggle">
                        <input type="checkbox" ${isActive ? 'checked' : ''} onchange="toggleUser(${u.id}, this.checked)">
                        <span class="slider"></span>
                    </label>
                </td>
                <td style="font-size:0.82rem;color:#64748b;">${u.created_at ? new Date(u.created_at).toLocaleDateString() : '-'}</td>
                <td style="text-align:right;">
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <button class="btn btn-trans btn-sm" onclick='editUser(${JSON.stringify(u)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button>
                        ${u.role !== 'super_admin' ? `<button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id},'${esc(u.username)}')"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) {
        console.error('Failed to load users:', e);
    }
}

function searchUsers() { loadUsers(); }

async function toggleUser(id, checked) {
    try {
        await fetch(`${SA_API}?endpoint=toggle_user`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
    } catch (e) { console.error(e); }
}

async function deleteUser(id, username) {
    if (!confirm(`Delete user "${username}"? This cannot be undone.`)) return;
    try {
        const res = await fetch(`${SA_API}?endpoint=delete_user`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) loadUsers();
        else alert(data.error || 'Failed to delete');
    } catch (e) { alert('Network error'); }
}

function openUserModal() {
    document.getElementById('sa-modal-title').textContent = 'New User';
    document.getElementById('sa-user-form').reset();
    document.getElementById('sa-form-id').value = '0';
    document.getElementById('sa-f-active').checked = true;
    document.getElementById('sa-user-modal').style.display = 'flex';
}

function editUser(u) {
    document.getElementById('sa-modal-title').textContent = 'Edit User';
    document.getElementById('sa-form-id').value = u.id;
    document.getElementById('sa-f-first').value = u.first_name || '';
    document.getElementById('sa-f-last').value = u.last_name || '';
    document.getElementById('sa-f-username').value = u.username || '';
    document.getElementById('sa-f-email').value = u.email || '';
    document.getElementById('sa-f-phone').value = u.phone_number || '';
    document.getElementById('sa-f-farm').value = u.farm_name || '';
    document.getElementById('sa-f-role').value = u.role || 'customer';
    document.getElementById('sa-f-pass').value = '';
    document.getElementById('sa-f-active').checked = u.is_active == 1;
    document.getElementById('sa-user-modal').style.display = 'flex';
}

function closeUserModal() {
    document.getElementById('sa-user-modal').style.display = 'none';
}

async function saveUser(e) {
    e.preventDefault();
    const form = document.getElementById('sa-user-form');
    const data = {
        id: parseInt(document.getElementById('sa-form-id').value),
        first_name: document.getElementById('sa-f-first').value,
        last_name: document.getElementById('sa-f-last').value,
        username: document.getElementById('sa-f-username').value,
        email: document.getElementById('sa-f-email').value,
        phone_number: document.getElementById('sa-f-phone').value,
        farm_name: document.getElementById('sa-f-farm').value,
        role: document.getElementById('sa-f-role').value,
        password: document.getElementById('sa-f-pass').value,
        is_active: document.getElementById('sa-f-active').checked ? 1 : 0
    };
    try {
        const res = await fetch(`${SA_API}?endpoint=save_user`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            closeUserModal();
            loadUsers();
        } else {
            alert(result.error || 'Failed to save');
        }
    } catch (e) { alert('Network error'); }
}

async function loadHealth() {
    try {
        const res = await fetch(`${SA_API}?endpoint=health`);
        const data = await res.json();

        // Database
        const db = data.database || {};
        document.getElementById('sa-health-db').innerHTML = `
            <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">
                <i data-lucide="database" style="width:18px;height:18px;vertical-align:middle;"></i> Database
            </h3>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <span style="width:10px;height:10px;border-radius:50%;background:${db.status==='healthy'?'#22C55E':'#EF4444'};"></span>
                <strong style="text-transform:capitalize;">${db.status||'unknown'}</strong>
            </div>
            <p style="font-size:0.85rem;color:#64748b;">${db.tables ? db.tables + ' tables' : ''}</p>`;

        // Redis
        const redis = data.redis || {};
        document.getElementById('sa-health-redis').innerHTML = `
            <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">
                <i data-lucide="zap" style="width:18px;height:18px;vertical-align:middle;"></i> Redis Cache
            </h3>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <span style="width:10px;height:10px;border-radius:50%;background:${redis.status==='healthy'?'#22C55E':'#EF4444'};"></span>
                <strong style="text-transform:capitalize;">${redis.status||'unknown'}</strong>
            </div>
            <p style="font-size:0.85rem;color:#64748b;">${redis.memory ? 'Memory: ' + redis.memory : ''}</p>`;

        // OPcache
        const oc = data.opcache || {};
        document.getElementById('sa-health-opcache').innerHTML = `
            <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">
                <i data-lucide="cpu" style="width:18px;height:18px;vertical-align:middle;"></i> OPcache
            </h3>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <span style="width:10px;height:10px;border-radius:50%;background:${oc.status==='healthy'?'#22C55E':'#F59E0B'};"></span>
                <strong style="text-transform:capitalize;">${oc.status||'unknown'}</strong>
            </div>
            <p style="font-size:0.85rem;color:#64748b;">${oc.hit_rate ? 'Hit rate: ' + oc.hit_rate + '%' : ''}</p>`;

        // PHP
        const php = data.php || {};
        document.getElementById('sa-health-php').innerHTML = `
            <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">
                <i data-lucide="code" style="width:18px;height:18px;vertical-align:middle;"></i> PHP
            </h3>
            <p style="font-size:0.9rem;"><strong>Version:</strong> ${php.version || '?'}</p>
            <p style="font-size:0.85rem;color:#64748b;">Memory: ${php.memory_limit || '?'} | Max time: ${php.max_execution_time || '?'}s</p>`;

        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) {
        console.error('Health check failed:', e);
    }
}

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// Init
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
