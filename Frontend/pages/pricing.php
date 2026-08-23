<?php
/**
 * Pricing, Wangari (Growvi style)
 * 3-tier pricing with strategic conversion design.
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Pricing | Wangari';
include '../includes/header.php';
?>

<section class="g-page-hero">
    <div class="g-container">
        <h1>Simple plans for farms of <span class="g-serif">every size</span></h1>
        <p>Use every Wangari web feature free for 30 days. No card is required, and your farm data remains yours.</p>
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
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; align-items: start;">

            <!-- PRO -->
            <div class="g-card g-reveal g-delay-1" style="display: flex; flex-direction: column; text-align: center;">
                <div style="margin-bottom: 0.5rem;">
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.9rem; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); border-radius: 999px; font-size: 0.75rem; font-weight: 700; color: var(--g-lime); text-transform: uppercase; letter-spacing: 0.06em;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V12M12 12C12 7 7 3 2 3c0 5 4 9 10 9z"/><path d="M12 12c0-5 5-9 10-9-1 5-5 9-10 9"/></svg>
                        Small Farm
                    </span>
                </div>
                <h4 style="margin-bottom: 0.3rem; font-size: 1.4rem;">Pro</h4>
                <p style="font-size: 0.85rem; color: var(--g-muted); margin-bottom: 1.2rem;">For backyard farmers and very small operations.</p>
                <div style="margin-bottom: 0.3rem;">
                    <span class="g-serif" style="font-size: 2.6rem; color: var(--g-ink);">KES 1,500</span>
                    <span style="color: var(--g-muted); font-size: 0.85rem;">/month</span>
                </div>
                <p style="color: var(--g-lime); font-size: 0.78rem; font-weight: 600; margin-bottom: 1.2rem;">Less than KES 50/day</p>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1.5rem; font-size: 0.88rem; padding: 0; text-align: left;">
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> 3 modules (Livestock OR Crops OR Finance)</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> 5 team members</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> 5 cows / 100 birds</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> 10 fields</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> 50 SMS alerts/month</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> PDF reports</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Basic AI assistant</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Email support</li>
                </ul>
                <button onclick="startPayment('pro', 'monthly')" class="g-btn g-btn-outline-dark" style="width: 100%;">Subscribe Pro - KES 1,500/mo</button>
            </div>

            <!-- PLUS (Most Popular) -->
            <div class="g-card g-reveal g-delay-2" style="display: flex; flex-direction: column; border: 2px solid var(--g-lime); position: relative; background: var(--g-ink); text-align: center;">
                <span style="position: absolute; top: -14px; right: 20px; background: var(--g-lime); color: var(--g-ink); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; padding: 0.4rem 0.9rem; border-radius: 999px;">MOST POPULAR</span>
                <div style="margin-bottom: 0.5rem;">
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.9rem; background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); border-radius: 999px; font-size: 0.75rem; font-weight: 700; color: var(--g-lime); text-transform: uppercase; letter-spacing: 0.06em;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V12M12 12C12 7 7 3 2 3c0 5 4 9 10 9z"/><path d="M12 12c0-5 5-9 10-9-1 5-5 9-10 9"/></svg>
                        Medium-Large Farm
                    </span>
                </div>
                <h4 style="margin-bottom: 0.3rem; color: #fff; font-size: 1.4rem;">Plus</h4>
                <p style="font-size: 0.85rem; color: rgba(255,255,255,0.6); margin-bottom: 1.2rem;">For growing farms with commercial scale.</p>
                <div style="margin-bottom: 0.3rem;">
                    <span class="g-serif" style="font-size: 2.6rem; color: var(--g-lime);">KES 4,500</span>
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">/month</span>
                </div>
                <p style="color: rgba(255,255,255,0.4); font-size: 0.78rem; margin-bottom: 1.2rem;">Save KES 10,000/year with annual billing</p>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1.5rem; font-size: 0.88rem; padding: 0; text-align: left;">
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> All modules included</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> 15 team members</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> 200 cows / 2,000 birds</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> 50 fields</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> 200 SMS alerts/month</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> PDF + CSV + Excel reports</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Advanced AI analytics</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Up to 3 farms</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.9);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Email + WhatsApp support</li>
                </ul>
                <button onclick="startPayment('plus', 'monthly')" class="g-btn g-btn-lime" style="width: 100%;">Subscribe Plus - KES 4,500/mo</button>
            </div>

            <!-- CUSTOM (Enterprise) -->
            <div class="g-card g-reveal g-delay-3" style="display: flex; flex-direction: column; text-align: center;">
                <div style="margin-bottom: 0.5rem;">
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.9rem; background: rgba(0,11,34,0.06); border: 1px solid rgba(0,11,34,0.1); border-radius: 999px; font-size: 0.75rem; font-weight: 700; color: var(--g-ink); text-transform: uppercase; letter-spacing: 0.06em;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 7v14M21 7v14M6 7V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v3M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/></svg>
                        Commercial / Enterprise
                    </span>
                </div>
                <h4 style="margin-bottom: 0.3rem; font-size: 1.4rem;">Custom</h4>
                <p style="font-size: 0.85rem; color: var(--g-muted); margin-bottom: 1.2rem;">For cooperatives, agribusinesses & management companies.</p>
                <div style="margin-bottom: 0.3rem;">
                    <span class="g-serif" style="font-size: 2.6rem; color: var(--g-ink);">Custom</span>
                </div>
                <p style="color: var(--g-muted); font-size: 0.78rem; margin-bottom: 1.2rem;">from KES 12,000/month</p>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1.5rem; font-size: 0.88rem; padding: 0; text-align: left;">
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> All modules + custom features</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Unlimited team members</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Unlimited animals & fields</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Unlimited SMS alerts</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> All report formats + API</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Full AI + custom models</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> White-label branding</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> API access & integrations</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Priority phone support</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> On-site training & setup</li>
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
                        <th style="width: 35%;">Feature</th>
                        <th style="text-align: center;">Pro</th>
                        <th style="text-align: center; background: rgba(34,197,94,0.06);">Plus</th>
                        <th style="text-align: center;">Custom</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Monthly Price</strong></td>
                        <td style="text-align: center;"><strong>KES 1,500</strong></td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);"><strong>KES 4,500</strong></td>
                        <td style="text-align: center;">KES 12,000+</td>
                    </tr>
                    <tr>
                        <td>Annual Price</td>
                        <td style="text-align: center;">KES 15,000/yr</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">KES 45,000/yr</td>
                        <td style="text-align: center;">Custom</td>
                    </tr>
                    <tr>
                        <td>Modules</td>
                        <td style="text-align: center;">3 (choice)</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">All</td>
                        <td style="text-align: center;">All + Custom</td>
                    </tr>
                    <tr>
                        <td>Team Members</td>
                        <td style="text-align: center;">5</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">15</td>
                        <td style="text-align: center;">Unlimited</td>
                    </tr>
                    <tr>
                        <td>Animals</td>
                        <td style="text-align: center;">5 / 100 birds</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">200 / 2,000 birds</td>
                        <td style="text-align: center;">500 / 10,000+ birds</td>
                    </tr>
                    <tr>
                        <td>Fields</td>
                        <td style="text-align: center;">10</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">50</td>
                        <td style="text-align: center;">Unlimited</td>
                    </tr>
                    <tr>
                        <td>SMS Alerts</td>
                        <td style="text-align: center;">50/month</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">200/month</td>
                        <td style="text-align: center;">Unlimited</td>
                    </tr>
                    <tr>
                        <td>Reports</td>
                        <td style="text-align: center;">PDF</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">PDF + CSV + Excel</td>
                        <td style="text-align: center;">All + Custom</td>
                    </tr>
                    <tr>
                        <td>AI Assistant</td>
                        <td style="text-align: center;">Basic</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">Advanced</td>
                        <td style="text-align: center;">Full + Custom</td>
                    </tr>
                    <tr>
                        <td>Multi-Farm</td>
                        <td style="text-align: center;">1 farm</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">Up to 3 farms</td>
                        <td style="text-align: center;">Unlimited</td>
                    </tr>
                    <tr>
                        <td>Data Export</td>
                        <td style="text-align: center;">—</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">CSV</td>
                        <td style="text-align: center;">API + CSV + Excel</td>
                    </tr>
                    <tr>
                        <td>M-Pesa Integration</td>
                        <td style="text-align: center;">Recording</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">Full</td>
                        <td style="text-align: center;">Full + API</td>
                    </tr>
                    <tr>
                        <td>White-Label</td>
                        <td style="text-align: center;">—</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">—</td>
                        <td style="text-align: center;">Yes</td>
                    </tr>
                    <tr>
                        <td>Support</td>
                        <td style="text-align: center;">Email</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">Email + WhatsApp</td>
                        <td style="text-align: center;">Priority Phone</td>
                    </tr>
                    <tr>
                        <td>Data Retention</td>
                        <td style="text-align: center;">1 year</td>
                        <td style="text-align: center; background: rgba(34,197,94,0.04);">Forever</td>
                        <td style="text-align: center;">Forever + Backup</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Social Proof -->
<section class="g-section">
    <div class="g-container" style="text-align: center; max-width: 700px;">
        <h2 class="g-reveal" style="font-size: clamp(1.8rem, 4vw, 2.5rem);">Try the complete farm system <span class="g-serif" style="color: var(--g-tan);">before you commit</span></h2>
        <p class="g-reveal g-delay-1" style="color: var(--g-muted); font-size: 1.05rem; margin-bottom: 2rem;">The trial is designed to let you test every web module with your own farm records, team, and workflows.</p>
        <div class="g-reveal g-delay-2" style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
            <div style="text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--g-lime);">30</div>
                <div style="font-size: 0.82rem; color: var(--g-muted);">Trial days</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--g-lime);">All</div>
                <div style="font-size: 0.82rem; color: var(--g-muted);">Web modules</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--g-lime);">No</div>
                <div style="font-size: 0.82rem; color: var(--g-muted);">Card required</div>
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
                    <span>Is there a free trial?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Yes! Every new account gets a 30-day free trial with full access to all web modules — no credit card required. After 30 days, choose a plan that fits your farm.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">02</span>
                    <span>How do I pay?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">We accept M-Pesa, bank transfer, and credit/debit cards. M-Pesa is the easiest — pay directly from your phone. You can also set up auto-debit for hassle-free monthly payments.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">03</span>
                    <span>Can I cancel anytime?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Absolutely. Cancel anytime with no penalties or lock-in contracts. Your data is retained for 90 days after cancellation so you can export it.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">04</span>
                    <span>What happens to my data if I downgrade?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Your data is always yours. If you downgrade, you keep all your data but some features become read-only until you upgrade again. No data is ever deleted.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">05</span>
                    <span>Do you offer discounts for cooperatives?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Yes! Cooperatives and farming groups get volume discounts on the Custom plan. Contact us for custom pricing based on your member count and needs.</div>
            </div>
            <div class="g-faq-item">
                <div class="g-faq-q" onclick="this.parentElement.classList.toggle('open')">
                    <span class="g-faq-num">06</span>
                    <span>What's the difference between Pro and Plus?</span>
                    <span class="g-plus">+</span>
                </div>
                <div class="g-faq-a">Pro is designed for very small farms (5 cows or 100 birds max). It gives you 3 modules of your choice with 5 team members. Plus unlocks all modules, supports 200 cows or 2,000 birds, 15 team members, up to 3 farms, and includes advanced analytics. If your farm grows beyond Pro limits, you'll need to upgrade.</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="g-section g-section-ink" style="text-align: center;">
    <div class="g-container" style="max-width: 680px;">
        <h2 class="g-reveal" style="color: #fff; font-size: clamp(1.9rem, 4vw, 2.8rem); margin-bottom: 1rem;">Wangari technology by <span class="g-serif" style="color: var(--g-lime);">iMeanTech</span></h2>
        <p class="g-reveal g-delay-1" style="color: rgba(255,255,255,0.66); font-size: 1.05rem; margin-bottom: 2rem;">Visit iMeanTech to learn about the technology, services and support behind Wangari.</p>
        <div class="g-reveal g-delay-2" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="https://imeantech.com" target="_blank" rel="noopener noreferrer" class="g-btn g-btn-lime">Visit iMeanTech.com</a>
        </div>
    </div>
</section>

<style>
@media (max-width: 900px) {
    .g-container > div[style*="grid-template-columns: repeat(3"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (max-width: 560px) {
    .g-container > div[style*="grid-template-columns: repeat(3"] {
        grid-template-columns: 1fr !important;
    }
    .g-table-wrap { overflow-x: auto; }
    .g-table { min-width: 450px; }
}
</style>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
function startPayment(plan, billing) {
    // Check if user is logged in
    fetch('/Backend/api/paystack.php?action=subscription')
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                // Not logged in, redirect to login
                window.location.href = '/Frontend/pages/login.php?redirect=pricing';
                return;
            }
            
            // Initialize payment
            fetch('/Backend/api/paystack.php?action=initialize', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ plan: plan, billing: billing })
            })
            .then(r => r.json())
            .then(result => {
                if (result.success && result.authorization_url) {
                    // Redirect to Paystack checkout
                    window.location.href = result.authorization_url;
                } else {
                    alert(result.error || 'Payment initialization failed. Please try again.');
                }
            })
            .catch(err => {
                console.error('Payment error:', err);
                alert('An error occurred. Please try again.');
            });
        })
        .catch(() => {
            window.location.href = '/Frontend/pages/login.php?redirect=pricing';
        });
}
</script>

<?php include '../includes/footer.php'; ?>
