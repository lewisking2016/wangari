<?php
/**
 * Sub-Module: Incoming Stock (Purchases) & Suppliers
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager', 'stock_manager', 'sales_staff'], true)) {
    echo "<script>window.location.href = '/wangariadmin';</script>";
    exit;
}

$path_prefix = '../../';
$page_title = 'Buy Ingredients & Manage Suppliers';
include __DIR__ . '/includes/admin_header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin-stock.css?v=1.3">

<div class="admin-stock-wrapper">
    <div class="dashboard-hero-card" style="background: linear-gradient(135deg, var(--admin-primary) 0%, #064e3b 100%); padding: 32px; border-radius: 8px; margin-bottom: 32px; color: #ffffff;">
        <h1 style="color: #ffffff; margin: 0 0 8px 0;">Buy Ingredients & Track Deliveries</h1>
        <p style="color: rgba(255, 255, 255, 0.9); margin: 0;">Record what you buy, who you buy from, and get notified when stock is running low.</p>
    </div>

    <?php include __DIR__ . '/includes/stock_nav.php'; ?>

    <!-- Tabs Header -->
    <div class="tabs" style="margin-bottom: 24px;">
        <button class="tab-button active" onclick="switchTab('shipments-tab', this)">Purchases Made</button>
        <button class="tab-button" onclick="switchTab('suppliers-tab', this)">Our Suppliers</button>
        <button class="tab-button" onclick="switchTab('auto-orders-tab', this)">Auto-Order Assistant</button>
    </div>

    <!-- Tab 1: Incoming Shipments -->
    <div id="shipments-tab" class="tab-content" style="display: block;">
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>All Purchases & Deliveries</h3>
                <button class="btn btn-primary btn-sm" onclick="openShipmentModal()">
                    <i data-lucide="plus"></i> Record Purchase
                </button>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Expected Date</th>
                            <th>Ingredient / Material</th>
                            <th>Quantity (kgs)</th>
                            <th>Price per kgs</th>
                            <th>Total Cost</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="shipments-body">
                        <tr><td colspan="8" style="text-align:center; padding: 20px;">Loading shipments data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Supplier Directory -->
    <div id="suppliers-tab" class="tab-content" style="display: none;">
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Our Suppliers</h3>
                <button class="btn btn-primary btn-sm" onclick="openSupplierModal()">
                    <i data-lucide="plus"></i> Add New Supplier
                </button>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Lead Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="suppliers-body">
                        <tr><td colspan="7" style="text-align:center; padding: 20px;">Loading suppliers list...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Auto-Order Assistant -->
    <div id="auto-orders-tab" class="tab-content" style="display: none;">
        <div class="admin-card">
            <div style="margin-bottom: 20px;">
                <h3>What Needs Restocking</h3>
                <p style="font-size: 0.85rem; color: #64748b; margin: 4px 0 0;">These are ingredients that have run low. Click "Place Order" to buy more from your supplier.</p>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th>Current Stock (kgs)</th>
                            <th>Minimum Stock (kgs)</th>
                            <th>Supplier</th>
                            <th>Delivery Time</th>
                            <th>How Much to Buy (kgs)</th>
                            <th>Price per kgs (KES)</th>
                            <th>Estimated Total Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="auto-orders-body">
                        <tr><td colspan="9" style="text-align:center; padding: 20px;">Checking stock levels...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Shipment Modal -->
<div id="shipment-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #ffffff; padding: 32px; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 id="shipment-modal-title" style="margin-bottom: 24px;">Record a New Purchase</h3>
        <form id="shipment-form">
            <input type="hidden" name="id" id="shipment-id">
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Who are you buying from? (Supplier)</label>
                <select name="supplier_id" id="shipment-supplier" class="form-control" required style="width:100%; height:42px;">
                    <option value="">Select Supplier</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">What ingredient are you buying?</label>
                <select name="raw_material_id" id="shipment-material" class="form-control" required style="width:100%; height:42px;">
                    <option value="">Select Material</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">How many kilograms (kgs) are you ordering?</label>
                <input type="number" name="quantity_kg" id="shipment-qty" class="form-control" step="0.1" min="1" placeholder="e.g. 500" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Price per kgs (KES), How much does 1 kgs cost?</label>
                <input type="number" name="cost_per_kg" id="shipment-cost" class="form-control" step="0.01" min="0" placeholder="e.g. 45" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">When will it arrive? (Expected Date)</label>
                <input type="date" name="expected_delivery_date" id="shipment-date" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Delivery Status</label>
                <select name="status" id="shipment-status" class="form-control" required style="width:100%; height:42px;">
                    <?php 
                    require_once __DIR__ . '/../../Backend/api/dropdowns.php';
                    echo renderDropdownOptions('shipment_statuses', null, ''); 
                    ?>
                </select>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn btn-trans" style="flex: 1;" onclick="closeShipmentModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Purchase</button>
            </div>
        </form>
    </div>
</div>

<!-- Supplier Modal -->
<div id="supplier-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #ffffff; padding: 32px; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 id="supplier-modal-title" style="margin-bottom: 24px;">Add Supplier</h3>
        <form id="supplier-form">
            <input type="hidden" name="id" id="supplier-id">
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Supplier Business Name</label>
                <input type="text" name="name" id="supplier-name" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Contact Person Name</label>
                <input type="text" name="contact_name" id="supplier-contact" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" id="supplier-phone" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" id="supplier-email" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Lead Time (Days to Deliver)</label>
                <input type="number" name="lead_time_days" id="supplier-lead" class="form-control" min="1" value="5">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label">Office Address / Location</label>
                <textarea name="address" id="supplier-address" class="form-control" style="height: 60px; font-family:inherit;"></textarea>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn btn-trans" style="flex: 1;" onclick="closeSupplierModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
window.raw_materials_list = [];
window.suppliers_list = [];
window.shipments_list = [];

function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).style.display = 'block';
    btn.classList.add('active');
}

async function loadData() {
    try {
        // Fetch raw materials from dashboard
        const dashboardRes = await fetch('/Backend/api/admin_stock.php?action=get_dashboard');
        const dashboardResult = await dashboardRes.json();
        if (dashboardResult.success) {
            window.raw_materials_list = dashboardResult.data.raw_materials;
            populateMaterialDropdowns();
        }

        // Fetch suppliers
        const suppliersRes = await fetch('/Backend/api/admin_incoming_stock.php?action=get_suppliers');
        const suppliersResult = await suppliersRes.json();
        if (suppliersResult.success) {
            window.suppliers_list = suppliersResult.data;
            renderSuppliers();
            populateSupplierDropdowns();
        }

        // Fetch shipments
        const shipmentsRes = await fetch('/Backend/api/admin_incoming_stock.php?action=get_incoming_shipments');
        const shipmentsResult = await shipmentsRes.json();
        if (shipmentsResult.success) {
            window.shipments_list = shipmentsResult.data;
            renderShipments();
        }

        // Fetch auto-orders
        const autoRes = await fetch('/Backend/api/admin_incoming_stock.php?action=get_auto_orders');
        const autoResult = await autoRes.json();
        if (autoResult.success) {
            renderAutoOrders(autoResult.data);
        }

    } catch (e) {
        console.error(e);
    }
}

function populateMaterialDropdowns() {
    const dropdown = document.getElementById('shipment-material');
    dropdown.innerHTML = '<option value="">Select Material</option>' + 
        window.raw_materials_list.map(m => {
            const formattedStock = Number(m.stock_tons).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 3});
            return `<option value="${m.id}">${m.name} (Current: ${formattedStock} kgs)</option>`;
        }).join('');
}

function populateSupplierDropdowns() {
    const dropdown = document.getElementById('shipment-supplier');
    dropdown.innerHTML = '<option value="">Select Supplier</option>' + 
        window.suppliers_list.map(s => `<option value="${s.id}">${s.name} (${s.lead_time_days} days lead)</option>`).join('');
}

function renderShipments() {
    const tbody = document.getElementById('shipments-body');
    if (!window.shipments_list.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 20px;">No purchases recorded yet. Click "Record Purchase" to add one.</td></tr>';
        return;
    }

    tbody.innerHTML = window.shipments_list.map(s => {
        let statusBadge = 'badge-pill-warning';
        if (s.status === 'delivered') statusBadge = 'badge-pill-success';
        if (s.status === 'cancelled') statusBadge = 'badge-pill-danger';

        const totalCost = Number(s.quantity_kg) * Number(s.cost_per_kg);
        
        return `
        <tr>
            <td>${s.expected_delivery_date}</td>
            <td><strong>${s.material_name}</strong></td>
            <td>${Number(s.quantity_kg).toLocaleString()} kgs</td>
            <td>KES ${Number(s.cost_per_kg).toLocaleString()}/kgs</td>
            <td><strong>KES ${totalCost.toLocaleString()}</strong></td>
            <td>${s.supplier_name}</td>
            <td><span class="badge-pill ${statusBadge}">${s.status.replace('_', ' ')}</span></td>
            <td>
                <div style="display:flex; gap: 8px;">
                    <button class="btn btn-trans btn-sm" onclick="editShipment(${s.id})">Edit</button>
                    <button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="deleteShipment(${s.id})">Delete</button>
                </div>
            </td>
        </tr>
        `;
    }).join('');
}

function renderSuppliers() {
    const tbody = document.getElementById('suppliers-body');
    if (!window.suppliers_list.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;">No suppliers added yet. Click "Add New Supplier" to get started.</td></tr>';
        return;
    }

    tbody.innerHTML = window.suppliers_list.map(s => `
        <tr>
            <td><strong>${s.name}</strong></td>
            <td>${s.contact_name || '-'}</td>
            <td>${s.phone || '-'}</td>
            <td>${s.email || '-'}</td>
            <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis;">${s.address || '-'}</td>
            <td>${s.lead_time_days} days</td>
            <td>
                <div style="display:flex; gap: 8px;">
                    <button class="btn btn-trans btn-sm" onclick="editSupplier(${s.id})">Edit</button>
                    <button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="deleteSupplier(${s.id})">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderAutoOrders(data) {
    const tbody = document.getElementById('auto-orders-body');
    if (!data || !data.length) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; color:#16a34a; padding: 30px; font-weight:600;">✓ All Good! Everything is well stocked. Nothing needs to be ordered right now.</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(o => `
        <tr style="background: rgba(239, 68, 68, 0.02);">
            <td><strong>${o.material_name}</strong></td>
            <td style="color:#dc2626; font-weight:600;">${Number(o.current_stock).toLocaleString()} kgs</td>
            <td>${Number(o.min_level).toLocaleString()} kgs</td>
            <td><strong>${o.supplier_name}</strong></td>
            <td>${o.lead_time_days} days delivery</td>
            <td><strong style="color:var(--admin-primary);">${Number(o.recommended_qty).toLocaleString()} kgs</strong></td>
            <td>KES ${Number(o.estimated_cost_per_kg).toLocaleString()}/kgs</td>
            <td><strong>KES ${Number(o.total_estimated_cost).toLocaleString()}</strong></td>
            <td>
                <button class="btn btn-primary btn-sm" onclick="draftAutoShipment(${o.raw_material_id}, ${o.supplier_id}, ${o.recommended_qty}, ${o.estimated_cost_per_kg})">
                    Place Order
                </button>
            </td>
        </tr>
    `).join('');
}

// Modal Handlers
function openShipmentModal() {
    document.getElementById('shipment-modal-title').textContent = 'Record a New Purchase';
    document.getElementById('shipment-form').reset();
    document.getElementById('shipment-id').value = '';
    document.getElementById('shipment-status').value = 'ordered';
    document.getElementById('shipment-modal').style.display = 'flex';
}

function closeShipmentModal() {
    document.getElementById('shipment-modal').style.display = 'none';
}

function draftAutoShipment(materialId, supplierId, qty, price) {
    openShipmentModal();
    document.getElementById('shipment-material').value = materialId;
    document.getElementById('shipment-supplier').value = supplierId;
    document.getElementById('shipment-qty').value = qty;
    document.getElementById('shipment-cost').value = price;
    
    // Set expected delivery date based on lead time
    const supplier = window.suppliers_list.find(s => s.id == supplierId);
    const leadDays = supplier ? parseInt(supplier.lead_time_days) : 5;
    const expDate = new Date();
    expDate.setDate(expDate.getDate() + leadDays);
    document.getElementById('shipment-date').value = expDate.toISOString().split('T')[0];
}

function editShipment(id) {
    const s = window.shipments_list.find(item => item.id == id);
    if (!s) return;

    document.getElementById('shipment-modal-title').textContent = 'Edit Purchase Record';
    document.getElementById('shipment-id').value = s.id;
    document.getElementById('shipment-supplier').value = s.supplier_id;
    document.getElementById('shipment-material').value = s.raw_material_id;
    document.getElementById('shipment-qty').value = s.quantity_kg;
    document.getElementById('shipment-cost').value = s.cost_per_kg;
    document.getElementById('shipment-date').value = s.expected_delivery_date;
    document.getElementById('shipment-status').value = s.status;
    document.getElementById('shipment-modal').style.display = 'flex';
}

async function deleteShipment(id) {
    if (!confirm('Are you sure you want to delete this shipment?')) return;
    try {
        const formData = new FormData();
        formData.append('id', id.toString());
        formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');

        const response = await fetch('/Backend/api/admin_incoming_stock.php?action=delete_incoming_shipment', {
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

// Supplier Modal Handlers
function openSupplierModal() {
    document.getElementById('supplier-modal-title').textContent = 'Add Supplier';
    document.getElementById('supplier-form').reset();
    document.getElementById('supplier-id').value = '';
    document.getElementById('supplier-modal').style.display = 'flex';
}

function closeSupplierModal() {
    document.getElementById('supplier-modal').style.display = 'none';
}

function editSupplier(id) {
    const s = window.suppliers_list.find(item => item.id == id);
    if (!s) return;

    document.getElementById('supplier-modal-title').textContent = 'Edit Supplier';
    document.getElementById('supplier-id').value = s.id;
    document.getElementById('supplier-name').value = s.name;
    document.getElementById('supplier-contact').value = s.contact_name;
    document.getElementById('supplier-phone').value = s.phone;
    document.getElementById('supplier-email').value = s.email;
    document.getElementById('supplier-lead').value = s.lead_time_days;
    document.getElementById('supplier-address').value = s.address;
    document.getElementById('supplier-modal').style.display = 'flex';
}

async function deleteSupplier(id) {
    if (!confirm('Are you sure you want to delete this supplier? All associated shipments will be deleted.')) return;
    try {
        const formData = new FormData();
        formData.append('id', id.toString());
        formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');

        const response = await fetch('/Backend/api/admin_incoming_stock.php?action=delete_supplier', {
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

// Form Submissions
document.getElementById('shipment-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');
    try {
        const response = await fetch('/Backend/api/admin_incoming_stock.php?action=save_incoming_shipment', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            closeShipmentModal();
            loadData();
        } else {
            alert(result.message);
        }
    } catch(e) { console.error(e); }
});

document.getElementById('supplier-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');
    try {
        const response = await fetch('/Backend/api/admin_incoming_stock.php?action=save_supplier', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            closeSupplierModal();
            loadData();
        } else {
            alert(result.message);
        }
    } catch(e) { console.error(e); }
});

document.addEventListener('DOMContentLoaded', () => {
    loadData();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
