<?php
/**
 * Sub-Module: Health & Vaccinations
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
$page_title = 'Health & Vaccines';
include __DIR__ . '/includes/admin_header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin-stock.css?v=1.3">

<div class="admin-stock-wrapper">
    <div class="dashboard-hero-card" style="background: linear-gradient(135deg, var(--admin-primary) 0%, #064e3b 100%); padding: 32px; border-radius: 8px; margin-bottom: 32px; color: #ffffff;">
        <h1 style="color: #ffffff; margin: 0 0 8px 0;">Health & Vaccines</h1>
        <p style="color: rgba(255, 255, 255, 0.9); margin: 0;">Schedule vaccine programs, mark treatments completed, and review immunization records.</p>
    </div>

    <!-- Vaccinations Card -->
    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Flock Immunization Schedule</h3>
            <button class="btn btn-primary btn-sm" onclick="openVaccinationModal()">
                <i data-lucide="plus"></i> Schedule Vaccine
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Scheduled Date</th>
                        <th>Flock</th>
                        <th>Vaccine Name</th>
                        <th>Administered Date</th>
                        <th>Status</th>
                        <th>Administered By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="vaccinations-body">
                    <tr><td colspan="7" style="text-align:center; padding: 20px;">Loading schedule...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Schedule Vaccine Modal -->
<div id="vaccination-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #ffffff; padding: 32px; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 id="vaccination-modal-title" style="margin-bottom: 24px;">Schedule Vaccine</h3>
        <form id="vaccination-form">
            <input type="hidden" name="id" id="vaccination-id">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Target Flock</label>
                <select name="flock_id" id="vaccination-flock-id" class="form-control" required style="width:100%; height:42px;">
                    <option value="">Select Flock...</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Vaccine / Treatment Name</label>
                <input type="text" name="vaccine_name" id="vaccination-name" class="form-control" placeholder="e.g. Gumboro (1st Dose)" required>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Scheduled Date</label>
                <input type="date" name="scheduled_date" id="vaccination-scheduled-date" class="form-control" required>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Administered Date (Leave empty if not completed)</label>
                <input type="date" name="administered_date" id="vaccination-administered-date" class="form-control">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Status</label>
                <select name="status" id="vaccination-status" class="form-control" style="width:100%; height:42px;">
                    <?php 
                    require_once __DIR__ . '/../../Backend/api/dropdowns.php';
                    echo renderDropdownOptions('vaccination_statuses', null, ''); 
                    ?>
                </select>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn btn-trans" style="flex: 1;" onclick="closeVaccinationModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Vaccine</button>
            </div>
        </form>
    </div>
</div>

<script>
window.vaccinations_list = [];
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
    setTableLoading('vaccinations-body', 7, 'Loading schedule...');
    try {
        const flockRes = await fetch('/Backend/api/admin_poultry.php?action=get_flocks');
        if (!flockRes.ok) throw new Error('Server error');
        const flockResult = await flockRes.json();
        if (flockResult.success) {
            window.flocks_list = flockResult.data;
            populateFlocksDropdown();
        }

        const vacRes = await fetch('/Backend/api/admin_poultry.php?action=get_vaccinations');
        if (!vacRes.ok) throw new Error('Server error');
        const vacResult = await vacRes.json();
        if (vacResult.success) {
            window.vaccinations_list = vacResult.data;
            renderVaccinations();
        } else {
            showTableError('vaccinations-body', 7, vacResult.message || 'Failed to load vaccinations.');
        }
    } catch(e) {
        showTableError('vaccinations-body', 7, 'Network error. Could not connect to server.');
        console.error('Error loading vaccination data:', e);
    }
}

function populateFlocksDropdown() {
    const dropdown = document.getElementById('vaccination-flock-id');
    dropdown.innerHTML = '<option value="">Select Flock...</option>' + 
        window.flocks_list.filter(f => f.status === 'active')
            .map(f => `<option value="${f.id}">${f.flock_name}</option>`).join('');
}

function renderVaccinations() {
    const tbody = document.getElementById('vaccinations-body');
    if (!window.vaccinations_list.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;">No vaccinations scheduled yet. Click "Schedule Vaccine" to start.</td></tr>';
        return;
    }

    tbody.innerHTML = window.vaccinations_list.map(v => {
        let statusBadge = 'badge-pill-warning';
        if (v.status === 'completed') statusBadge = 'badge-pill-success';
        if (v.status === 'missed') statusBadge = 'badge-pill-danger';

        return `
        <tr>
            <td>${v.scheduled_date}</td>
            <td><strong>${v.flock_name}</strong></td>
            <td>${v.vaccine_name}</td>
            <td>${v.administered_date || '<span style="color:#94a3b8;">Not Administered</span>'}</td>
            <td><span class="badge-pill ${statusBadge}">${v.status}</span></td>
            <td>${v.user_name || '-'}</td>
            <td>
                <div style="display:flex; gap: 8px;">
                    <button class="btn btn-trans btn-sm" onclick="editVaccination(${v.id})">Edit</button>
                    <button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="deleteVaccination(${v.id})">Delete</button>
                </div>
            </td>
        </tr>
        `;
    }).join('');
}

function openVaccinationModal() {
    document.getElementById('vaccination-modal-title').textContent = 'Schedule Vaccine';
    document.getElementById('vaccination-form').reset();
    document.getElementById('vaccination-id').value = '';
    document.getElementById('vaccination-modal').style.display = 'flex';
}

function closeVaccinationModal() {
    document.getElementById('vaccination-modal').style.display = 'none';
}

function editVaccination(id) {
    const v = window.vaccinations_list.find(item => item.id == id);
    if (!v) return;

    document.getElementById('vaccination-modal-title').textContent = 'Edit Vaccination Program';
    document.getElementById('vaccination-id').value = v.id;
    document.getElementById('vaccination-flock-id').value = v.flock_id;
    document.getElementById('vaccination-name').value = v.vaccine_name;
    document.getElementById('vaccination-scheduled-date').value = v.scheduled_date;
    document.getElementById('vaccination-administered-date').value = v.administered_date || '';
    document.getElementById('vaccination-status').value = v.status;

    document.getElementById('vaccination-modal').style.display = 'flex';
}

async function deleteVaccination(id) {
    if (!confirm('Are you sure you want to delete this scheduled vaccination?')) return;
    try {
        const formData = new FormData();
        formData.append('id', id.toString());
        formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');

        const response = await fetch('/Backend/api/admin_poultry.php?action=delete_vaccination', {
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

document.getElementById('vaccination-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('[type="submit"]');
    setBtnLoading(btn, true);
    const formData = new FormData(e.target);
    formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');
    try {
        const response = await fetch('/Backend/api/admin_poultry.php?action=save_vaccination', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            closeVaccinationModal();
            loadData();
        } else {
            alert('Error: ' + (result.message || 'Could not save vaccination.'));
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
