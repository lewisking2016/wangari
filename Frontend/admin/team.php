<?php
/**
 * Team Management Dashboard — Premium UI
 * Pending requests, team overview, sub-farms, notifications, sales performance.
 */
declare(strict_types=1);
session_start();
$page_title = 'Team Management — Wangari';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','farm_owner','stock_manager','sales_staff'])) {
    header('Location: /Frontend/admin/login.php');
    exit;
}

include __DIR__ . '/includes/admin_header.php';
$pdo = getDB();
$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
?>
<style>
:root {
    --tm-bg: #F8FAF9; --tm-surface: #FFFFFF; --tm-border: #E2E8F0;
    --tm-ink: #0F172A; --tm-muted: #64748B; --tm-accent: #16A34A;
    --tm-accent-light: #DCFCE7; --tm-danger: #EF4444; --tm-warning: #F59E0B;
    --tm-radius: 16px; --tm-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 4px 16px rgba(15,23,42,0.04);
}
.tm-page { padding: 28px 32px; max-width: 1400px; margin: 0 auto; }
.tm-page-header { margin-bottom: 28px; }
.tm-page-header h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
.tm-page-header p { color: var(--tm-muted); font-size: 13px; margin-top: 4px; }

/* Tabs */
.tm-tabs { display: flex; gap: 4px; margin-bottom: 28px; background: var(--tm-surface); border: 1px solid var(--tm-border); border-radius: 12px; padding: 4px; width: fit-content; }
.tm-tab {
    padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600;
    border: none; background: none; cursor: pointer; color: var(--tm-muted);
    transition: all 0.2s ease; font-family: inherit; position: relative;
}
.tm-tab:hover { color: var(--tm-ink); background: #F1F5F9; }
.tm-tab.active { color: #fff; background: var(--tm-accent); }
.tm-tab .badge {
    position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px;
    background: var(--tm-danger); color: #fff; font-size: 10px; font-weight: 700;
    border-radius: 999px; display: flex; align-items: center; justify-content: center;
    padding: 0 5px; display: none;
}
.tm-tab .badge.show { display: flex; }

/* Pending Requests */
.tm-pending { margin-bottom: 28px; }
.tm-pending-card {
    background: var(--tm-surface); border: 1px solid var(--tm-border);
    border-radius: var(--tm-radius); overflow: hidden; box-shadow: var(--tm-shadow);
}
.tm-pending-header {
    padding: 18px 24px; border-bottom: 1px solid var(--tm-border);
    display: flex; align-items: center; justify-content: space-between;
}
.tm-pending-header h3 { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.tm-pending-count {
    background: var(--tm-danger); color: #fff; font-size: 11px; font-weight: 700;
    padding: 2px 8px; border-radius: 999px;
}
.tm-request-row {
    padding: 18px 24px; border-bottom: 1px solid #F1F5F9;
    display: flex; align-items: center; gap: 16px; transition: background 0.15s;
}
.tm-request-row:hover { background: #FAFBFC; }
.tm-request-row:last-child { border-bottom: none; }
.tm-request-avatar {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 700; color: #fff;
}
.tm-request-info { flex: 1; }
.tm-request-info h4 { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
.tm-request-info .meta { font-size: 12px; color: var(--tm-muted); display: flex; gap: 12px; flex-wrap: wrap; }
.tm-request-actions { display: flex; gap: 8px; }
.tm-btn {
    padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 600;
    border: none; cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.tm-btn-approve { background: var(--tm-accent); color: #fff; }
.tm-btn-approve:hover { background: #15803D; }
.tm-btn-reject { background: #FEE2E2; color: #991B1B; }
.tm-btn-reject:hover { background: #FECACA; }
.tm-btn-ghost { background: #F1F5F9; color: var(--tm-muted); }

/* Team Cards */
.tm-team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.tm-member-card {
    background: var(--tm-surface); border: 1px solid var(--tm-border);
    border-radius: var(--tm-radius); padding: 24px; box-shadow: var(--tm-shadow);
    transition: all 0.3s ease; position: relative; overflow: hidden;
}
.tm-member-card:hover { transform: translateY(-2px); box-shadow: 0 4px 24px rgba(15,23,42,0.08); }
.tm-member-top { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
.tm-member-avatar {
    width: 52px; height: 52px; border-radius: 14px; position: relative;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 700; color: #fff;
}
.tm-status-dot {
    position: absolute; bottom: -2px; right: -2px; width: 14px; height: 14px;
    border-radius: 50%; border: 3px solid var(--tm-surface);
}
.tm-status-dot.online { background: #22C55E; }
.tm-status-dot.idle { background: #F59E0B; }
.tm-status-dot.offline { background: #94A3B8; }
.tm-status-dot.never { background: #CBD5E1; }
.tm-member-name { font-size: 15px; font-weight: 700; }
.tm-member-role { font-size: 12px; color: var(--tm-muted); margin-top: 2px; }
.tm-member-meta { display: flex; gap: 16px; font-size: 12px; color: var(--tm-muted); margin-top: 12px; }
.tm-member-meta span { display: flex; align-items: center; gap: 4px; }
.tm-member-actions { display: flex; gap: 8px; margin-top: 16px; padding-top: 14px; border-top: 1px solid #F1F5F9; }

/* Role badge colors */
.tm-role-badge {
    display: inline-flex; padding: 3px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 600;
}
.tm-role-farm_owner { background: #FEF3C7; color: #92400E; }
.tm-role-farm_manager { background: #DBEAFE; color: #1E40AF; }
.tm-role-stock_manager { background: #E0E7FF; color: #3730A3; }
.tm-role-sales_staff { background: #D1FAE5; color: #065F46; }
.tm-role-field_worker { background: #F1F5F9; color: #475569; }
.tm-role-veterinarian { background: #FCE7F3; color: #9D174D; }
.tm-role-accountant { background: #FEF9C3; color: #854D0E; }
.tm-role-auditor { background: #F3E8FF; color: #6B21A8; }
.tm-role-guest { background: #F1F5F9; color: #64748B; }

/* Stats strip */
.tm-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 28px; }
.tm-stat {
    background: var(--tm-surface); border: 1px solid var(--tm-border);
    border-radius: 12px; padding: 18px; text-align: center; box-shadow: var(--tm-shadow);
}
.tm-stat .value { font-size: 28px; font-weight: 800; }
.tm-stat .label { font-size: 11px; font-weight: 600; color: var(--tm-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }

/* Sub-farms */
.tm-farm-card {
    background: var(--tm-surface); border: 1px solid var(--tm-border);
    border-radius: var(--tm-radius); padding: 24px; box-shadow: var(--tm-shadow);
    transition: all 0.3s ease; cursor: pointer;
}
.tm-farm-card:hover { border-color: var(--tm-accent); transform: translateY(-2px); }
.tm-farm-icon { font-size: 32px; margin-bottom: 12px; display: block; }
.tm-farm-name { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
.tm-farm-meta { font-size: 12px; color: var(--tm-muted); }

/* Notifications */
.tm-notif-item {
    padding: 14px 20px; border-bottom: 1px solid #F1F5F9;
    display: flex; gap: 12px; align-items: flex-start;
}
.tm-notif-item.unread { background: rgba(74,222,128,0.04); }
.tm-notif-icon { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.tm-notif-content { flex: 1; }
.tm-notif-content h4 { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
.tm-notif-content p { font-size: 12px; color: var(--tm-muted); line-height: 1.5; }
.tm-notif-time { font-size: 11px; color: var(--tm-muted); white-space: nowrap; }

/* Leaderboard */
.tm-lb-row {
    display: flex; align-items: center; gap: 14px; padding: 14px 20px;
    border-bottom: 1px solid #F1F5F9;
}
.tm-lb-rank {
    width: 32px; height: 32px; border-radius: 10px; display: flex;
    align-items: center; justify-content: center; font-size: 14px; font-weight: 800;
}
.tm-lb-rank.gold { background: #FEF3C7; color: #92400E; }
.tm-lb-rank.silver { background: #F1F5F9; color: #475569; }
.tm-lb-rank.bronze { background: #FED7AA; color: #9A3412; }
.tm-lb-info { flex: 1; }
.tm-lb-info h4 { font-size: 14px; font-weight: 700; }
.tm-lb-info .sub { font-size: 12px; color: var(--tm-muted); }
.tm-lb-stats { text-align: right; }
.tm-lb-stats .revenue { font-size: 16px; font-weight: 700; color: var(--tm-accent); }
.tm-lb-stats .orders { font-size: 12px; color: var(--tm-muted); }

/* Empty state */
.tm-empty { text-align: center; padding: 48px; color: var(--tm-muted); }
.tm-empty .icon { font-size: 48px; margin-bottom: 16px; display: block; }
.tm-empty h3 { font-size: 16px; font-weight: 700; color: var(--tm-ink); margin-bottom: 6px; }

/* Section tabs */
.tm-section { display: none; }
.tm-section.active { display: block; }

/* Search */
.tm-search { padding: 10px 16px; border: 1px solid var(--tm-border); border-radius: 10px; font-size: 13px; width: 260px; outline: none; }
.tm-search:focus { border-color: var(--tm-accent); }

@media (max-width: 768px) {
    .tm-stats { grid-template-columns: repeat(2, 1fr); }
    .tm-team-grid { grid-template-columns: 1fr; }
}
</style>

<div class="tm-page">
    <div class="tm-page-header" style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <h1>👥 Team Management</h1>
            <p>Manage your team, review join requests, track performance</p>
        </div>
        <div style="display:flex;gap:10px">
            <input class="tm-search" placeholder="Search team..." oninput="filterMembers(this.value)">
            <button class="tm-btn tm-btn-approve" onclick="generateInviteCode()" style="padding:10px 20px">+ Generate Invite Code</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="tm-stats" id="tm-stats"></div>

    <!-- Tabs -->
    <div class="tm-tabs">
        <button class="tm-tab active" onclick="showSection('pending')" id="tab-pending">
            Pending Requests <span class="badge" id="pending-badge">0</span>
        </button>
        <button class="tm-tab" onclick="showSection('team')">Team Members</button>
        <button class="tm-tab" onclick="showSection('subfarms')">Sub-Farms</button>
        <button class="tm-tab" onclick="showSection('performance')">Performance</button>
        <button class="tm-tab" onclick="showSection('notifications')">
            Notifications <span class="badge" id="notif-badge">0</span>
        </button>
        <button class="tm-tab" onclick="showSection('activity')">Activity Log</button>
    </div>

    <!-- Pending Requests -->
    <div class="tm-section active" id="section-pending">
        <div class="tm-pending-card">
            <div class="tm-pending-header">
                <h3><span class="tm-pending-count" id="pending-count">0</span> Pending Join Requests</h3>
            </div>
            <div id="pending-list"></div>
        </div>
    </div>

    <!-- Team Members -->
    <div class="tm-section" id="section-team">
        <div class="tm-team-grid" id="team-grid"></div>
    </div>

    <!-- Sub-Farms -->
    <div class="tm-section" id="section-subfarms">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h3 style="font-size:16px;font-weight:700">Your Farms</h3>
            <button class="tm-btn tm-btn-approve" onclick="showCreateFarm()">+ Add Farm</button>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px" id="farms-grid"></div>
    </div>

    <!-- Performance -->
    <div class="tm-section" id="section-performance">
        <div class="tm-pending-card">
            <div class="tm-pending-header">
                <h3>🏆 Sales Leaderboard — <span id="lb-month"></span></h3>
            </div>
            <div id="leaderboard-list"></div>
        </div>
    </div>

    <!-- Notifications -->
    <div class="tm-section" id="section-notifications">
        <div class="tm-pending-card">
            <div class="tm-pending-header">
                <h3>🔔 Notifications</h3>
                <button class="tm-btn tm-btn-ghost" onclick="markAllRead()">Mark all read</button>
            </div>
            <div id="notif-list"></div>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="tm-section" id="section-activity">
        <div class="tm-pending-card">
            <div class="tm-pending-header">
                <h3>📋 Recent Activity</h3>
            </div>
            <div id="activity-list"></div>
        </div>
    </div>
</div>

<!-- Modal for invite code -->
<div id="tm-modal-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:1000;display:none;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:32px;max-width:440px;width:90%;box-shadow:0 24px 64px rgba(0,0,0,0.2)">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:4px" id="modal-title">Generate Invite Code</h3>
        <p style="font-size:13px;color:var(--tm-muted);margin-bottom:20px" id="modal-sub">Create a code for a new team member</p>
        <div id="modal-body"></div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
            <button class="tm-btn tm-btn-ghost" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
const API = '/api/team.php';
let allMembers = [];

// ── Fetch helpers ──
async function fetchAPI(action, data = null) {
    const opts = data ? { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({action, ...data}) } : {};
    const res = await fetch(`${API}?action=${action}`, opts);
    return res.json();
}

// ── Section switching ──
function showSection(name) {
    document.querySelectorAll('.tm-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tm-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('section-' + name)?.classList.add('active');
    document.getElementById('tab-' + name)?.classList.add('active');

    if (name === 'pending') loadPending();
    if (name === 'team') loadTeam();
    if (name === 'subfarms') loadSubFarms();
    if (name === 'performance') loadLeaderboard();
    if (name === 'notifications') loadNotifications();
    if (name === 'activity') loadActivity();
}

// ── Pending Requests ──
async function loadPending() {
    const data = await fetchAPI('pending_requests');
    const list = document.getElementById('pending-list');
    const count = document.getElementById('pending-count');
    const badge = document.getElementById('pending-badge');
    const requests = data.requests || [];

    count.textContent = requests.length;
    badge.textContent = requests.length;
    badge.classList.toggle('show', requests.length > 0);

    if (requests.length === 0) {
        list.innerHTML = '<div class="tm-empty"><span class="icon">✅</span><h3>No pending requests</h3><p>All join requests have been reviewed</p></div>';
        return;
    }

    const colors = ['#16A34A','#2563EB','#7C3AED','#DB2777','#D97706','#0891B2'];
    list.innerHTML = requests.map((r, i) => {
        const initials = (r.full_name || 'U').split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2);
        const color = colors[i % colors.length];
        return `
        <div class="tm-request-row">
            <div class="tm-request-avatar" style="background:${color}">${initials}</div>
            <div class="tm-request-info">
                <h4>${esc(r.full_name)} <span class="tm-role-badge tm-role-${r.requested_role}">${r.requested_role.replace(/_/g,' ')}</span></h4>
                <div class="meta">
                    <span>📧 ${esc(r.email || '')}</span>
                    <span>🔑 Code: ${esc(r.code_used || '')}</span>
                    <span>⏰ ${timeAgo(r.created_at)}</span>
                </div>
            </div>
            <div class="tm-request-actions">
                <button class="tm-btn tm-btn-approve" onclick="handleRequest(${r.id}, 'approve_request')">✓ Approve</button>
                <button class="tm-btn tm-btn-reject" onclick="handleRequest(${r.id}, 'reject_request')">✕ Reject</button>
            </div>
        </div>`;
    }).join('');
}

async function handleRequest(id, action) {
    if (!confirm(action === 'approve_request' ? 'Approve this request?' : 'Reject this request?')) return;
    const data = await fetchAPI(action, { request_id: id });
    if (data.success) { loadPending(); loadStats(); }
}

// ── Team Members ──
async function loadTeam() {
    const data = await fetchAPI('team_dashboard');
    allMembers = data.members || [];
    renderMembers(allMembers);
    renderStats(data.stats || {});
}

function renderMembers(members) {
    const grid = document.getElementById('team-grid');
    if (members.length === 0) {
        grid.innerHTML = '<div class="tm-empty"><span class="icon">👥</span><h3>No team members yet</h3><p>Share an invite code to grow your team</p></div>';
        return;
    }
    const colors = ['#16A34A','#2563EB','#7C3AED','#DB2777','#D97706','#0891B2','#DC2626','#0D9488'];
    grid.innerHTML = members.map((m, i) => {
        const initials = (m.full_name || m.username || 'U').split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2);
        const color = colors[i % colors.length];
        const statusText = m.online_status === 'online' ? 'Online now' : m.online_status === 'idle' ? 'Idle' : m.online_status === 'never' ? 'Never active' : 'Offline';
        const lastActive = m.last_active ? timeAgo(m.last_active) : 'Never';

        return `
        <div class="tm-member-card" data-name="${esc((m.full_name || '').toLowerCase())}">
            <div class="tm-member-top">
                <div class="tm-member-avatar" style="background:${color}">
                    ${initials}
                    <div class="tm-status-dot ${m.online_status}"></div>
                </div>
                <div>
                    <div class="tm-member-name">${esc(m.full_name || m.username)}</div>
                    <div class="tm-member-role"><span class="tm-role-badge tm-role-${m.role}">${m.role.replace(/_/g,' ')}</span></div>
                </div>
            </div>
            <div class="tm-member-meta">
                <span>🟢 ${statusText}</span>
                <span>⏰ ${lastActive}</span>
                ${m.current_page ? '<span>📄 ' + esc(m.current_page) + '</span>' : ''}
            </div>
            ${m.role !== 'farm_owner' ? `
            <div class="tm-member-actions">
                <button class="tm-btn tm-btn-ghost" onclick="changeRole(${m.membership_id}, '${m.role}')">Change Role</button>
                <button class="tm-btn tm-btn-reject" style="padding:6px 12px;font-size:11px" onclick="removeMember(${m.membership_id}, '${esc(m.full_name)}')">Remove</button>
            </div>` : ''}
        </div>`;
    }).join('');
}

function renderStats(stats) {
    document.getElementById('tm-stats').innerHTML = `
        <div class="tm-stat"><div class="value" style="color:var(--tm-accent)">${stats.total || 0}</div><div class="label">Total Members</div></div>
        <div class="tm-stat"><div class="value" style="color:#22C55E">${stats.online || 0}</div><div class="label">Online Now</div></div>
        <div class="tm-stat"><div class="value" style="color:#3B82F6">${Object.keys(stats.by_role || {}).length}</div><div class="label">Roles Active</div></div>
        <div class="tm-stat"><div class="value" style="color:#8B5CF6">${Object.values(stats.by_role || {}).reduce((a,b) => a+b, 0)}</div><div class="label">Team Size</div></div>
    `;
}

function filterMembers(q) {
    const filtered = allMembers.filter(m => (m.full_name || '').toLowerCase().includes(q.toLowerCase()) || (m.role || '').includes(q.toLowerCase()));
    renderMembers(filtered);
}

// ── Sub-Farms ──
async function loadSubFarms() {
    const data = await fetchAPI('sub_farms');
    const grid = document.getElementById('farms-grid');
    const farms = data.farms || [];

    grid.innerHTML = farms.map(f => `
        <div class="tm-farm-card">
            <span class="tm-farm-icon">🏠</span>
            <div class="tm-farm-name">${esc(f.name)}</div>
            <div class="tm-farm-meta">
                📍 ${esc(f.location || 'No location')} · 👥 ${f.member_count || 0} members · 🔑 ${esc(f.farm_code)}
            </div>
            <div style="margin-top:12px;font-size:11px;color:var(--tm-muted)">Created ${timeAgo(f.created_at)}</div>
        </div>
    `).join('') + `
        <div class="tm-farm-card" onclick="showCreateFarm()" style="display:flex;align-items:center;justify-content:center;border-style:dashed;cursor:pointer;min-height:140px">
            <div style="text-align:center;color:var(--tm-muted)">
                <div style="font-size:32px;margin-bottom:8px">+</div>
                <div style="font-size:13px;font-weight:600">Add New Farm</div>
            </div>
        </div>
    `;
}

function showCreateFarm() {
    openModal('Create New Farm', 'Add a branch or separate farm to your account', `
        <div style="margin-bottom:16px">
            <label style="font-size:12px;font-weight:600;color:var(--tm-muted);display:block;margin-bottom:6px">FARM NAME</label>
            <input id="farm-name-input" style="width:100%;padding:10px 14px;border:1px solid var(--tm-border);border-radius:10px;font-size:14px;outline:none" placeholder="e.g. Wangari Nakuru Branch">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--tm-muted);display:block;margin-bottom:6px">LOCATION</label>
            <input id="farm-location-input" style="width:100%;padding:10px 14px;border:1px solid var(--tm-border);border-radius:10px;font-size:14px;outline:none" placeholder="e.g. Nakuru, Kenya">
        </div>
        <button class="tm-btn tm-btn-approve" style="width:100%;margin-top:16px;padding:12px" onclick="createFarm()">Create Farm</button>
    `);
}

async function createFarm() {
    const name = document.getElementById('farm-name-input').value;
    const location = document.getElementById('farm-location-input').value;
    if (!name) return alert('Farm name is required');
    const data = await fetchAPI('create_farm', { name, location });
    if (data.success) { closeModal(); loadSubFarms(); }
}

// ── Invite Code ──
async function generateInviteCode() {
    openModal('Generate Invite Code', 'Create a code for a new team member', `
        <div style="margin-bottom:16px">
            <label style="font-size:12px;font-weight:600;color:var(--tm-muted);display:block;margin-bottom:6px">ROLE</label>
            <select id="code-role" style="width:100%;padding:10px 14px;border:1px solid var(--tm-border);border-radius:10px;font-size:14px">
                <option value="field_worker">Field Worker</option>
                <option value="stock_manager">Stock Manager</option>
                <option value="sales_staff">Sales Staff</option>
                <option value="farm_manager">Farm Manager</option>
                <option value="veterinarian">Veterinarian</option>
                <option value="accountant">Accountant</option>
                <option value="auditor">Auditor (Read-only)</option>
                <option value="guest">Guest/Client</option>
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--tm-muted);display:block;margin-bottom:6px">MAX USES</label>
                <input id="code-uses" type="number" value="1" min="1" max="100" style="width:100%;padding:10px 14px;border:1px solid var(--tm-border);border-radius:10px;font-size:14px;outline:none">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--tm-muted);display:block;margin-bottom:6px">EXPIRES IN (DAYS)</label>
                <input id="code-expires" type="number" value="7" min="0" max="90" style="width:100%;padding:10px 14px;border:1px solid var(--tm-border);border-radius:10px;font-size:14px;outline:none">
            </div>
        </div>
        <button class="tm-btn tm-btn-approve" style="width:100%;padding:12px" onclick="doGenerateCode()">Generate Code</button>
        <div id="code-result" style="margin-top:16px;display:none;padding:16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;text-align:center">
            <div style="font-size:12px;color:var(--tm-muted);margin-bottom:6px">INVITE CODE</div>
            <div id="generated-code" style="font-size:22px;font-weight:800;font-family:monospace;letter-spacing:2px;color:var(--tm-accent)"></div>
            <div style="font-size:11px;color:var(--tm-muted);margin-top:6px">Share this code with your team member</div>
        </div>
    `);
}

async function doGenerateCode() {
    const role = document.getElementById('code-role').value;
    const maxUses = document.getElementById('code-uses').value;
    const expiresDays = document.getElementById('code-expires').value;
    const data = await fetchAPI('/api/farm_codes.php?action=generate_code', null);
    // Use fetch directly for farm_codes API
    const res = await fetch('/api/farm_codes.php?action=generate_code', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ role, max_uses: parseInt(maxUses), expires_days: parseInt(expiresDays) })
    });
    const result = await res.json();
    if (result.success) {
        document.getElementById('generated-code').textContent = result.code.code;
        document.getElementById('code-result').style.display = 'block';
    }
}

// ── Leaderboard ──
async function loadLeaderboard() {
    const data = await fetchAPI('sales_leaderboard');
    const list = document.getElementById('leaderboard-list');
    const lb = data.leaderboard || [];
    document.getElementById('lb-month').textContent = data.month || new Date().toISOString().slice(0,7);

    if (lb.length === 0) {
        list.innerHTML = '<div class="tm-empty"><span class="icon">🏆</span><h3>No sales data yet</h3><p>Sales performance will appear here once orders are placed</p></div>';
        return;
    }

    list.innerHTML = lb.map(p => `
        <div class="tm-lb-row">
            <div class="tm-lb-rank ${p.tier}">${p.rank}</div>
            <div style="width:40px;height:40px;border-radius:10px;background:${p.tier==='gold'?'#FEF3C7':p.tier==='silver'?'#F1F5F9':p.tier==='bronze'?'#FED7AA':'#F1F5F9'};display:flex;align-items:center;justify-content:center;font-size:16px">${p.rank===1?'🥇':p.rank===2?'🥈':p.rank===3?'🥉':'👤'}</div>
            <div class="tm-lb-info">
                <h4>${esc(p.full_name)}</h4>
                <div class="sub">${p.total_orders} orders · ${p.completed_orders} completed · <span class="tm-role-badge tm-role-${p.role}" style="font-size:10px">${p.role.replace(/_/g,' ')}</span></div>
            </div>
            <div class="tm-lb-stats">
                <div class="revenue">KES ${(p.total_revenue || 0).toLocaleString()}</div>
                <div class="orders">${p.completed_orders} completed</div>
            </div>
        </div>
    `).join('');
}

// ── Notifications ──
async function loadNotifications() {
    const data = await fetchAPI('notifications&all');
    const list = document.getElementById('notif-list');
    const badge = document.getElementById('notif-badge');
    const notifs = data.notifications || [];
    badge.textContent = data.unread_count || 0;
    badge.classList.toggle('show', (data.unread_count || 0) > 0);

    const icons = { join_approved: '✅', join_rejected: '❌', role_changed: '🔄', member_approved: '👤', member_rejected: '👤', removed_from_farm: '🚫', low_stock: '⚠️', new_order: '📋', info: 'ℹ️' };

    if (notifs.length === 0) {
        list.innerHTML = '<div class="tm-empty"><span class="icon">🔔</span><h3>No notifications</h3><p>You\'re all caught up!</p></div>';
        return;
    }

    list.innerHTML = notifs.map(n => `
        <div class="tm-notif-item ${n.is_read ? '' : 'unread'}">
            <div class="tm-notif-icon" style="background:${n.is_read ? '#F1F5F9' : '#DCFCE7'}">${icons[n.type] || '📌'}</div>
            <div class="tm-notif-content">
                <h4>${esc(n.title)}</h4>
                <p>${esc(n.message || '')}</p>
            </div>
            <div class="tm-notif-time">${timeAgo(n.created_at)}</div>
        </div>
    `).join('');
}

async function markAllRead() {
    await fetchAPI('mark_read', {});
    loadNotifications();
}

// ── Activity Log ──
async function loadActivity() {
    const data = await fetchAPI('activity_trail');
    const list = document.getElementById('activity-list');
    const activity = data.activity || [];

    if (activity.length === 0) {
        list.innerHTML = '<div class="tm-empty"><span class="icon">📋</span><h3>No activity yet</h3><p>Actions will appear here as your team works</p></div>';
        return;
    }

    list.innerHTML = activity.map(a => `
        <div class="tm-notif-item">
            <div class="tm-notif-icon" style="background:#F1F5F9;font-size:14px">📋</div>
            <div class="tm-notif-content">
                <h4>${esc(a.action)} ${a.entity_type ? '· ' + esc(a.entity_type) : ''}</h4>
                <p>${esc(a.details || '')} ${a.full_name ? '· by ' + esc(a.full_name) : ''}</p>
            </div>
            <div class="tm-notif-time">${timeAgo(a.created_at)}</div>
        </div>
    `).join('');
}

// ── Role Change ──
function changeRole(memberId, currentRole) {
    const roles = ['farm_manager','stock_manager','sales_staff','field_worker','veterinarian','accountant','auditor','guest'];
    openModal('Change Role', 'Update this member\'s role and permissions', `
        <label style="font-size:12px;font-weight:600;color:var(--tm-muted);display:block;margin-bottom:6px">NEW ROLE</label>
        <select id="new-role" style="width:100%;padding:10px 14px;border:1px solid var(--tm-border);border-radius:10px;font-size:14px">
            ${roles.map(r => `<option value="${r}" ${r===currentRole?'selected':''}>${r.replace(/_/g,' ')}</option>`).join('')}
        </select>
        <button class="tm-btn tm-btn-approve" style="width:100%;margin-top:16px;padding:12px" onclick="doChangeRole(${memberId})">Save Changes</button>
    `);
}

async function doChangeRole(memberId) {
    const newRole = document.getElementById('new-role').value;
    const data = await fetchAPI('change_role', { member_id: memberId, new_role: newRole });
    if (data.success) { closeModal(); loadTeam(); }
}

// ── Remove Member ──
async function removeMember(memberId, name) {
    if (!confirm(`Remove ${name} from the team?`)) return;
    const data = await fetchAPI('remove_member', { member_id: memberId });
    if (data.success) { loadTeam(); loadStats(); }
}

// ── Modal ──
function openModal(title, sub, body) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-sub').textContent = sub;
    document.getElementById('modal-body').innerHTML = body;
    document.getElementById('tm-modal-overlay').style.display = 'flex';
}
function closeModal() { document.getElementById('tm-modal-overlay').style.display = 'none'; }

// ── Utils ──
function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
}

// ── Load on start ──
loadPending();
loadTeam();
loadStats();
async function loadStats() {
    const data = await fetchAPI('team_dashboard');
    renderStats(data.stats || {});
}

// ── Auto-refresh online status every 30s ──
setInterval(() => { if (document.querySelector('#section-team.active')) loadTeam(); }, 30000);
// ── Auto-refresh pending every 60s ──
setInterval(() => { if (document.querySelector('#section-pending.active')) loadPending(); }, 60000);
</script>
</body>
</html>
