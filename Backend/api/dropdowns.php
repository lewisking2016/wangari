<?php
/**
 * System Dropdowns & Master Data API and Helper Utilities
 * Wangari Website & Admin Dashboard
 */
declare(strict_types=1);

// Only start a session when this file is the entry point (API calls) —
// when included from admin pages the session is already active, and calling
// session_save_path()/session_start() again would emit warnings into the page.
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/session.php';
    wangariStartSession();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Get active options for a specific dropdown group.
 */
function getSystemDropdownOptions(string $groupKey, bool $onlyActive = true): array {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return [];
    }

    try {
        $sql = "SELECT id, group_key, group_label, option_value, option_label, sort_order, is_active, is_system 
                FROM system_dropdowns 
                WHERE group_key = :group_key";
        if ($onlyActive) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, option_label ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':group_key' => $groupKey]);
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        // Table not found error
        if ($e->getCode() === '42S02' || str_contains($e->getMessage(), '1146')) {
            $migrationFile = __DIR__ . '/../config/migration_v4_dropdowns.sql';
            if (file_exists($migrationFile)) {
                $sqlText = file_get_contents($migrationFile);
                $statements = array_filter(array_map('trim', explode(';', $sqlText)));
                foreach ($statements as $stmtText) {
                    if (!empty($stmtText)) {
                        try {
                            $pdo->exec($stmtText);
                        } catch (Exception $ex) {
                            // ignore
                        }
                    }
                }
                // Try executing again
                try {
                    $sql = "SELECT id, group_key, group_label, option_value, option_label, sort_order, is_active, is_system 
                            FROM system_dropdowns 
                            WHERE group_key = :group_key";
                    if ($onlyActive) {
                        $sql .= " AND is_active = 1";
                    }
                    $sql .= " ORDER BY sort_order ASC, option_label ASC";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':group_key' => $groupKey]);
                    return $stmt->fetchAll() ?: [];
                } catch (Exception $ex) {
                    return [];
                }
            }
        }
        return [];
    }
}

/**
 * Render HTML <option> tags for a specific dropdown group.
 */
function renderDropdownOptions(string $groupKey, ?string $selectedValue = null, string $placeholder = '-- Select --', bool $onlyActive = true): string {
    $options = getSystemDropdownOptions($groupKey, $onlyActive);
    $html = '';

    if (!empty($placeholder)) {
        $html .= '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</option>';
    }

    foreach ($options as $opt) {
        $val = htmlspecialchars($opt['option_value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lbl = htmlspecialchars($opt['option_label'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $selected = ($selectedValue !== null && (string)$selectedValue === (string)$opt['option_value']) ? ' selected' : '';
        $html .= sprintf('<option value="%s"%s>%s</option>', $val, $selected, $lbl);
    }

    return $html;
}

/**
 * Get all available dropdown groups with metadata.
 */
function getAllDropdownGroups(): array {
    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    try {
        $sql = "SELECT group_key, group_label, COUNT(*) as total_options, 
                       SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_options
                FROM system_dropdowns
                GROUP BY group_key, group_label
                ORDER BY group_label ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02' || str_contains($e->getMessage(), '1146')) {
            // Trigger auto-creation
            getSystemDropdownOptions('product_categories', false);
            try {
                $sql = "SELECT group_key, group_label, COUNT(*) as total_options, 
                               SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_options
                        FROM system_dropdowns
                        GROUP BY group_key, group_label
                        ORDER BY group_label ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll() ?: [];
            } catch (Exception $ex) {
                return [];
            }
        }
        return [];
    }
}

// Check if request is an API HTTP call (directly requested, not included)
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    header('Content-Type: application/json; charset=utf-8');

    // Admin authorization check for modifying actions
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Process GET request: fetch groups or options
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $groupKey = $_GET['group'] ?? '';
        if ($groupKey) {
            $options = getSystemDropdownOptions($groupKey, false);
            echo json_encode(['success' => true, 'group' => $groupKey, 'data' => $options]);
        } else {
            $groups = getAllDropdownGroups();
            $pdo = getDatabaseConnection();
            $allOptions = [];
            if ($pdo) {
                $stmt = $pdo->query("SELECT * FROM system_dropdowns ORDER BY group_key, sort_order ASC, option_label ASC");
                $allOptions = $stmt->fetchAll() ?: [];
            }
            echo json_encode(['success' => true, 'groups' => $groups, 'options' => $allOptions]);
        }
        exit;
    }

    // Require admin privileges for POST/PUT/DELETE actions
    if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin permissions required.']);
        exit;
    }

    $pdo = getDatabaseConnection();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
        exit;
    }

    try {
        if ($action === 'add_option') {
            $groupKey = trim($_POST['group_key'] ?? '');
            $groupLabel = trim($_POST['group_label'] ?? '');
            $optionLabel = trim($_POST['option_label'] ?? '');
            $optionValue = trim($_POST['option_value'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if (empty($groupKey) || empty($optionLabel)) {
                echo json_encode(['success' => false, 'message' => 'Group key and option label are required.']);
                exit;
            }

            if (empty($optionValue)) {
                // Auto slugify value
                $optionValue = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $optionLabel));
            }

            if (empty($groupLabel)) {
                $groupLabel = ucwords(str_replace('_', ' ', $groupKey));
            }

            // Check if group already exists to get canonical group_label
            $existingGroup = fetchOne($pdo, "SELECT group_label FROM system_dropdowns WHERE group_key = ? LIMIT 1", [$groupKey]);
            if ($existingGroup) {
                $groupLabel = $existingGroup['group_label'];
            }

            $stmt = $pdo->prepare("INSERT INTO system_dropdowns (group_key, group_label, option_value, option_label, sort_order, is_active, is_system) 
                                   VALUES (?, ?, ?, ?, ?, 1, 0)
                                   ON DUPLICATE KEY UPDATE option_label = VALUES(option_label), sort_order = VALUES(sort_order), is_active = 1");
            $stmt->execute([$groupKey, $groupLabel, $optionValue, $optionLabel, $sortOrder]);

            echo json_encode(['success' => true, 'message' => 'Dropdown option added successfully!']);
            exit;
        }

        if ($action === 'update_option') {
            $id = (int)($_POST['id'] ?? 0);
            $optionLabel = trim($_POST['option_label'] ?? '');
            $optionValue = trim($_POST['option_value'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if ($id <= 0 || empty($optionLabel)) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID or option label.']);
                exit;
            }

            $existing = fetchOne($pdo, "SELECT * FROM system_dropdowns WHERE id = ?", [$id]);
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => 'Dropdown option not found.']);
                exit;
            }

            // If system item, don't allow changing option_value if it's protected
            if ($existing['is_system'] && empty($optionValue)) {
                $optionValue = $existing['option_value'];
            }

            $stmt = $pdo->prepare("UPDATE system_dropdowns SET option_label = ?, option_value = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$optionLabel, $optionValue, $sortOrder, $isActive, $id]);

            echo json_encode(['success' => true, 'message' => 'Dropdown option updated successfully!']);
            exit;
        }

        if ($action === 'toggle_status') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE system_dropdowns SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Status toggled successfully!']);
            exit;
        }

        if ($action === 'delete_option') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
                exit;
            }

            $existing = fetchOne($pdo, "SELECT * FROM system_dropdowns WHERE id = ?", [$id]);
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => 'Option not found.']);
                exit;
            }

            if ($existing['is_system']) {
                echo json_encode(['success' => false, 'message' => 'Core system dropdown options cannot be deleted. You can disable them instead.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM system_dropdowns WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Dropdown option deleted successfully!']);
            exit;
        }

        if ($action === 'delete_group') {
            $groupKey = trim($_POST['group_key'] ?? '');
            if (empty($groupKey)) {
                echo json_encode(['success' => false, 'message' => 'Group key required.']);
                exit;
            }

            // Check if contains system items
            $hasSystem = fetchOne($pdo, "SELECT COUNT(*) as count FROM system_dropdowns WHERE group_key = ? AND is_system = 1", [$groupKey]);
            if (!empty($hasSystem['count']) && (int)$hasSystem['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete a group containing protected system dropdown items.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM system_dropdowns WHERE group_key = ? AND is_system = 0");
            $stmt->execute([$groupKey]);

            echo json_encode(['success' => true, 'message' => 'Custom dropdown group deleted successfully!']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
