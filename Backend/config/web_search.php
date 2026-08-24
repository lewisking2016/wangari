<?php
/**
 * Web Search Helper for Wangari AI
 * 
 * Uses Jina Reader API for web search and content extraction
 * This allows the AI to research topics when local knowledge is insufficient
 */

// ═══════════════════════════════════════════════════════════════
// Jina Reader API Configuration
// ═══════════════════════════════════════════════════════════════

define('JINA_READER_URL', 'https://r.jina.ai/');
define('JINA_SEARCH_URL', 'https://s.jina.ai/');

// ═══════════════════════════════════════════════════════════════
// Web Search Functions
// ═══════════════════════════════════════════════════════════════

/**
 * Search the web for information
 */
function wangari_web_search($query, $numResults = 5) {
    $url = JINA_SEARCH_URL . urlencode($query);
    
    $response = wangari_http_get($url, [
        'Accept: application/json',
        'X-Retain-Images: none',
    ]);
    
    if (!$response) {
        return [];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['data']) || !is_array($data['data'])) {
        return [];
    }
    
    $results = [];
    foreach (array_slice($data['data'], 0, $numResults) as $item) {
        $results[] = [
            'title' => $item['title'] ?? '',
            'url' => $item['url'] ?? '',
            'content' => $item['content'] ?? '',
            'snippet' => $item['snippet'] ?? '',
        ];
    }
    
    return $results;
}

/**
 * Read content from a URL
 */
function wangari_read_url($url, $maxChars = 5000) {
    $response = wangari_http_get($url, [
        'Accept: text/plain',
        'X-Retain-Images: none',
    ]);
    
    if (!$response) {
        return null;
    }
    
    // Truncate to maxChars
    if (strlen($response) > $maxChars) {
        $response = substr($response, 0, $maxChars) . '...';
    }
    
    return $response;
}

/**
 * Research a topic deeply
 */
function wangari_research_topic($topic) {
    $research = [];
    
    // Step 1: Search for the topic
    $searchResults = wangari_web_search($topic, 3);
    $research['search_results'] = $searchResults;
    
    // Step 2: Read top 2 results for detailed content
    $detailedContent = [];
    foreach (array_slice($searchResults, 0, 2) as $result) {
        if (!empty($result['url'])) {
            $content = wangari_read_url($result['url'], 3000);
            if ($content) {
                $detailedContent[] = [
                    'source' => $result['url'],
                    'content' => $content,
                ];
            }
        }
    }
    $research['detailed_content'] = $detailedContent;
    
    // Step 3: Search for Kenya-specific information
    $kenyaResults = wangari_web_search($topic . ' Kenya', 3);
    $research['kenya_specific'] = $kenyaResults;
    
    return $research;
}

/**
 * Search for current market prices in Kenya
 */
function wangari_search_market_prices($commodity) {
    $query = $commodity . ' price Kenya KES current 2024 2025';
    return wangari_web_search($query, 5);
}

/**
 * Search for farming best practices
 */
function wangari_search_best_practices($topic) {
    $query = $topic . ' best practices Kenya farming';
    return wangari_web_search($query, 5);
}

/**
 * Search for disease information
 */
function wangari_search_disease_info($disease, $animal) {
    $query = $disease . ' ' . $animal . ' symptoms treatment Kenya';
    return wangari_web_search($query, 5);
}

// ═══════════════════════════════════════════════════════════════
// HTTP Helper
// ═══════════════════════════════════════════════════════════════

function wangari_http_get($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $defaultHeaders = [
        'User-Agent: Wangari-Farm-AI/1.0',
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return $response;
    }
    
    return null;
}
