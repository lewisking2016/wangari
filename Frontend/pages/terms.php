<?php
/**
 * Wangari — Terms & Conditions
 * Compliant with: Kenya Data Protection Act 2019, Kenya Consumer Protection Act 2012,
 * Kenya Electronic Transactions Act 2008, Kenya Computer Misuse & Cybercrimes Act 2018,
 * and international SaaS best practices (GDPR-aligned).
 */
$page_title = 'Terms & Conditions — Wangari';
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
        .toc { background: var(--xai-surface); border: 1px solid var(--xai-border); border-radius: 12px; padding: 24px; margin-bottom: 40px; }
        .toc h3 { font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--xai-text); }
        .toc ol { padding-left: 20px; }
        .toc li { font-size: 13px; margin-bottom: 6px; }
        .toc a { color: var(--xai-text-secondary); text-decoration: none; }
        .toc a:hover { color: var(--xai-lime-dark); }
    </style>
</head>
<body>
<div class="legal-page">
    <h1>Terms &amp; Conditions</h1>
    <div class="meta">
        Effective Date: August 22, 2026 · Last Updated: August 22, 2026<br>
        Company: iMeanTech Limited · Jurisdiction: Republic of Kenya<br>
        Governing Law: Laws of Kenya
    </div>

    <div class="toc">
        <h3>Table of Contents</h3>
        <ol>
            <li><a href="#acceptance">Acceptance of Terms</a></li>
            <li><a href="#definitions">Definitions</a></li>
            <li><a href="#account">Account Registration &amp; Eligibility</a></li>
            <li><a href="#subscription">Subscription Plans &amp; Payment</a></li>
            <li><a href="#farm-codes">Farm Codes &amp; Team Access</a></li>
            <li><a href="#license">Software License &amp; Intellectual Property</a></li>
            <li><a href="#data">Data Ownership &amp; Portability</a></li>
            <li><a href="#privacy">Privacy &amp; Data Protection</a></li>
            <li><a href="#acceptable-use">Acceptable Use Policy</a></li>
            <li><a href="#availability">Service Availability &amp; SLA</a></li>
            <li><a href="#liability">Limitation of Liability</a></li>
            <li><a href="#warranty">Warranties &amp; Disclaimers</a></li>
            <li><a href="#termination">Termination</a></li>
            <li><a href="#disputes">Dispute Resolution</a></li>
            <li><a href="#governing-law">Governing Law &amp; Jurisdiction</a></li>
            <li><a href="#amendments">Amendments</a></li>
            <li><a href="#contact">Contact Information</a></li>
        </ol>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="acceptance">1. Acceptance of Terms</h2>
    <p>By accessing or using the Wangari platform ("Service"), including the website at <a href="https://wangari.imeantech.com">wangari.imeantech.com</a>, the desktop application, and all related APIs, you ("User," "you," or "your") agree to be bound by these Terms &amp; Conditions ("Terms").</p>
    <p>If you are using the Service on behalf of an organization, you represent that you have authority to bind that organization to these Terms.</p>
    <div class="legal-highlight">
        <h4>⚡ Kenya Legal Compliance</h4>
        <p>These Terms are governed by the laws of the Republic of Kenya, including the Data Protection Act, 2019, the Consumer Protection Act, 2012, the Electronic Transactions Act, 2008, and the Computer Misuse and Cybercrimes Act, 2018.</p>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="definitions">2. Definitions</h2>
    <ul>
        <li><strong>"Service"</strong> — The Wangari farm management platform, including web application, desktop application, APIs, and all associated tools.</li>
        <li><strong>"Farm Owner"</strong> — A registered user who creates a farm account and manages team members.</li>
        <li><strong>"Team Member"</strong> — Any user invited to join a farm via a Farm Code.</li>
        <li><strong>"Farm Code"</strong> — A unique alphanumeric code (e.g., WGRI-XXXX-XXXX-XXXX) used to invite team members to a farm.</li>
        <li><strong>"Customer Data"</strong> — All data entered, uploaded, or generated by users within the Service, including animal records, inventory, financial data, and user profiles.</li>
        <li><strong>"Subscription"</strong> — A paid plan granting access to premium features of the Service.</li>
        <li><strong>"Personal Data"</strong> — As defined under the Kenya Data Protection Act, 2019, any information relating to an identified or identifiable natural person.</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="account">3. Account Registration &amp; Eligibility</h2>
    <h3>3.1 Eligibility</h3>
    <p>You must be at least 18 years old (or the age of majority in your jurisdiction) to create an account. By registering, you represent and warrant that:</p>
    <ul>
        <li>You are at least 18 years of age</li>
        <li>You have the legal capacity to enter into binding agreements</li>
        <li>All information you provide is accurate and complete</li>
        <li>You will maintain the accuracy of your information</li>
    </ul>

    <h3>3.2 Account Security</h3>
    <p>You are responsible for maintaining the confidentiality of your account credentials. You must:</p>
    <ul>
        <li>Use a strong, unique password (minimum 8 characters recommended)</li>
        <li>Enable two-factor authentication when available</li>
        <li>Notify us immediately of any unauthorized access</li>
        <li>Not share your account credentials with others</li>
    </ul>

    <h3>3.3 Farm Owner Responsibilities</h3>
    <p>Farm Owners are responsible for:</p>
    <ul>
        <li>Managing team access and permissions within their farm</li>
        <li>Ensuring team members comply with these Terms</li>
        <li>The accuracy and legality of data entered into the Service</li>
        <li>All financial transactions processed through the Service</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="subscription">4. Subscription Plans &amp; Payment</h2>
    <h3>4.1 Free Tier</h3>
    <p>Wangari offers a free tier with limited features. The free tier includes:</p>
    <ul>
        <li>Up to 1 farm</li>
        <li>Up to 5 team members</li>
        <li>Basic inventory and order management</li>
        <li>Community support</li>
    </ul>

    <h3>4.2 Paid Plans</h3>
    <p>Premium plans unlock additional features. By subscribing, you agree to:</p>
    <ul>
        <li>Pay all applicable fees in advance on the billing date</li>
        <li>Provide valid payment information (M-Pesa, bank transfer, or credit card)</li>
        <li>Authorization for recurring charges until cancellation</li>
    </ul>

    <h3>4.3 Pricing &amp; Changes</h3>
    <p>All prices are quoted in Kenya Shillings (KES) unless otherwise stated. We reserve the right to modify pricing with 30 days' written notice. Price changes take effect at your next billing cycle.</p>

    <h3>4.4 Refund Policy</h3>
    <p>Annual subscriptions may be refunded within 14 days of purchase if the Service has not been materially used. Monthly subscriptions are non-refundable. This complies with the Kenya Consumer Protection Act, 2012, Section 12.</p>

    <h3>4.5 Taxes</h3>
    <p>Subscription fees are exclusive of applicable taxes. Kenyan VAT (16%) will be charged where required by the Kenya Revenue Authority (KRA).</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="farm-codes">5. Farm Codes &amp; Team Access</h2>
    <h3>5.1 Farm Code Generation</h3>
    <p>Farm Owners may generate Farm Codes to invite team members. Farm Codes:</p>
    <ul>
        <li>Are unique to each farm and each role</li>
        <li>May have expiry dates and maximum usage limits</li>
        <li>Grant specific role-based access (Field Worker, Stock Manager, Sales Staff, etc.)</li>
        <li>Can be revoked at any time by the Farm Owner</li>
    </ul>

    <h3>5.2 Role-Based Access</h3>
    <p>Each team role has specific permissions. Users must not attempt to access features outside their assigned role. Role definitions are available in the application settings.</p>

    <h3>5.3 Team Member Obligations</h3>
    <p>Team members who join via a Farm Code agree to:</p>
    <ul>
        <li>Use the Service only for purposes authorized by the Farm Owner</li>
        <li>Not share their access with unauthorized persons</li>
        <li>Comply with all farm policies set by the Farm Owner</li>
        <li>Report any security concerns immediately</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="license">6. Software License &amp; Intellectual Property</h2>
    <h3>6.1 Limited License</h3>
    <p>We grant you a limited, non-exclusive, non-transferable, revocable license to use the Service for your internal business purposes, subject to these Terms.</p>

    <h3>6.2 Restrictions</h3>
    <p>You shall NOT:</p>
    <ul>
        <li>Copy, modify, or distribute the Service or any part thereof</li>
        <li>Reverse engineer, decompile, or disassemble the desktop application</li>
        <li>Remove or alter any proprietary notices or labels</li>
        <li>Use the Service to build a competing product</li>
        <li>Resell, sublicense, or lease the Service to third parties</li>
        <li>Use automated means (bots, scrapers) to access the Service</li>
    </ul>

    <h3>6.3 Desktop Application</h3>
    <p>The Wangari Desktop application includes a hardware fingerprint-based license. Each license is tied to a specific machine and may not be transferred without written authorization.</p>

    <h3>6.4 Offline Grace Period</h3>
    <p>The desktop application includes a 14-day offline grace period. After this period, the application requires an internet connection to verify the license. This is a security measure, not a restriction on your data access.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="data">7. Data Ownership &amp; Portability</h2>

    <div class="legal-highlight">
        <h4>📊 Your Data Belongs to You</h4>
        <p>You retain all rights, title, and interest in your Customer Data. We do not claim ownership over any data you enter into the Service. This complies with Section 25 of the Kenya Data Protection Act, 2019.</p>
    </div>

    <h3>7.1 Data Access</h3>
    <p>You may export your data at any time in standard formats (CSV, JSON, PDF). We will provide data export within 30 days of any request, free of charge, as required by the Data Protection Act.</p>

    <h3>7.2 Data Portability</h3>
    <p>Under the Kenya Data Protection Act, 2019, Section 26, you have the right to data portability — to receive your personal data in a structured, commonly used, machine-readable format.</p>

    <h3>7.3 Data Deletion</h3>
    <p>Upon termination of your account:</p>
    <ul>
        <li>Your data will be retained for 90 days (to allow recovery)</li>
        <li>After 90 days, all data is permanently deleted from our servers</li>
        <li>Backups are purged within 180 days</li>
        <li>You may request early deletion by contacting us</li>
    </ul>

    <h3>7.4 Anonymized Analytics</h3>
    <p>We may collect anonymized, aggregated data for service improvement. This data cannot identify you or your farm. You may opt out at any time.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="privacy">8. Privacy &amp; Data Protection</h2>
    <p>Your privacy is governed by our <a href="/Frontend/pages/privacy.php">Privacy Policy</a>, which forms part of these Terms.</p>
    <h3>8.1 Kenya Data Protection Act, 2019 Compliance</h3>
    <p>We comply with the Kenya Data Protection Act, 2019, including:</p>
    <ul>
        <li><strong>Registration</strong> — We are registered with the Office of the Data Protection Commissioner (ODPC)</li>
        <li><strong>Lawful Basis</strong> — We process data based on consent, contractual necessity, and legitimate interest</li>
        <li><strong>Data Protection Impact Assessment</strong> — Conducted for all high-risk processing activities</li>
        <li><strong>Data Protection Officer</strong> — Appointed to oversee compliance</li>
        <li><strong>Breach Notification</strong> — We notify the ODPC within 72 hours of any data breach</li>
        <li><strong>Data Subject Rights</strong> — You have the right to access, rectify, erase, restrict, and port your data</li>
    </ul>

    <h3>8.2 International Data Transfers</h3>
    <p>Where data is transferred outside Kenya, we ensure adequate safeguards are in place, including Standard Contractual Clauses and adequacy determinations as required by the Data Protection Act.</p>

    <h3>8.3 Children's Data</h3>
    <p>The Service is not directed to individuals under 18. We do not knowingly collect data from children.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="acceptable-use">9. Acceptable Use Policy</h2>
    <p>You agree not to use the Service to:</p>
    <ul>
        <li>Violate any applicable law, including the Kenya Computer Misuse and Cybercrimes Act, 2018</li>
        <li>Transmit malware, viruses, or harmful code</li>
        <li>Attempt to gain unauthorized access to other accounts or systems</li>
        <li>Engage in fraudulent activity or misrepresent your identity</li>
        <li>Harvest or collect personal information of other users</li>
        <li>Spam, harass, or abuse other users</li>
        <li>Use the Service for illegal gambling, narcotics, or weapons trading</li>
        <li>Interfere with the proper functioning of the Service</li>
    </ul>

    <h3>9.1 Violations</h3>
    <p>Violations may result in:</p>
    <ul>
        <li>Warning notice</li>
        <li>Temporary suspension of access</li>
        <li>Permanent account termination</li>
        <li>Referral to law enforcement (for criminal violations)</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="availability">10. Service Availability &amp; SLA</h2>
    <h3>10.1 Uptime Commitment</h3>
    <p>We target 99.9% uptime for the web platform, excluding scheduled maintenance. Scheduled maintenance is communicated at least 48 hours in advance.</p>

    <h3>10.2 Offline Mode</h3>
    <p>The desktop application includes offline functionality. Data created offline will sync when an internet connection is restored. We are not liable for data conflicts during sync.</p>

    <h3>10.3 Support</h3>
    <ul>
        <li><strong>Free Tier</strong> — Community support via email</li>
        <li><strong>Professional</strong> — Email support with 24-hour response</li>
        <li><strong>Enterprise</strong> — Priority support with 4-hour response</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="liability">11. Limitation of Liability</h2>
    <p>TO THE MAXIMUM EXTENT PERMITTED BY LAW:</p>
    <ul>
        <li>The Service is provided "AS IS" without warranties of any kind</li>
        <li>We shall not be liable for indirect, incidental, special, consequential, or punitive damages</li>
        <li>Our total liability shall not exceed the amount paid by you in the 12 months preceding the claim</li>
        <li>We are not liable for data loss, business interruption, or lost profits</li>
    </ul>

    <div class="legal-highlight">
        <h4>⚠️ Important — Financial Decisions</h4>
        <p>Wangari is a management tool, not a financial advisor. All financial decisions made based on data in the Service are your responsibility. We recommend consulting qualified professionals for financial, veterinary, or legal advice.</p>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="warranty">12. Warranties &amp; Disclaimers</h2>
    <p>We warrant that:</p>
    <ul>
        <li>The Service will perform materially as described in our documentation</li>
        <li>We have the right to provide the Service</li>
        <li>The Service will not intentionally introduce malware</li>
    </ul>
    <p>We disclaim all other warranties, including implied warranties of merchantability, fitness for a particular purpose, and non-infringement.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="termination">13. Termination</h2>
    <h3>13.1 By You</h3>
    <p>You may terminate your account at any time by:</p>
    <ul>
        <li>Using the account settings page</li>
        <li>Contacting us at <a href="mailto:support@imeantech.com">support@imeantech.com</a></li>
    </ul>

    <h3>13.2 By Us</h3>
    <p>We may terminate or suspend your access if:</p>
    <ul>
        <li>You violate these Terms</li>
        <li>Your subscription expires and is not renewed</li>
        <li>We are required to do so by law</li>
        <li>We discontinue the Service (with 90 days' notice)</li>
    </ul>

    <h3>13.3 Effect of Termination</h3>
    <ul>
        <li>Your license to use the Service ends immediately</li>
        <li>Data is retained for 90 days for recovery</li>
        <li>Provisions that by their nature should survive will survive (including limitations of liability, dispute resolution, and data ownership)</li>
    </ul>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="disputes">14. Dispute Resolution</h2>
    <h3>14.1 Informal Resolution</h3>
    <p>Before filing any formal dispute, you agree to contact us at <a href="mailto:legal@imeantech.com">legal@imeantech.com</a> and attempt to resolve the matter informally for at least 30 days.</p>

    <h3>14.2 Mediation</h3>
    <p>If informal resolution fails, the parties agree to submit to mediation under the Nairobi Centre for International Arbitration (NCIA) Mediation Rules.</p>

    <h3>14.3 Arbitration</h3>
    <p>If mediation fails, disputes shall be resolved by binding arbitration under the NCIA Arbitration Rules, conducted in Nairobi, Kenya, in the English language.</p>

    <h3>14.4 Class Action Waiver</h3>
    <p>You agree that any dispute resolution proceedings will be conducted only on an individual basis and not in a class, consolidated, or representative action.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="governing-law">15. Governing Law &amp; Jurisdiction</h2>
    <p>These Terms are governed by and construed in accordance with the laws of the Republic of Kenya. Subject to the arbitration clause, the courts of Nairobi shall have exclusive jurisdiction.</p>

    <div class="legal-highlight">
        <h4>🇰🇪 Kenya-Specific Provisions</h4>
        <p>These Terms are subject to the following Kenyan laws: Data Protection Act 2019, Consumer Protection Act 2012, Electronic Transactions Act 2008, Computer Misuse and Cybercrimes Act 2018, Kenya Information and Communications Act 1998, and the Sale of Goods Act (Cap 31).</p>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="amendments">16. Amendments</h2>
    <p>We may update these Terms from time to time. Material changes will be communicated via:</p>
    <ul>
        <li>Email notification to the address on your account</li>
        <li>In-app notification at least 30 days before changes take effect</li>
        <li>Updated "Last Updated" date on this page</li>
    </ul>
    <p>Continued use of the Service after changes take effect constitutes acceptance of the updated Terms.</p>

    <!-- ════════════════════════════════════════════════════ -->
    <h2 id="contact">17. Contact Information</h2>
    <p>For questions about these Terms:</p>
    <ul>
        <li><strong>Email:</strong> <a href="mailto:legal@imeantech.com">legal@imeantech.com</a></li>
        <li><strong>Support:</strong> <a href="mailto:support@imeantech.com">support@imeantech.com</a></li>
        <li><strong>Company:</strong> iMeanTech Limited</li>
        <li><strong>Address:</strong> Nairobi, Kenya</li>
        <li><strong>Data Protection Officer:</strong> <a href="mailto:dpo@imeantech.com">dpo@imeantech.com</a></li>
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
                    <li><a href="/Frontend/pages/privacy.php">Privacy Policy</a></li>
                    <li><a href="/Frontend/pages/terms.php">Terms & Conditions</a></li>
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
