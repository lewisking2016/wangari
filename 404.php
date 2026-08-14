<?php
/**
 * Custom 404 Error Page
 */
declare(strict_types=1);

$path_prefix = '';
$page_title = '404 - Page Not Found | Wangari';

// Check if we are inside a subdirectory to resolve imports
if (file_exists('Frontend/includes/header.php')) {
    include 'Frontend/includes/header.php';
} else {
    include '../includes/header.php';
}
?>

<section style="padding: 120px 20px; min-height: 70vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; text-align: center;">
    <div style="max-width: 500px; padding: 40px; border-radius: var(--radius-lg); background: #ffffff; border: 1px solid rgba(0,0,0,0.06); box-shadow: var(--shadow-card);">
        
        <div style="font-size: 6rem; font-weight: 400; color: var(--primary); font-family: 'Instrument Serif', serif; line-height: 1; margin-bottom: 20px;">
            404
        </div>
        
        <h2 style="font-family: 'Inter Tight', sans-serif; font-weight: 800; margin-bottom: 12px; color: var(--dark);">This field is empty</h2>
        
        <p style="color: var(--gray-600); line-height: 1.6; font-size: 0.98rem; margin-bottom: 32px;">
            The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>

        <a href="/" class="btn btn-primary" style="display: inline-flex; justify-content: center; width: 100%; max-width: 240px; margin: 0 auto;">
            Go Back Home
        </a>

    </div>
</section>

<?php
if (file_exists('Frontend/includes/footer.php')) {
    include 'Frontend/includes/footer.php';
} else {
    include '../includes/footer.php';
}
?>
