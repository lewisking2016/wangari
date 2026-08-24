<?php
/**
 * Wangari Farm AI Assistant - Complete System
 * 
 * Features:
 * - Floating chat widget (always visible)
 * - Pre-loaded farm knowledge base
 * - Natural language queries
 * - Voice-to-text support
 * - Multi-language (English + Swahili)
 * - WhatsApp integration
 * - Real-time farm data access
 */

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
// Database is optional - works without it
// Database is optional - works without it
// require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/../config/ai_engine.php';

class FarmAI {
    private $db;
    private $conversationHistory = [];
    private $knowledgeBase = [];
    
    public function __construct() {
        $this->db = null; // Database is optional
        $this->loadKnowledgeBase();
        $this->loadConversationHistory();
    }
    
    private function loadKnowledgeBase() {
        $this->knowledgeBase = [
            // CHICKEN MANAGEMENT
            'chicken' => [
                'feeding' => [
                    'broiler' => [
                        '0-2 weeks' => 'Starter feed: 25g per bird/day',
                        '2-4 weeks' => 'Grower feed: 50g per bird/day',
                        '4-6 weeks' => 'Finisher feed: 100g per bird/day',
                        'water' => 'Always available, 1.5x feed weight'
                    ],
                    'layer' => [
                        '0-8 weeks' => 'Chick starter: 10g per bird/day',
                        '8-18 weeks' => 'Grower: 40g per bird/day',
                        '18+ weeks' => 'Layer mash: 110g per bird/day',
                        'calcium' => 'Add oyster shell for strong eggs'
                    ],
                    'kienyeji' => [
                        'morning' => 'Maize bran + fish meal mix',
                        'evening' => 'Kitchen scraps + greens',
                        'supplements' => 'Grit, oyster shell, green vegetables'
                    ]
                ],
                'health' => [
                    'newcastle' => 'Vaccinate at day 7, 28, and 8 weeks. Signs: gasping, green diarrhea',
                    'gumboro' => 'Vaccinate at day 14 and 21. Signs: watery eyes, trembling',
                    'fowl_pox' => 'Vaccinate at 8 weeks. Signs: lesions on comb, wattles',
                    'deworming' => 'Every 3 months. Use Albendazole or Ivermectin',
                    'coccidiosis' => 'Vaccinate at day 1. Signs: bloody droppings'
                ],
                'housing' => [
                    'space' => 'Broilers: 0.1sqm/bird, Layers: 0.2sqm/bird',
                    'temperature' => 'Broilers: 33°C first week, reduce 3°C/week',
                    'ventilation' => 'Good airflow, no drafts, avoid ammonia buildup',
                    'lighting' => 'Layers: 16 hours light for egg production'
                ]
            ],
            
            // CATTLE MANAGEMENT
            'cattle' => [
                'feeding' => [
                    'dairy' => [
                        'morning' => 'Napier grass + dairy meal (4kg)',
                        'evening' => 'Hay + dairy meal (4kg)',
                        'water' => '30-50 liters per cow per day'
                    ],
                    'beef' => [
                        'pasture' => 'Rotate grazing every 2 weeks',
                        'supplements' => 'Salt lick, mineral block',
                        'dry_season' => 'Hay + drought-resistant fodder'
                    ]
                ],
                'health' => [
                    'anthrax' => 'Vaccinate annually. Signs: sudden death, black blood',
                    'fmd' => 'Vaccinate every 6 months. Signs: blisters on mouth/feet',
                    'brucellosis' => 'Test all cows. Signs: abortion, infertility',
                    'deworming' => 'Every 3 months. Use Ivermectin',
                    'ticks' => 'Dip every 2 weeks. Use Amitraz or Cypermethrin'
                ],
                'breeding' => [
                    'heat_signs' => 'Restless, mounting, mucous discharge',
                    'best_time' => '12 hours after heat starts',
                    'gestation' => '9 months (283 days)',
                    'calving' => 'Provide clean area, assist if needed'
                ]
            ],
            
            // GOAT MANAGEMENT
            'goats' => [
                'feeding' => [
                    'morning' => 'Browse ( shrubs, leaves) + supplements',
                    'evening' => 'Hay + goat pellets',
                    'minerals' => 'Goat mineral block always available'
                ],
                'health' => [
                    'ppr' => 'Vaccinate annually. Signs: mouth sores, diarrhea',
                    'enterotoxemia' => 'Vaccinate pregnant does 2 weeks before kidding',
                    'deworming' => 'Every 3 months. Use Albendazole',
                    'foot_rot' => 'Trim hooves monthly. Keep area dry'
                ]
            ],
            
            // CROPS
            'crops' => [
                'maize' => [
                    'planting' => 'Space 75cm x 30cm. Plant 2 seeds per hole',
                    'fertilizer' => 'DAP at planting, CAN at knee height',
                    'harvesting' => 'When husks turn brown, dry to 13% moisture'
                ],
                'beans' => [
                    'planting' => 'Space 50cm x 20cm. 2-3 seeds per hole',
                    'fertilizer' => 'DAP at planting only',
                    'harvesting' => 'When pods turn brown and dry'
                ],
                'vegetables' => [
                    'kale' => 'Plant seeds in nursery, transplant at 4 weeks',
                    'spinach' => 'Direct seeding, harvest in 6 weeks',
                    'tomatoes' => 'Stake plants, prune suckers'
                ]
            ],
            
            // FINANCIAL
            'finance' => [
                'pricing' => [
                    'broiler' => 'KSh 350-500 per bird at 6 weeks',
                    'layer' => 'KSh 800-1200 per bird',
                    'eggs' => 'KSh 12-18 per egg',
                    'milk' => 'KSh 40-60 per liter'
                ],
                'costs' => [
                    'feed_per_broiler' => 'KSh 250-350 for 6 weeks',
                    'medicine_per_bird' => 'KSh 30-50',
                    'housing_per_bird' => 'KSh 50-100 (amortized)'
                ],
                'mpesa' => [
                    'paybill' => 'Business number: 123456',
                    'till' => 'Till number: 789012',
                    'send_money' => 'Use Safaricom M-PESA app'
                ]
            ],
            
            // COMMON QUESTIONS
            'faqs' => [
                'how_many_chickens' => 'Start with 50-100 broilers or 50 layers. Scale up as you gain experience.',
                'best_feed' => 'Use branded feeds (Unga, Kenchic) for best results. Avoid homemade for commercial farming.',
                'vaccination_schedule' => 'Newcastle (day 7, 28, 8 weeks), Gumboro (day 14, 21), Fowl Pox (8 weeks)',
                'when_to_sell' => 'Broilers: 6-8 weeks. Layers: start selling eggs at 18-20 weeks.',
                'common_mistakes' => 'Poor hygiene, overstocking, skipping vaccines, wrong feed ratios.',
                'profit_tips' => 'Buy day-old chicks, use quality feed, prevent diseases, sell at right time.'
            ]
        ];
    }
    
    private function loadConversationHistory() {
        if (isset($_SESSION['ai_chat_history'])) {
            $this->conversationHistory = $_SESSION['ai_chat_history'];
        }
    }
    
    public function processMessage($message) {
        $message = strtolower(trim($message));
        
        // Save user message
        $this->conversationHistory[] = ['role' => 'user', 'content' => $message];
        
        // Check for voice command
        if (strpos($message, 'voice command:') === 0) {
            $message = substr($message, 14);
        }
        
        // Check for Swahili
        if ($this->isSwahili($message)) {
            return $this->handleSwahili($message);
        }
        
        // Intent detection
        $response = $this->detectIntent($message);
        
        // Save AI response
        $this->conversationHistory[] = ['role' => 'assistant', 'content' => $response];
        $_SESSION['ai_chat_history'] = $this->conversationHistory;
        
        return $response;
    }
    
    private function isSwahili($message) {
        $swahiliWords = ['habari', 'niaje', 'shida', 'sawa', 'ndio', 'hapana', 'asante', 'tafadhali'];
        foreach ($swahiliWords as $word) {
            if (strpos($message, $word) !== false) return true;
        }
        return false;
    }
    
    private function handleSwahili($message) {
        if (strpos($message, 'habari') !== false || strpos($message, 'niaje') !== false) {
            return "Habari! 👋 Karibu Wangari Farm System. Mimi ni msaidizi wako wa kilimo. Ninaweza kukusaidia na:\n\n" .
                   "🐔 Kuku - ULISHO, AFYA, NYUMBA\n" .
                   "🐄 Ng'ombe - ULISHO, AFYA, UZAZI\n" .
                   "🐐 Mbuzi - ULISHO, AFYA\n" .
                   "🌾 Mimea - KUPANDA, MAVUNO\n" .
                   "💰 Fedha - FAIDA, MATUMIZI\n" .
                   "📞 M-PESA - KULIPA, KUPOKEA\n\n" .
                   "Niulize maswali yoyote! 😊";
        }
        if (strpos($message, 'asante') !== false) {
            return "Karibu! 🙏 Furaha yangu kukusaidia. Endelea kuuliza maswali yoyote kuhusu shamba lako!";
        }
        return "Sawa, ninaelewa. Mimi bado ninajifunza Kiswahili vizuri zaidi. Jaribu kwa Kiingereza au niulize kuhusu kuku, ng'ombe, mbuzi, mimea, au fedha. 😊";
    }
    
    private function detectIntent($message) {
        // Feeding questions
        if (preg_match('/(feed|ulisha|chakula|what.*eat|how.*feed)/', $message)) {
            return $this->handleFeeding($message);
        }
        
        // Health questions
        if (preg_match('/(health|sick|disease|afya|ugonjwa|vaccine|divaccinate)/', $message)) {
            return $this->handleHealth($message);
        }
        
        // Housing
        if (pregatch('/(house|coop|housing|kraal|barn|nyumba|structure)/', $message)) {
            return $this->handleHousing($message);
        }
        
        // Financial
        if (preg_match('/(cost|price|profit|money|fedha|bei|faida|mpesa|pay|sell)/', $message)) {
            return $this->handleFinance($message);
        }
        
        // Weather
        if (preg_match('/(weather|rain|sun|mvua|jua|hali ya hewa)/', $message)) {
            return $this->handleWeather();
        }
        
        // Breeding
        if (preg_match('/(breed|mate|pregnant|calving|kidding|uzazi|mating|heat)/', $message)) {
            return $this->handleBreeding($message);
        }
        
        // Market prices
        if (preg_match('/(market.*price|current.*price|how.*much.*sell|bei.*sasa)/', $message)) {
            return $this->handleMarketPrices();
        }
        
        // Mortality
        if (preg_match('/(dead|died|mortality|death|kufa|vifo)/', $message)) {
            return $this->handleMortality($message);
        }
        
        // Greetings
        if (preg_match('/^(hi|hello|hey|habari|niaje|good\s*(morning|afternoon|evening))/', $message)) {
            return $this->getGreeting();
        }
        
        // Help
        if (preg_match('/(help|support|assist|nisaidie|maelezo)/', $message)) {
            return $this->getHelp();
        }
        
        // Default - try to be helpful
        return $this->getDefaultResponse($message);
    }
    
    private function handleFeeding($message) {
        $response = "🐔 **CHICKEN FEEDING GUIDE**\n\n";
        
        if (strpos($message, 'broiler') !== false || strpos($message, 'kuku') !== false) {
            $response .= "**Broilers:**\n";
            $response .= "• Week 1-2: Starter feed (25g/bird/day)\n";
            $response .= "• Week 2-4: Grower feed (50g/bird/day)\n";
            $response .= "• Week 4-6: Finisher feed (100g/bird/day)\n";
            $response .= "• Water: Always available (1.5x feed weight)\n\n";
            $response .= "**Cost per broiler:** KSh 250-350 for 6 weeks";
        } elseif (strpos($message, 'layer') !== false) {
            $response .= "**Layers:**\n";
            $response .= "• Week 0-8: Chick starter (10g/bird/day)\n";
            $response .= "• Week 8-18: Grower (40g/bird/day)\n";
            $response .= "• Week 18+: Layer mash (110g/bird/day)\n";
            $response .= "• Add oyster shell for strong eggs\n\n";
            $response .= "**Cost per layer:** KSh 800-1200 (one-time)";
        } elseif (strpos($message, 'kienyeji') !== false || strpos($message, 'indigenous') !== false) {
            $response .= "**Kienyeji (Indigenous):**\n";
            $response .= "• Morning: Maize bran + fish meal mix\n";
            $response .= "• Evening: Kitchen scraps + greens\n";
            $response .= "• Supplements: Grit, oyster shell, vegetables\n\n";
            $response .= "**Cost:** Low - uses locally available food";
        } elseif (strpos($message, 'cow') !== false || strpos($message, 'ng\'ombe') !== false) {
            $response .= "**Cattle Feeding:**\n";
            $response .= "• Morning: Napier grass + dairy meal (4kg)\n";
            $response .= "• Evening: Hay + dairy meal (4kg)\n";
            $response .= "• Water: 30-50 liters per cow per day\n";
            $response .= "• Salt lick and mineral block always available";
        } elseif (strpos($message, 'goat') !== false || strpos($message, 'mbuzi') !== false) {
            $response .= "**Goat Feeding:**\n";
            $response .= "• Morning: Browse (shrubs, leaves) + supplements\n";
            $response .= "• Evening: Hay + goat pellets\n";
            $response .= "• Minerals: Goat mineral block always available";
        } else {
            $response .= "I can help with feeding for:\n";
            $response .= "🐔 Chickens (Broilers, Layers, Kienyeji)\n";
            $response .= "🐄 Cattle (Dairy, Beef)\n";
            $response .= "🐐 Goats\n\n";
            $response .= "Tell me which animal and I'll give you the feeding guide!";
        }
        
        return $response;
    }
    
    private function handleHealth($message) {
        $response = "🏥 **ANIMAL HEALTH GUIDE**\n\n";
        
        if (strpos($message, 'vaccine') !== false || strpos($message, 'divaccinate') !== false) {
            $response .= "**Vaccination Schedule:**\n";
            $response .= "🐔 Chickens:\n";
            $response .= "• Day 7: Newcastle\n";
            $response .= "• Day 14: Gumboro\n";
            $response .= "• Day 21: Gumboro booster\n";
            $response .= "• Week 8: Newcastle booster + Fowl Pox\n\n";
            $response .= "🐄 Cattle:\n";
            $response .= "• Annually: Anthrax\n";
            $response .= "• Every 6 months: FMD\n";
            $response .= "• Test for Brucellosis\n\n";
            $response .= "🐐 Goats:\n";
            $response .= "• Annually: PPR\n";
            $response .= "• Pregnant does: Enterotoxemia 2 weeks before kidding";
        } elseif (strpos($message, 'chicken') !== false || strpos($message, 'kuku') !== false) {
            $response .= "**Common Chicken Diseases:**\n\n";
            $response .= "1. **Newcastle Disease**\n";
            $response .= "   Signs: Gasping, green diarrhea, twisted neck\n";
            $response .= "   Treatment: Vaccinate, no cure\n\n";
            $response .= "2. **Gumboro Disease**\n";
            $response .= "   Signs: Watery eyes, trembling, diarrhea\n";
            $response .= "   Treatment: Vaccinate, keep warm\n\n";
            $response .= "3. **Coccidiosis**\n";
            $response .= "   Signs: Bloody droppings, weakness\n";
            $response .= "   Treatment: Corid in water, keep dry\n\n";
            $response .= "4. **Fowl Pox**\n";
            $response .= "   Signs: Lesions on comb, wattles\n";
            $response .= "   Treatment: Vaccinate, no cure";
        } elseif (strpos($message, 'cow') !== false || strpos($message, 'ng\'ombe') !== false) {
            $response .= "**Common Cattle Diseases:**\n\n";
            $response .= "1. **Foot and Mouth Disease (FMD)**\n";
            $response .= "   Signs: Blisters on mouth and feet\n";
            $response .= "   Treatment: Vaccinate every 6 months\n\n";
            $response .= "2. **Anthrax**\n";
            $response .= "   Signs: Sudden death, black blood\n";
            $response .= "   Treatment: Vaccinate annually\n\n";
            $response .= "3. **Brucellosis**\n";
            $response .= "   Signs: Abortion, infertility\n";
            $response .= "   Treatment: Test and cull positive animals";
        } else {
            $response .= "Tell me which animal is sick and I'll help:\n";
            $response .= "🐔 Chicken - diseases and treatments\n";
            $response .= "🐄 Cow - diseases and treatments\n";
            $response .= "🐐 Goat - diseases and treatments\n\n";
            $response .= "Also ask about vaccination schedules!";
        }
        
        return $response;
    }
    
    private function handleHousing($message) {
        $response = "🏠 **HOUSING GUIDE**\n\n";
        
        $response .= "**🐔 Chicken Housing:**\n";
        $response .= "• Space: 0.1sqm/bird (broilers), 0.2sqm/bird (layers)\n";
        $response .= "• Temperature: 33°C first week, reduce 3°C/week\n";
        $response .= "• Ventilation: Good airflow, no drafts\n";
        $response .= "• Lighting: 16 hours for layers (egg production)\n";
        $response .= "• Bedding: Dry, clean, change regularly\n\n";
        
        $response .= "**🐄 Cattle Housing (Zero Grazing):**\n";
        $response .= "• Space: 1.5m x 3m per cow\n";
        $response .= "• Floor: Concrete, sloped for drainage\n";
        $response .= "• Roof: Iron sheets, 3-4m height\n";
        $response .= "• Ventilation: Open sides with curtains\n";
        $response .= "• Feed trough: 60cm height, 45cm width\n";
        $response .= "• Water trough: Clean, always full\n\n";
        
        $response .= "**🐐 Goat Housing:**\n";
        $response .= "• Space: 1.5sqm per goat\n";
        $response .= "• Floor: Raised slatted floor (wood/metal)\n";
        $response .= "• Roof: Iron sheets, 2.5m height\n";
        $response .= "• Ventilation: Open sides for air flow\n";
        $response .= "• Keep dry, clean weekly";
        
        return $response;
    }
    
    private function handleFinance($message) {
        $response = "💰 **FARM FINANCE GUIDE**\n\n";
        
        if (strpos($message, 'mpesa') !== false || strpos($message, 'pay') !== false) {
            $response .= "**M-PESA Integration:**\n";
            $response .= "• Paybill: 123456\n";
            $response .= "• Till: 789012\n";
            $response .= "• Send Money: Use Safaricom M-PESA app\n";
            $response .= "• Buy Goods: Use Till number\n\n";
            $response .= "**To receive payment:**\n";
            $response .= "1. Go to M-PESA\n";
            $response .= "2. Lipa na M-PESA\n";
            $response .= "3. Enter Till/Paybill number\n";
            $response .= "4. Enter amount\n";
            $response .= "5. Confirm with PIN";
        } elseif (strpos($message, 'cost') !== false || strpos($message, 'price') !== false) {
            $response .= "**Farm Costs & Prices:**\n\n";
            $response .= "**Chicken:**\n";
            $response .= "• Broiler (6 weeks): KSh 350-500\n";
            $response .= "• Layer bird: KSh 800-1200\n";
            $response .= "• Eggs: KSh 12-18 per egg\n\n";
            $response .= "**Cattle:**\n";
            $response .= "• Milk: KSh 40-60 per liter\n";
            $response .= "• Beef: KSh 350-450 per kg\n";
            $response .= "• Calf: KSh 25,000-40,000\n\n";
            $response .= "**Feed Costs:**\n";
            $response .= "• Broiler feed (6 weeks): KSh 250-350/bird\n";
            $response .= "• Layer feed (monthly): KSh 800-1000/bird\n";
            $response .= "• Dairy meal (50kg): KSh 3,500-4,000";
        } elseif (strpos($message, 'profit') !== false || strpos($message, 'faida') !== false) {
            $response .= "**Profit Calculation:**\n\n";
            $response .= "**Broiler Example (50 birds):**\n";
            $response .= "• Cost: 50 × KSh 350 = KSh 17,500\n";
            $response .= "• Revenue: 50 × KSh 450 = KSh 22,500\n";
            $response .= "• Profit: KSh 5,000 (6 weeks)\n\n";
            $response .= "**Layer Example (50 birds):**\n";
            $response .= "• Cost: KSh 50,000 (one-time)\n";
            $response .= "• Eggs/month: 50 × 25 eggs = 1,250 eggs\n";
            $response .= "• Revenue: 1,250 × KSh 15 = KSh 18,750/month\n";
            $response .= "• Profit: KSh 12,000+/month (after feed)";
        } else {
            $response .= "I can help with:\n";
            $response .= "• 💰 M-PESA payments\n";
            $response .= "• 📊 Cost calculations\n";
            $response .= "• 💵 Profit projections\n";
            $response .= "• 🧾 Invoice generation\n\n";
            $response .= "Ask about specific costs or profits!";
        }
        
        return $response;
    }
    
    private function handleWeather() {
        $response = "🌤️ **WEATHER INFORMATION**\n\n";
        $response .= "Current weather data requires internet connection.\n\n";
        $response .= "**General Farming Weather Tips:**\n";
        $response .= "• Rainy season: Ensure good drainage\n";
        $response .= "• Dry season: Store water, plant drought-resistant crops\n";
        $response .= "• Hot weather: Provide shade and extra water\n";
        $response .= "• Cold weather: Keep animals warm, increase feed\n\n";
        $response .= "**For real-time weather:**\n";
        $response .= "• Check Kenya Met Department: www.meteo.go.ke\n";
        $response .= "• Or ask me for general seasonal advice";
        
        return $response;
    }
    
    private function handleBreeding($message) {
        $response = "繁殖 **BREEDING GUIDE**\n\n";
        
        $response .= "**🐔 Chicken Breeding:**\n";
        $response .= "• Incubation: 21 days\n";
        $response .= "• Eggs for hatching: Fresh, clean, 60g+\n";
        $response .= "• Fertility: Keep 1 rooster per 10 hens\n";
        $response .= "• Broody hens: Remove eggs, give nesting box\n\n";
        
        $response .= "**🐄 Cattle Breeding:**\n";
        $response .= "• Heat signs: Restless, mounting, discharge\n";
        $response .= "• Best time: 12 hours after heat starts\n";
        $response .= "• Gestation: 9 months (283 days)\n";
        $response .= "• Calving: Clean area, assist if needed\n";
        $response .= "• AI service: Available at nearest insemination center\n\n";
        
        $response .= "**🐐 Goat Breeding:**\n";
        $response .= "• Heat signs: Tail wagging, restlessness\n";
        $response .= "• Gestation: 5 months (150 days)\n";
        $response .= "• Kidding: Provide clean area, assist if needed";
        
        return $response;
    }
    
    private function handleMarketPrices() {
        $response = "📈 **CURRENT MARKET PRICES**\n\n";
        $response .= "Prices vary by location and season.\n\n";
        
        $response .= "**🐔 Poultry:**\n";
        $response .= "• Broiler (live): KSh 350-500\n";
        $response .= "• Broiler (dressed): KSh 500-700\n";
        $response .= "• Layer bird: KSh 800-1200\n";
        $response .= "• Eggs (tray of 30): KSh 360-540\n";
        $response .= "• Kienyeji: KSh 500-800\n\n";
        
        $response .= "**🐄 Cattle:**\n";
        $response .= "• Milk: KSh 40-60/liter\n";
        $response .= "• Beef (live): KSh 280-350/kg\n";
        $response .= "• Beef (dressed): KSh 450-600/kg\n";
        $response .= "• Calf (2 months): KSh 25,000-35,000\n";
        $response .= "• Heifer: KSh 60,000-100,000\n\n";
        
        $response .= "**🌾 Crops:**\n";
        $response .= "• Maize (90kg bag): KSh 3,000-4,500\n";
        $response .= "• Beans (90kg bag): KSh 8,000-12,000\n";
        $response .= "• Sukuma Wiki (bunch): KSh 10-20\n";
        $response .= "• Tomatoes (kg): KSh 40-80\n\n";
        
        $response .= "💡 **Tip:** Check local markets for current prices in your area!";
        
        return $response;
    }
    
    private function handleMortality($message) {
        $response = "⚠️ **MORTALITY MANAGEMENT**\n\n";
        
        $response .= "**Common Causes:**\n";
        $response .= "1. Disease (Newcastle, Gumboro, Coccidiosis)\n";
        $response .= "2. Poor nutrition\n";
        $response .= "3. Predators (rats, snakes, wild birds)\n";
        $response .= "4. Overcrowding\n";
        $response .= "5. Heat stress\n";
        $response .= "6. Poor ventilation\n\n";
        
        $response .= "**Prevention:**\n";
        $response .= "• Vaccinate on schedule\n";
        $response .= "• Provide clean water\n";
        $response .= "• Don't overcrowd\n";
        $response .= "• Secure housing at night\n";
        $response .= "• Remove sick birds immediately\n";
        $response .= "• Keep records of deaths\n\n";
        
        $response .= "**What to do when birds die:**\n";
        $response .= "1. Remove dead birds immediately\n";
        $response .= "2. Check for signs of disease\n";
        $response .= "3. Isolate sick birds\n";
        $response .= "4. Disinfect affected area\n";
        $response .= "5. Contact vet if many die suddenly";
        
        return $response;
    }
    
    private function getGreeting() {
        $hour = date('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        
        return "👋 $greeting! I'm Wangari AI, your farm assistant.\n\n" .
               "I can help you with:\n" .
               "🐔 Feeding schedules for all animals\n" .
               "🏥 Health & vaccination guides\n" .
               "🏠 Housing & management\n" .
               "💰 Financial calculations\n" .
               "🌤️ Weather advice\n" .
               "📈 Market prices\n" .
               "📞 M-PESA payments\n\n" .
               "How can I help you today?";
    }
    
    private function getHelp() {
        return "🤝 **HOW I CAN HELP**\n\n" .
               "Just ask me anything about farming:\n\n" .
               "**Examples:**\n" .
               "• 'How much feed for 50 broilers?'\n" .
               "• 'When to vaccinate chickens?'\n" .
               "• 'What's the profit on 100 layers?'\n" .
               "• 'How to build a chicken coop?'\n" .
               "• 'Current egg prices in Kenya?'\n" .
               "• 'Why are my chickens dying?'\n" .
               "• 'How to use M-PESA?'\n\n" .
               "**I also speak Swahili!**\n" .
               "• 'Habari, niaje?'\n" .
               "• 'Niulize kuhusu kuku'\n" .
               "• 'Bei ya mayai sasa'\n\n" .
               "💡 **Tip:** Tap the microphone to speak instead of typing!";
    }
    
    private function getDefaultResponse($message) {
        // Try OpenRouter LLM with web search for complex queries
        $llmResult = $this->callOpenRouterWithSearch($message);
        
        if ($llmResult && isset($llmResult['answer'])) {
            return $llmResult['answer'];
        }
        
        // Fall back to local knowledge base response
        return "🤔 I'm not sure I understand. I can help with:\n\n" .
               "🐔 **Chickens** - feeding, health, housing, breeding\n" .
               "🐄 **Cattle** - feeding, health, housing, breeding\n" .
               "🐐 **Goats** - feeding, health, housing, breeding\n" .
               "🌾 **Crops** - planting, harvesting, storage\n" .
               "💰 **Finance** - costs, profits, M-PESA\n" .
               "🌤️ **Weather** - seasonal advice\n" .
               "📈 **Market** - current prices\n\n" .
               "Try asking about any of these topics!\n\n" .
               "**Example:** 'How much feed for 100 broilers?'";
    }
    
    // ═══════════════════════════════════════════════════════════════
    // OpenRouter + Web Search Integration
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Detect if a query needs research/web search
     */
    private function needsResearch($message) {
        $researchKeywords = [
            'research', 'search', 'find', 'look up', 'google', 'latest', 'current',
            'today', 'now', 'recent', 'news', 'update', 'what is', 'who is',
            'how to', 'tutorial', 'guide', 'example', 'case study',
            'best practices', 'comparison', 'review', 'recommend',
            'price', 'cost', 'market', 'trend', 'forecast',
        ];
        
        foreach ($researchKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        
        // Check for question patterns that suggest research needed
        if (preg_match('/\b(what|how|why|when|where|which|who)\b.*\?/', $message)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Call OpenRouter with web search context
     */
    public function callOpenRouterWithSearch($message, $farmContext = []) {
        // Check if OpenRouter is configured
        if (!file_exists(dirname(__DIR__, 2) . '/Backend/config/openrouter.php')) {
            return null;
        }
        require_once dirname(__DIR__, 2) . '/Backend/config/openrouter.php';
        
        if (!openrouter_is_configured()) {
            return null;
        }
        
        // Check rate limit
        $userId = $_SESSION['user_id'] ?? 0;
        $subStatus = $_SESSION['subscription_status'] ?? 'trial';
        
        if ($userId > 0 && openrouter_is_rate_limited($userId, $subStatus)) {
            return [
                'answer' => "I've reached my daily limit for AI-powered responses. Please try again tomorrow or upgrade your plan for more queries.\n\n" .
                           "💡 **Tip:** I can still help with basic farming questions using my built-in knowledge base!",
                'mode' => 'rate_limited',
                'source' => 'local'
            ];
        }
        
        // Check if this query needs research
        $needsResearch = $this->needsResearch($message);
        $webContext = '';
        
        if ($needsResearch) {
            // Load web search helper
            require_once dirname(__DIR__, 2) . '/Backend/config/web_search.php';
            
            // Search the web for relevant information
            $searchResults = wangari_web_search($message, 3);
            
            if (!empty($searchResults)) {
                $webContext = "\n\nWEB SEARCH RESULTS:\n";
                foreach ($searchResults as $i => $result) {
                    $webContext .= ($i + 1) . ". " . $result['title'] . "\n";
                    $webContext .= "   " . $result['snippet'] . "\n";
                    $webContext .= "   Source: " . $result['url'] . "\n\n";
                }
                $webContext .= "Use these search results to provide accurate, up-to-date information.\n";
            }
        }
        
        // Build context with farm data
        $contextPrompt = $this->buildFarmContext($farmContext);
        
        // Prepare messages
        $messages = [
            ['role' => 'system', 'content' => OPENROUTER_SYSTEM_PROMPT . "\n\n" . $contextPrompt . $webContext],
        ];
        
        // Add conversation history (last 10 messages)
        $recentHistory = array_slice($this->conversationHistory, -10);
        foreach ($recentHistory as $msg) {
            $messages[] = $msg;
        }
        
        // Add current message
        $messages[] = ['role' => 'user', 'content' => $message];
        
        // Build request payload
        $payload = [
            'model' => OPENROUTER_MODEL,
            'messages' => $messages,
            'max_tokens' => OPENROUTER_MAX_TOKENS,
            'temperature' => OPENROUTER_TEMPERATURE,
        ];
        
        // Enable reasoning if configured
        if (defined('OPENROUTER_ENABLE_REASONING') && OPENROUTER_ENABLE_REASONING) {
            $payload['reasoning'] = [
                'effort' => 'medium',
                'exclude' => false,
            ];
        }
        
        // Call OpenRouter API
        $response = $this->httpPost(
            OPENROUTER_API_URL,
            $payload,
            [
                'Authorization: Bearer ' . OPENROUTER_API_KEY,
                'HTTP-Referer: https://wangari.imeantech.com',
                'X-OpenRouter-Title: Wangari Farm AI',
                'Content-Type: application/json',
            ]
        );
        
        if (!$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $answer = $data['choices'][0]['message']['content'];
            
            // Log the usage
            $this->logLLMUsage($userId, $message, $answer);
            
            return [
                'answer' => $answer,
                'mode' => 'llm',
                'source' => $needsResearch ? 'openrouter+search' : 'openrouter',
                'model' => OPENROUTER_MODEL,
                'used_research' => $needsResearch,
            ];
        }
        
        return null;
    }
    
    /**
     * Build context string from farm data
     */
    private function buildFarmContext($farmContext) {
        $context = "FARM DATA CONTEXT:\n";
        $context .= "The user has access to the Wangari farm management system. " .
                    "Use this context to give personalized advice:\n\n";
        
        if (!empty($farmContext['farm_name'])) {
            $context .= "- Farm Name: " . $farmContext['farm_name'] . "\n";
        }
        if (!empty($farmContext['farm_type'])) {
            $context .= "- Farm Type: " . $farmContext['farm_type'] . "\n";
        }
        if (!empty($farmContext['location'])) {
            $context .= "- Location: " . $farmContext['location'] . "\n";
        }
        if (!empty($farmContext['animals'])) {
            $context .= "- Animals: " . json_encode($farmContext['animals']) . "\n";
        }
        if (!empty($farmContext['recent_activity'])) {
            $context .= "- Recent Activity: " . $farmContext['recent_activity'] . "\n";
        }
        
        return $context;
    }
    
    /**
     * Log LLM usage for tracking
     */
    private function logLLMUsage($userId, $question, $answer) {
        if ($userId <= 0) return;
        
        try {
            if (function_exists('getDatabaseConnection')) {
                $pdo = getDatabaseConnection();
                if ($pdo) {
                    $stmt = $pdo->prepare("INSERT INTO ai_chat_logs (user_id, question, answer, mode) VALUES (?, ?, ?, 'llm')");
                    $stmt->execute([$userId, $question, $answer]);
                }
            }
        } catch (Exception $e) {
            // Silently fail - don't break the chat
        }
    }
    
    /**
     * Simple HTTP POST helper
     */
    private function httpPost($url, $data, $headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return $response;
        }
        
        return null;
    }
    
    public function getConversationHistory() {
        return $this->conversationHistory;
    }
    
    public function clearHistory() {
        $this->conversationHistory = [];
        $_SESSION['ai_chat_history'] = [];
    }
    
    public function getQuickActions() {
        return [
            ['id' => 'feeding', 'icon' => '🍽️', 'text' => 'Feeding Guide'],
            ['id' => 'health', 'icon' => '💊', 'text' => 'Health Tips'],
            ['id' => 'vaccine', 'icon' => '💉', 'text' => 'Vaccination'],
            ['id' => 'prices', 'icon' => '💰', 'text' => 'Market Prices'],
            ['id' => 'weather', 'icon' => '🌤️', 'text' => 'Weather'],
            ['id' => 'mpesa', 'icon' => '📱', 'text' => 'M-PESA'],
            ['id' => 'whatsapp', 'icon' => '💬', 'text' => 'WhatsApp'],
        ];
    }
}
