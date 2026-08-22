<?php
/**
 * Global Footer, Wangari
 * Shared public footer used across the site.
 */
declare(strict_types=1);

if (!isset($path_prefix)) {
    $path_prefix = '';
}

$site_name = function_exists('getSetting') ? getSetting('farm_name', 'Wangari') : 'Wangari';
$site_email = function_exists('getSetting') ? getSetting('farm_email', 'info@wangari.farm') : 'info@wangari.farm';
$site_phone = function_exists('getSetting') ? getSetting('farm_phone', '+254 727 585 599') : '+254 727 585 599';
?>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- FOOTER, Growvi ink style                       -->
    <!-- ═══════════════════════════════════════════════ -->
    <footer class="g-footer">
        <div class="g-container">
            <div class="g-footer-top">
                <!-- Brand -->
                <div class="g-footer-brand">
                    <a href="/" class="g-logo">
                        <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
                        <span>Wangari<em>.</em></span>
                    </a>
                    <p>Farm management that keeps your records, sales, and team in one place.</p>
                    <p style="margin-top: 1rem;"><a href="https://imeantech.com" target="_blank" rel="noopener noreferrer" style="color: var(--g-lime); font-weight: 700;">Built and supported by iMeanTech.com</a></p>
                    <p style="margin-top: 1rem; color: rgba(255,255,255,0.8);">
                        <?php echo htmlspecialchars($site_phone, ENT_QUOTES, 'UTF-8'); ?><br>
                        <?php echo htmlspecialchars($site_email, ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>

                <!-- Menus -->
                <div>
                    <h4>Browse</h4>
                    <ul class="g-footer-links">
                        <li><a href="/Frontend/pages/about.php">About Us</a></li>
                        <li><a href="/Frontend/pages/pricing.php">Pricing</a></li>
                        <li><a href="/Frontend/pages/contact.php">Contact Us</a></li>
                        <li><a href="/Frontend/pages/services.php">Services</a></li>
                    </ul>
                </div>

                <!-- CMS Pages -->
                <div>
                    <h4>Useful Links</h4>
                    <ul class="g-footer-links">
                        <li><a href="/Frontend/pages/services.php">Services</a></li>
                        <li><a href="/Frontend/pages/faq.php">FAQ</a></li>
                        <li><a href="/Frontend/pages/login.php">System Login</a></li>
                    </ul>
                </div>

                <!-- Stay Up To Date -->
                <div class="g-subscribe">
                    <h4>Stay Informed</h4>
                    <p>Receive product updates, farming tips, and support news by email.</p>
                    <form class="g-subscribe-form" id="gSubscribeForm" action="<?php echo $path_prefix; ?>pages/newsletter.php" method="post">
                        <input type="email" name="email" placeholder="you@farm.co.ke" required aria-label="Email address">
                        <button type="submit" aria-label="Subscribe">Subscribe</button>
                    </form>
                </div>
            </div>

            <div class="g-footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>. All rights reserved.</p>
                <div style="display: flex; gap: 1.5rem;">
                    <a href="/Frontend/pages/privacy.php">Privacy Policy</a>
                    <a href="/Frontend/pages/terms.php">Terms of Service</a>
                    <a href="/Frontend/pages/contact.php">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Global Javascript Files -->
    <script src="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/vendor/gsap/gsap.min.js"></script>
    <script src="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/vendor/lucide/lucide.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Lucide icons (legacy pages)
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // GSAP hero + scroll reveals (kept safe, no ScrollTrigger plugin required)
            if (typeof gsap !== 'undefined') {
                const heroContent = document.querySelectorAll('.hero-content > *');
                if (heroContent.length > 0) {
                    gsap.fromTo(heroContent,
                        { opacity: 0, y: 30 },
                        { opacity: 1, y: 0, duration: 0.8, stagger: 0.1, ease: "power2.out" }
                    );
                }

                const revealObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            gsap.fromTo(entry.target,
                                { opacity: 0, y: 40 },
                                { opacity: 1, y: 0, duration: 0.8, ease: "power2.out" }
                            );
                            revealObserver.unobserve(entry.target);
                        }
                    });
                }, { rootMargin: '0px 0px -80px 0px', threshold: 0.1 });

                document.querySelectorAll('section:not(:first-of-type)').forEach(section => {
                    section.style.opacity = '0';
                    revealObserver.observe(section);
                });
            }
        });
    </script>

    <script src="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/js/main.js" defer></script>
    <script src="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/js/hero-slider.js" defer></script>
    <script src="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/js/professional-animations.js" defer></script>
    <script src="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/js/growvi-animations.js" defer></script>
</body>
</html>
