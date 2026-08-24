<?php
/**
 * Wangari AI Tool Definitions
 * 
 * These are the tools that OpenRouter can call via function calling.
 * The AI decides which tool to use based on user input.
 */

function wangari_get_ai_tools() {
    return [
        // ═══════════════════════════════════════════════════════════════
        // POULTRY TOOLS
        // ═══════════════════════════════════════════════════════════════
        [
            'type' => 'function',
            'function' => [
                'name' => 'add_flock',
                'description' => 'Create a new poultry flock/batch. Use when user wants to add chickens, broilers, layers, or start a new flock.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => ['broiler', 'layer', 'kienyeji'],
                            'description' => 'Type of poultry'
                        ],
                        'quantity' => [
                            'type' => 'integer',
                            'description' => 'Number of birds'
                        ],
                        'breed' => [
                            'type' => 'string',
                            'description' => 'Breed if known (e.g., Kenchic, Rainbow)'
                        ],
                        'notes' => [
                            'type' => 'string',
                            'description' => 'Any additional notes'
                        ]
                    ],
                    'required' => ['type', 'quantity']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'record_poultry_production',
                'description' => 'Record daily poultry production data. Use when user wants to log eggs, mortality, or feed used.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'eggs' => [
                            'type' => 'integer',
                            'description' => 'Number of eggs produced'
                        ],
                        'mortality' => [
                            'type' => 'integer',
                            'description' => 'Number of birds that died'
                        ],
                        'feed_used' => [
                            'type' => 'number',
                            'description' => 'Kilograms of feed used'
                        ],
                        'notes' => [
                            'type' => 'string',
                            'description' => 'Any additional notes'
                        ]
                    ],
                    'required' => []
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'list_flocks',
                'description' => 'List all active poultry flocks/batches. Use when user wants to see their chickens or poultry status.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => []
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'delete_flock',
                'description' => 'Delete or deactivate a poultry flock. Use when user wants to remove a flock.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'flock_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the flock to delete'
                        ]
                    ],
                    'required' => ['flock_id']
                ]
            ]
        ],

        // ═══════════════════════════════════════════════════════════════
        // LIVESTOCK TOOLS
        // ═══════════════════════════════════════════════════════════════
        [
            'type' => 'function',
            'function' => [
                'name' => 'add_animal',
                'description' => 'Add a new animal to the herd. Use when user wants to add a cow, goat, sheep, or any livestock.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Name of the animal'
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['cattle', 'goat', 'sheep'],
                            'description' => 'Type of animal'
                        ],
                        'breed' => [
                            'type' => 'string',
                            'description' => 'Breed if known (e.g., Friesian, Galla)'
                        ],
                        'gender' => [
                            'type' => 'string',
                            'enum' => ['male', 'female'],
                            'description' => 'Gender of the animal'
                        ],
                        'notes' => [
                            'type' => 'string',
                            'description' => 'Any additional notes'
                        ]
                    ],
                    'required' => ['name', 'type']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'record_milk',
                'description' => 'Record milk production. Use when user wants to log milk yield.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'animal_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the cow'
                        ],
                        'animal_name' => [
                            'type' => 'string',
                            'description' => 'Name of the cow (if ID not known)'
                        ],
                        'liters' => [
                            'type' => 'number',
                            'description' => 'Total liters of milk'
                        ],
                        'morning_liters' => [
                            'type' => 'number',
                            'description' => 'Morning milking liters'
                        ],
                        'evening_liters' => [
                            'type' => 'number',
                            'description' => 'Evening milking liters'
                        ]
                    ],
                    'required' => ['liters']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'list_animals',
                'description' => 'List all animals in the herd. Use when user wants to see their livestock.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => ['cattle', 'goat', 'sheep'],
                            'description' => 'Filter by animal type (optional)'
                        ]
                    ],
                    'required' => []
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'delete_animal',
                'description' => 'Remove an animal from the herd. Use when user wants to sell, slaughter, or remove an animal.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'animal_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the animal to remove'
                        ],
                        'reason' => [
                            'type' => 'string',
                            'enum' => ['sold', 'slaughtered', 'died', 'other'],
                            'description' => 'Reason for removal'
                        ]
                    ],
                    'required' => ['animal_id', 'reason']
                ]
            ]
        ],

        // ═══════════════════════════════════════════════════════════════
        // CROP TOOLS
        // ═══════════════════════════════════════════════════════════════
        [
            'type' => 'function',
            'function' => [
                'name' => 'add_field',
                'description' => 'Add a new field/plot. Use when user wants to add farmland or planting area.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Name of the field'
                        ],
                        'crop' => [
                            'type' => 'string',
                            'description' => 'Main crop to grow'
                        ],
                        'acreage' => [
                            'type' => 'number',
                            'description' => 'Size in acres'
                        ],
                        'location' => [
                            'type' => 'string',
                            'description' => 'Location description'
                        ]
                    ],
                    'required' => ['name']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'list_fields',
                'description' => 'List all fields. Use when user wants to see their farm plots.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => []
                ]
            ]
        ],

        // ═══════════════════════════════════════════════════════════════
        // FINANCE TOOLS
        // ═══════════════════════════════════════════════════════════════
        [
            'type' => 'function',
            'function' => [
                'name' => 'record_expense',
                'description' => 'Record a farm expense. Use when user wants to log spending.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => [
                            'type' => 'string',
                            'enum' => ['feed', 'medicine', 'labor', 'transport', 'equipment', 'utilities', 'other'],
                            'description' => 'Expense category'
                        ],
                        'amount' => [
                            'type' => 'number',
                            'description' => 'Amount in KES'
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'What the expense was for'
                        ]
                    ],
                    'required' => ['category', 'amount']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'record_income',
                'description' => 'Record farm income/sales. Use when user wants to log revenue.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => [
                            'type' => 'string',
                            'enum' => ['eggs', 'meat', 'milk', 'crops', 'animals', 'other'],
                            'description' => 'Income category'
                        ],
                        'amount' => [
                            'type' => 'number',
                            'description' => 'Amount in KES'
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'What the income was from'
                        ],
                        'payment_method' => [
                            'type' => 'string',
                            'enum' => ['cash', 'mpesa', 'bank', 'other'],
                            'description' => 'How payment was received'
                        ]
                    ],
                    'required' => ['category', 'amount']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_finance_summary',
                'description' => 'Get financial summary. Use when user wants to see income, expenses, profit.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => [
                            'type' => 'string',
                            'enum' => ['today', 'week', 'month', 'year'],
                            'description' => 'Time period for summary'
                        ]
                    ],
                    'required' => []
                ]
            ]
        ],

        // ═══════════════════════════════════════════════════════════════
        // CUSTOMER & ORDER TOOLS
        // ═══════════════════════════════════════════════════════════════
        [
            'type' => 'function',
            'function' => [
                'name' => 'add_customer',
                'description' => 'Add a new customer. Use when user wants to save a buyer/client.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Customer name'
                        ],
                        'phone' => [
                            'type' => 'string',
                            'description' => 'Phone number'
                        ],
                        'email' => [
                            'type' => 'string',
                            'description' => 'Email address'
                        ],
                        'notes' => [
                            'type' => 'string',
                            'description' => 'Additional notes'
                        ]
                    ],
                    'required' => ['name']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'create_order',
                'description' => 'Create a sales order. Use when user wants to record a sale.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_name' => [
                            'type' => 'string',
                            'description' => 'Customer name'
                        ],
                        'items' => [
                            'type' => 'string',
                            'description' => 'Items sold (e.g., "100 eggs, 2 broilers")'
                        ],
                        'total_amount' => [
                            'type' => 'number',
                            'description' => 'Total amount in KES'
                        ],
                        'payment_method' => [
                            'type' => 'string',
                            'enum' => ['cash', 'mpesa', 'bank', 'credit'],
                            'description' => 'Payment method'
                        ],
                        'payment_status' => [
                            'type' => 'string',
                            'enum' => ['paid', 'partial', 'unpaid'],
                            'description' => 'Payment status'
                        ]
                    ],
                    'required' => ['items', 'total_amount']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'list_customers',
                'description' => 'List all customers. Use when user wants to see their buyers.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => []
                ]
            ]
        ],

        // ═══════════════════════════════════════════════════════════════
        // QUERY TOOLS
        // ═══════════════════════════════════════════════════════════════
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_farm_summary',
                'description' => 'Get overall farm summary with key metrics. Use when user wants a dashboard overview.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => []
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'search_web',
                'description' => 'Search the internet for current information. Use when user asks about current prices, news, how-to guides, or anything requiring up-to-date info.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Search query'
                        ]
                    ],
                    'required' => ['query']
                ]
            ]
        ]
    ];
}
