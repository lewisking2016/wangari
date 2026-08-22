<?php
/**
 * FAQ Page
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'FAQs - Wangari';

include '../includes/header.php';
?>

<!-- Breadcrumb -->
<!-- Hero Section -->
<section class="g-page-hero">
    <div class="g-container">
        <h1>Frequently Asked <span class="g-serif">Questions</span></h1>
        <p>Find answers to common questions about our products and services.</p>
    </div>
</section>

<!-- FAQ Sections -->
<section style="padding: var(--space-3xl) 0; background-color: var(--white);">
    <div class="container" style="max-width: 900px;">
        <!-- Product FAQs -->
        <div style="margin-bottom: var(--space-3xl);">
            <h2 style="margin-bottom: var(--space-xl);">Products & Chickens</h2>

            <div class="faq-item fade-up">
                <div class="faq-question">
                    <span>What breeds of chickens do you offer?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    We offer three main breeds: Ross 308 and Cobb 500 for broilers, ISA Brown for layers, and Lohmann for white egg production. All birds are sourced from certified hatcheries and meet international standards.
                </div>
            </div>

            <div class="faq-item fade-up stagger-1">
                <div class="faq-question">
                    <span>Are your chicks vaccinated?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Yes, all day-old chicks are vaccinated against common poultry diseases including Newcastle, Gumboro, and Marek's disease before delivery. We provide vaccination records with each order.
                </div>
            </div>

            <div class="faq-item fade-up stagger-2">
                <div class="faq-question">
                    <span>What's the difference between broilers and layers?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Broilers are meat chickens bred for rapid growth and reach market weight (2-2.5kg) in 6-7 weeks. Layers are egg-laying chickens that produce 300+ eggs per year over 18+ months of production.
                </div>
            </div>

            <div class="faq-item fade-up stagger-3">
                <div class="faq-question">
                    <span>How do I care for day-old chicks?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    New chicks need: warm brooder (35°C for first week), clean bedding, starter feed, fresh water, and protection from predators. We provide detailed care guides with every order and offer phone support.
                </div>
            </div>

            <div class="faq-item fade-up stagger-4">
                <div class="faq-question">
                    <span>What's the hatch rate guarantee?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    We guarantee 95% hatch rate on all day-old chicks. If hatch rate falls below 95%, we provide replacement chicks at no charge. Proper care is essential to achieve this rate.
                </div>
            </div>
        </div>

        <!-- Feed FAQs -->
        <div style="margin-bottom: var(--space-3xl);">
            <h2 style="margin-bottom: var(--space-xl);">Animal Feeds</h2>

            <div class="faq-item fade-up">
                <div class="faq-question">
                    <span>What ingredients are in your feeds?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Our feeds contain quality grains (maize), protein sources (soybean meal), minerals, vitamins, and probiotics. All ingredients meet food safety standards and are sourced from certified suppliers. Detailed ingredient lists available on request.
                </div>
            </div>

            <div class="faq-item fade-up stagger-1">
                <div class="faq-question">
                    <span>When should I switch from starter to grower feed?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Switch from starter (0-4 weeks) to grower (4-8 weeks) at 4 weeks of age. For broilers, use finisher feed (8+ weeks) until market. For layers, switch to layer mash at 16 weeks when they start laying.
                </div>
            </div>

            <div class="faq-item fade-up stagger-2">
                <div class="faq-question">
                    <span>How much feed does a chicken need daily?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Day-old chicks: 5-10g/day. Growing chicks: 20-40g/day. Adult layers: 100-120g/day. Adult broilers: 100-150g/day. Feed consumption varies by breed, age, and environmental conditions.
                </div>
            </div>

            <div class="faq-item fade-up stagger-3">
                <div class="faq-question">
                    <span>How long can I store feed?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Feed should be stored in cool, dry conditions away from moisture and pests. Properly stored feed lasts 2-3 months. Once opened, use within 2-4 weeks for optimal nutrition. Store in metal bins or sealed containers.
                </div>
            </div>

            <div class="faq-item fade-up stagger-4">
                <div class="faq-question">
                    <span>Do you offer bulk discounts on feed?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Yes! Orders of 6-20 bags: 5% discount. Orders of 21-50 bags: 10% discount. Orders of 51+ bags: 15% discount. Contact our sales team for custom pricing on very large orders.
                </div>
            </div>
        </div>

        <!-- Ordering FAQs -->
        <div style="margin-bottom: var(--space-3xl);">
            <h2 style="margin-bottom: var(--space-xl);">Ordering & Delivery</h2>

            <div class="faq-item fade-up">
                <div class="faq-question">
                    <span>How do I place an order?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    You can contact the Wangari team at info@imeantech.com or +254 114 971 070 for support and order enquiries.
                </div>
            </div>

            <div class="faq-item fade-up stagger-1">
                <div class="faq-question">
                    <span>What's the minimum order value?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Minimum order is KES 2,000. Free delivery is offered on orders above KES 5,000 within Wangari County. Smaller orders within Wangari incur a KES 500 delivery charge.
                </div>
            </div>

            <div class="faq-item fade-up stagger-2">
                <div class="faq-question">
                    <span>How quickly can you deliver?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Orders within Wangari: 1-2 days. Orders to Kakamega/Kisumu: 2-3 days. Orders to Kisii: 3-4 days. Emergency orders can be arranged for same-day delivery in Wangari. Call us for urgent requests.
                </div>
            </div>

            <div class="faq-item fade-up stagger-3">
                <div class="faq-question">
                    <span>What payment methods do you accept?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    We accept M-Pesa, bank transfers, and cash on delivery (COD). Online orders require M-Pesa payment or bank deposit. COD available for orders within Wangari County.
                </div>
            </div>

            <div class="faq-item fade-up stagger-4">
                <div class="faq-question">
                    <span>Can you deliver outside Wangari County?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Yes! We deliver to Kakamega, Kisumu, and Kisii counties. Delivery charges vary by location (KES 500-1,200). We're expanding to more regions. Contact us for custom delivery arrangements.
                </div>
            </div>
        </div>

        <!-- Payment & Returns FAQs -->
        <div style="margin-bottom: var(--space-3xl);">
            <h2 style="margin-bottom: var(--space-xl);">Payment & Returns</h2>

            <div class="faq-item fade-up">
                <div class="faq-question">
                    <span>Is M-Pesa payment secure?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Absolutely. M-Pesa is managed by Safaricom and uses encrypted transactions. Your payment details are safe. We use official M-Pesa API for all transactions with no hidden charges.
                </div>
            </div>

            <div class="faq-item fade-up stagger-1">
                <div class="faq-question">
                    <span>What's your return/refund policy?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    For defective products: Full refund within 24 hours of delivery with proof. For chicks: Replacement chicks provided if mortality exceeds 5% in first 48 hours. For feeds: Exchange if damaged or contaminated upon delivery.
                </div>
            </div>

            <div class="faq-item fade-up stagger-2">
                <div class="faq-question">
                    <span>Do you offer payment plans?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    For bulk orders (51+ bags or 500+ chicks), we offer 2-week payment terms for established customers. Contact our sales team to discuss flexible payment arrangements.
                </div>
            </div>

            <div class="faq-item fade-up stagger-3">
                <div class="faq-question">
                    <span>Is there a warranty on the chicks?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Yes. All chicks come with a 48-hour survival warranty. If mortality exceeds 5%, we provide replacement chicks at no cost. Proper brooding conditions must be maintained. We provide care instructions.
                </div>
            </div>
        </div>

        <!-- Farm Management FAQs -->
        <div>
            <h2 style="margin-bottom: var(--space-xl);">Farm Management & Support</h2>

            <div class="faq-item fade-up">
                <div class="faq-question">
                    <span>Do you offer farm consulting services?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Yes! We provide free phone support and paid farm visits. Our team can help with facility design, biosafety protocols, feed optimization, and productivity improvements. Contact us for consultation rates.
                </div>
            </div>

            <div class="faq-item fade-up stagger-1">
                <div class="faq-question">
                    <span>What's the best season to start farming?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Poultry farming year-round, but best results in dry seasons (Jan-Feb, July-Sept). Rainy seasons require extra biosafety precautions. We can help plan your farming calendar for optimal productivity.
                </div>
            </div>

            <div class="faq-item fade-up stagger-2">
                <div class="faq-question">
                    <span>How do I join the digital farm management system?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Customers can log into our dashboard to track flock records, production data, expenses, and get personalized recommendations. Free for all customers. Mobile app coming soon. Register at login page.
                </div>
            </div>

            <div class="faq-item fade-up stagger-3">
                <div class="faq-question">
                    <span>What should I do if birds get sick?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Call +254 114 971 070 for urgent support. Isolate sick birds, improve ventilation, and ensure clean water while waiting for professional guidance.
                </div>
            </div>

            <div class="faq-item fade-up stagger-4">
                <div class="faq-question">
                    <span>Can I visit the farm?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-answer">
                    Farm visits can be arranged by appointment. Email info@imeantech.com or call +254 114 971 070 to schedule.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Still Need Help -->
<section style="padding: var(--space-3xl) 0; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: var(--white); text-align: center;">
    <div class="container">
        <h2 style="color: var(--white); margin-bottom: var(--space-md);">Didn't Find Your Answer?</h2>
        <p style="opacity: 0.9; margin-bottom: var(--space-xl);">Our team is always ready to help.</p>
        <a href="/Frontend/pages/contact.php" class="btn btn-accent">Contact Us</a>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            
            // Close all other items
            faqItems.forEach(otherItem => {
                otherItem.classList.remove('active');
            });
            
            // Toggle current item
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
});
</script>

<?php
include '../includes/footer.php';
?>
