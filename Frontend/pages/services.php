<?php
/**
 * Services, Wangari System
 * Sells the Wangari farm management platform and its modules.
 * Growvi design language.
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Features & Modules - Wangari';

include '../includes/header.php';
?>

<main class="g-main">

    <section class="g-page-hero">
        <div class="g-container">
            <h1>Wangari <span class="g-serif">Modules</span></h1>
            <p>One system that runs your whole agribusiness, production, inventory, sales, credit and finance working together.</p>
        </div>
    </section>

    <section class="g-section">
        <div class="g-container">
            <div class="g-section-head center g-reveal">
                <span class="g-eyebrow">Core Modules</span>
                <h2>Everything your farm needs, <span class="g-serif">in one place</span></h2>
                <p>Seven connected hubs that run the daily life of a farm, from the flock to the ledger.</p>
            </div>

            <div class="g-numbered">
                <div class="g-numbered-item g-reveal g-delay-1">
                    <span class="g-num">01</span>
                    <h3>Farm Operations</h3>
                    <p>Flocks, herds, production, health, vaccinations, hatchery and breeding, captured daily in minutes, with automatic alerts.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">Poultry</span>
                        <span class="g-service-tag">Livestock</span>
                        <span class="g-service-tag">Hatchery</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-2">
                    <span class="g-num">02</span>
                    <h3>Inventory &amp; Production</h3>
                    <p>Raw materials, feed recipes, bag production, egg grading and low-stock alerts with live costing on every bag.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">Feed Formulas</span>
                        <span class="g-service-tag">Stock Alerts</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-3">
                    <span class="g-num">03</span>
                    <h3>Sales, Credit &amp; Finance</h3>
                    <p>Orders, cashbook, customer credit, LPOs, invoices and profit reports, M-Pesa payment ready for Kenya.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">M-Pesa</span>
                        <span class="g-service-tag">Profit Reports</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-4">
                    <span class="g-num">04</span>
                    <h3>Online Shop</h3>
                    <p>Your own web storefront with cart, checkout, order tracking and M-Pesa payments, built into the system.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">Storefront</span>
                        <span class="g-service-tag">Cart &amp; Checkout</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-5">
                    <span class="g-num">05</span>
                    <h3>Staff &amp; Permissions</h3>
                    <p>Give your team role-based access, record keepers, managers, accountants, each seeing only what they need.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">Roles</span>
                        <span class="g-service-tag">Audit Trail</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-6">
                    <span class="g-num">06</span>
                    <h3>Reports &amp; Analytics</h3>
                    <p>Profit and loss, daily sales, stock movement and production trends, exportable to Excel whenever you need.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">P&L Reports</span>
                        <span class="g-service-tag">Excel Export</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-7">
                    <span class="g-num">07</span>
                    <h3>AI Assistant</h3>
                    <p>Ask anything about your farm in plain language and get instant answers, summaries and alerts from your own records.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">Ask Wangari</span>
                        <span class="g-service-tag">Smart Alerts</span>
                    </div>
                </div>
                <div class="g-numbered-item g-reveal g-delay-8">
                    <span class="g-num">08</span>
                    <h3>Multi-Language &amp; Mobile</h3>
                    <p>Works on any device, smartphone, tablet or computer, with simple forms designed for the farm shed.</p>
                    <div class="g-service-tags">
                        <span class="g-service-tag">Mobile First</span>
                        <span class="g-service-tag">Your Language</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="g-section g-section-ink">
        <div class="g-container" style="text-align: center; max-width: 680px;">
            <h2 style="color: #fff;">Ready to run your farm on <span class="g-serif" style="color: var(--g-lime);">Wangari?</span></h2>
            <p style="color: rgba(255,255,255,0.66); margin-bottom: 2rem;">Create a free account and start tracking today, no credit card required.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="/Frontend/pages/register.php" class="g-btn g-btn-lime">Start Free</a>
                <a href="/Frontend/pages/contact.php" class="g-btn g-btn-outline">Book a Demo</a>
            </div>
        </div>
    </section>

</main>

<?php
include '../includes/footer.php';
?>
