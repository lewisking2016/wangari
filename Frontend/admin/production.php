<?php
/**
 * Sub-Module: Daily Production Logs
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','sales_staff'], true)) {
    echo "<script>window.location.href = '/wangariadmin';</script>";
    exit;
}

$path_prefix = '../../';
$page_title = 'Production Tracker';
include __DIR__ . '/includes/admin_header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin-stock.css?v=1.3">

<div class="admin-stock-wrapper">
    <div class="dashboard-hero-card" style="background: linear-gradient(135deg, var(--admin-primary) 0%, #064e3b 100%); padding: 32px; border-radius: 8px; margin-bottom: 32px; color: #ffffff;">
        <h1 style="color: #ffffff; margin: 0 0 8px 0;">Production Tracker</h1>
        <p style="color: rgba(255, 255, 255, 0.9); margin: 0;">Log daily egg yields, broiler meat output weights, feed consumption, and mortality stats.</p>
    </div>

    <!-- Production Logs Card -->
    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Daily Yield & Production Logbook</h3>
            <button class="btn btn-primary btn-sm" onclick="openProductionModal()">
                <i data-lucide="plus"></i> Log Daily Yield
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Flock</th>
                        <th>Eggs Collected</th>
                        <th>Cracked Eggs</th>
                        <th>Meat Weight (kgs)</th>
                        <th>Feed Eaten (kgs)</th>
                        <th>Mortality</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="production-body">
                    <tr><td colspan="9" style="text-align:center; padding: 20px;">Loading yield records...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Log Daily Yield Modal -->
<div id="production-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #ffffff; padding: 32px; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow-y:auto; max-height:90vh;">
        <h3 id="production-modal-title" style="margin-bottom: 24px;">Log Daily Yield</h3>
        <form id="production-form">
            <input type="hidden" name="id" id="production-id">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Select Flock</label>
                <select name="flock_id" id="production-flock-id" class="form-control" required style="width:100%; height:42px;">
                    <option value="">Choose a Flock...</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Record Date</label>
                <input type="date" name="record_date" id="production-date" class="form-control" required>
            </div>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <div class="form-group">
                    <label class="form-label">Eggs Collected (Pieces)</label>
                    <input type="number" name="eggs_collected" id="production-eggs" class="form-control" min="0" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Cracked Eggs</label>
                    <input type="number" name="cracked_eggs" id="production-cracked" class="form-control" min="0" value="0">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <div class="form-group">
                    <label class="form-label">Meat Yield (kgs)</label>
                    <input type="number" name="meat_weight_kg" id="production-meat" class="form-control" step="0.01" min="0" value="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Feed Eaten (kgs)</label>
                    <input type="number" name="feed_consumed_kg" id="production-feed" class="form-control" step="0.01" min="0" value="0.00">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Mortality (Birds Lost Today)</label>
                <input type="number" name="mortality" id="production-mortality" class="form-control" min="0" value="0">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Notes / Remarks</label>
                <textarea name="notes" id="production-notes" class="form-control" style="height: 60px; font-family:inherit;"></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn btn-trans" style="flex: 1;" onclick="closeProductionModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Log</button>
            </div>
        </form>
    </div>
</div>

<script>
window.production_list = [];
window.flocks_list = [];

function showTableError(tbodyId, colSpan, message) {
    document.getElementById(tbodyId).innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; padding: 32px;"><div style="display:inline-flex; align-items:center; gap:10px; color:#dc2626; background:#fef2f2; border:1px solid #fecaca; padding:14px 24px; border-radius:8px; font-weight:600;"><i data-lucide=\"alert-triangle\" style=\"width:18px;height:18px;\"></i>${message}</div></td></tr>`;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function setTableLoading(tbodyId, colSpan, message) {
    document.getElementById(tbodyId).innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; padding: 32px; color:#64748b;"><div style="display:inline-flex; align-items:center; gap:10px;"><div style="width:20px;height:20px;border:2px solid #cbd5e1;border-top-color:var(--admin-primary);border-radius:50%;animation:spin 0.8s linear infinite;"></div>${message}</div></td></tr>`;
}

function setBtnLoading(btn, loading) {
    if (loading) {
        btn.disabled = true;
        btn.dataset.origText = btn.innerHTML;
        btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:8px;"><div style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.8s linear infinite;"></div>Saving...</span>';
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.origText || 'Save';
    }
}

async function loadData() {
    setTableLoading('production-body', 9, 'Loading yield records...');
    try {
        const flockRes = await fetch('/Backend/api/admin_poultry.php?action=get_flocks');
        if (!flockRes.ok) throw new Error('Server error');
        const flockResult = await flockRes.json();
        if (flockResult.success) {
            window.flocks_list = flockResult.data;
            populateFlocksDropdown();
        }

        const prodRes = await fetch('/Backend/api/admin_poultry.php?action=get_production');
        if (!prodRes.ok) throw new Error('Server error');
        const prodResult = await prodRes.json();
        if (prodResult.success) {
            window.production_list = prodResult.data;
            renderProduction();
        } else {
            showTableError('production-body', 9, prodResult.message || 'Failed to load production logs.');
        }
    } catch (e) {
        showTableError('production-body', 9, 'Network error. Could not connect to server.');
        console.error('Error loading production data:', e);
    }
}

function populateFlocksDropdown() {
    const dropdown = document.getElementById('production-flock-id');
    dropdown.innerHTML = '<option value="">Choose a Flock...</option>' + 
        window.flocks_list.filter(f => f.status === 'active')
            .map(f => `<option value="${f.id}">${f.flock_name} (${f.breed})</option>`).join('');
}

function renderProduction() {
    const tbody = document.getElementById('production-body');
    if (!window.production_list.length) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding: 20px;">No daily yield logs recorded yet. Click "Log Daily Yield" to begin.</td></tr>';
        return;
    }

    tbody.innerHTML = window.production_list.map(p => `
        <tr>
            <td>${p.record_date}</td>
            <td><strong>${p.flock_name}</strong></td>
            <td>${Number(p.eggs_collected).toLocaleString()}</td>
            <td>${Number(p.cracked_eggs).toLocaleString()}</td>
            <td>${Number(p.meat_weight_kg).toLocaleString()} kgs</td>
            <td>${Number(p.feed_consumed_kg).toLocaleString()} kgs</td>
            <td><strong style="color:${p.mortality > 0 ? '#dc2626' : '#1e293b'}">${p.mortality}</strong></td>
            <td style="max-width:200px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">${p.notes || '-'}</td>
            <td>
                <div style="display:flex; gap: 8px;">
                    <button class="btn btn-trans btn-sm" onclick="editProduction(${p.id})">Edit</button>
                    <button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="deleteProduction(${p.id})">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function openProductionModal() {
    document.getElementById('production-modal-title').textContent = 'Log Daily Yield';
    document.getElementById('production-form').reset();
    document.getElementById('production-id').value = '';
    document.getElementById('production-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('production-modal').style.display = 'flex';
}

function closeProductionModal() {
    document.getElementById('production-modal').style.display = 'none';
}

function editProduction(id) {
    const p = window.production_list.find(item => item.id == id);
    if (!p) return;

    document.getElementById('production-modal-title').textContent = 'Edit Daily Log';
    document.getElementById('production-id').value = p.id;
    document.getElementById('production-flock-id').value = p.flock_id;
    document.getElementById('production-date').value = p.record_date;
    document.getElementById('production-eggs').value = p.eggs_collected;
    document.getElementById('production-cracked').value = p.cracked_eggs;
    document.getElementById('production-meat').value = p.meat_weight_kg;
    document.getElementById('production-feed').value = p.feed_consumed_kg;
    document.getElementById('production-mortality').value = p.mortality;
    document.getElementById('production-notes').value = p.notes;

    document.getElementById('production-modal').style.display = 'flex';
}

async function deleteProduction(id) {
    if (!confirm('Are you sure you want to delete this log? If this log has mortality, deleting will add the birds back to the flock count.')) return;
    try {
        const formData = new FormData();
        formData.append('id', id.toString());
        formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');

        const response = await fetch('/Backend/api/admin_poultry.php?action=delete_production', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            loadData();
        } else {
            alert(result.message);
        }
    } catch(e) { console.error(e); }
}

document.getElementById('production-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('[type="submit"]');
    setBtnLoading(btn, true);
    const formData = new FormData(e.target);
    formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');
    try {
        const response = await fetch('/Backend/api/admin_poultry.php?action=save_production', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            closeProductionModal();
            loadData();
        } else {
            alert('Error: ' + (result.message || 'Could not save log.'));
        }
    } catch(e) {
        alert('Network error. Please check your connection and try again.');
        console.error(e);
    } finally {
        setBtnLoading(btn, false);
    }
});

if (!document.getElementById('admin-spin-style')) {
    const style = document.createElement('style');
    style.id = 'admin-spin-style';
    style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(style);
}

document.addEventListener('DOMContentLoaded', () => {
    loadData();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
