<?php
/**
 * Global Footer, Wangari
 * Matches the homepage xai-footer design exactly.
 */

declare(strict_types=1);

if (!isset($path_prefix)) {
    $path_prefix = '';
}
?>

<!-- ═══════════════════════════════════════════════ -->
<!-- FOOTER — Wangari Standardized                  -->
<!-- ═══════════════════════════════════════════════ -->
<footer class="xai-footer" style="background: #0D3320; padding: 80px 0 0; position: relative; overflow: hidden;">
    <div style="position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(34,197,94,0.3), transparent);"></div>
    <div style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 600px; height: 300px; background: radial-gradient(ellipse, rgba(34,197,94,0.06) 0%, transparent 70%); pointer-events: none;"></div>

    <div style="max-width: 1200px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 1;">
        <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1.2fr; gap: 48px; padding-bottom: 60px;">

            <!-- Brand & Description -->
            <div>
                <div style="display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 800; margin-bottom: 20px; color: #fff;">
                    <img src="/Frontend/images/wangari-logo.png" alt="Wangari" style="height: 40px; width: auto; border-radius: 10px;">
                    Wangari<span style="color: #22C55E;">.</span>
                </div>
                <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; line-height: 1.7; max-width: 320px; margin-bottom: 24px;">
                    Smart Farming for a Sustainable Future. All-in-one farm management system for poultry, livestock, crops, and finances - built for African agriculture.
                </p>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <a href="mailto:info@imeantech.com" style="display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: color 0.2s;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                        info@imeantech.com
                    </a>
                    <a href="tel:+254114971070" style="display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: color 0.2s;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/></svg>
                        +254 114 971 070
                    </a>
                    <div style="display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.6); font-size: 0.9rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                        Waris Mall, Ruiru, Kenya
                    </div>
                </div>
            </div>

            <!-- Product Links -->
            <div>
                <h4 style="font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,0.4); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.12em;">Product</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 14px;">
                    <li><a href="/Frontend/pages/about.php" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">About</a></li>
                    <li><a href="/Frontend/pages/services.php" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">Services</a></li>
                    <li><a href="/Frontend/pages/pricing.php" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">Pricing</a></li>
                    <li><a href="/Frontend/pages/contact.php" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">Contact</a></li>
                </ul>
            </div>

            <!-- Company Links -->
            <div>
                <h4 style="font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,0.4); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.12em;">Company</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 14px;">
                    <li><a href="https://imeantech.com" target="_blank" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">iMeanTech</a></li>
                    <li><a href="/Frontend/pages/about.php" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">About</a></li>
                    <li><a href="/Frontend/pages/contact.php" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">Contact</a></li>
                    <li><a href="/Frontend/pages/faq.php" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">FAQ</a></li>
                </ul>
            </div>

            <!-- Legal Links -->
            <div>
                <h4 style="font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,0.4); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.12em;">Legal</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 14px;">
                    <li><a href="/Frontend/pages/privacy.php" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">Privacy Policy</a></li>
                    <li><a href="/Frontend/pages/terms.php" style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-decoration: none; transition: all 0.2s;">Terms of Service</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div style="border-top: 1px solid rgba(255,255,255,0.06); padding: 24px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <p style="margin: 0; color: rgba(255,255,255,0.35); font-size: 0.8rem;">&copy; <?php echo date('Y'); ?> Wangari. All rights reserved.</p>
            <div style="display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.35); font-size: 0.8rem;">
                Built by <a href="https://imeantech.com" target="_blank" style="color: rgba(255,255,255,0.5); text-decoration: none; transition: color 0.2s;">iMeanTech</a>
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
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

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
