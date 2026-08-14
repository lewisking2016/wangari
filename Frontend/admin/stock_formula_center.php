<?php
/**
 * Consolidated Module: Formula & Production Center
 * Combines recipe configuration with automated production planning and bottleneck analysis.
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager', 'stock_manager'], true)) {
    echo "<script>window.location.href = '/wangariadmin';</script>";
    exit;
}

$path_prefix = '../../';
$page_title = 'Formula & Production Center';
include __DIR__ . '/includes/admin_header.php';
?>

<link rel="stylesheet" href="/Frontend/assets/css/admin-stock.css">
<style>
    .formula-card {
        transition: transform 0.2s;
        border-left: 4px solid transparent;
    }
    .formula-card:hover {
        transform: translateY(-4px);
    }
    .formula-card.bottleneck { border-left-color: #ef4444; }
    .formula-card.healthy { border-left-color: #22c55e; }

    /* Improved Live Tip Section Styles */
    .tip-container {
        background: #f8fafc;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        position: relative;
        overflow: hidden;
    }
    .tip-container::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--admin-primary);
        opacity: 0.5;
    }
    .tip-container.critical::before { background: #ef4444; opacity: 1; }
    .tip-container.optimization::before { background: #f59e0b; opacity: 1; }
    .tip-container.balanced::before { background: #22c55e; opacity: 1; }

    .tip-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .tip-header i { width: 14px; height: 14px; }
    .tip-content {
        font-size: 0.9rem;
        line-height: 1.5;
        color: #334155;
    }
    .tip-content strong { color: #0f172a; font-weight: 700; }

    .stock-stat-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 20px;
    }
    .stock-stat-item {
        padding: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .stock-stat-item:hover {
        border-color: var(--admin-primary);
        background: rgba(27, 94, 32, 0.02);
    }
    .stock-stat-label {
        display: block;
        color: #64748b;
        font-size: 0.7rem;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .stock-stat-value {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
    }
    .tab-btn.active {
        color: var(--admin-primary);
        border-bottom-color: var(--admin-primary);
        background: rgba(27, 94, 32, 0.05);
    }

    .formula-toolbar {
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 14px;
        flex-wrap: wrap;
    }

    .tab-group {
        display: inline-flex;
        gap: 10px;
        background: #f8fafc;
        border: 1px solid #dbe4ee;
        border-radius: 9999px;
        padding: 6px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }

    .tab-btn {
        appearance: none;
        border: 1px solid transparent;
        background: transparent;
        color: #475569;
        border-radius: 9999px;
        padding: 10px 16px;
        font-weight: 700;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        line-height: 1;
    }

    .tab-btn:hover {
        background: rgba(27, 94, 32, 0.08);
        color: var(--admin-primary);
        transform: translateY(-1px);
    }

    .tab-btn.active {
        background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-light) 100%);
        color: #ffffff;
        border-color: rgba(27, 94, 32, 0.2);
        box-shadow: 0 10px 20px rgba(27, 94, 32, 0.18);
    }

    .tab-btn:focus-visible,
    .toolbar-btn:focus-visible {
        outline: 3px solid rgba(27, 94, 32, 0.2);
        outline-offset: 2px;
    }

    .toolbar-btn {
        appearance: none;
        border: 1px solid rgba(27, 94, 32, 0.18);
        background: #ffffff;
        color: var(--admin-primary);
        border-radius: 12px;
        padding: 11px 16px;
        font-weight: 700;
        font-size: 0.92rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        transition: all 0.2s ease;
    }

    .toolbar-btn:hover {
        transform: translateY(-1px);
        border-color: rgba(27, 94, 32, 0.28);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        background: #f8fffa;
    }
</style>

<div class="admin-stock-wrapper">
    <div class="dashboard-hero-card">
        <h1>Formula & Production Center</h1>
        <p>Automated production planning integrated with live recipe management and product synchronization.</p>
    </div>

    <?php include __DIR__ . '/includes/stock_nav.php'; ?>

    <div class="formula-toolbar">
        <div class="tab-group" role="tablist" aria-label="Formula module sections">
            <button type="button" class="tab-btn active" onclick="switchTab('production', this)" aria-pressed="true">
                <i data-lucide="zap" style="width: 18px; height: 18px;"></i>
                Live Production Center
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('recipes', this)" aria-pressed="false">
                <i data-lucide="settings" style="width: 18px; height: 18px;"></i>
                Recipe Configurations
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('history', this); loadBatchHistory();" aria-pressed="false">
                <i data-lucide="history" style="width: 18px; height: 18px;"></i>
                Batch History
            </button>
        </div>
        <button type="button" class="toolbar-btn" onclick="syncStorePrices()">
            <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> Sync Store Prices
        </button>
    </div>

    <!-- Production Tab -->
    <div id="production-tab" class="tab-content">
        <div class="stock-grid" id="production-cards" style="grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));">
            <!-- Cards populated via JS -->
        </div>
    </div>

    <!-- Recipes Tab -->
    <div id="recipes-tab" class="tab-content" style="display: none;">
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h3 style="margin: 0;">Master Recipes</h3>
                    <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Define ingredients and link to store products.</p>
                </div>
                <button class="btn btn-primary btn-sm" onclick="openRecipeModal()">
                    <i data-lucide="plus"></i> Create New Formula
                </button>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Formula Name</th>
                            <th>Linked Product</th>
                            <th>Ingredients</th>
                            <th>Estimated COGS / Bag</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="recipes-body">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Batch History Tab -->
    <div id="history-tab" class="tab-content" style="display: none;">
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h3 style="margin: 0;">Production Batch History</h3>
                    <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Track cost-per-bag and formula cost creep over time.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Batch #</th>
                            <th>Formula</th>
                            <th>Bags Produced</th>
                            <th>Total Cost</th>
                            <th>Cost / Bag</th>
                            <th>Sell Price</th>
                            <th>Profit / Bag</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="batch-history-body">
                        <tr><td colspan="8" style="text-align:center;color:#64748b;padding:20px;">Click "Batch History" tab to load data.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Recipe Modal (Keep existing logic) -->
<div id="recipe-modal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #ffffff; padding: 32px; border-radius: 8px; width: 100%; max-width: 600px; box-shadow: var(--shadow-xl); max-height: 90vh; overflow-y: auto;">
        <h3 id="recipe-modal-title" style="margin-bottom: 24px;">Configure Formula</h3>
        <form id="recipe-form">
            <input type="hidden" name="id" id="recipe-id">
            <div class="admin-form-group">
                <label class="admin-form-label">Formula Name</label>
                <input type="text" name="name" id="recipe-name" class="admin-form-control" placeholder="e.g. Premium Layers Mash" required>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Store Product Link</label>
                <select name="product_id" id="recipe-product" class="admin-form-control" required>
                    <!-- Populated via JS -->
                </select>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Base Bag Size (KG)</label>
                <input type="number" name="base_bag_size_kg" id="recipe-bag-size" class="admin-form-control" value="70" step="0.1" required>
            </div>

            <div style="margin-top: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h4 style="margin: 0; font-size: 0.95rem;">Ingredients (KG per bag)</h4>
                    <button type="button" class="btn btn-trans btn-sm" onclick="addIngredientRow()">+ Add Material</button>
                </div>
                <div id="ingredients-container">
                    <!-- Rows added here -->
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn btn-trans" style="flex: 1;" onclick="closeRecipeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Configuration</button>
            </div>
        </form>
    </div>
</div>

<script>
let allRecipes = [];
let allRawMaterials = [];
let allProducts = [];

function switchTab(tab, trigger) {
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-pressed', 'false');
    });
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    
    if (trigger) {
        trigger.classList.add('active');
        trigger.setAttribute('aria-pressed', 'true');
    }
    document.getElementById(`${tab}-tab`).style.display = 'block';
}

async function loadData() {
    try {
        const response = await fetch('/Backend/api/admin_stock.php?action=get_dashboard');
        const result = await response.json();
        if (!result.success) return;

        allRecipes = result.data.recipes;
        allRawMaterials = result.data.raw_materials;
        allProducts = result.data.finished_products;

        renderProduction();
        renderRecipes();
        
        // Populate Product Dropdown
        document.getElementById('recipe-product').innerHTML = '<option value="">Select Store Product...</option>' + allProducts.map(p => 
            `<option value="${p.id}">${p.name} (Stock: ${p.stock_quantity})</option>`
        ).join('');

        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (err) { console.error(err); }
}

function renderProduction() {
    const container = document.getElementById('production-cards');
    container.innerHTML = allRecipes.map(r => {
        const isLow = r.auto_capacity_bags < 10;
        
        // Determine tip type and icon
        let tipClass = 'balanced';
        let tipIcon = 'check-circle';
        let tipTitle = 'Live Insight';

        if (r.tip.includes('Critical') || r.tip.includes('halted') || r.tip.includes('stalled')) {
            tipClass = 'critical';
            tipIcon = 'alert-triangle';
            tipTitle = 'Critical Alert';
        } else if (r.tip.includes('Optimization')) {
            tipClass = 'optimization';
            tipIcon = 'trending-up';
            tipTitle = 'Strategy Tip';
        }

        return `
            <div class="admin-card formula-card ${isLow ? 'bottleneck' : 'healthy'}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">${r.name}</h3>
                        <span style="font-size: 0.8rem; color: #64748b; font-weight: 500;">${r.base_bag_size_kg}kg Standard Feed</span>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.75rem; font-weight: 900; color: ${isLow ? '#ef4444' : 'var(--admin-primary)'}; line-height: 1;">
                            ${r.auto_capacity_bags}
                        </div>
                        <small style="text-transform: uppercase; font-weight: 700; color: #94a3b8; font-size: 0.6rem; letter-spacing: 0.025em;">Max Bags</small>
                    </div>
                </div>

                <div class="tip-container ${tipClass}">
                    <div class="tip-header" style="color: ${tipClass === 'critical' ? '#ef4444' : (tipClass === 'optimization' ? '#f59e0b' : '#22c55e')};">
                        <i data-lucide="${tipIcon}"></i>
                        <span>${tipTitle}</span>
                    </div>
                    <div class="tip-content">
                        ${r.tip.replace(/Critical: |Optimization Opportunity: |Balanced Production: |Production halted. |Production stalled. /g, '')}
                    </div>
                </div>

                <div class="stock-stat-grid">
                    <div class="stock-stat-item">
                        <span class="stock-stat-label">Cost / Bag</span>
                        <span class="stock-stat-value">KES ${Number(r.estimated_cogs).toLocaleString()}</span>
                        <span style="display:block; margin-top:4px; color:#64748b; font-size:0.75rem;">Based on base bag size</span>
                    </div>
                    <div class="stock-stat-item">
                        <span class="stock-stat-label">Retail Price</span>
                        <span class="stock-stat-value">KES ${Number(r.selling_price).toLocaleString()}</span>
                    </div>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-primary" style="flex: 2; height: 48px; font-weight: 700;" 
                        onclick="recordProduction(${r.id}, ${r.base_bag_size_kg}, ${r.auto_capacity_bags})"
                        ${r.auto_capacity_bags === 0 ? 'disabled' : ''}>
                        <i data-lucide="package-check"></i> Produce Max
                    </button>
                    <button class="btn btn-trans" style="flex: 1; height: 48px; font-weight: 600;" onclick="openCustomProduction(${r.id})">
                        Custom
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function renderRecipes() {
    document.getElementById('recipes-body').innerHTML = allRecipes.map(r => `
        <tr>
            <td>
                <strong>${r.name}</strong><br>
                <small style="color: #64748b;">${r.base_bag_size_kg}kg base</small>
            </td>
            <td>
                <span class="badge-pill badge-pill-success">
                    <i data-lucide="link" style="width: 10px; height: 10px; display: inline;"></i> ${r.name}
                </span>
            </td>
            <td>${r.ingredient_count} Materials</td>
            <td>
                <strong style="color: var(--admin-primary);">KES ${Number(r.estimated_cogs).toLocaleString()}</strong>
                <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">Per bag estimate</div>
            </td>
            <td>${new Date(r.updated_at).toLocaleDateString()}</td>
            <td>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-trans btn-sm" onclick="editRecipe(${r.id})">Edit</button>
                    <button class="btn btn-trans btn-sm" style="color: #dc2626;" onclick="deleteRecipe(${r.id})">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function syncStorePrices() {
    if (!confirm("This will automatically update all product prices in the shop based on their current production COGS plus a 25% profit margin. Proceed?")) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'sync_prices');
        formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');
        const response = await fetch('/Backend/api/admin_stock.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            loadData();
        } else {
            alert(result.message);
        }
    } catch (err) { console.error(err); }
}

async function recordProduction(recipeId, bagSize, quantity) {
    if (quantity <= 0) {
        alert("Insufficient stock to produce even a single bag.");
        return;
    }
    
    if (!confirm(`Confirm Production: This will deduct raw materials and add ${quantity} bags to your store inventory. Continue?`)) return;
    
    try {
        const formData = new FormData();
        formData.append('recipe_id', recipeId);
        formData.append('bag_size', bagSize);
        formData.append('quantity', quantity);
        formData.append('action', 'record_production');
        formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');
        
        const response = await fetch('/Backend/api/admin_stock.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            loadData(); // Real-time refresh
        } else {
            alert(result.message);
        }
    } catch (err) { console.error(err); }
}

function openCustomProduction(recipeId) {
    const qty = prompt("Enter the number of bags to produce:");
    if (qty && !isNaN(qty)) {
        const recipe = allRecipes.find(r => r.id == recipeId);
        if (parseInt(qty) > recipe.auto_capacity_bags) {
            alert(`Error: You only have enough raw materials for ${recipe.auto_capacity_bags} bags.`);
            return;
        }
        recordProduction(recipeId, recipe.base_bag_size_kg, parseInt(qty));
    }
}

// Reuse existing modal logic from recipes page
function openRecipeModal() {
    document.getElementById('recipe-modal-title').textContent = 'Create New Formula';
    document.getElementById('recipe-form').reset();
    document.getElementById('recipe-id').value = '';
    document.getElementById('ingredients-container').innerHTML = '';
    addIngredientRow();
    document.getElementById('recipe-modal').style.display = 'flex';
}

function closeRecipeModal() {
    document.getElementById('recipe-modal').style.display = 'none';
}

function addIngredientRow(rmId = '', amount = '') {
    const container = document.getElementById('ingredients-container');
    const row = document.createElement('div');
    row.className = 'ingredient-row';
    row.style = 'display: flex; gap: 8px; margin-bottom: 8px;';
    
    row.innerHTML = `
        <select class="admin-form-control ing-rm" style="flex: 2;" required>
            <option value="">Select Material...</option>
            ${allRawMaterials.map(rm => `<option value="${rm.id}" ${rm.id == rmId ? 'selected' : ''}>${rm.name}</option>`).join('')}
        </select>
        <input type="number" class="admin-form-control ing-amt" style="flex: 1;" placeholder="KG" step="0.001" value="${amount}" required>
        <button type="button" class="btn btn-trans" style="padding: 4px 8px; color: #dc2626;" onclick="this.parentElement.remove()"><i data-lucide="trash"></i></button>
    `;
    container.appendChild(row);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function editRecipe(id) {
    try {
        const response = await fetch(`/Backend/api/admin_stock.php?action=get_recipe_details&id=${id}`);
        const result = await response.json();
        if (!result.success) return;

        const { recipe, ingredients } = result.data;
        document.getElementById('recipe-modal-title').textContent = 'Edit Formula';
        document.getElementById('recipe-id').value = recipe.id;
        document.getElementById('recipe-name').value = recipe.name;
        document.getElementById('recipe-product').value = recipe.product_id;
        document.getElementById('recipe-bag-size').value = recipe.base_bag_size_kg;

        document.getElementById('ingredients-container').innerHTML = '';
        ingredients.forEach(ing => addIngredientRow(ing.raw_material_id, ing.amount_kg));
        
        document.getElementById('recipe-modal').style.display = 'flex';
    } catch (err) { console.error(err); }
}

async function deleteRecipe(id) {
    if (!confirm('Permanently delete this formula? This cannot be undone.')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete_recipe');
        formData.append('id', id);
        formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');
        const response = await fetch('/Backend/api/admin_stock.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) loadData();
    } catch (err) { console.error(err); }
}

document.getElementById('recipe-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    const ingredients = [];
    document.querySelectorAll('.ingredient-row').forEach(row => {
        const rmId = row.querySelector('.ing-rm').value;
        const amt = row.querySelector('.ing-amt').value;
        if (rmId && amt) {
            ingredients.push({ raw_material_id: rmId, amount_kg: amt });
        }
    });
    
    formData.append('ingredients', JSON.stringify(ingredients));
    formData.append('action', 'save_recipe');
    formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');

    try {
        const response = await fetch('/Backend/api/admin_stock.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            closeRecipeModal();
            loadData();
        } else {
            alert(result.message);
        }
    } catch (err) { console.error(err); }
});

// ==================== BATCH HISTORY ====================
async function loadBatchHistory() {
    const tbody = document.getElementById('batch-history-body');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#64748b;padding:20px;">Loading batch history...</td></tr>';
    
    try {
        const response = await fetch('/Backend/api/admin_stock.php?action=get_production_history&limit=50');
        const result = await response.json();
        if (!result.success || !result.data.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#64748b;padding:30px;">No production batches recorded yet. Use "Produce Max" or "Custom" on the Live Production Center tab to create your first batch.</td></tr>';
            return;
        }

        tbody.innerHTML = result.data.map(h => {
            const profitColor = h.profit_per_bag > 0 ? '#16a34a' : '#dc2626';
            const marginPct = h.current_selling_price > 0 && h.cost_per_bag > 0
                ? ((h.current_selling_price - h.cost_per_bag) / h.current_selling_price * 100).toFixed(1)
                : '0.0';
            return `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--admin-primary); font-size: 0.85rem;">${h.batch_number || '-'}</td>
                <td>
                    <strong>${h.recipe_name}</strong><br>
                    <small style="color: #64748b;">${h.product_name}</small>
                </td>
                <td style="font-weight: 600;">${h.quantity_bags}</td>
                <td>KES ${Number(h.total_cost).toLocaleString()}</td>
                <td style="font-weight: 700;">KES ${Number(h.cost_per_bag).toLocaleString()}</td>
                <td>KES ${Number(h.current_selling_price).toLocaleString()}</td>
                <td>
                    <span style="font-weight: 700; color: ${profitColor};">
                        KES ${Number(h.profit_per_bag).toLocaleString()}
                    </span>
                    <div style="font-size: 0.7rem; color: ${profitColor};">${marginPct}% margin</div>
                </td>
                <td style="color: #64748b; font-size: 0.85rem;">${new Date(h.produced_at).toLocaleDateString()}</td>
            </tr>`;
        }).join('');
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;padding:20px;">Failed to load batch history.</td></tr>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadData();
    // Auto-refresh every 30 seconds for a "Live" feel
    setInterval(loadData, 30000);
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
