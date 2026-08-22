<?php
/**
 * Admin — Feed Production Module
 * Recipes + ingredient composition + batch production
 * Mirrors feed production logic from STORES TRACKING
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Feed Production - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$tab = $_GET['tab'] ?? 'recipes';
$validTabs = ['recipes','produce','history'];
if (!in_array($tab, $validTabs, true)) $tab = 'recipes';

$recipes = [];
if ($pdo) {
    $recipes = safeQueryAll($pdo, "SELECT * FROM feed_recipes ORDER BY recipe_name");
}
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Feed Production</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Define feed recipes (ingredients per bag), produce batches, track cost per kg. Stock is auto-deducted on production.</p>
    </div>
</div>

<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;">
    <a href="?tab=recipes" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='recipes'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="book-open" style="width:15px;height:15px;"></i> Recipes</a>
    <a href="?tab=produce" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='produce'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="flask-conical" style="width:15px;height:15px;"></i> Produce</a>
    <a href="?tab=history" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='history'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="history" style="width:15px;height:15px;"></i> History</a>
</div>

<?php if ($tab === 'recipes'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Feed Recipes</h3>
        <button class="btn btn-primary" onclick="openRecipeModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> New Recipe</button>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Recipe</th><th>Target</th><th>Bag Size (kg)</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($recipes)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No recipes yet.</td></tr>
            <?php else: foreach ($recipes as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['recipe_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($r['target_species'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= (float)$r['base_bag_size_kg'] ?> kg</td>
                    <td><?= htmlspecialchars($r['description'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill <?= $r['is_active']?'badge-pill-success':'badge-pill-info' ?>"><?= $r['is_active']?'Active':'Inactive' ?></span></td>
                    <td><button class="btn btn-trans btn-sm" onclick='openRecipeModal(<?= json_encode($r) ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'produce'): ?>
<div class="admin-card">
    <h3 style="margin:0 0 18px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Produce Feed Batch</h3>
    <form id="produce-form" style="max-width:520px;">
        <div class="admin-form-group">
            <label class="admin-form-label">Recipe *</label>
            <select class="admin-form-control" id="pr-recipe" required onchange="loadRecipeIngredients()">
                <option value="">Choose recipe...</option>
                <?php foreach ($recipes as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" data-bag="<?= (float)$r['base_bag_size_kg'] ?>"><?= htmlspecialchars($r['recipe_name'], ENT_QUOTES, 'UTF-8') ?> (<?= (float)$r['base_bag_size_kg'] ?> kg/bag)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-form-group">
            <label class="admin-form-label">Number of Bags *</label>
            <input class="admin-form-control" type="number" id="pr-bags" min="1" value="10" required oninput="recalcProduction()">
        </div>

        <div id="pr-ingredients" style="margin:20px 0;"></div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;padding:14px;background:#f8fafc;border-radius:8px;margin-top:14px;">
            <div><small style="color:#64748b;">Total kg</small><strong id="pr-total-kg" style="display:block;font-size:1.2rem;">0 kg</strong></div>
            <div><small style="color:#64748b;">Total cost</small><strong id="pr-total-cost" style="display:block;font-size:1.2rem;">KES 0</strong></div>
            <div><small style="color:#64748b;">Cost per kg</small><strong id="pr-cost-kg" style="display:block;font-size:1.2rem;color:var(--admin-primary);">KES 0</strong></div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:18px;width:100%;"><i data-lucide="play" style="width:15px;height:15px;"></i> Produce Now</button>
    </form>
</div>

<?php else: ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Production History</h3>
        <a href="/Backend/api/export.php?module=feed_production" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Recipe</th><th>Bags</th><th>Bag Size</th><th>Total kg</th><th>Total Cost</th><th>Cost/kg</th></tr></thead>
            <tbody id="history-body">
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Recipe Modal -->
<div id="recipe-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:680px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 id="recipe-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">New Recipe</h3>
        <form id="recipe-form">
            <input type="hidden" id="r-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Recipe Name *</label><input class="admin-form-control" id="rc-name" required placeholder="e.g. Grower Feed Standard"></div>
                <div class="admin-form-group"><label class="admin-form-label">Target Species</label>
                    <select class="admin-form-control" id="rc-target">
                        <option value="layers">Layers</option>
                        <option value="broilers">Broilers</option>
                        <option value="chicks">Chicks</option>
                        <option value="kienyeji">Kienyeji</option>
                        <option value="all">All</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Base Bag Size (kg)</label><input class="admin-form-control" type="number" step="0.01" id="rc-bag" value="70"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Description</label><textarea class="admin-form-control" id="rc-desc" rows="2"></textarea></div>
            </div>

            <h4 style="margin:20px 0 10px;font-family:'Outfit',sans-serif;font-size:0.95rem;">Ingredients (per bag)</h4>
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Material</th>
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">kg per bag</th>
                    <th style="padding:8px;"></th>
                </tr></thead>
                <tbody id="recipe-ingredients"></tbody>
            </table>
            <button type="button" class="btn btn-outline btn-sm" style="margin-top:10px;" onclick="addIngredientRow()"><i data-lucide="plus" style="width:13px;height:13px;"></i> Add Ingredient</button>

            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeRecipeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Recipe</button>
            </div>
        </form>
    </div>
</div>

<?php
$allMaterials = [];
if ($pdo) {
    $allMaterials = safeQueryAll($pdo, "SELECT id, material_name, current_stock, unit, current_price_per_unit FROM raw_materials WHERE category='feed_ingredient' ORDER BY material_name");
}
?>
<script>
let ingredients = <?= json_encode($allMaterials) ?>;
let ingIdx = 0;

function addIngredientRow(data = null) {
    const idx = ingIdx++;
    const tr = document.createElement('tr');
    tr.dataset.idx = idx;
    tr.style.borderBottom = '1px solid #e2e8f0';
    let opts = '<option value="">Choose material...</option>' + ingredients.map(m => `<option value="${m.id}" data-price="${m.current_price_per_unit}" data-stock="${m.current_stock}">${escapeHtml(m.material_name)} (${m.current_stock} ${m.unit})</option>`).join('');
    tr.innerHTML = `
        <td style="padding:6px;"><select class="admin-form-control" data-field="raw_material_id" style="font-size:0.85rem;padding:6px 8px;">${opts}</select></td>
        <td style="padding:6px;"><input class="admin-form-control" type="number" step="0.001" data-field="amount" value="${data?.amount_per_bag_kg||0}" style="font-size:0.85rem;padding:6px 8px;"></td>
        <td style="padding:6px;"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()" style="padding:4px 8px;"><i data-lucide="x" style="width:12px;height:12px;"></i></button></td>
    `;
    document.getElementById('recipe-ingredients').appendChild(tr);
    if (data) tr.querySelector('[data-field=raw_material_id]').value = data.raw_material_id;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function openRecipeModal(d) {
    document.getElementById('recipe-modal-title').textContent = d?.id ? 'Edit Recipe' : 'New Recipe';
    document.getElementById('r-id').value = d?.id || '';
    document.getElementById('rc-name').value = d?.recipe_name || '';
    document.getElementById('rc-target').value = d?.target_species || 'layers';
    document.getElementById('rc-bag').value = d?.base_bag_size_kg || 70;
    document.getElementById('rc-desc').value = d?.description || '';
    document.getElementById('recipe-ingredients').innerHTML = '';
    ingIdx = 0;
    if (d?.id) {
        fetch('/Backend/api/admin_poultry_v2.php?action=get_recipe_ingredients&recipe_id=' + d.id)
            .then(r => r.json())
            .then(r => { (r.data||[]).forEach(i => addIngredientRow(i)); });
    } else {
        addIngredientRow();
        addIngredientRow();
        addIngredientRow();
    }
    document.getElementById('recipe-modal').style.display = 'flex';
}
function closeRecipeModal() { document.getElementById('recipe-modal').style.display = 'none'; }

document.getElementById('recipe-form').addEventListener('submit', async e => {
    e.preventDefault();
    const ings = [];
    document.querySelectorAll('#recipe-ingredients tr').forEach(tr => {
        const mat = tr.querySelector('[data-field=raw_material_id]').value;
        const amt = tr.querySelector('[data-field=amount]').value;
        if (mat && amt > 0) ings.push({raw_material_id: mat, amount_per_bag_kg: amt});
    });
    if (!ings.length) { alert('Add at least one ingredient'); return; }
    const fd = new FormData();
    fd.append('id', document.getElementById('r-id').value);
    fd.append('recipe_name', document.getElementById('rc-name').value);
    fd.append('description', document.getElementById('rc-desc').value);
    fd.append('base_bag_size_kg', document.getElementById('rc-bag').value);
    fd.append('target_species', document.getElementById('rc-target').value);
    fd.append('ingredients', JSON.stringify(ings));
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_recipe', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Recipe saved'); closeRecipeModal(); location.reload(); }
    else alert('Error: ' + r.message);
});

async function loadRecipeIngredients() {
    const rid = document.getElementById('pr-recipe').value;
    const container = document.getElementById('pr-ingredients');
    if (!rid) { container.innerHTML = ''; recalcProduction(); return; }
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=get_recipe_ingredients&recipe_id=' + rid);
    const r = await res.json();
    if (!r.success || !r.data?.length) { container.innerHTML = '<p style="color:#94a3b8;font-size:0.85rem;">No ingredients defined for this recipe.</p>'; return; }
    let html = '<h4 style="margin:0 0 8px;font-family:Outfit;font-size:0.9rem;color:var(--admin-primary);">Required ingredients (per bag):</h4><div style="border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;">';
    r.data.forEach(i => {
        html += `<div style="display:flex;justify-content:space-between;padding:8px 12px;border-bottom:1px solid #f1f5f9;font-size:0.85rem;">
            <span>${escapeHtml(i.material_name)}</span>
            <span><strong>${parseFloat(i.amount_per_bag_kg)} kg</strong> <small style="color:#94a3b8;">(KES ${parseFloat(i.unit_price || 0).toFixed(2)}/unit)</small></span>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
    recalcProduction();
}

function recalcProduction() {
    const bags = parseInt(document.getElementById('pr-bags').value || 0);
    const totalKg = bags * parseFloat(document.getElementById('pr-recipe').selectedOptions[0]?.dataset.bag || 0);
    let totalCost = 0;
    document.querySelectorAll('#pr-ingredients > div > div').forEach(row => {
        const txt = row.querySelector('strong')?.textContent || '';
        const kg = parseFloat(txt) * bags;
        const priceTxt = row.querySelector('small')?.textContent || '';
        const price = parseFloat(priceTxt.replace(/[^\d.]/g, '') || 0);
        totalCost += kg * price;
    });
    document.getElementById('pr-total-kg').textContent = totalKg.toFixed(1) + ' kg';
    document.getElementById('pr-total-cost').textContent = 'KES ' + totalCost.toFixed(2);
    document.getElementById('pr-cost-kg').textContent = 'KES ' + (totalKg > 0 ? (totalCost/totalKg).toFixed(2) : '0');
}

document.getElementById('produce-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('recipe_id', document.getElementById('pr-recipe').value);
    fd.append('bags_produced', document.getElementById('pr-bags').value);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=produce_feed', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) {
        alert('Produced! Total: ' + r.total_kg + ' kg, Cost: KES ' + r.total_cost.toFixed(2) + ', Cost/kg: KES ' + r.cost_per_kg.toFixed(2));
        loadRecipeIngredients();
    } else alert('Error: ' + r.message);
});

async function loadHistory() {
    const tbody = document.getElementById('history-body');
    if (!tbody) return;
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=get_production_history');
    const r = await res.json();
    if (!r.success) return;
    if (!r.data.length) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No history yet.</td></tr>'; return; }
    tbody.innerHTML = r.data.map(p => `<tr>
        <td>${p.production_date}</td>
        <td><strong>${escapeHtml(p.recipe_name)}</strong></td>
        <td>${parseInt(p.bags_produced)}</td>
        <td>${parseFloat(p.bag_size_kg)} kg</td>
        <td>${parseFloat(p.total_kg).toFixed(1)} kg</td>
        <td>KES ${parseFloat(p.total_cost).toFixed(2)}</td>
        <td>KES ${parseFloat(p.cost_per_kg).toFixed(2)}</td>
    </tr>`).join('');
}

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => {
    if ('<?= $tab ?>' === 'history') loadHistory();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
