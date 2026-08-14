<?php
/**
 * Recipes, Wangari
 * Growvi design language.
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Recipes - Wangari';

include '../includes/header.php';

$pdo = getDB();
$recipes = [];
if ($pdo) {
    try {
        $recipes = $pdo->query("SELECT * FROM recipes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        @error_log("Failed to fetch recipes from database: " . $e->getMessage());
    }
}

$db_error = empty($recipes) && !$pdo;
?>

<main class="g-main">

    <section class="g-page-hero">
        <div class="g-container">
            <h1>Delicious <span class="g-serif">Recipes</span></h1>
            <p>Culinary ideas and cooking tips for our farm-fresh eggs and poultry meats.</p>
        </div>
    </section>

    <section class="g-section">
        <div class="g-container">
            <?php if ($db_error): ?>
                <div style="text-align: center; padding: 4rem 1rem;">
                    <h3>Service Unavailable</h3>
                    <p style="color: var(--g-muted);">We couldn't load recipes right now. Please try again shortly.</p>
                </div>
            <?php elseif (empty($recipes)): ?>
                <div style="text-align: center; padding: 4rem 1rem;">
                    <h3>No Recipes Yet</h3>
                    <p style="color: var(--g-muted);">Our culinary collection is being prepared. Check back soon!</p>
                </div>
            <?php else: ?>
                <div class="g-products">
                    <?php foreach ($recipes as $r): ?>
                        <div class="g-product">
                            <div class="g-product-img">
                                <img src="<?php echo htmlspecialchars($r['image_url'] ?? '/Frontend/images/download (4).png'); ?>"
                                     alt="<?php echo htmlspecialchars($r['title']); ?>"
                                     loading="lazy"
                                     onerror="this.src='/Frontend/images/download (4).png'">
                            </div>
                            <div class="g-product-body">
                                <span style="font-size: 0.75rem; color: var(--g-muted); text-transform: uppercase; letter-spacing: 0.06em;">
                                    <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                </span>
                                <h3 style="margin-top: 0.5rem;"><?php echo htmlspecialchars($r['title']); ?></h3>
                                <p class="g-product-desc"><?php echo htmlspecialchars(mb_substr($r['content'], 0, 160)); ?>…</p>
                                <a href="/Frontend/pages/recipe-detail.php?id=<?php echo (int)$r['id']; ?>" class="g-product-btn" style="align-self: flex-start; text-decoration: none;">
                                    Read Full Recipe →
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php
include '../includes/footer.php';
?>
