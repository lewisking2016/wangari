<?php
/**
 * Wangari — Privacy Policy
 * Compliant with: Kenya Data Protection Act 2019, GDPR (for EU users),
 * Kenya Electronic Transactions Act 2008.
 */
$page_title = 'Privacy Policy — Wangari';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Frontend/assets/css/xai-public.css">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        .legal-page { max-width: 820px; margin: 0 auto; padding: 100px 24px 100px; }
        .legal-page h1 { font-size: 2.2rem; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.5px; color: var(--xai-text); }
        .legal-page .meta { color: var(--xai-text-muted); font-size: 14px; margin-bottom: 40px; line-height: 1.6; }
        .legal-page h2 { font-size: 1.3rem; font-weight: 700; margin: 40px 0 16px; padding-top: 20px; border-top: 1px solid var(--xai-border); color: var(--xai-text); }
        .legal-page h3 { font-size: 1.05rem; font-weight: 700; margin: 24px 0 12px; color: var(--xai-text); }
        .legal-page p, .legal-page li { font-size: 0.95rem; line-height: 1.8; color: var(--xai-text-secondary); }
        .legal-page ul { padding-left: 24px; margin-bottom: 16px; }
        .legal-page li { margin-bottom: 8px; }
        .legal-page a { color: var(--xai-lime-dark); text-decoration: none; font-weight: 500; }
        .legal-page a:hover { color: var(--xai-lime); text-decoration: underline; }
        .legal-highlight { background: rgba(34, 197, 94, 0.06); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 12px; padding: 20px 24px; margin: 20px 0; }
        .legal-highlight h4 { font-size: 14px; font-weight: 700; color: var(--xai-lime-dark); margin-bottom: 8px; }
        .legal-highlight p { margin: 0; font-size: 14px; color: var(--xai-text-secondary); }
        .data-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; background: var(--xai-card-bg); border-radius: 12px; overflow: hidden; border: 1px solid var(--xai-border); }
        .data-table th { text-align: left; padding: 12px 16px; background: var(--xai-surface); border-bottom: 1px solid var(--xai-border); font-weight: 700; color: var(--xai-text); }
        .data-table td { padding: 12px 16px; border-bottom: 1px solid rgba(201, 223, 208, 0.4); color: var(--xai-text-secondary); }
        .data-table tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
<div class="legal-page">
    <h1>Privacy Policy</h1>
    <div class="meta">
        Effective Date: August 22, 2026 · Last Updated: August 22, 2026<br>
        Company: iMeanTech Limited · Jurisdiction: Republic of Kenya<br>
        Data Protection Registration: ODPC Registered
    </div>

    <div class="legal-highlight">
        <h4>🔒 Your Privacy Matters</h4>
        <p>This Privacy Policy explains how we collect, use, store, and protect your personal data when you use the Wangari platform. We comply with the Kenya Data Protection Act, 2019, and international best practices.</p>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>1. Data Controller</h2>
    <p>The Data Controller responsible for your personal data is:</p>
    <ul>
        <li><strong>Company:</strong> iMeanTech Limited</li>
        <li><strong>Jurisdiction:</strong> Republic of Kenya</li>
        <li><strong>Data Protection Officer:</strong> <a href="mailto:dpo@imeantech.com">dpo@imeantech.com</a></li>
        <li><strong>ODPC Registration:</strong> [Registration Number]</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>2. Data We Collect</h2>

    <h3>2.1 Account Data (when you register)</h3>
    <table class="data-table">
        <tr><th>Data Type</th><th>Examples</th><th>Legal Basis</th></tr>
        <tr><td>Identity</td><td>Full name, username, email</td><td>Contract performance</td></tr>
        <tr><td>Contact</td><td>Phone number, email address</td><td>Contract performance</td></tr>
        <tr><td>Authentication</td><td>Password hash, session tokens</td><td>Contract performance</td></tr>
        <tr><td>Profile</td><td>Profile picture, role</td><td>Contract performance</td></tr>
        <tr><td>Google OAuth</td><td>Google ID, profile picture</td><td>Consent</td></tr>
    </table>

    <h3>2.2 Farm Data (when you use the Service)</h3>
    <table class="data-table">
        <tr><th>Data Type</th><th>Examples</th><th>Legal Basis</th></tr>
        <tr><td>Animal Records</td><td>Species, breed, tag numbers, health records</td><td>Contract performance</td></tr>
        <tr><td>Inventory</td><td>Products, quantities, suppliers, costs</td><td>Contract performance</td></tr>
        <tr><td>Financial</td><td>Orders, revenue, expenses, invoices</td><td>Contract performance</td></tr>
        <tr><td>Team</td><td>Member roles, join dates, activity</td><td>Contract performance</td></tr>
        <tr><td>Tasks</td><td>Assignments, status, notes</td><td>Contract performance</td></tr>
    </table>

    <h3>2.3 Technical Data (automatically collected)</h3>
    <table class="data-table">
        <tr><th>Data Type</th><th>Examples</th><th>Purpose</th></tr>
        <tr><td>Device</td><td>OS, browser, screen resolution</td><td>Service optimization</td></tr>
        <tr><td>Usage</td><td>Pages visited, features used, time spent</td><td>Product improvement</td></tr>
        <tr><td>Network</td><td>IP address, connection type</td><td>Security &amp; fraud prevention</td></tr>
        <tr><td>Errors</td><td>Crash logs, performance metrics</td><td>Bug fixes</td></tr>
        <tr><td>Desktop</td><td>Hardware fingerprint (for license only)</td><td>License management</td></tr>
    </table>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>3. How We Use Your Data</h2>
    <ul>
        <li><strong>Provide the Service</strong> — Process transactions, manage accounts, sync data</li>
        <li><strong>Security</strong> — Detect fraud, prevent abuse, authenticate users</li>
        <li><strong>Improvement</strong> — Analyze usage patterns, fix bugs, develop new features</li>
        <li><strong>Communication</strong> — Send service updates, respond to support requests</li>
        <li><strong>Legal Compliance</strong> — Meet regulatory requirements (KRA, ODPC)</li>
        <li><strong>Marketing</strong> — Only with your explicit consent (you may opt out anytime)</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>4. Data Sharing</h2>
    <h3>4.1 We Do NOT Sell Your Data</h3>
    <div class="legal-highlight">
        <h4>🚫 No Data Selling</h4>
        <p>We never sell, rent, or trade your personal data or farm data to third parties for their marketing purposes. Period.</p>
    </div>

    <h3>4.2 Service Providers</h3>
    <p>We share data with trusted service providers who assist in operating the Service:</p>
    <table class="data-table">
        <tr><th>Provider</th><th>Purpose</th><th>Location</th></tr>
        <tr><td>Contabo (VPS hosting)</td><td>Server infrastructure</td><td>Germany / Kenya</td></tr>
        <tr><td>Cloudflare</td><td>CDN &amp; DNS</td><td>USA</td></tr>
        <tr><td>Zoho Mail</td><td>Email delivery</td><td>USA</td></tr>
        <tr><td>Google OAuth</td><td>Authentication</td><td>USA</td></tr>
    </table>
    <p>All service providers are bound by Data Processing Agreements (DPAs) that ensure they process data only as instructed and implement appropriate security measures.</p>

    <h3>4.3 Legal Requirements</h3>
    <p>We may disclose data if required by:</p>
    <ul>
        <li>Kenyan court order or subpoena</li>
        <li>Request from the ODPC or other regulatory authority</li>
        <li>Law enforcement request related to criminal investigation</li>
        <li>Protection of our rights, property, or safety</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>5. Data Security</h2>
    <h3>5.1 Technical Measures</h3>
    <ul>
        <li><strong>Encryption in transit</strong> — TLS 1.2/1.3 for all web traffic (HTTPS)</li>
        <li><strong>Encryption at rest</strong> — AES-256 for stored database data</li>
        <li><strong>Password hashing</strong> — bcrypt with salt (never stored in plain text)</li>
        <li><strong>Session management</strong> — Redis-based sessions with automatic expiration</li>
        <li><strong>API security</strong> — CSRF tokens, rate limiting, input validation</li>
        <li><strong>Desktop</strong> — AES-256-GCM encryption for license data</li>
    </ul>

    <h3>5.2 Organizational Measures</h3>
    <ul>
        <li>Access controls — staff access only on need-to-know basis</li>
        <li>Security training for all team members</li>
        <li>Regular security audits and penetration testing</li>
        <li>Incident response plan with 72-hour breach notification</li>
    </ul>

    <h3>5.3 Desktop App Security</h3>
    <p>The desktop application stores data locally in an encrypted SQLite database. The encryption key is derived from a hardware fingerprint, making data inaccessible on other machines. When you connect to the internet, data syncs to our encrypted servers.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>6. Data Retention</h2>
    <table class="data-table">
        <tr><th>Data Type</th><th>Retention Period</th></tr>
        <tr><td>Account data</td><td>While account is active + 90 days</td></tr>
        <tr><td>Farm data</td><td>While account is active + 90 days</td></tr>
        <tr><td>Financial records</td><td>7 years (Kenya KRA requirement)</td></tr>
        <tr><td>Activity logs</td><td>1 year</td></tr>
        <tr><td>Support tickets</td><td>2 years</td></tr>
        <tr><td>Security logs</td><td>2 years</td></tr>
        <tr><td>Marketing consent</td><td>Until withdrawn</td></tr>
    </table>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>7. Your Rights (Kenya DPA 2019)</h2>
    <p>Under the Kenya Data Protection Act, 2019, you have the following rights:</p>

    <h3>7.1 Right of Access (Section 24)</h3>
    <p>You may request a copy of all personal data we hold about you. We will provide this within 30 days, free of charge.</p>

    <h3>7.2 Right to Rectification (Section 25)</h3>
    <p>You may request correction of inaccurate or incomplete data. You can also update your data directly in the application settings.</p>

    <h3>7.3 Right to Erasure (Section 26)</h3>
    <p>You may request deletion of your personal data. We will comply unless we have a legal obligation to retain it (e.g., financial records under KRA requirements).</p>

    <h3>7.4 Right to Object (Section 26)</h3>
    <p>You may object to processing of your data for specific purposes, including direct marketing.</p>

    <h3>7.5 Right to Data Portability (Section 26)</h3>
    <p>You may request your data in a structured, commonly used, machine-readable format (CSV, JSON). We support automated data export through the application.</p>

    <h3>7.6 Right to Withdraw Consent (Section 27)</h3>
    <p>Where processing is based on consent, you may withdraw consent at any time without affecting the lawfulness of prior processing.</p>

    <h3>7.7 How to Exercise Your Rights</h3>
    <p>Contact our Data Protection Officer:</p>
    <ul>
        <li><strong>Email:</strong> <a href="mailto:dpo@imeantech.com">dpo@imeantech.com</a></li>
        <li><strong>Response time:</strong> Within 30 days</li>
        <li><strong>Fee:</strong> Free for the first request; reasonable fee for repeat requests</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>8. International Data Transfers</h2>
    <p>Some of our service providers are located outside Kenya. When we transfer data internationally, we ensure:</p>
    <ul>
        <li>The receiving country has adequate data protection laws, OR</li>
        <li>Standard Contractual Clauses (SCCs) are in place, OR</li>
        <li>The data subject has given explicit consent</li>
    </ul>
    <p>This is in compliance with Section 48 of the Kenya Data Protection Act, 2019.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>9. Cookies &amp; Tracking</h2>
    <h3>9.1 Essential Cookies</h3>
    <p>We use cookies necessary for the Service to function (session cookies, authentication tokens). These cannot be disabled.</p>

    <h3>9.2 Analytics Cookies</h3>
    <p>We may use privacy-respecting analytics (e.g., Plausible, Umami) that do not use cookies and comply with GDPR. These help us understand usage patterns without tracking individuals.</p>

    <h3>9.3 Marketing Cookies</h3>
    <p>We do not currently use marketing or advertising cookies. If this changes, we will request your consent before placing them.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>10. Children's Privacy</h2>
    <p>The Service is not directed to individuals under 18 years of age. We do not knowingly collect personal data from children. If we learn that we have collected data from a child, we will delete it within 72 hours.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>11. Data Breach Notification</h2>
    <p>In the event of a personal data breach, we will:</p>
    <ul>
        <li>Notify the <strong>Office of the Data Protection Commissioner (ODPC)</strong> within 72 hours</li>
        <li>Notify affected individuals without undue delay if the breach poses a high risk</li>
        <li>Document the breach, its effects, and the remedial action taken</li>
    </ul>
    <p>This complies with Section 43 of the Kenya Data Protection Act, 2019.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>12. Changes to This Policy</h2>
    <p>We may update this Privacy Policy periodically. Material changes will be communicated via email and in-app notification at least 30 days before they take effect. The "Last Updated" date at the top reflects the most recent revision.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2>13. Contact Us</h2>
    <p>For privacy-related inquiries:</p>
    <ul>
        <li><strong>Data Protection Officer:</strong> <a href="mailto:dpo@imeantech.com">dpo@imeantech.com</a></li>
        <li><strong>General Privacy:</strong> <a href="mailto:privacy@imeantech.com">privacy@imeantech.com</a></li>
        <li><strong>Office of the Data Protection Commissioner:</strong> <a href="https://www.odpc.go.ke" target="_blank">www.odpc.go.ke</a></li>
    </ul>

    <div style="margin-top: 60px; padding-top: 20px; border-top: 1px solid var(--xai-border); text-align: center; color: var(--xai-text-muted); font-size: 13px;">
        © <?php echo date('Y'); ?> iMeanTech Limited. All rights reserved. · Wangari Smart Farm Manager
    </div>
</div>

<!-- Footer -->
<footer class="xai-footer">
    <div class="xai-container">
        <div class="xai-footer-inner">
            <div>
                <div class="xai-footer-brand">
                    <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
                    Wangari<span>.</span>
                </div>
                <p class="xai-footer-desc">Smart Farming for a Sustainable Future.</p>
            </div>
            <div>
                <h4>Legal</h4>
                <ul class="xai-footer-links">
                    <li><a href="/Frontend/pages/terms.php">Terms & Conditions</a></li>
                    <li><a href="/Frontend/pages/privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="xai-footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Wangari. All rights reserved.</span>
            <div class="xai-footer-credits">
                Built by <a href="https://imeantech.com" target="_blank">iMeanTech</a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
