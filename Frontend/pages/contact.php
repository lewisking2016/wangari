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

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $phone && $subject && $message) {
        try {
            $db = function_exists('getDB') ? getDB() : null;
            if (!$db) {
                throw new RuntimeException('Contact storage is unavailable.');
            }

            $ticketCode = 'WEB-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $ticket = $db->prepare('INSERT INTO support_tickets (user_id, ticket_code, subject, category, priority, is_anonymous, reporter_name, reporter_email, reporter_phone, description) VALUES (NULL, ?, ?, ?, ?, 0, ?, ?, ?, ?)');
            $ticket->execute([$ticketCode, $subject, 'other', 'medium', $name, $email, $phone, $message]);
            $form_submitted = true;
            $form_message = "Your message was received. Reference: {$ticketCode}. Our team will follow up using the contact details you provided.";
        } catch (Throwable $e) {
            error_log('Public contact submission failed: ' . $e->getMessage());
            $form_message = 'We could not record your message right now. Please email info@imeantech.com or call us directly.';
        }
    } else {
        $form_message = "Please fill in all fields with a valid email address.";
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
                        <p style="margin-bottom: 0.2rem;"><a href="tel:+254114971070" style="color: var(--g-ink); font-weight: 600; font-size: 1.05rem;">+254 114 971 070</a></p>
                        <p style="color: var(--g-muted); font-size: 0.9rem; margin: 0;">Mon - Fri, 8:00 AM - 6:00 PM EAT</p>
                    </div>
                </div>

                <div style="display: flex; gap: 1.1rem; margin-bottom: 1.6rem;">
                    <div style="width: 52px; height: 52px; background: var(--g-lime-soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--g-ink); flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.2rem;">Email</h4>
                        <p style="margin-bottom: 0.2rem;"><a href="mailto:info@imeantech.com" style="color: var(--g-ink); font-weight: 600; font-size: 1.05rem;">info@imeantech.com</a></p>
                        <p style="color: var(--g-muted); font-size: 0.9rem; margin: 0;">We aim to respond within 24 hours</p>
                    </div>
                </div>

                <div style="display: flex; gap: 1.1rem; margin-bottom: 1.6rem;">
                    <div style="width: 52px; height: 52px; background: var(--g-lime-soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--g-ink); flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.2rem;">Location</h4>
                        <p style="color: var(--g-text); font-weight: 600; margin-bottom: 0.2rem;">Waris Mall, Ruiru</p>
                        <p style="color: var(--g-muted); font-size: 0.9rem; margin: 0;">Kiambu County, Kenya</p>
                    </div>
                </div>

                <div style="margin-top: 2rem;">
                    <h4 style="margin-bottom: 1rem;">Follow Us</h4>
                    <div style="display: flex; gap: 0.8rem;">
                        <a href="mailto:info@imeantech.com" aria-label="Email Wangari" style="width: 42px; height: 42px; border: 1px solid var(--g-line); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--g-muted); transition: all 0.2s;">@</a>
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
                <h2>Work with us <span class="g-serif">remotely or in person</span></h2>
                <p>Our team supports onboarding and scheduled farm visits by arrangement.</p>
            </div>
            <div style="border-radius: var(--g-radius); padding: 2.5rem; background: var(--g-ink); color: #fff; border: 1px solid var(--g-line);">
                <h3 style="color: #fff; margin-bottom: 0.8rem;">A clear next step</h3>
                <p style="color: rgba(255,255,255,0.72); max-width: 620px; margin-bottom: 1.2rem;">Tell us what you manage, how your team records it today, and which part of the system you want to test. We will use your message to guide the next conversation.</p>
                <a href="mailto:info@imeantech.com" class="g-btn g-btn-lime">Email the team</a>
            </div>
        </div>
    </section>

</main>

<style>
@media (max-width: 860px) {
    .g-container.g-stack-mobile {
        grid-template-columns: 1fr !important;
        gap: 2rem !important;
    }
    .g-form-card { padding: 20px; }
}
@media (max-width: 560px) {
    .g-form-card input, .g-form-card textarea, .g-form-card select {
        font-size: 16px !important;
    }
}
</style>
<?php
include '../includes/footer.php';
?>
