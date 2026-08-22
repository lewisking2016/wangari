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
            <h2>The team behind <span class="g-serif">the platform</span></h2>
            <p>Wangari is improved around the workflows farm owners, managers and record keepers need every day.</p>
        </div>
        <div class="g-grid-3">
            <div class="g-card g-reveal g-delay-1" style="text-align: center; padding: 2.5rem 1.7rem;">
                <div style="width: 84px; height: 84px; margin: 0 auto 1.2rem; border-radius: 50%; background: var(--g-ink); color: var(--g-lime); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700;">S</div>
                <h3>Farm Operations</h3>
                <p style="color: var(--g-tan); font-weight: 600; margin-bottom: 0.8rem;">Built around daily records</p>
                <p style="color: var(--g-muted); font-size: 0.9rem;">The system connects production, health, inventory and sales information in one workflow.</p>
            </div>
            <div class="g-card g-reveal g-delay-2" style="text-align: center; padding: 2.5rem 1.7rem;">
                <div style="width: 84px; height: 84px; margin: 0 auto 1.2rem; border-radius: 50%; background: var(--g-ink); color: var(--g-lime); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700;">G</div>
                <h3>People &amp; Permissions</h3>
                <p style="color: var(--g-tan); font-weight: 600; margin-bottom: 0.8rem;">Every team member sees their work</p>
                <p style="color: var(--g-muted); font-size: 0.9rem;">Role-based access helps owners share work without giving away administrative control.</p>
            </div>
            <div class="g-card g-reveal g-delay-3" style="text-align: center; padding: 2.5rem 1.7rem;">
                <div style="width: 84px; height: 84px; margin: 0 auto 1.2rem; border-radius: 50%; background: var(--g-ink); color: var(--g-lime); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700;">P</div>
                <h3>Reports &amp; Decisions</h3>
                <p style="color: var(--g-tan); font-weight: 600; margin-bottom: 0.8rem;">From records to action</p>
                <p style="color: var(--g-muted); font-size: 0.9rem;">Dashboards, reports and exports help teams understand costs, stock, production and revenue.</p>
            </div>
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

<?php
include '../includes/footer.php';
?>
