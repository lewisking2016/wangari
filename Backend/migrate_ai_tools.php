<?php
/**
 * Migration: Add AI tool calling tables
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "ERROR: Could not connect to database\n";
    exit(1);
}

echo "Running AI Tools migration...\n\n";

// ═══════════════════════════════════════════════════════════════
// 1. AI Tool Logs Table
// ═══════════════════════════════════════════════════════════════
$sql = "CREATE TABLE IF NOT EXISTS ai_tool_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tool_name VARCHAR(100) NOT NULL,
    arguments JSON,
    result JSON,
    success BOOLEAN DEFAULT TRUE,
    execution_time_ms INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_tool_name (tool_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "✓ Created ai_tool_logs table\n";
} catch (Exception $e) {
    echo "✗ ai_tool_logs: " . $e->getMessage() . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 2. AI Conversation Memory Table
// ═══════════════════════════════════════════════════════════════
$sql = "CREATE TABLE IF NOT EXISTS ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(100),
    messages JSON NOT NULL,
    total_tokens INT DEFAULT 0,
    tools_used JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "✓ Created ai_conversations table\n";
} catch (Exception $e) {
    echo "✗ ai_conversations: " . $e->getMessage() . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 3. AI Usage Tracking Table
// ═══════════════════════════════════════════════════════════════
$sql = "CREATE TABLE IF NOT EXISTS ai_usage_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    usage_date DATE NOT NULL,
    total_requests INT DEFAULT 0,
    tool_calls INT DEFAULT 0,
    llm_only INT DEFAULT 0,
    total_tokens INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, usage_date),
    INDEX idx_usage_date (usage_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "✓ Created ai_usage_daily table\n";
} catch (Exception $e) {
    echo "✗ ai_usage_daily: " . $e->getMessage() . "\n";
}

echo "\nMigration complete!\n";
