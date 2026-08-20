<?php
/**
 * Wangari Landing Page — x.ai/bot inspired design
 */
declare(strict_types=1);

$page_title = 'Wangari — Smart Farming for a Sustainable Future';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Frontend/assets/css/xai-public.css">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
</head>
<body>

<!-- Navigation -->
<nav class="xai-nav" id="mainNav">
    <div class="xai-nav-inner">
        <a href="/" class="xai-nav-brand">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
            Wangari<span>.</span>
        </a>
        <ul class="xai-nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#preview">System</a></li>
            <li><a href="#testimonials">Testimonials</a></li>
            <li><a href="/Frontend/pages/pricing.php">Pricing</a></li>
        </ul>
        <div class="xai-nav-actions">
            <a href="/Frontend/pages/login.php" class="xai-nav-ghost">Sign In</a>
            <a href="/Frontend/pages/register.php" class="xai-nav-cta">Get Started</a>
            <button class="xai-mobile-toggle" id="mobileMenuBtn" aria-label="Open menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Menu -->
<div id="mobileMenu" class="xai-mobile-menu">
    <button class="xai-mobile-close" id="mobileMenuClose" aria-label="Close menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <a href="#features">Features</a>
    <a href="#preview">System</a>
    <a href="#testimonials">Testimonials</a>
    <a href="/Frontend/pages/pricing.php">Pricing</a>
    <a href="/Frontend/pages/login.php">Sign In</a>
    <a href="/Frontend/pages/register.php" class="xai-btn xai-btn-primary" style="text-align: center; margin-top: 16px;">Get Started</a>
</div>

<main>
    <!-- Hero Section -->
    <section class="xai-hero">
        <div class="xai-hero-content xai-reveal">
            <div class="xai-hero-eyebrow xai-icon-animated">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Trusted by 200+ farms across Kenya
            </div>
            <h1>One System.<br>Every Farm.<br><span>Smart Farming Technology</span></h1>
            <p class="xai-hero-sub">Wangari runs the daily life of your farm — flocks, feed production, sales, and finances — in one simple platform that works on any device.</p>
            <div class="xai-hero-actions">
                <a href="/Frontend/pages/register.php" class="xai-btn xai-btn-primary xai-btn-lg">
                    Get Started Free
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="#preview" class="xai-btn xai-btn-secondary xai-btn-lg">
                    See the System
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M10 8l6 4-6 4V8z"/></svg>
                </a>
            </div>
            <div class="xai-hero-stats">
                <div class="xai-stat">
                    <div class="xai-stat-value">12+</div>
                    <div class="xai-stat-label">Integrated Modules</div>
                </div>
                <div class="xai-stat">
                    <div class="xai-stat-value">7</div>
                    <div class="xai-stat-label">Hubs: Farm to Finance</div>
                </div>
                <div class="xai-stat">
                    <div class="xai-stat-value">100%</div>
                    <div class="xai-stat-label">Your Data, Exportable</div>
                </div>
            </div>
        </div>
        <div class="xai-scroll-hint">
            <span>Scroll to explore</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
        </div>
    </section>

    <!-- System Preview -->
    <section class="xai-section-sm" id="preview">
        <div class="xai-container">
            <div class="xai-preview xai-reveal">
                <div class="xai-preview-frame">
                    <div class="xai-preview-bar">
                        <div class="xai-preview-dot"></div>
                        <div class="xai-preview-dot"></div>
                        <div class="xai-preview-dot"></div>
                        <span class="xai-preview-title">Wangari Farm OS — Live Dashboard</span>
                    </div>
                    <img src="/Frontend/images/demo-walkthrough.webp" alt="Wangari dashboard showing live revenue, orders, inventory alerts and operations" class="xai-preview-img" style="border-radius:0 0 12px 12px; width:100%; display:block; object-fit:cover;">
                </div>
            </div>
        </div>
    </section>

    <!-- Statement Section (x.ai-style) -->
    <section class="xai-section">
        <div class="xai-container">
            <div class="xai-statement xai-reveal">
                <div class="xai-statement-text">
                    <h2>Manage your farm like a <span style="color: var(--xai-lime); font-family: var(--font-serif); font-style: italic;">professional</span></h2>
                    <p>Wangari replaces your spreadsheets, notebooks, and scattered tools with one connected platform. Track flocks, feed costs, sales, credit, and finances — all in real time, on any device.</p>
                </div>
                <div class="xai-statement-visual">
                    <div class="xai-statement-card">
                        <div class="xai-statement-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div class="xai-statement-stat">
                            <span class="xai-statement-num">12+</span>
                            <span class="xai-statement-label">Integrated Modules</span>
                        </div>
                    </div>
                    <div class="xai-statement-card">
                        <div class="xai-statement-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <div class="xai-statement-stat">
                            <span class="xai-statement-num">AI</span>
                            <span class="xai-statement-label">Smart Assistant Built In</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="xai-section" id="features">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">Features</div>
                <h2>Everything your farm needs,<br><span style="color: var(--xai-lime); font-family: var(--font-serif); font-style: italic;">in one system</span></h2>
                <p>Seven connected hubs run the daily life of your farm, from the flock to the ledger.</p>
            </div>
            
            <div class="xai-features">
                <div class="xai-feature xai-reveal">
                    <div class="xai-feature-icon xai-icon-animated xai-icon-float">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h3>Farm Operations</h3>
                    <p>Track flocks, herds, production, health, vaccinations, and breeding — all captured daily in minutes.</p>
                </div>
                
                <div class="xai-feature xai-reveal">
                    <div class="xai-feature-icon xai-icon-animated xai-icon-float" style="animation-delay: 0.2s;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05M12 22.08V12"/></svg>
                    </div>
                    <h3>Inventory & Production</h3>
                    <p>Raw materials, feed formulas, bag production, egg grading and low-stock alerts with live costing.</p>
                </div>
                
                <div class="xai-feature xai-reveal">
                    <div class="xai-feature-icon xai-icon-animated xai-icon-float" style="animation-delay: 0.4s;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3>Sales, Credit & Finance</h3>
                    <p>Orders, cashbook, customer credit, LPOs, invoices and profit reports, M-Pesa payment ready.</p>
                </div>
                
                <div class="xai-feature xai-reveal">
                    <div class="xai-feature-icon xai-icon-animated xai-icon-float" style="animation-delay: 0.6s;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h3>AI Assistant</h3>
                    <p>Ask anything about your farm in plain language and get instant answers from your own records.</p>
                </div>
                
                <div class="xai-feature xai-reveal">
                    <div class="xai-feature-icon xai-icon-animated xai-icon-float" style="animation-delay: 0.8s;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3>CRM & Customers</h3>
                    <p>Manage customer relationships, track credit balances, and automate follow-ups.</p>
                </div>
                
                <div class="xai-feature xai-reveal">
                    <div class="xai-feature-icon xai-icon-animated xai-icon-float" style="animation-delay: 1s;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <h3>Reminders & Weather</h3>
                    <p>Smart reminders for vaccinations, payments, and tasks, plus live weather forecasts.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="xai-section" id="testimonials">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">Testimonials</div>
                <h2>What Our <span style="color: var(--xai-lime); font-family: var(--font-serif); font-style: italic;">Clients Say</span></h2>
                <p>Trusted by farmers and agribusinesses across Kenya.</p>
            </div>
            
            <div class="xai-testimonials">
                <div class="xai-testimonial xai-reveal">
                    <div class="xai-testimonial-stars">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <p>"Wangari replaced our spreadsheets overnight. Production, feed costs, sales and credit — one place, always up to date. We finally know our real profit."</p>
                    <div class="xai-testimonial-author">
                        <div class="xai-testimonial-avatar">N</div>
                        <div>
                            <div class="xai-testimonial-name">Njeri W.</div>
                            <div class="xai-testimonial-role">Poultry Farm Owner, Kiambu</div>
                        </div>
                    </div>
                </div>
                
                <div class="xai-testimonial xai-reveal">
                    <div class="xai-testimonial-stars">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <p>"The feed costing alone paid for itself. We know exactly what each bag costs to produce, and our profit reports are ready in one click."</p>
                    <div class="xai-testimonial-author">
                        <div class="xai-testimonial-avatar">O</div>
                        <div>
                            <div class="xai-testimonial-name">Otieno M.</div>
                            <div class="xai-testimonial-role">Feed Mill Owner, Kisumu</div>
                        </div>
                    </div>
                </div>
                
                <div class="xai-testimonial xai-reveal">
                    <div class="xai-testimonial-stars">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <p>"I run my shop, my credit customers and my orders from my phone. The AI assistant answers questions from our own records — it just works."</p>
                    <div class="xai-testimonial-author">
                        <div class="xai-testimonial-avatar">A</div>
                        <div>
                            <div class="xai-testimonial-name">Amina K.</div>
                            <div class="xai-testimonial-role">Agro-Vet Owner, Nakuru</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="xai-section-sm">
        <div class="xai-container">
            <div class="xai-cta xai-reveal">
                <h2>Let's grow something <span style="font-family: var(--font-serif); font-style: italic;">better</span></h2>
                <p>Tell us about your farm, and we'll show you how Wangari can organise it.</p>
                <div class="xai-cta-actions">
                    <a href="/Frontend/pages/register.php" class="xai-btn xai-btn-primary xai-btn-lg">
                        Get Started Free
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="/Frontend/pages/contact.php" class="xai-btn xai-btn-secondary xai-btn-lg">
                        Book a Demo
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="xai-section" id="faq">
        <div class="xai-container">
            <div class="xai-header xai-reveal">
                <div class="xai-header-eyebrow">FAQ</div>
                <h2>Frequently Asked <span style="color: var(--xai-lime); font-family: var(--font-serif); font-style: italic;">Questions</span></h2>
            </div>
            
            <div class="xai-faq xai-reveal">
                <div class="xai-faq-item open">
                    <div class="xai-faq-question" onclick="this.parentElement.classList.toggle('open')">
                        <span>What exactly is Wangari?</span>
                        <svg class="xai-faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </div>
                    <div class="xai-faq-answer">
                        <p>Wangari is an all-in-one farm management system — a mix of ERP, CRM and operations software. It handles production, inventory, feed, sales, credit, and finance for farms and agribusinesses of every size.</p>
                    </div>
                </div>
                
                <div class="xai-faq-item">
                    <div class="xai-faq-question" onclick="this.parentElement.classList.toggle('open')">
                        <span>Do you support small-scale farmers?</span>
                        <svg class="xai-faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </div>
                    <div class="xai-faq-answer">
                        <p>Absolutely. Wangari is built for farms of every size, from a backyard flock to a full commercial operation. You can run it on your own laptop, on your server, or in the cloud.</p>
                    </div>
                </div>
                
                <div class="xai-faq-item">
                    <div class="xai-faq-question" onclick="this.parentElement.classList.toggle('open')">
                        <span>Is my data safe, and who owns it?</span>
                        <svg class="xai-faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </div>
                    <div class="xai-faq-answer">
                        <p>You own your data, always. Records are encrypted, access is role-based, and you can export or delete everything at any time. No lock-in, no hidden fees.</p>
                    </div>
                </div>
                
                <div class="xai-faq-item">
                    <div class="xai-faq-question" onclick="this.parentElement.classList.toggle('open')">
                        <span>Does it work on my phone?</span>
                        <svg class="xai-faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </div>
                    <div class="xai-faq-answer">
                        <p>Yes. Wangari is a web app that works on any device — smartphone, tablet or computer. Capture production in the shed and check reports from home.</p>
                    </div>
                </div>
                
                <div class="xai-faq-item">
                    <div class="xai-faq-question" onclick="this.parentElement.classList.toggle('open')">
                        <span>How do I get started?</span>
                        <svg class="xai-faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </div>
                    <div class="xai-faq-answer">
                        <p>Choose how you run it: installed on your own laptop or server for full privacy, or hosted in the cloud so you can access it from anywhere. Every installation includes support, training, and the AI assistant.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="xai-footer">
    <div class="xai-container">
        <div class="xai-footer-inner">
            <!-- Brand & Description -->
            <div>
                <div class="xai-footer-brand">
                    <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
                    Wangari<span>.</span>
                </div>
                <p class="xai-footer-desc">Smart Farming for a Sustainable Future. All-in-one farm management system for poultry, livestock, crops, and finances — built for African agriculture.</p>
                <div class="xai-footer-contact">
                    <a href="mailto:info@imeantech.com" class="xai-footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                        info@imeantech.com
                    </a>
                    <a href="tel:+254114971070" class="xai-footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/></svg>
                        +254 114 971 070
                    </a>
                    <div class="xai-footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                        Waris Mall, Ruiru, Kenya
                    </div>
                </div>
            </div>
            
            <!-- Product Links -->
            <div>
                <h4>Product</h4>
                <ul class="xai-footer-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#preview">System</a></li>
                    <li><a href="/Frontend/pages/pricing.php">Pricing</a></li>
                    <li><a href="/Frontend/pages/services.php">Services</a></li>
                </ul>
            </div>
            
            <!-- Company Links -->
            <div>
                <h4>Company</h4>
                <ul class="xai-footer-links">
                    <li><a href="https://imeantech.com" target="_blank">iMeanTech</a></li>
                    <li><a href="/Frontend/pages/about.php">About</a></li>
                    <li><a href="/Frontend/pages/contact.php">Contact</a></li>
                    <li><a href="/Frontend/pages/faq.php">FAQ</a></li>
                </ul>
            </div>
            
            <!-- Legal Links -->
            <div>
                <h4>Legal</h4>
                <ul class="xai-footer-links">
                    <li><a href="/Frontend/pages/privacy.php">Privacy Policy</a></li>
                    <li><a href="/Frontend/pages/terms.php">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="xai-footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Wangari. All rights reserved.</span>
            <div class="xai-footer-credits">
                Built by <a href="https://imeantech.com" target="_blank">iMeanTech</a>
            </div>
        </div>
    </div>
</footer>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // ===== NAVIGATION SCROLL EFFECT =====
    const nav = document.getElementById('mainNav');
    const handleNavScroll = () => {
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    };
    window.addEventListener('scroll', handleNavScroll, { passive: true });
    handleNavScroll(); // Initial check
    
    // ===== MOBILE MENU =====
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    }
    
    if (mobileMenuClose && mobileMenu) {
        mobileMenuClose.addEventListener('click', () => {
            mobileMenu.classList.remove('open');
            document.body.style.overflow = '';
        });
    }
    
    // Close mobile menu when clicking a link
    mobileMenu?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.remove('open');
            document.body.style.overflow = '';
        });
    });
    
    // Close mobile menu on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileMenu?.classList.contains('open')) {
            mobileMenu.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
    
    // ===== SCROLL REVEAL ANIMATION =====
    const revealElements = document.querySelectorAll('.xai-reveal');
    const revealOnScroll = () => {
        revealElements.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight - 100) {
                el.classList.add('visible');
            }
        });
    };
    
    window.addEventListener('scroll', revealOnScroll, { passive: true });
    revealOnScroll(); // Initial check
    
    // ===== 3D PREVIEW FRAME SCROLL EFFECT =====
    const previewFrame = document.querySelector('.xai-preview-frame');
    const update3DPreview = () => {
        if (!previewFrame) return;
        const rect = previewFrame.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        
        // Calculate progress of the element passing through viewport
        const start = viewportHeight;
        const end = -rect.height;
        const current = rect.top;
        
        // Clamp progress between 0 and 1
        let progress = (start - current) / (start - end);
        progress = Math.max(0, Math.min(1, progress));
        
        // Starts tilted back and down, flattens and lifts as it scrolls into view
        const rotateX = 18 - (progress * 18); // 18deg -> 0deg
        const translateY = 60 - (progress * 60); // 60px -> 0px
        const scale = 0.9 + (progress * 0.1); // 0.9 -> 1.0
        
        previewFrame.style.transform = `perspective(1200px) rotateX(${rotateX}deg) translateY(${translateY}px) scale(${scale})`;
        previewFrame.style.transformOrigin = 'center top';
        previewFrame.style.transition = 'transform 0.1s ease-out';
    };
    
    window.addEventListener('scroll', update3DPreview, { passive: true });
    update3DPreview(); // Initial check
    // ===== SMOOTH SCROLL FOR ANCHOR LINKS =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // ===== ACTIVE NAV LINK HIGHLIGHT =====
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.xai-nav-links a');
    
    const highlightNavLink = () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (window.scrollY >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    };
    
    window.addEventListener('scroll', highlightNavLink, { passive: true });
});
</script>

</body>
</html>
