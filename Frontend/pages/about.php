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
            <h2 style="font-size: clamp(1.9rem, 4vw, 2.9rem); margin-bottom: 1rem;">From a small family operation to a <span class="g-serif" style="color: var(--g-tan);">smart farming platform</span></h2>
            <p style="color: var(--g-muted); font-size: 1.05rem;">
                Wangari began in 2015 as a small family poultry operation. What started with just 500 birds taught us something important: the farms that thrive are the ones that keep good records. Today we build the Wangari system so every farm, from a backyard flock to a full agribusiness, can run its operations, inventory, sales and finances in one place.
            </p>
            <p style="color: var(--g-muted); font-size: 1.05rem;">
                Our platform is named in honour of Prof. Wangari Maathai, the first African woman to win the Nobel Peace Prize, who taught us that small actions by many people create great change, one record, one flock, one farm at a time.
            </p>
        </div>
        <div class="g-grid-2 g-reveal g-delay-1" style="gap: 1.2rem;">
            <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem; text-align: center;">
                <span class="g-dash-num" style="font-size: 1.9rem;">One Platform</span>
                <span class="g-dash-label" style="display: block; margin-top: 0.4rem;">Production to profit, connected</span>
            </div>
            <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem; text-align: center;">
                <span class="g-dash-num" style="font-size: 1.9rem;">M-Pesa Ready</span>
                <span class="g-dash-label" style="display: block; margin-top: 0.4rem;">Payments built for Kenya</span>
            </div>
            <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem; text-align: center;">
                <span class="g-dash-num" style="font-size: 1.9rem;">Your Data</span>
                <span class="g-dash-label" style="display: block; margin-top: 0.4rem;">Export anytime, no lock-in</span>
            </div>
            <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem; text-align: center;">
                <span class="g-dash-num" style="font-size: 1.9rem;">24/7 Support</span>
                <span class="g-dash-label" style="display: block; margin-top: 0.4rem;">Help & training included</span>
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
                    <span class="stat-counter" data-target="10" data-suffix="k+">0</span>
                </div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.6); font-weight: 500;">Farms on Wangari</div>
            </div>
            <div class="g-reveal g-delay-2">
                <div style="font-size: 3.2rem; font-weight: 600; color: var(--g-lime); margin-bottom: 0.5rem;">
                    <span class="stat-counter" data-target="5" data-suffix="k+">0</span>
                </div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.6); font-weight: 500;">Satisfied Users</div>
            </div>
            <div class="g-reveal g-delay-3">
                <div style="font-size: 3.2rem; font-weight: 600; color: var(--g-lime); margin-bottom: 0.5rem;">
                    <span class="stat-counter" data-target="10" data-suffix="+">0</span>
                </div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.6); font-weight: 500;">Years in Business</div>
            </div>
            <div class="g-reveal g-delay-4">
                <div style="font-size: 3.2rem; font-weight: 600; color: var(--g-lime); margin-bottom: 0.5rem;">
                    <span class="stat-counter" data-target="100" data-suffix="%">0</span>
                </div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.6); font-weight: 500;">Data Ownership Guarantee</div>
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
            <span class="g-eyebrow">Leadership Team</span>
            <h2>The people behind <span class="g-serif">Wangari</span></h2>
            <p>The dedicated professionals driving our mission forward.</p>
        </div>
        <div class="g-grid-3">
            <div class="g-card g-reveal g-delay-1" style="text-align: center; padding: 2.5rem 1.7rem;">
                <div style="width: 84px; height: 84px; margin: 0 auto 1.2rem; border-radius: 50%; background: var(--g-ink); color: var(--g-lime); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700;">S</div>
                <h3>Samuel Kiplagat</h3>
                <p style="color: var(--g-tan); font-weight: 600; margin-bottom: 0.8rem;">Founder &amp; Product Director</p>
                <p style="color: var(--g-muted); font-size: 0.9rem;">20+ years in poultry and agribusiness. Leads product vision and works with farms daily.</p>
            </div>
            <div class="g-card g-reveal g-delay-2" style="text-align: center; padding: 2.5rem 1.7rem;">
                <div style="width: 84px; height: 84px; margin: 0 auto 1.2rem; border-radius: 50%; background: var(--g-ink); color: var(--g-lime); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700;">G</div>
                <h3>Grace Wanjiru</h3>
                <p style="color: var(--g-tan); font-weight: 600; margin-bottom: 0.8rem;">Customer Success Lead</p>
                <p style="color: var(--g-muted); font-size: 0.9rem;">Onboarding, training and support for every farm that joins the platform.</p>
            </div>
            <div class="g-card g-reveal g-delay-3" style="text-align: center; padding: 2.5rem 1.7rem;">
                <div style="width: 84px; height: 84px; margin: 0 auto 1.2rem; border-radius: 50%; background: var(--g-ink); color: var(--g-lime); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700;">P</div>
                <h3>Peter Omondi</h3>
                <p style="color: var(--g-tan); font-weight: 600; margin-bottom: 0.8rem;">Head of Engineering</p>
                <p style="color: var(--g-muted); font-size: 0.9rem;">Builds and maintains the Wangari platform, modules, payments and data security.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="g-section g-section-cream" style="text-align: center;">
    <div class="g-container" style="max-width: 680px;">
        <h2 class="g-reveal" style="font-size: clamp(1.9rem, 4vw, 2.8rem); margin-bottom: 1rem;">Ready to Partner With <span class="g-serif" style="color: var(--g-tan);">Us?</span></h2>
        <p class="g-reveal g-delay-1" style="font-size: 1.1rem; color: var(--g-muted); margin-bottom: 2rem;">
            Whether you run a commercial farm, an agro-vet, a feed mill, or a family homestead, Wangari organises your records, tracks your costs and grows your business.
        </p>
        <div class="g-reveal g-delay-2" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="contact.php" class="g-btn g-btn-lime">Contact Us</a>
            <a href="shop.php" class="g-btn g-btn-outline-dark">Shop Now</a>
        </div>
    </div>
</section>

<?php
include '../includes/footer.php';
?>
