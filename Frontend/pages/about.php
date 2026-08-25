<?php
/**
 * About Us Page, Wangari (Growvi style)
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'About Us - Wangari';

include '../includes/header.php';
?>

<section class="g-page-hero">
    <div class="g-container">
        <h1>About <span class="g-serif">Wangari</span></h1>
        <p>The all-in-one farm management system for East African agriculture, production, inventory, sales, credit and finance in one platform, rooted in the spirit of Prof. Wangari Maathai.</p>
    </div>
</section>

<!-- Company Story -->
<section class="g-section">
    <div class="g-container g-stack-mobile" style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: clamp(2.5rem, 6vw, 5rem); align-items: center;">
        <div class="g-reveal">
            <span class="g-eyebrow">Our Story</span>
            <h2 style="font-size: clamp(1.9rem, 4vw, 2.9rem); margin-bottom: 1rem;">A practical platform for <span class="g-serif" style="color: var(--g-tan);">better farm decisions</span></h2>
            <p style="color: var(--g-muted); font-size: 1.05rem;">
                Wangari is a farm management platform for poultry, livestock, crops, feed production, sales and finance. It brings the records that teams usually keep in notebooks and spreadsheets into one workspace so owners can see what is happening and make decisions from current information.
            </p>
            <p style="color: var(--g-muted); font-size: 1.05rem;">
                The name reflects the values associated with Prof. Wangari Maathai: practical action, responsible stewardship and progress built consistently over time. The product is designed for real farm teams, with role-based access, exports and workflows that can grow with the business.
            </p>
        </div>
        <div class="g-grid-2 g-reveal g-delay-1" style="gap: 1.2rem;">
            <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem; text-align: center;">
                <span class="g-dash-num" style="font-size: 1.9rem;">One Platform</span>
                <span class="g-dash-label" style="display: block; margin-top: 0.4rem;">Production to profit, connected</span>
            </div>
            <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem; text-align: center;">
                <span class="g-dash-num" style="font-size: 1.9rem;">M-Pesa Records</span>
                <span class="g-dash-label" style="display: block; margin-top: 0.4rem;">Record and reconcile payments</span>
            </div>
            <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem; text-align: center;">
                <span class="g-dash-num" style="font-size: 1.9rem;">Your Data</span>
                <span class="g-dash-label" style="display: block; margin-top: 0.4rem;">Export anytime, no lock-in</span>
            </div>
            <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem; text-align: center;">
                <span class="g-dash-num" style="font-size: 1.9rem;">Support &amp; Training</span>
                <span class="g-dash-label" style="display: block; margin-top: 0.4rem;">Guidance when your team needs it</span>
            </div>
        </div>
    </div>
</section>

<!-- Key Statistics -->
<section class="g-section g-section-ink">
    <div class="g-container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2.5rem; text-align: center;">
            <div class="g-reveal g-delay-1">
                <div style="font-size: 3.2rem; font-weight: 600; color: var(--g-lime); margin-bottom: 0.5rem;">
                    <span class="stat-counter" data-target="7" data-suffix="">0</span>
                </div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.6); font-weight: 500;">Connected farm hubs</div>
            </div>
            <div class="g-reveal g-delay-2">
                <div style="font-size: 3.2rem; font-weight: 600; color: var(--g-lime); margin-bottom: 0.5rem;">
                    <span class="stat-counter" data-target="40" data-suffix="">0</span>
                </div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.6); font-weight: 500;">Trial days with full access</div>
            </div>
            <div class="g-reveal g-delay-3">
                <div style="font-size: 3.2rem; font-weight: 600; color: var(--g-lime); margin-bottom: 0.5rem;">
                    <span class="stat-counter" data-target="1" data-suffix="">0</span>
                </div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.6); font-weight: 500;">Workspace for each farm</div>
            </div>
            <div class="g-reveal g-delay-4">
                <div style="font-size: 3.2rem; font-weight: 600; color: var(--g-lime); margin-bottom: 0.5rem;">
                    <span class="stat-counter" data-target="100" data-suffix="%">0</span>
                </div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.6); font-weight: 500;">Your records remain exportable</div>
            </div>
        </div>
    </div>
</section>

<!-- Our Values -->
<section class="g-section g-section-cream">
    <div class="g-container">
        <div class="g-section-head center g-reveal">
            <span class="g-eyebrow">Our Core Values</span>
            <h2>The principles that guide <span class="g-serif">our work</span></h2>
            <p>From record-keeping to selling, these values shape everything we do.</p>
        </div>
        <div class="g-grid-3">
            <div class="g-card g-reveal g-delay-1">
                <div class="g-card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <h3>Quality First</h3>
                <p>Every product meets strict quality standards. We prioritize health, genetics, and product excellence above all else.</p>
            </div>
            <div class="g-card g-reveal g-delay-2">
                <div class="g-card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <h3>Sustainability</h3>
                <p>We practice responsible farming with proper waste management, animal welfare, and environmental stewardship, the Green Belt way.</p>
            </div>
            <div class="g-card g-reveal g-delay-3">
                <div class="g-card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <h3>Customer Focus</h3>
                <p>We're committed to excellent service, transparent communication, and building lasting relationships with our customers.</p>
            </div>
        </div>
    </div>
</section>

<!-- Leadership Team -->
<section class="g-section">
    <div class="g-container">
        <div class="g-section-head center g-reveal">
            <span class="g-eyebrow">How We Work</span>
            <h2>Built by <span class="g-serif">iMeanTech</span></h2>
            <p>Wangari is designed and maintained by iMeanTech — a technology company based in Nairobi, Kenya — around the workflows farm owners, managers and record keepers need every day.</p>
        </div>
        <!-- Swiper Slideshow -->
        <div style="position:relative;padding-bottom:60px;">
            <div class="swiper about-slideshow" style="overflow:hidden;">
                <div class="swiper-wrapper">
                    <!-- Slide 1: Farm Operations -->
                    <div class="swiper-slide">
                        <div style="background:var(--g-card,#fff);border:1px solid var(--g-border,#e2e8f0);border-radius:16px;padding:40px;min-height:300px;">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                                <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#22C55E22,#16A34A11);display:flex;align-items:center;justify-content:center;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                </div>
                                <div>
                                    <div style="font-size:0.75rem;font-weight:700;color:#22C55E;text-transform:uppercase;letter-spacing:0.08em;">Farm Operations</div>
                                    <div style="font-size:1.1rem;font-weight:700;">Daily Records & Health</div>
                                </div>
                            </div>
                            <p style="color:var(--g-muted,#64748b);font-size:0.95rem;line-height:1.7;margin:0 0 16px;">Record egg counts, mortality, weight gain and vaccinations in one place. No more scattered notebooks — every entry connects to your batch and updates your profit report automatically.</p>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Egg Tracking</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Mortality Log</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Vaccinations</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Weight Records</span>
                            </div>
                        </div>
                    </div>
                    <!-- Slide 2: Inventory -->
                    <div class="swiper-slide">
                        <div style="background:var(--g-card,#fff);border:1px solid var(--g-border,#e2e8f0);border-radius:16px;padding:40px;min-height:300px;">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                                <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#F59E0B22,#D9770611);display:flex;align-items:center;justify-content:center;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                </div>
                                <div>
                                    <div style="font-size:0.75rem;font-weight:700;color:#F59E0B;text-transform:uppercase;letter-spacing:0.08em;">Inventory & Store</div>
                                    <div style="font-size:1.1rem;font-weight:700;">Feed, Stock & Costs</div>
                                </div>
                            </div>
                            <p style="color:var(--g-muted,#64748b);font-size:0.95rem;line-height:1.7;margin:0 0 16px;">Know exactly how much feed, medicine and supplies you have left. Track every bag of layers mash and every bottle of vaccine — the system alerts you before you run out and shows what each batch is costing you.</p>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Feed Stock</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Low Alerts</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Cost Tracking</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Auto Reorder</span>
                            </div>
                        </div>
                    </div>
                    <!-- Slide 3: Sales & Credit -->
                    <div class="swiper-slide">
                        <div style="background:var(--g-card,#fff);border:1px solid var(--g-border,#e2e8f0);border-radius:16px;padding:40px;min-height:300px;">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                                <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#10B98122,#05966911);display:flex;align-items:center;justify-content:center;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </div>
                                <div>
                                    <div style="font-size:0.75rem;font-weight:700;color:#10B981;text-transform:uppercase;letter-spacing:0.08em;">Sales & Finance</div>
                                    <div style="font-size:1.1rem;font-weight:700;">Orders, Credit & M-Pesa</div>
                                </div>
                            </div>
                            <p style="color:var(--g-muted,#64748b);font-size:0.95rem;line-height:1.7;margin:0 0 16px;">Track who bought eggs today, who still owes you money, and how much you made this week. Every sale — cash, M-Pesa or credit — shows up in your profit report so you always know where your money is.</p>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">M-Pesa Sales</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Credit Tracking</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Customer Accounts</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Profit Reports</span>
                            </div>
                        </div>
                    </div>
                    <!-- Slide 4: Teams -->
                    <div class="swiper-slide">
                        <div style="background:var(--g-card,#fff);border:1px solid var(--g-border,#e2e8f0);border-radius:16px;padding:40px;min-height:300px;">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                                <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#6366F122,#4F46E511);display:flex;align-items:center;justify-content:center;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                                <div>
                                    <div style="font-size:0.75rem;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:0.08em;">Teams & Workers</div>
                                    <div style="font-size:1.1rem;font-weight:700;">Roles & Access Control</div>
                                </div>
                            </div>
                            <p style="color:var(--g-muted,#64748b);font-size:0.95rem;line-height:1.7;margin:0 0 16px;">Your farm worker records daily tasks, your manager handles sales, and you see everything. Each person gets the right access — nobody sees what they shouldn't, and you stay in control of your data.</p>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Worker Access</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Manager View</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Owner Dashboard</span>
                                <span style="background:var(--g-cream,#f0fdf4);color:var(--g-muted,#64748b);font-size:0.8rem;padding:4px 12px;border-radius:100px;">Secure Data</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Navigation arrows -->
            <div class="about-prev" style="position:absolute;left:-16px;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;background:var(--g-card,#fff);border:1px solid var(--g-border,#e2e8f0);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </div>
            <div class="about-next" style="position:absolute;right:-16px;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;background:var(--g-card,#fff);border:1px solid var(--g-border,#e2e8f0);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </div>
            <!-- Pagination dots -->
            <div class="about-pagination" style="position:absolute;bottom:0;left:50%;transform:translateX(-50%);display:flex;gap:8px;"></div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="g-section g-section-cream" style="text-align: center;">
    <div class="g-container" style="max-width: 680px;">
        <h2 class="g-reveal" style="font-size: clamp(1.9rem, 4vw, 2.8rem); margin-bottom: 1rem;">Digital tools by <span class="g-serif" style="color: var(--g-tan);">iMeanTech</span></h2>
        <p class="g-reveal g-delay-1" style="font-size: 1.1rem; color: var(--g-muted); margin-bottom: 2rem;">
            iMeanTech builds and supports the Wangari platform, combining software, data workflows and practical tools for modern farm teams.
        </p>
        <div class="g-reveal g-delay-2" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="https://imeantech.com" target="_blank" rel="noopener noreferrer" class="g-btn g-btn-lime">Visit iMeanTech.com</a>
        </div>
    </div>
</section>

<script>
function initAboutSlideshow() {
    var el = document.querySelector('.about-slideshow');
    if (!el || typeof Swiper === 'undefined') return;
    var s = new Swiper(el, {
        slidesPerView: 1,
        spaceBetween: 24,
        loop: true,
        autoplay: { delay: 5000, disableOnInteraction: false },
        pagination: { el: '.about-pagination', clickable: true },
        navigation: { prevEl: '.about-prev', nextEl: '.about-next' },
        breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }
    });
    // Style dots
    function styleDots() {
        document.querySelectorAll('.about-pagination .swiper-pagination-bullet').forEach(function(d) {
            d.style.width = '8px'; d.style.height = '8px'; d.style.background = 'var(--g-border,#cbd5e1)'; d.style.opacity = '1'; d.style.borderRadius = '50%'; d.style.transition = 'all 0.3s';
        });
        var a = document.querySelector('.about-pagination .swiper-pagination-bullet-active');
        if (a) { a.style.background = '#22C55E'; a.style.width = '24px'; a.style.borderRadius = '4px'; }
    }
    styleDots();
    s.on('slideChange', styleDots);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAboutSlideshow);
else initAboutSlideshow();
</script>

<?php
include '../includes/footer.php';
?>
