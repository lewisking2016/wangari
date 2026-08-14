<?php
/**
 * Contact Us, Wangari
 * Growvi design language.
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Contact Us - Wangari';

include '../includes/header.php';

// Handle form submission
$form_submitted = false;
$form_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $phone && $subject && $message) {
        $form_submitted = true;
        $form_message = "Thank you for reaching out! We'll get back to you within 24 hours.";
    } else {
        $form_message = "Please fill in all fields.";
    }
}
?>

<main class="g-main">

    <section class="g-page-hero">
        <div class="g-container">
            <h1>Get In <span class="g-serif">Touch</span></h1>
            <p>Have questions about our products or want to discuss a bulk order? We're here to help.</p>
        </div>
    </section>

    <section class="g-section">
        <div class="g-container g-stack-mobile" style="display: grid; grid-template-columns: 0.9fr 1.1fr; gap: 3rem; align-items: start;">

            <!-- Contact information -->
            <div>
                <div class="g-section-head" style="margin-bottom: 2rem;">
                    <span class="g-eyebrow">Contact Info</span>
                    <h2 style="font-size: 1.9rem;">Reach us <span class="g-serif">anytime</span></h2>
                </div>

                <div style="display: flex; gap: 1.1rem; margin-bottom: 1.6rem;">
                    <div style="width: 52px; height: 52px; background: var(--g-lime-soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--g-ink); flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.2rem;">Phone</h4>
                        <p style="margin-bottom: 0.2rem;"><a href="tel:+254727585599" style="color: var(--g-ink); font-weight: 600; font-size: 1.05rem;">+254 727 585 599</a></p>
                        <p style="color: var(--g-muted); font-size: 0.9rem; margin: 0;">Mon - Fri, 8:00 AM - 6:00 PM EAT</p>
                    </div>
                </div>

                <div style="display: flex; gap: 1.1rem; margin-bottom: 1.6rem;">
                    <div style="width: 52px; height: 52px; background: var(--g-lime-soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--g-ink); flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.2rem;">Email</h4>
                        <p style="margin-bottom: 0.2rem;"><a href="mailto:info@wangari.farm" style="color: var(--g-ink); font-weight: 600; font-size: 1.05rem;">info@wangari.farm</a></p>
                        <p style="color: var(--g-muted); font-size: 0.9rem; margin: 0;">We aim to respond within 24 hours</p>
                    </div>
                </div>

                <div style="display: flex; gap: 1.1rem; margin-bottom: 1.6rem;">
                    <div style="width: 52px; height: 52px; background: var(--g-lime-soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--g-ink); flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.2rem;">Location</h4>
                        <p style="color: var(--g-text); font-weight: 600; margin-bottom: 0.2rem;">Busibwabo, Bungoma County</p>
                        <p style="color: var(--g-muted); font-size: 0.9rem; margin: 0;">Bungoma, Kenya</p>
                    </div>
                </div>

                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 1rem;">Follow Us</h4>
                    <div style="display: flex; gap: 0.8rem;">
                        <a href="#" aria-label="Facebook" style="width: 42px; height: 42px; border: 1px solid var(--g-line); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--g-muted); transition: all 0.2s;">FB</a>
                        <a href="#" aria-label="X" style="width: 42px; height: 42px; border: 1px solid var(--g-line); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--g-muted); transition: all 0.2s;">X</a>
                        <a href="#" aria-label="Instagram" style="width: 42px; height: 42px; border: 1px solid var(--g-line); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--g-muted); transition: all 0.2s;">IG</a>
                    </div>
                </div>
            </div>

            <!-- Contact form -->
            <div>
                <div class="g-form-card">
                    <h3 style="font-size: 1.3rem; margin-bottom: 1.6rem;">Send us a message</h3>

                    <?php if ($form_submitted): ?>
                        <div style="padding: 1rem 1.2rem; background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; margin-bottom: 1.2rem; border-radius: var(--g-radius-sm);">
                            <strong>Success!</strong> <?php echo $form_message; ?>
                        </div>
                    <?php elseif ($form_message): ?>
                        <div style="padding: 1rem 1.2rem; background-color: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; margin-bottom: 1.2rem; border-radius: var(--g-radius-sm);">
                            <strong>Error:</strong> <?php echo $form_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="g-form">
                        <div class="g-field">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="g-field">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="g-field">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                        </div>

                        <div class="g-field">
                            <label for="subject">Subject *</label>
                            <select id="subject" name="subject" required>
                                <?php
                                require_once __DIR__ . '/../../Backend/api/dropdowns.php';
                                echo renderDropdownOptions('contact_subjects', null, 'Select a subject');
                                ?>
                            </select>
                        </div>

                        <div class="g-field">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>

                        <button type="submit" name="contact_submit" value="1" class="g-btn g-btn-lime" style="width: 100%;">
                            Send Message →
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="g-section g-section-cream" style="padding-top: 2rem;">
        <div class="g-container">
            <div class="g-section-head center">
                <span class="g-eyebrow">Find Us</span>
                <h2>Visit our <span class="g-serif">farm</span></h2>
                <p>Open for scheduled visits and pickups.</p>
            </div>
            <div style="border-radius: var(--g-radius); overflow: hidden; height: 420px; border: 1px solid var(--g-line);">
                <iframe
                    width="100%"
                    height="100%"
                    frameborder="0"
                    style="border:0"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3981.5234567890!2d34.1234567!3d0.4567890!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sWangari%20Farm!5e0!3m2!1sen!2ske!4v1234567890"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

</main>

<?php
include '../includes/footer.php';
?>
