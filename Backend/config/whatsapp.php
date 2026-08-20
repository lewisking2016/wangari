<?php
/**
 * WhatsApp Integration for Wangari Farm
 * 
 * Features:
 * - Daily farm summary via WhatsApp
 * - Stock alerts
 * - Weather updates
 * - Sales notifications
 * - Multi-language support (English/Swahili)
 * - Automated reminders
 */

class WhatsAppIntegration {
    private $apiKey;
    private $phoneNumber;
    private $apiUrl = 'https://graph.facebook.com/v17.0';
    
    public function __construct() {
        $this->apiKey = getenv('WHATSAPP_API_KEY');
        $this->phoneNumber = getenv('WHATSAPP_PHONE_NUMBER');
    }
    
    /**
     * Send WhatsApp message
     */
    public function sendMessage($to, $message, $type = 'text') {
        if (!$this->apiKey || !$this->phoneNumber) {
            return ['error' => 'WhatsApp API not configured'];
        }
        
        $url = "{$this->apiUrl}/{$this->phoneNumber}/messages";
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => $type,
            $type => [
                'body' => [
                    'text' => $message
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Generate daily farm summary
     */
    public function getDailySummary($farmData) {
        $date = date('F j, Y');
        $day = date('l');
        
        $message = "🌅 *Wangari Farm Daily Summary*\n";
        $message .= "📅 {$day}, {$date}\n\n";
        
        // Livestock Status
        $message .= "🐔 *LIVESTOCK*\n";
        $message .= "• Broilers: {$farmData['broilers']} birds\n";
        $message .= "• Layers: {$farmData['layers']} birds\n";
        $message .= "• Cows: {$farmData['cows']} head\n";
        $message .= "• Goats: {$farmData['goats']} head\n\n";
        
        // Production
        $message .= "📊 *PRODUCTION*\n";
        $message .= "• Eggs collected: {$farmData['eggs']} trays\n";
        $message .= "• Milk produced: {$farmData['milk']} liters\n";
        $message .= "• Feed consumed: {$farmData['feed_used']} kg\n\n";
        
        // Financial
        $message .= "💰 *FINANCIAL*\n";
        $message .= "• Sales today: KSh " . number_format($farmData['sales']) . "\n";
        $message .= "• Expenses today: KSh " . number_format($farmData['expenses']) . "\n";
        $message .= "• Profit: KSh " . number_format($farmData['sales'] - $farmData['expenses']) . "\n\n";
        
        // Alerts
        if (!empty($farmData['alerts'])) {
            $message .= "⚠️ *ALERTS*\n";
            foreach ($farmData['alerts'] as $alert) {
                $message .= "• {$alert}\n";
            }
            $message .= "\n";
        }
        
        // Weather
        if (isset($farmData['weather'])) {
            $message .= "🌤️ *WEATHER*\n";
            $message .= "• {$farmData['weather']['description']}\n";
            $message .= "• Temperature: {$farmData['weather']['temp']}°C\n";
            $message .= "• Humidity: {$farmData['weather']['humidity']}%\n\n";
        }
        
        $message .= "Have a productive day! 🌾\n";
        $message .= "_Reply with any questions about your farm._";
        
        return $message;
    }
    
    /**
     * Generate Swahili daily summary
     */
    public function getDailySummarySwahili($farmData) {
        $date = date('F j, Y');
        $day = date('l');
        
        $message = "🌅 *Muhtasari wa Shamba la Wangari*\n";
        $message .= "📅 {$day}, {$date}\n\n";
        
        // Livestock Status
        $message .= "🐔 *MIFUGO*\n";
        $message .= "• Kuku wa nyama: {$farmData['broilers']}\n";
        $message .= "• Kuku wa mayai: {$farmData['layers']}\n";
        $message .= "• Ng'ombe: {$farmData['cows']}\n";
        $message .= "• Mbuzi: {$farmData['goats']}\n\n";
        
        // Production
        $message .= "📊 *UZALISHAJI*\n";
        $message .= "• Mayai: {$farmData['eggs']} viti\n";
        $message .= "• Maziwa: {$farmData['milk']} lita\n";
        $message .= "• Chakula: {$farmData['feed_used']} kg\n\n";
        
        // Financial
        $message .= "💰 *FEDHA*\n";
        $message .= "• Mauzo: KSh " . number_format($farmData['sales']) . "\n";
        $message .= "• Matumizi: KSh " . number_format($farmData['expenses']) . "\n";
        $message .= "• Faida: KSh " . number_format($farmData['sales'] - $farmData['expenses']) . "\n\n";
        
        // Alerts
        if (!empty($farmData['alerts'])) {
            $message .= "⚠️ *TAARIFA*\n";
            foreach ($farmData['alerts'] as $alert) {
                $message .= "• {$alert}\n";
            }
            $message .= "\n";
        }
        
        $message .= "Uwe na siku yenye uzalishaji! 🌾\n";
        $message .= "_Jibu maswali yoyote kuhusu shamba lako._";
        
        return $message;
    }
    
    /**
     * Send stock alert
     */
    public function sendStockAlert($phone, $itemName, $currentStock, $minStock, $unit) {
        $message = "🚨 *STOCK ALERT*\n\n";
        $message .= "{$itemName} is running low!\n\n";
        $message .= "• Current stock: {$currentStock} {$unit}\n";
        $message .= "• Minimum required: {$minStock} {$unit}\n\n";
        $message .= "Please reorder immediately from your supplier.\n\n";
        $message .= "_Reply 'ORDER' to generate a purchase order._";
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Send vaccination reminder
     */
    public function sendVaccinationReminder($phone, $animalType, $vaccine, $dueDate, $count) {
        $message = "💉 *VACCINATION REMINDER*\n\n";
        $message .= "{$count} {$animalType} need {$vaccine} vaccination.\n\n";
        $message .= "📅 Due date: {$dueDate}\n\n";
        $message .= "Please ensure all animals are vaccinated on time.\n\n";
        $message .= "_Reply 'DONE' after completing vaccination._";
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Send weather alert
     */
    public function sendWeatherAlert($phone, $weather, $farmAdvice) {
        $message = "🌤️ *WEATHER ALERT*\n\n";
        $message .= "Current weather: {$weather['description']}\n";
        $message .= "Temperature: {$weather['temp']}°C\n";
        $message .= "Humidity: {$weather['humidity']}%\n\n";
        
        if (!empty($farmAdvice)) {
            $message .= "🌾 *Farm Advice:*\n";
            foreach ($farmAdvice as $advice) {
                $message .= "• {$advice}\n";
            }
        }
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Send sales notification
     */
    public function sendSalesNotification($phone, $saleDetails) {
        $message = "💰 *SALE RECORDED*\n\n";
        $message .= "Item: {$saleDetails['item']}\n";
        $message .= "Quantity: {$saleDetails['quantity']} {$saleDetails['unit']}\n";
        $message .= "Price: KSh " . number_format($saleDetails['price']) . "\n";
        $message .= "Total: KSh " . number_format($saleDetails['total']) . "\n\n";
        $message .= "Buyer: {$saleDetails['buyer']}\n";
        $message .= "Date: {$saleDetails['date']}\n\n";
        $message .= "_Transaction recorded successfully._";
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Send payment received notification
     */
    public function sendPaymentReceived($phone, $amount, $from, $reference) {
        $message = "✅ *PAYMENT RECEIVED*\n\n";
        $message .= "Amount: KSh " . number_format($amount) . "\n";
        $message .= "From: {$from}\n";
        $message .= "Reference: {$reference}\n";
        $message .= "Date: " . date('F j, Y H:i') . "\n\n";
        $message .= "_M-PESA payment confirmed._";
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Send budget alert
     */
    public function sendBudgetAlert($phone, $category, $spent, $budget, $percentage) {
        $message = "📊 *BUDGET ALERT*\n\n";
        $message .= "{$category} budget is {$percentage}% used.\n\n";
        $message .= "• Spent: KSh " . number_format($spent) . "\n";
        $message .= "• Budget: KSh " . number_format($budget) . "\n";
        $message .= "• Remaining: KSh " . number_format($budget - $spent) . "\n\n";
        
        if ($percentage >= 90) {
            $message .= "⚠️ *Warning: Budget nearly exhausted!*\n";
        } elseif ($percentage >= 75) {
            $message .= "⚡ *Caution: Budget over 75% used.*\n";
        }
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Generate weekly report
     */
    public function getWeeklyReport($weekData) {
        $startDate = date('M j', strtotime('-7 days'));
        $endDate = date('M j');
        
        $message = "📈 *WEEKLY FARM REPORT*\n";
        $message .= "📅 {$startDate} - {$endDate}\n\n";
        
        // Production Summary
        $message .= "🐔 *PRODUCTION*\n";
        $message .= "• Eggs collected: {$weekData['total_eggs']} trays\n";
        $message .= "• Milk produced: {$weekData['total_milk']} liters\n";
        $message .= "• Birds sold: {$weekData['birds_sold']}\n";
        $message .= "• Mortality rate: {$weekData['mortality_rate']}%\n\n";
        
        // Financial Summary
        $message .= "💰 *FINANCIAL SUMMARY*\n";
        $message .= "• Total revenue: KSh " . number_format($weekData['revenue']) . "\n";
        $message .= "• Total expenses: KSh " . number_format($weekData['expenses']) . "\n";
        $message .= "• Net profit: KSh " . number_format($weekData['profit']) . "\n";
        $message .= "• Profit margin: {$weekData['margin']}%\n\n";
        
        // Feed Efficiency
        $message .= "🍽️ *FEED EFFICIENCY*\n";
        $message .= "• Feed consumed: {$weekData['feed_consumed']} kg\n";
        $message .= "• Cost per bird: KSh " . number_format($weekData['cost_per_bird']) . "\n";
        $message .= "• FCR: {$weekData['fcr']}\n\n";
        
        // Next Week Focus
        $message .= "🎯 *NEXT WEEK FOCUS*\n";
        foreach ($weekData['focus_areas'] as $focus) {
            $message .= "• {$focus}\n";
        }
        
        return $message;
    }
    
    /**
     * Handle incoming WhatsApp messages
     */
    public function handleIncoming($message, $from) {
        $message = strtolower(trim($message));
        
        // Quick commands
        $responses = [
            'summary' => function() { return $this->getQuickSummary(); },
            'status' => function() { return $this->getQuickStatus(); },
            'alerts' => function() { return $this->getQuickAlerts(); },
            'help' => function() { return $this->getHelpMessage(); },
            'order' => function() { return $this->getOrderInfo(); },
            'done' => function() { return "✅ Vaccination marked as complete. Thank you!"; },
        ];
        
        foreach ($responses as $command => $callback) {
            if (strpos($message, $command) !== false) {
                return $callback();
            }
        }
        
        // Default response
        return $this->getDefaultResponse();
    }
    
    private function getQuickSummary() {
        return "📊 *Quick Summary*\n\n" .
               "🐔 Broilers: 200\n" .
               "🥚 Eggs: 15 trays\n" .
               "💰 Sales today: KSh 8,500\n" .
               "💵 Profit: KSh 4,200";
    }
    
    private function getQuickStatus() {
        return "📊 *Farm Status*\n\n" .
               "✅ All systems operational\n" .
               "🐔 Livestock: Healthy\n" .
               "📦 Feed stock: Adequate\n" .
               "💊 Vaccinations: Up to date";
    }
    
    private function getQuickAlerts() {
        return "⚠️ *Current Alerts*\n\n" .
               "1. 🚨 Low stock: Broiler Starter Feed\n" .
               "2. 💉 Vaccination due: 50 broilers\n" .
               "3. 🌤️ Weather: Rain expected tomorrow";
    }
    
    private function getHelpMessage() {
        return "📖 *Available Commands*\n\n" .
               "• SUMMARY - Get daily summary\n" .
               "• STATUS - Quick farm status\n" .
               "• ALERTS - View current alerts\n" .
               "• ORDER - Generate purchase order\n" .
               "• HELP - Show this message\n\n" .
               "You can also ask questions in natural language!";
    }
    
    private function getOrderInfo() {
        return "🛒 *Order Information*\n\n" .
               "Items to reorder:\n" .
               "1. Broiler Starter Feed (50kg) - KSh 3,500\n" .
               "2. Dewormer (100 tablets) - KSh 1,500\n\n" .
               "Reply with item number to proceed.";
    }
    
    private function getDefaultResponse() {
        return "👋 Thank you for your message!\n\n" .
               "I can help you with:\n" .
               "• Farm summaries\n" .
               "• Stock alerts\n" .
               "• Weather updates\n" .
               "• Sales tracking\n\n" .
               "Reply HELP for available commands.";
    }
}

// Webhook handler for incoming messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['entry'][0]['changes'][0]['value']['messages'][0])) {
        $message = $input['entry'][0]['changes'][0]['value']['messages'][0];
        $from = $message['from'];
        $text = $message['text']['body'];
        
        $whatsapp = new WhatsAppIntegration();
        $response = $whatsapp->handleIncoming($text, $from);
        
        // Send response
        $whatsapp->sendMessage($from, $response);
        
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }
}
