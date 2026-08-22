<?php
/**
 * Pricing, Wangari (Growvi style)
 * 4-tier freemium pricing with strategic conversion design.
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Pricing | Wangari';
include '../includes/header.php';
?>

<section class="g-page-hero">
    <div class="g-container">
        <h1>Simple plans for farms of <span class="g-serif">every size</span></h1>
        <p>Start free. Grow with us. Your data stays yours on every plan. Pay via M-Pesa, bank transfer, or card.</p>
        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap;">
            <span style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 999px; font-size: 0.85rem; color: rgba(255,255,255,0.9);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                14-day money-back guarantee
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 999px; font-size: 0.85rem; color: rgba(255,255,255,0.9);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Cancel anytime, no lock-in
            </span>
        </div>
    </div>
</section>

<!-- Pricing Cards -->
<section class="g-section">
    <div class="g-container">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.2rem; align-items: start;">

            <!-- FREE -->
            <div class="g-card g-reveal g-delay-1" style="display: flex; flex-direction: column; text-align: center;">
                <div style="margin-bottom: 0.3rem;">
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.8rem; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); border-radius: 999px; font-size: 0.7rem; font-weight: 700; color: var(--g-lime); text-transform: uppercase; letter-spacing: 0.06em;">🌱 Starter</span>
                </div>
                <h4 style="margin-bottom: 0.3rem;">Free</h4>
                <p style="font-size: 0.82rem; color: var(--g-muted); margin-bottom: 1.2rem;">For individual farmers getting started.</p>
                <div style="margin-bottom: 1.5rem;">
                    <span class="g-serif" style="font-size: 2.8rem; color: var(--g-ink);">KES 0</span>
                    <span style="color: var(--g-muted); font-size: 0.82rem; display: block;">forever</span>
                </div>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.55rem; margin-bottom: 1.5rem; font-size: 0.85rem; padding: 0; text-align: left;">
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> 1 farm, 1 flock, 100 animals</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> Daily production tracking</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> Basic expense tracking</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> AI assistant: 5 questions/day</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> Mobile app access</li>
                    <li style="display: flex; gap: 8px; color: var(--g-muted);"><i data-lucide="x" style="width: 16px; height: 16px; color: #ccc; flex-shrink: 0;"></i> No invoicing or CRM</li>
                    <li style="display: flex; gap: 8px; color: var(--g-muted);"><i data-lucide="x" style="width: 16px; height: 16px; color: #ccc; flex-shrink: 0;"></i> No team collaboration</li>
                    <li style="display: flex; gap: 8px; color: var(--g-muted);"><i data-lucide="x" style="width: 16px; height: 16px; color: #ccc; flex-shrink: 0;"></i> Current month data only</li>
                </ul>
                <a href="/Frontend/pages/register.php" class="g-btn g-btn-outline-dark" style="width: 100%;">Start Free</a>
            </div>

            <!-- GROW (Most Popular) -->
            <div class="g-card g-reveal g-delay-2" style="display: flex; flex-direction: column; border: 2px solid var(--g-lime); position: relative; background: var(--g-ink); text-align: center;">
                <span style="position: absolute; top: -12px; right: 20px; background: var(--g-lime); color: var(--g-ink); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; padding: 0.35rem 0.8rem; border-radius: 999px;">MOST POPULAR</span>
                <div style="margin-bottom: 0.3rem;">
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.8rem; background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); border-radius: 999px; font-size: 0.7rem; font-weight: 700; color: var(--g-lime); text-transform: uppercase; letter-spacing: 0.06em;">🌿 Small Farm</span>
                </div>
                <h4 style="margin-bottom: 0.3rem; color: #fff;">Grow</h4>
                <p style="font-size: 0.82rem; color: rgba(255,255,255,0.6); margin-bottom: 1.2rem;">For small-scale commercial farms.</p>
                <div style="margin-bottom: 0.3rem;">
                    <span class="g-serif" style="font-size: 2.4rem; color: var(--g-lime);">KES 999</span>
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.82rem;">/month</span>
                </div>
                <p style="color: rgba(255,255,255,0.4); font-size: 0.75rem; margin-bottom: 1.2rem;">less than KES 33/day ☕</p>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.55rem; margin-bottom: 1.5rem; font-size: 0.85rem; padding: 0; text-align: left;">
                    <li style="display: flex; gap: 8px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> 1 farm, 3 flocks, 2,000 animals</li>
                    <li style="display: flex; gap: 8px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> 50 customers with invoicing</li>
                    <li style="display: flex; gap: 8px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> M-Pesa payment recording</li>
                    <li style="display: flex; gap: 8px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> 3 feed formulas with costing</li>
                    <li style="display: flex; gap: 8px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> Vaccination schedules &amp; alerts</li>
                    <li style="display: flex; gap: 8px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> 3 team members</li>
                    <li style="display: flex; gap: 8px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> AI assistant: 50 questions/day</li>
                    <li style="display: flex; gap: 8px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> P&amp;L reports &amp; 30-day history</li>
                    <li style="display: flex; gap: 8px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-lime); flex-shrink: 0;"></i> CSV data export</li>
                </ul>
                <a href="/Frontend/pages/register.php" class="g-btn g-btn-lime" style="width: 100%;">Choose Grow</a>
            </div>

            <!-- SCALE -->
            <div class="g-card g-reveal g-delay-3" style="display: flex; flex-direction: column; text-align: center;">
                <div style="margin-bottom: 0.3rem;">
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.8rem; background: rgba(129,117,79,0.1); border: 1px solid rgba(129,117,79,0.2); border-radius: 999px; font-size: 0.7rem; font-weight: 700; color: var(--g-tan); text-transform: uppercase; letter-spacing: 0.06em;">🌳 Large Farm</span>
                </div>
                <h4 style="margin-bottom: 0.3rem;">Scale</h4>
                <p style="font-size: 0.82rem; color: var(--g-muted); margin-bottom: 1.2rem;">For large-scale commercial farms.</p>
                <div style="margin-bottom: 0.3rem;">
                    <span class="g-serif" style="font-size: 2.4rem; color: var(--g-ink);">KES 2,999</span>
                    <span style="color: var(--g-muted); font-size: 0.82rem;">/month</span>
                </div>
                <p style="color: var(--g-muted); font-size: 0.75rem; margin-bottom: 1.2rem;">save KES 7,200/year with annual</p>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.55rem; margin-bottom: 1.5rem; font-size: 0.85rem; padding: 0; text-align: left;">
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> 3 farms, unlimited flocks &amp; animals</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Unlimited customers &amp; invoicing</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Full M-Pesa &amp; credit management</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Unlimited feed formulas</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> 10 team members</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Unlimited AI questions</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Advanced analytics &amp; trends</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> All-time history &amp; PDF export</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Priority phone support (4h)</li>
                </ul>
                <a href="/Frontend/pages/register.php" class="g-btn g-btn-outline-dark" style="width: 100%;">Choose Scale</a>
            </div>

            <!-- ENTERPRISE -->
            <div class="g-card g-reveal g-delay-4" style="display: flex; flex-direction: column; text-align: center;">
                <div style="margin-bottom: 0.3rem;">
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.8rem; background: rgba(0,11,34,0.06); border: 1px solid rgba(0,11,34,0.1); border-radius: 999px; font-size: 0.7rem; font-weight: 700; color: var(--g-ink); text-transform: uppercase; letter-spacing: 0.06em;">🏢 Management</span>
                </div>
                <h4 style="margin-bottom: 0.3rem;">Enterprise</h4>
                <p style="font-size: 0.82rem; color: var(--g-muted); margin-bottom: 1.2rem;">For cooperatives, agribusinesses &amp; management companies.</p>
                <div style="margin-bottom: 0.3rem;">
                    <span class="g-serif" style="font-size: 2.4rem; color: var(--g-ink);">Custom</span>
                </div>
                <p style="color: var(--g-muted); font-size: 0.75rem; margin-bottom: 1.2rem;">from KES 15,000/month</p>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.55rem; margin-bottom: 1.5rem; font-size: 0.85rem; padding: 0; text-align: left;">
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Unlimited farms &amp; locations</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Unlimited team members</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> White-label branding</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> API access &amp; integrations</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Custom AI training</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> Dedicated account manager</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> On-site training &amp; setup</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> SLA guarantee (99.9% uptime)</li>
                    <li style="display: flex; gap: 8px;"><i data-lucide="check" style="width: 16px; height: 16px; color: var(--g-tan); flex-shrink: 0;"></i> 7-year data retention</li>
                </ul>
                <a href="/Frontend/pages/contact.php" class="g-btn g-btn-outline-dark" style="width: 100%;">Contact Sales</a>
            </div>

        </div>
    </div>
</section>

<!-- Feature Comparison -->
<section class="g-section g-section-cream">
    <div class="g-container">
        <div class="g-section-head center g-reveal">
            <span class="g-eyebrow">Compare Plans</span>
            <h2>Everything you need, <span class="g-serif">nothing you don't</span></h2>
        </div>
        <div class="g-table-wrap g-reveal g-delay-1">
            <table class="g-table">
                <thead>
                    <tr>
                        <th style="width: 40%;">Feature</th>
                        <th style="text-align: center;">Free</th>
                        <th style="text-align: center; background: rgba(34,197,94,0.06);">Grow</th>
                        <th style="text-align: center;">Scale</th>
                        <th style="text-align: center;">Enterprise</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><strong>Price</strong></td><td style="text-align: center;">KES 0</td><td style="text-align: center; background: rgba(34,197,94,0.04);"><strong>KES 999/mo</strong></td><td style="text-align: center;">KES 2,999/mo</td><td style="text-align: center;">Custom</td></tr>
                    <tr><td>Farms</td><td style="text-align: center;">1</td><td style="text-align: center; background: rgba(34,197,94,0.04);">1</td><td style="text-align: center;">3</td><td style="text-align: center;">Unlimited</td></tr>
                    <tr><td>Flocks / Batches</td><td style="text-align: center;">1</td><td style="text-align: center; background: rgba(34,197,94,0.04);">3</td><td style="text-align: center;">Unlimited</td><td style="text-align: center;">Unlimited</td></tr>
                    <tr><td>Animals</td><td style="text-align: center;">100</td><td style="text-align: center; background: rgba(34,197,94,0.04);">2,000</td><td style="text-align: center;">Unlimited</td><td style="text-align: center;">Unlimited</td></tr>
                    <tr><td>Customers</td><td style="text-align: center;">—</td><td style="text-align: center; background: rgba(34,197,94,0.04);">50</td><td style="text-align: center;">Unlimited</td><td style="text-align: center;">Unlimited</td></tr>
                    <tr><td>Team Members</td><td style="text-align: center;">1</td><td style="text-align: center; background: rgba(34,197,94,0.04);">3</td><td style="text-align: center;">10</td><td style="text-align: center;">Unlimited</td></tr>
                    <tr><td>AI Questions / Day</td><td style="text-align: center;">5</td><td style="text-align: center; background: rgba(34,197,94,0.04);">50</td><td style="text-align: center;">Unlimited</td><td style="text-align: center;">Unlimited + Custom</td></tr>
                    <tr><td>Invoicing &amp; LPOs</td><td style="text-align: center;">—</td><td style="text-align: center; background: rgba(34,197,94,0.04);">Basic</td><td style="text-align: center;">Advanced + PDF</td><td style="text-align: center;">White-label</td></tr>
                    <tr><td>Feed Formulas</td><td style="text-align: center;">—</td><td style="text-align: center; background: rgba(34,197,94,0.04);">3</td><td style="text-align: center;">Unlimited</td><td style="text-align: center;">Unlimited</td></tr>
                    <tr><td>Vaccination Schedules</td><td style="text-align: center;">—</td><td style="text-align: center; background: rgba(34,197,94,0.04);">✓</td><td style="text-align: center;">✓</td><td style="text-align: center;">✓</td></tr>
                    <tr><td>Data History</td><td style="text-align: center;">Current month</td><td style="text-align: center; background: rgba(34,197,94,0.04);">30 days</td><td style="text-align: center;">All time</td><td style="text-align: center;">All time</td></tr>
                    <tr><td>Data Export</td><td style="text-align: center;">—</td><td style="text-align: center; background: rgba(34,197,94,0.04);">CSV</td><td style="text-align: center;">CSV + PDF</td><td style="text-align: center;">All + API</td></tr>
                    <tr><td>M-Pesa Integration</td><td style="text-align: center;">—</td><td style="text-align: center; background: rgba(34,197,94,0.04);">Recording</td><td style="text-align: center;">Full</td><td style="text-align: center;">Full + API</td></tr>
                    <tr><td>Support</td><td style="text-align: center;">Community</td><td style="text-align: center; background: rgba(34,197,94,0.04);">Email 24h</td><td style="text-align: center;">Phone 4h</td><td style="text-align: center;">Dedicated 1h</td></tr>
                    <tr><td>Data Retention</td><td style="text-align: center;">30 days</td><td style="text-align: center; background: rgba(34,197,94,0.04);">1 year</td><td style="text-align: center;">Forever</td><td style="text-align: center;">Forever + backup</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Social Proof -->
<section class="g-section">
    <div class="g-container" style="text-align: center; max-width: 700px;">
        <h2 class="g-reveal" style="font-size: clamp(1.8rem, 4vw, 2.5rem);">Trusted by <span class="g-serif" style="color: var(--g-tan);">200+ farms</span> across Kenya</h2>
        <p class="g-reveal g-delay-1" style="color: var(--g-muted); font-size: 1.05rem; margin-bottom: 2rem;">From backyard flocks to commercial agribusinesses, Wangari scales with your farm.</p>
        <div class="g-reveal g-delay-2" style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
            <div style="text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--g-lime);">200+</div>
                <div style="font-size: 0.82rem; color: var(--g-muted);">Farms</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--g-lime);">50,000+</div>
                <div style="font-size: 0.82rem; color: var(--g-muted);">Animals Tracked</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--g-lime);">KES 50M+</div>
                <div style="font-size: 0.82rem; color: var(--g-muted);">Revenue Managed</div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="g-section g-section-cream">
    <div class="g-container">
        <div class="g-section-head center g-reveal">
            <span class="g-eyebrow">Pricing FAQ</span>
            <h2>Common <span class="g-serif">questions</span></h2>
        </div>
        <div class="g-faq g-reveal g-delay-1">
            <div class="g-faq-item open">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">01</span>
                    <span>Can I try before I buy?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Yes! Start with our free plan — no credit card required. Upgrade anytime when you need more features.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">02</span>
                    <span>How do I pay?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">We accept M-Pesa, bank transfer, and credit/debit cards. M-Pesa is the easiest — pay directly from your phone.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">03</span>
                    <span>Can I cancel anytime?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Absolutely. Cancel anytime with no penalties. Your data is retained for 90 days after cancellation so you can export it.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">04</span>
                    <span>What happens to my data if I downgrade?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Your data is always yours. If you downgrade, you keep all your data but some features become read-only until you upgrade again.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">05</span>
                    <span>Do you offer discounts for cooperatives?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Yes! Cooperatives and farming groups get volume discounts on the Enterprise plan. Contact us for custom pricing.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">06</span>
                    <span>Is there a daily payment option?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Yes! Pay via M-Pesa daily auto-debit — just KES 33/day for Grow (less than a cup of chai!) or KES 100/day for Scale.</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="g-section g-section-ink" style="text-align: center;">
    <div class="g-container" style="max-width: 680px;">
        <h2 class="g-reveal" style="color: #fff; font-size: clamp(1.9rem, 4vw, 2.8rem); margin-bottom: 1rem;">Ready to run your farm on <span class="g-serif" style="color: var(--g-lime);">Wangari?</span></h2>
        <p class="g-reveal g-delay-1" style="color: rgba(255,255,255,0.66); font-size: 1.05rem; margin-bottom: 2rem;">Create a free account in 2 minutes. No credit card required.</p>
        <div class="g-reveal g-delay-2" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="/Frontend/pages/register.php" class="g-btn g-btn-lime">Start Free</a>
            <a href="/Frontend/pages/contact.php" class="g-btn g-btn-outline">Book a Demo</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
