<?php
/**
 * SEO & Schema Markup Utilities
 * Implements Schema.org microdata for search engines
 */
declare(strict_types=1);

/**
 * Generate Organization schema markup
 */
function generateOrganizationSchema(): string
{
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Wangari',
        'alternateName' => 'Wangari Farm Platform',
        'url' => 'https://wangari.farm',
        'logo' => 'https://wangari.farm/Frontend/images/wangari-logo.png',
        'description' => 'Leading poultry supplier in East Africa. Premium chickens, eggs, and animal feeds.',
        'sameAs' => [
            'https://www.facebook.com/wangari.farm',
            'https://twitter.com/wangarifarm',
            'https://www.instagram.com/wangari.farm'
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Nasira AC sub-location, Busibwabo Location',
            'addressLocality' => 'Nairobi',
            'addressRegion' => 'Nairobi County',
            'postalCode' => '50400',
            'addressCountry' => 'KE'
        ],
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'contactType' => 'Customer Service',
            'telephone' => '+254-727-585599',
            'email' => 'info@wangari.farm',
            'areaServed' => ['KE', 'UG', 'TZ'],
            'availableLanguage' => ['en', 'sw']
        ]
    ], JSON_UNESCAPED_SLASHES);
}

/**
 * Generate LocalBusiness schema markup
 */
function generateLocalBusinessSchema(): string
{
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => 'Wangari',
        'image' => 'https://wangari.com/assets/images/farm.jpg',
        'description' => 'Poultry farming, egg production, and premium animal feeds',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Nasira AC sub-location',
            'addressLocality' => 'Wangari',
            'addressCountry' => 'KE'
        ],
        'telephone' => '+254-727-585599',
        'priceRange' => 'KES 500 - 50000',
        'openingHoursSpecification' => [
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'opens' => '08:00',
                'closes' => '18:00'
            ],
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'Saturday',
                'opens' => '08:00',
                'closes' => '14:00'
            ]
        ],
        'sameAs' => [
            'https://www.facebook.com/wangari',
            'https://www.instagram.com/wangari'
        ]
    ], JSON_UNESCAPED_SLASHES);
}

/**
 * Generate Product schema markup
 */
function generateProductSchema(array $product): string
{
    return json_encode([
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $product['name'],
        'image' => $product['image'] ?? '',
        'description' => $product['description'] ?? '',
        'brand' => [
            '@type' => 'Brand',
            'name' => 'Wangari'
        ],
        'offers' => [
            '@type' => 'Offer',
            'url' => $product['url'] ?? '',
            'priceCurrency' => 'KES',
            'price' => $product['price'] ?? '0',
            'availability' => $product['availability'] ?? 'InStock'
        ],
        'aggregateRating' => $product['rating'] ?? null
    ], JSON_UNESCAPED_SLASHES);
}

/**
 * Generate BreadcrumbList schema
 */
function generateBreadcrumbSchema(array $breadcrumbs): string
{
    $items = [];
    foreach ($breadcrumbs as $index => $crumb) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $crumb['name'],
            'item' => $crumb['url']
        ];
    }

    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items
    ], JSON_UNESCAPED_SLASHES);
}

/**
 * Generate FAQPage schema
 */
function generateFAQSchema(array $faqs): string
{
    $mainEntity = [];
    foreach ($faqs as $faq) {
        $mainEntity[] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer']
            ]
        ];
    }

    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $mainEntity
    ], JSON_UNESCAPED_SLASHES);
}

/**
 * Generate meta tags for page
 */
function generateMetaTags(array $seo): string
{
    $tags = "\n";
    
    // Open Graph tags
    if (!empty($seo['title'])) {
        $tags .= '<meta property="og:title" content="' . htmlspecialchars($seo['title'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    
    if (!empty($seo['description'])) {
        $tags .= '<meta property="og:description" content="' . htmlspecialchars($seo['description'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    
    if (!empty($seo['image'])) {
        $tags .= '<meta property="og:image" content="' . htmlspecialchars($seo['image'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    
    if (!empty($seo['url'])) {
        $tags .= '<meta property="og:url" content="' . htmlspecialchars($seo['url'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    
    // Twitter Card tags
    $tags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    if (!empty($seo['title'])) {
        $tags .= '<meta name="twitter:title" content="' . htmlspecialchars($seo['title'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    if (!empty($seo['description'])) {
        $tags .= '<meta name="twitter:description" content="' . htmlspecialchars($seo['description'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    
    return $tags;
}

/**
 * Generate sitemap entry
 */
function generateSitemapEntry(string $url, string $lastMod = '', string $changeFreq = 'weekly', string $priority = '0.8'): string
{
    $xml = "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
    
    if (!empty($lastMod)) {
        $xml .= "    <lastmod>" . htmlspecialchars($lastMod, ENT_XML1, 'UTF-8') . "</lastmod>\n";
    }
    
    $xml .= "    <changefreq>" . htmlspecialchars($changeFreq, ENT_XML1, 'UTF-8') . "</changefreq>\n";
    $xml .= "    <priority>" . htmlspecialchars($priority, ENT_XML1, 'UTF-8') . "</priority>\n";
    $xml .= "  </url>\n";
    
    return $xml;
}

/**
 * Generate robots.txt content
 */
function generateRobotsTxt(): string
{
    return <<<ROBOTS
User-agent: *
Allow: /

Disallow: /admin/
Disallow: /api/
Disallow: /config/
Disallow: /logs/

Sitemap: https://wangari.com/sitemap.xml
ROBOTS;
}

/**
 * Optimize meta description length
 */
function optimizeMetaDescription(string $description, int $maxLength = 160): string
{
    $description = trim(strip_tags($description));
    
    if (strlen($description) <= $maxLength) {
        return $description;
    }
    
    $truncated = substr($description, 0, $maxLength - 3);
    return substr($truncated, 0, strrpos($truncated, ' ')) . '...';
}

/**
 * Generate structured data for reviews
 */
function generateReviewSchema(array $review): string
{
    return json_encode([
        '@context' => 'https://schema.org/',
        '@type' => 'Review',
        'reviewRating' => [
            '@type' => 'Rating',
            'ratingValue' => $review['rating'],
            'bestRating' => '5',
            'worstRating' => '1'
        ],
        'author' => [
            '@type' => 'Person',
            'name' => $review['author'] ?? 'Anonymous'
        ],
        'reviewBody' => $review['body'] ?? '',
        'datePublished' => $review['date'] ?? date('Y-m-d')
    ], JSON_UNESCAPED_SLASHES);
}

/**
 * Canonicalize URL
 */
function getCanonicalURL(string $currentUrl = ''): string
{
    if (empty($currentUrl)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'wangari.com';
        $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
        $currentUrl = $protocol . $host . $path;
    }
    
    return rtrim($currentUrl, '/');
}

?>
