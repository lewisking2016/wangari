<?php
/**
 * Homepage, Wangari
 * Sells the Wangari farm management system.
 * Growvi design language: image hero, numbered features, system modules,
 * process, testimonials, CTA form, FAQ, dashboard preview, blog.
 */
declare(strict_types=1);

$page_title = 'Wangari - All-in-One Farm Management System';
include 'includes/header.php';

$pdo = getDB();
$productCount = 0;
try {
    if ($pdo) {
        $productCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
    }
} catch (Exception $e) {
    $productCount = 0;
}
$productCount = max($productCount, 1);
?>

<main class="g-main">

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 1. HERO, image + white text + stats            -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-hero">
        <div class="g-hero-bg"><img src="/Frontend/images/farm-tractor.jpg" alt="" aria-hidden="true"></div>
        <div class="g-container">
            <span class="g-hero-tag">All-in-One Farm Management System</span>
            <h1 id="gHeroTitle">One System. Every Farm. <span class="g-serif">Smart Farming Technology</span></h1>
            <p class="g-hero-sub">Wangari runs the daily life of your farm, flocks, feed production, sales, and finances, in one simple platform that works on any device, in your language.</p>
            <div class="g-hero-actions">
                <a href="#wangari-preview" class="g-btn g-btn-lime">See the System Live</a>
                <a href="/Frontend/pages/contact.php" class="g-btn g-btn-outline">Book a Demo</a>
            </div>

            <div class="g-hero-stats">
                <div class="g-stat">
                    <span class="g-stat-num">12+</span>
                    <span class="g-stat-label">Integrated Modules</span>
                </div>
                <div class="g-stat">
                    <span class="g-stat-num">7</span>
                    <span class="g-stat-label">Hubs: Farm to Finance</span>
                </div>
                <div class="g-stat">
                    <span class="g-stat-num">100%</span>
                    <span class="g-stat-label">Your Data, Exportable</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 2. ABOUT THE SYSTEM, stats + strip             -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-section">
        <div class="g-container g-about-split">
            <div class="g-reveal">
                <span class="g-eyebrow" style="color: var(--g-tan); font-size: 0.8rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; display: block; margin-bottom: 0.9rem;">95% Client Satisfaction</span>
                <span class="g-eyebrow">The System</span>
                <h2 style="font-size: clamp(1.9rem, 4vw, 2.9rem); margin-bottom: 1rem;">A complete farm ERP, <span class="g-serif" style="color: var(--g-tan);">built for African agriculture</span></h2>
                <p style="color: var(--g-muted); font-size: 1.05rem;">Wangari replaces your spreadsheets, notebooks, and scattered tools with one connected platform: production, inventory, sales, credit, and finance working together in real time.</p>
                <a href="/Frontend/pages/services.php" class="g-btn g-btn-dark" style="margin-top: 0.5rem;">Explore the Features</a>
            </div>

            <div style="display: grid; gap: 1.5rem;">
                <div class="g-grid-2 g-reveal g-delay-1" style="gap: 1.2rem;">
                    <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem;">
                        <span class="g-dash-num">12+</span>
                        <span class="g-dash-label">Modules Included</span>
                    </div>
                    <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem;">
                        <span class="g-dash-num">M-Pesa</span>
                        <span class="g-dash-label">Payment Ready</span>
                    </div>
                    <div class="g-dash-tile" style="background: #fff; border: 1px solid var(--g-line); border-radius: var(--g-radius); padding: 1.5rem;">
                        <span class="g-dash-num">100%</span>
                        <span class="g-dash-label">Your Data, Yours</span>
                    </div>
                    <div class="g-dash-tile" style="background: var(--g-ink); border: 1px solid var(--g-ink); border-radius: var(--g-radius); padding: 1.5rem; color: #fff;">
                        <span class="g-dash-num" style="color: var(--g-lime);">24/7</span>
                        <span class="g-dash-label" style="color: rgba(255,255,255,0.6);">Support &amp; Training</span>
                    </div>
                </div>
                <p class="g-logo-strip g-reveal g-delay-2" style="font-size: 0.9rem; justify-content: flex-start;">Trusted by 200+ farms and agribusinesses!</p>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 3. CORE MODULES, numbered 01-04                -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-section g-section-cream">
        <div class="g-container">
            <div class="g-section-head center g-reveal">
                <span class="g-eyebrow">Core Modules</span>
                <h2>Everything your farm needs, <span class="g-serif">in one system</span></h2>
                <p>Seven connected hubs run the daily life of your farm, from the flock to the ledger, in your language, on any device.</p>
            </div>

            <div class="g-numbered">
                <div class="g-numbered-item g-reveal g-delay-1">
                    <span class="g-num">01</span>
                    <h3>Farm Operations</h3>
                    <p>Track flocks, herds, production, health, vaccinations, hatchery and breeding, captured daily in minutes.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">Poultry</span>
                        <span class="g-service-tag">Livestock</span>
                        <span class="g-service-tag">Hatchery</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-2">
                    <span class="g-num">02</span>
                    <h3>Inventory &amp; Production</h3>
                    <p>Raw materials, feed recipes, bag production, egg grading and low-stock alerts with live costing.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">Feed Formulas</span>
                        <span class="g-service-tag">Stock Alerts</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-3">
                    <span class="g-num">03</span>
                    <h3>Sales, Credit &amp; Finance</h3>
                    <p>Orders, cashbook, customer credit, LPOs, invoices and profit reports, M-Pesa payment ready.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">M-Pesa</span>
                        <span class="g-service-tag">Profit Reports</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-4">
                    <span class="g-num">04</span>
                    <h3>AI Assistant</h3>
                    <p>Ask anything about your farm in plain language and get instant answers from your own records.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">Smart Insights</span>
                        <span class="g-service-tag">Alerts</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 4. WHY WANGARI, 3 cards                        -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-section">
        <div class="g-container">
            <div class="g-section-head center g-reveal">
                <span class="g-eyebrow">Why Wangari</span>
                <h2>The Right Partner for<br><span class="g-serif">Your Growth</span></h2>
                <p>We combine experience, technology, and sustainability to help you grow better and smarter, from record-keeping to selling.</p>
            </div>

            <div class="g-grid-3">
                <div class="g-card g-reveal g-delay-1">
                    <div class="g-card-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    </div>
                    <h3>Everything in One Place</h3>
                    <p>No more juggling spreadsheets, notebooks and apps. Production, stock, sales and money, all connected automatically.</p>
                </div>
                <div class="g-card g-reveal g-delay-2">
                    <div class="g-card-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                    </div>
                    <h3>Made for African Farms</h3>
                    <p>Built around Kenyan reality, M-Pesa payments, credit customers, local feeds, and simple forms that work on cheap phones.</p>
                </div>
                <div class="g-card g-reveal g-delay-3">
                    <div class="g-card-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                    </div>
                    <h3>Your Data, Always Yours</h3>
                    <p>Export everything anytime, no lock-in, no hidden fees. Your farm records belong to you, in the spirit of the Green Belt Movement.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 5. SYSTEM FEATURES, module cards               -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-section g-section-cream">
        <div class="g-container">
            <div class="g-section-head center g-reveal">
                <span class="g-eyebrow">System Features</span>
                <h2>One platform, <span class="g-serif">every department</span></h2>
                <p>From the farm shed to the accountant's desk, Wangari connects every part of your agribusiness.</p>
            </div>

            <div class="g-projects">
                <div class="g-project g-reveal g-delay-1">
                    <div class="g-project-img g-project-img-1"><img src="/Frontend/images/farm-poultry.jpg" alt="Poultry production managed in Wangari"></div>
                    <div class="g-project-body">
                        <h3>Production &amp; Health</h3>
                        <p>Daily egg collection, growth records, mortality and vaccination schedules, with automatic alerts when something needs attention.</p>
                        <div class="g-project-loc">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 14 4-4 3 3 5-6"/></svg>
                            <span>Live dashboards &amp; reports</span>
                        </div>
                        <div class="g-project-tags">
                            <span class="g-project-tag">Egg Grading</span>
                            <span class="g-project-tag">Health Alerts</span>
                        </div>
                    </div>
                </div>

                <div class="g-project g-reveal g-delay-2">
                    <div class="g-project-img g-project-img-2"><img src="/Frontend/images/farm-crops.jpg" alt="Feed and stock control in Wangari"></div>
                    <div class="g-project-body">
                        <h3>Feed &amp; Stock Control</h3>
                        <p>Raw material stores, feed formulas, bag production and live costing, know exactly what each bag costs to produce.</p>
                        <div class="g-project-loc">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 14 4-4 3 3 5-6"/></svg>
                            <span>Real-time cost per bag</span>
                        </div>
                        <div class="g-project-tags">
                            <span class="g-project-tag">Feed Formulas</span>
                            <span class="g-project-tag">Low-Stock Alerts</span>
                        </div>
                    </div>
                </div>

                <div class="g-project g-reveal g-delay-3">
                    <div class="g-project-img g-project-img-3"><img src="/Frontend/images/farm-eggs.jpg" alt="Sales, credit and ledger in Wangari"></div>
                    <div class="g-project-body">
                        <h3>Sales, Credit &amp; Ledger</h3>
                        <p>Orders, cashbook, credit customers, LPOs, invoices and profit reports, with M-Pesa-ready payment records.</p>
                        <div class="g-project-loc">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 14 4-4 3 3 5-6"/></svg>
                            <span>P&L at a glance</span>
                        </div>
                        <div class="g-project-tags">
                            <span class="g-project-tag">M-Pesa</span>
                            <span class="g-project-tag">Credit &amp; LPO</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 6. HOW IT WORKS, 3 steps                       -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-section g-section-ink">
        <div class="g-container">
            <div class="g-section-head center g-reveal">
                <span class="g-eyebrow" style="color: var(--g-lime);">Get Started</span>
                <h2>Live in three <span class="g-serif" style="color: var(--g-lime);">simple steps</span></h2>
                <p>A streamlined approach designed to get your farm organised quickly, no technical skills needed.</p>
            </div>

            <div class="g-process">
                <div class="g-step g-reveal g-delay-1">
                    <span class="g-step-num">01</span>
                    <h3>Request the System</h3>
                    <p>Tell us about your farm. We recommend the modules you need and set your installation up on your laptop, server or the cloud.</p>
                </div>
                <div class="g-step g-reveal g-delay-2">
                    <span class="g-step-num">02</span>
                    <h3>We Configure It</h3>
                    <p>Your farm name, currency, staff roles and modules configured for you, and existing Excel records imported at no extra cost.</p>
                </div>
                <div class="g-step g-reveal g-delay-3">
                    <span class="g-step-num">03</span>
                    <h3>Train Your Team</h3>
                    <p>We train you and your staff, then the AI assistant answers questions from your own farm records as you grow.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 7. TESTIMONIALS, 4.9/5 rating cards            -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-section">
        <div class="g-container">
            <div class="g-section-head center g-reveal">
                <span class="g-eyebrow">Testimonial</span>
                <h2>What Our <span class="g-serif">Clients Say</span></h2>
                <p>Showcasing the results of our commitment to quality, innovation, and sustainable growth.</p>
            </div>

            <div class="g-testi-wrap">
                <div class="g-quote g-reveal g-delay-1">
                    <div class="g-quote-stars">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <p>"Wangari replaced our spreadsheets overnight. Production, feed costs, sales and credit, one place, always up to date. We finally know our real profit."</p>
                    <cite>
                        <span class="g-quote-avatar">N</span>
                        <span>Njeri W.<span class="g-quote-role"> · Poultry Farm Owner, Kiambu</span></span>
                    </cite>
                </div>

                <div class="g-quote g-reveal g-delay-2">
                    <div class="g-quote-stars">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <p>"The feed costing alone paid for itself. We know exactly what each bag costs to produce, and our profit reports are ready in one click."</p>
                    <cite>
                        <span class="g-quote-avatar">O</span>
                        <span>Otieno M.<span class="g-quote-role"> · Feed Mill Owner, Kisumu</span></span>
                    </cite>
                </div>

                <div class="g-quote g-reveal g-delay-3">
                    <div class="g-quote-stars">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <p>"I run my shop, my credit customers and my orders from my phone. The AI assistant answers questions from our own records, it just works."</p>
                    <cite>
                        <span class="g-quote-avatar">A</span>
                        <span>Amina K.<span class="g-quote-role"> · Agro-Vet Owner, Nakuru</span></span>
                    </cite>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 8. CTA, "Let's grow something better" + form   -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-cta">
        <div class="g-cta-bg"><img src="/Frontend/images/farm-dairy.jpg" alt="" aria-hidden="true"></div>
        <div class="g-container">
            <div class="g-cta-grid">
                <div class="g-reveal">
                    <h2>Let's grow something <span class="g-serif">better</span></h2>
                    <p>Tell us about your farm, and we'll show you how Wangari can organise it, free demo, no commitment.</p>
                    <div class="g-cta-contact">
                        <span>Questions? Call us at <strong>+254 727 585 599</strong></span>
                        <span>Prefer email? <strong>info@wangari.farm</strong></span>
                    </div>
                </div>

                <form class="g-form g-reveal g-delay-2" action="/Frontend/pages/contact.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(function_exists('generateCSRFToken') ? generateCSRFToken() : '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="g-field">
                        <label for="g-cta-name">Your Name<span>*</span></label>
                        <input type="text" id="g-cta-name" name="name" required placeholder="Jane Wanjiku">
                    </div>
                    <div class="g-field">
                        <label for="g-cta-phone">Phone Number<span>*</span></label>
                        <input type="tel" id="g-cta-phone" name="phone" required placeholder="+254 7XX XXX XXX">
                    </div>
                    <div class="g-field">
                        <label for="g-cta-email">Email Address<span>*</span></label>
                        <input type="email" id="g-cta-email" name="email" required placeholder="you@farm.co.ke">
                    </div>
                    <div class="g-field">
                        <label for="g-cta-service">I'm interested in</label>
                        <select id="g-cta-service" name="service">
                            <option value="">Select…</option>
                            <option value="Poultry Farm Management">Poultry Farm Management</option>
                            <option value="Feed Production">Feed Production</option>
                            <option value="Livestock / Mixed Farming">Livestock / Mixed Farming</option>
                            <option value="Agro-Vet / Shop">Agro-Vet / Shop</option>
                        </select>
                    </div>
                    <div class="g-field">
                        <label for="g-cta-msg">Write Message</label>
                        <textarea id="g-cta-msg" name="message" placeholder="Tell us about your farm…"></textarea>
                    </div>
                    <button type="submit" class="g-btn g-btn-lime" style="width: 100%;">Request a Demo</button>
                </form>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 9. FAQ                                          -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-section">
        <div class="g-container">
            <div class="g-section-head center g-reveal">
                <span class="g-eyebrow">FAQ</span>
                <h2>Frequently Asked <span class="g-serif">Questions</span></h2>
                <p>Everything you need to know about the Wangari system.</p>
            </div>

            <div class="g-faq g-reveal g-delay-1">
                <div class="g-faq-item open">
                    <div class="g-faq-q">
                        <span class="g-faq-num">1.</span>
                        <span>What exactly is Wangari?</span>
                        <span class="g-plus">+</span>
                    </div>
                    <div class="g-faq-a">Wangari is an all-in-one farm management system, a mix of ERP, CRM and operations software. It handles production, inventory, feed, sales, credit, and finance for farms and agribusinesses of every size.</div>
                </div>
                <div class="g-faq-item">
                    <div class="g-faq-q">
                        <span class="g-faq-num">2.</span>
                        <span>Do you support small-scale farmers?</span>
                        <span class="g-plus">+</span>
                    </div>
                    <div class="g-faq-a">Absolutely. Wangari is built for farms of every size, from a backyard flock to a full commercial operation. You can run it on your own laptop, on your server, or in the cloud, and the price scales with the size of your farm.</div>
                </div>
                <div class="g-faq-item">
                    <div class="g-faq-q">
                        <span class="g-faq-num">3.</span>
                        <span>Is my data safe, and who owns it?</span>
                        <span class="g-plus">+</span>
                    </div>
                    <div class="g-faq-a">You own your data, always. Records are encrypted, access is role-based, and you can export or delete everything at any time. No lock-in, no hidden fees.</div>
                </div>
                <div class="g-faq-item">
                    <div class="g-faq-q">
                        <span class="g-faq-num">4.</span>
                        <span>Does it work on my phone?</span>
                        <span class="g-plus">+</span>
                    </div>
                    <div class="g-faq-a">Yes. Wangari is a web app that works on any device, smartphone, tablet or computer. Capture production in the shed and check reports from home.</div>
                </div>
                <div class="g-faq-item">
                    <div class="g-faq-q">
                        <span class="g-faq-num">5.</span>
                        <span>How do I get started, and is there support?</span>
                        <span class="g-plus">+</span>
                    </div>
                    <div class="g-faq-a">Choose how you run it: installed on your own laptop or server for full privacy, or hosted in the cloud so you can access it from anywhere. Every installation includes our support team, training, and the AI assistant that answers questions from your own farm records.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 10. DASHBOARD PREVIEW, what the system looks   -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-section g-section-cream" id="wangari-preview">
        <div class="g-container">
            <div class="g-section-head center g-reveal">
                <span class="g-eyebrow">Inside Wangari</span>
                <h2>Your whole farm, <span class="g-serif">on one screen</span></h2>
                <p>A live dashboard that tells you what needs attention today, production, stock, sales and cash in real time.</p>
            </div>

            <div class="g-dash-frame g-reveal g-delay-1">
                <div class="g-dash-frame-bar">
                    <i></i><i></i><i></i>
                    <span>Wangari Farm OS — Live Dashboard</span>
                </div>
                <img src="/Frontend/images/dashboard-preview.png" alt="Wangari dashboard screenshot showing revenue, orders, stock alerts and the AI assistant">
            </div>

            <div style="text-align: center; margin-top: 2.5rem;" class="g-reveal g-delay-2">
                <a href="/Frontend/pages/contact.php" class="g-btn g-btn-dark">Book a Free Demo</a>
                <a href="/Frontend/pages/pricing.php" style="display: inline-block; margin-left: 1rem; color: var(--g-tan); font-weight: 600; font-size: 0.9rem;">Installation &amp; Pricing →</a>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════ -->
    <!-- 11. BLOGS, Smart Farming Insights              -->
    <!-- ═══════════════════════════════════════════════ -->
    <section class="g-section">
        <div class="g-container">
            <div class="g-section-head center g-reveal">
                <span class="g-eyebrow">Blogs</span>
                <h2>Smart Farming <span class="g-serif">Insights</span></h2>
                <p>Stay updated with expert tips, industry trends, and practical guides for running a profitable farm.</p>
            </div>

            <div class="g-blogs">
                <article class="g-blog g-reveal g-delay-1">
                    <div class="g-blog-img g-blog-img-1"><img src="/Frontend/images/farm-dairy.jpg" alt="Feed costing for dairy and livestock"></div>
                    <div class="g-blog-body">
                        <div class="g-blog-meta"><span>By</span><strong>Esther Howerd</strong></div>
                        <h3>Feed Costing 101: Know the Real Cost of Every Bag</h3>
                        <p>How to calculate feed cost per bag and why it changes your profit picture.</p>
                        <span class="g-blog-date">Mar 15, 2026</span>
                    </div>
                </article>

                <article class="g-blog g-reveal g-delay-2">
                    <div class="g-blog-img g-blog-img-2"><img src="/Frontend/images/farm-livestock.jpg" alt="Managing credit customers for livestock sales"></div>
                    <div class="g-blog-body">
                        <div class="g-blog-meta"><span>By</span><strong>Esther Howerd</strong></div>
                        <h3>Managing Credit Customers Without Losing Sleep</h3>
                        <p>Simple practices to track credit sales, avoid bad debt, and keep customers happy.</p>
                        <span class="g-blog-date">Mar 18, 2026</span>
                    </div>
                </article>

                <article class="g-blog g-reveal g-delay-3">
                    <div class="g-blog-img g-blog-img-3"><img src="/Frontend/images/farm-eggs.jpg" alt="Record-keeping for your farm's best investment"></div>
                    <div class="g-blog-body">
                        <div class="g-blog-meta"><span>By</span><strong>Esther Howerd</strong></div>
                        <h3>Why Record-Keeping Is Your Farm's Best Investment</h3>
                        <p>Records turn guesses into decisions, here's what to track and why it pays off.</p>
                        <span class="g-blog-date">Apr 10, 2026</span>
                    </div>
                </article>
            </div>
        </div>
    </section>

</main>

<?php
include 'includes/footer.php';
?>
