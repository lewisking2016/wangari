<?php
/**
 * Onboarding Wizard — Guided first-time setup for new users.
 * Steps: Welcome → Farm Details → First Branch → First Animals → Done
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
require_once dirname(__DIR__, 2) . '/Backend/config/limits.php';
wangariStartSession();

if (empty($_SESSION['user_id']) || !wangariIsFarmSystemRole((string)($_SESSION['role'] ?? ''))) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$pdo = getDB();
$userId = (int)$_SESSION['user_id'];
$step = (int)($_GET['step'] ?? 1);
$message = '';
$error = '';

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postStep = (int)($_POST['step'] ?? 1);
    
    if ($postStep === 2) {
        // Save farm details
        $farmName = trim($_POST['farm_name'] ?? '');
        $farmLocation = trim($_POST['farm_location'] ?? '');
        $farmType = trim($_POST['farm_type'] ?? 'mixed');
        
        if ($farmName) {
            // Check if user already has a farm
            $existing = $pdo->prepare("SELECT id FROM farms WHERE owner_id = ?");
            $existing->execute([$userId]);
            
            if (!$existing->fetch()) {
                // Create first farm
                $code = 'WGRI-' . strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
                $stmt = $pdo->prepare("INSERT INTO farms (name, owner_id, farm_code, location, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$farmName, $userId, $code, $farmLocation, 'Farm type: ' . $farmType]);
                
                // Set as current farm
                $farmId = (int)$pdo->lastInsertId();
                $pdo->prepare("UPDATE users SET current_farm_id = ? WHERE id = ?")->execute([$farmId, $userId]);
                
                $_SESSION['current_farm_id'] = $farmId;
            }
            
            // Mark onboarding as started
            $pdo->prepare("UPDATE users SET onboarding_step = 2 WHERE id = ?")->execute([$userId]);
            
            header('Location: /Frontend/admin/onboarding.php?step=3');
            exit;
        } else {
            $error = 'Please enter a farm name';
            $step = 2;
        }
    }
    
    if ($postStep === 3) {
        // Save first animals (optional)
        $animalType = trim($_POST['animal_type'] ?? '');
        $animalCount = (int)($_POST['animal_count'] ?? 0);
        $animalBreed = trim($_POST['animal_breed'] ?? '');
        
        if ($animalType && $animalCount > 0) {
            $farmId = (int)($_SESSION['current_farm_id'] ?? 0);
            
            // Insert individual animals
            for ($i = 0; $i < min($animalCount, 50); $i++) {
                $pdo->prepare("INSERT INTO animals (type, breed, status, farm_id, name) VALUES (?, ?, 'Active', ?, ?)")
                    ->execute([$animalType, $animalBreed, $farmId, $animalType . ' #' . ($i + 1)]);
            }
            
            // If more than 50, create a group
            if ($animalCount > 50) {
                $pdo->prepare("INSERT INTO animal_groups (name, species, head_count, status, farm_id) VALUES (?, ?, ?, 'active', ?)")
                    ->execute([$animalType . ' Group', $animalType, $animalCount, $farmId]);
            }
            
            $message = "Added {$animalCount} {$animalType}s to your farm!";
        }
        
        $pdo->prepare("UPDATE users SET onboarding_step = 3 WHERE id = ?")->execute([$userId]);
        
        header('Location: /Frontend/admin/onboarding.php?step=4');
        exit;
    }
    
    if ($postStep === 4) {
        // Complete onboarding
        $pdo->prepare("UPDATE users SET onboarding_step = 4 WHERE id = ?")->execute([$userId]);
        $_SESSION['onboarding_complete'] = true;
        
        header('Location: /Frontend/admin/dashboard.php?welcome=1');
        exit;
    }
}

// Check if onboarding is already complete
$check = $pdo->prepare("SELECT onboarding_step FROM users WHERE id = ?");
$check->execute([$userId]);
$userStep = (int)($check->fetchColumn() ?? 0);
if ($userStep >= 4 && $step < 4) {
    header('Location: /Frontend/admin/dashboard.php');
    exit;
}

$page_title = 'Setup Your Farm';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup — Wangari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter+Tight:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Frontend/assets/css/tokens.css">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: var(--w-background);
            font-family: var(--w-font-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .onboard-container {
            width: 100%;
            max-width: 560px;
            padding: var(--w-space-6);
        }
        .onboard-card {
            background: var(--w-surface);
            border: 1px solid var(--w-border);
            border-radius: var(--w-radius-2xl);
            box-shadow: var(--w-shadow-lg);
            padding: var(--w-space-10);
            animation: w-slideUp 0.4s var(--w-ease-spring);
        }
        .onboard-progress {
            display: flex;
            gap: var(--w-space-2);
            margin-bottom: var(--w-space-8);
        }
        .onboard-step {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: var(--w-border);
            transition: background 0.3s var(--w-ease);
        }
        .onboard-step.active {
            background: var(--w-primary);
        }
        .onboard-step.done {
            background: var(--w-accent);
        }
        .onboard-icon {
            width: 72px;
            height: 72px;
            border-radius: var(--w-radius-xl);
            background: linear-gradient(135deg, var(--w-primary) 0%, var(--w-primary-light) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--w-space-6);
            box-shadow: 0 8px 24px rgba(22, 101, 52, 0.2);
        }
        .onboard-icon svg {
            width: 32px;
            height: 32px;
        }
        .onboard-title {
            margin: 0 0 var(--w-space-2);
            font-family: var(--w-font-display);
            font-size: var(--w-text-2xl);
            font-weight: var(--w-weight-extrabold);
            color: var(--w-text);
            text-align: center;
            letter-spacing: -0.02em;
        }
        .onboard-subtitle {
            margin: 0 0 var(--w-space-8);
            font-size: var(--w-text-sm);
            color: var(--w-text-muted);
            text-align: center;
            line-height: var(--w-leading-relaxed);
        }
        .onboard-form {
            display: flex;
            flex-direction: column;
            gap: var(--w-space-5);
        }
        .onboard-actions {
            display: flex;
            gap: var(--w-space-3);
            margin-top: var(--w-space-6);
        }
        .onboard-actions .w-btn {
            flex: 1;
        }
        .animal-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--w-space-3);
        }
        .animal-option {
            padding: var(--w-space-4);
            border: 2px solid var(--w-border);
            border-radius: var(--w-radius-lg);
            text-align: center;
            cursor: pointer;
            transition: all var(--w-duration-base) var(--w-ease);
            background: var(
