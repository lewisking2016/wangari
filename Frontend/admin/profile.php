<?php
/**
 * Admin Profile Management Page
 * Allows viewing and editing profile details, connecting Google accounts, and updating password.
 */
declare(strict_types=1);

$page_title = 'My Profile — Wangari';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$userId = (int)($_SESSION['user_id'] ?? 0);
$successMsg = '';
$errorMsg = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $phone     = trim($_POST['phone_number'] ?? '');
    $farmName  = trim($_POST['farm_name'] ?? '');
    
    if (empty($firstName)) {
        $errorMsg = 'First name is required.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, farm_name = ? WHERE id = ?");
            $stmt->execute([$firstName, $lastName, $phone, $farmName, $userId]);
            
            $_SESSION['first_name'] = $firstName;
            $successMsg = 'Profile details updated successfully!';
        } catch (Exception $e) {
            $errorMsg = 'Failed to update profile: ' . $e->getMessage();
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    
    if (empty($newPass) || strlen($newPass) < 6) {
        $errorMsg = 'New password must be at least 6 characters.';
    } elseif ($newPass !== $confirmPass) {
        $errorMsg = 'New passwords do not match.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If user has a password set, verify it
            if (!empty($u['password_hash']) && !password_verify($currentPass, $u['password_hash'])) {
                $errorMsg = 'Incorrect current password.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $userId]);
                $successMsg = 'Password updated successfully!';
            }
        } catch (Exception $e) {
            $errorMsg = 'Failed to update password.';
        }
    }
}

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'username' => $_SESSION['username'] ?? 'User',
    'email' => $_SESSION['email'] ?? '',
    'first_name' => $_SESSION['first_name'] ?? 'Admin',
    'last_name' => '',
    'role' => $_SESSION['role'] ?? 'farm_manager',
    'phone_number' => '',
    'farm_name' => '',
    'google_id' => '',
    'profile_pic' => $_SESSION['profile_pic'] ?? ''
];

$roleLabels = [
    'super_admin' => 'Super Administrator',
    'farm_manager' => 'Farm Manager',
    'stock_manager' => 'Stock Manager',
    'sales_staff' => 'Sales Staff',
    'customer' => 'Customer'
];

$hasGoogle = !empty($user['google_id']);
$avatarUrl = $user['profile_pic'] ?: '';
?>

<div class="admin-main">
    <div class="admin-topbar">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 700; color: var(--admin-text-heading); margin: 0 0 4px 0;">User Profile & Settings</h1>
            <p style="font-size: 0.9rem; color: var(--admin-text-muted); margin: 0;">Manage your personal profile, linked accounts, and security credentials.</p>
        </div>
    </div>

    <div style="padding: 24px; max-width: 1000px;">
        <?php if (!empty($successMsg)): ?>
            <div style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="check-circle" style="width: 20px; height: 20px; color: #22C55E;"></i>
                <span><?php echo htmlspecialchars($successMsg); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="alert-circle" style="width: 20px; height: 20px; color: #EF4444;"></i>
                <span><?php echo htmlspecialchars($errorMsg); ?></span>
            </div>
        <?php endif; ?>

        <!-- Profile Overview Card -->
        <div style="background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); padding: 28px; margin-bottom: 24px; box-shadow: var(--admin-shadow); display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
            <div style="position: relative;">
                <?php if (!empty($avatarUrl)): ?>
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" style="width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #22C55E; box-shadow: 0 4px 14px rgba(0,0,0,0.08);">
                <?php else: ?>
                    <div style="width: 90px; height: 90px; border-radius: 50%; background: linear-gradient(135deg, #166534 0%, #22C55E 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 700; box-shadow: 0 4px 14px rgba(22,101,52,0.25);">
                        <?php echo strtoupper(substr($user['first_name'] ?: ($user['username'] ?: 'U'), 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($hasGoogle): ?>
                    <div style="position: absolute; bottom: 0; right: 0; background: #fff; border-radius: 50%; padding: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);" title="Connected to Google">
                        <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#EA4335" d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.114-5.136 4.114A5.99 5.99 0 0 1 8 12.5a5.99 5.99 0 0 1 5.99-6.015c1.558 0 2.973.597 4.05 1.576l3.078-3.078A9.97 9.97 0 0 0 13.99 2C8.472 2 4 6.472 4 12s4.472 10 9.99 10c5.305 0 9.774-3.842 10.01-9h-11.76Z"/></svg>
                    </div>
                <?php endif; ?>
            </div>

            <div style="flex: 1; min-width: 240px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
                    <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--admin-text-heading); margin: 0;">
                        <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                    </h2>
                    <span style="background: rgba(34, 197, 94, 0.12); color: #166534; font-size: 0.78rem; font-weight: 600; padding: 4px 10px; border-radius: 999px;">
                        <?php echo htmlspecialchars($roleLabels[$user['role'] ?? ''] ?? 'Member'); ?>
                    </span>
                </div>
                <p style="font-size: 0.9rem; color: var(--admin-text-muted); margin: 0 0 12px 0;">
                    @<?php echo htmlspecialchars($user['username']); ?> &bull; <?php echo htmlspecialchars($user['email']); ?>
                </p>
                <div style="display: flex; gap: 16px; font-size: 0.85rem; color: var(--admin-text-muted);">
                    <div><strong style="color: var(--admin-text-heading);">Farm:</strong> <?php echo htmlspecialchars($user['farm_name'] ?: 'Wangari Farm'); ?></div>
                    <div><strong style="color: var(--admin-text-heading);">Phone:</strong> <?php echo htmlspecialchars($user['phone_number'] ?: 'Not set'); ?></div>
                </div>
            </div>
        </div>

        <?php
        // Get subscription status
        require_once dirname(__DIR__, 2) . '/Backend/config/limits.php';
        $subStatus = 'trial';
        $subPlan = 'Free Trial';
        $subExpires = '';
        $maxAnimals = 5;
        $maxFields = 5;
        
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT subscription_status, subscription_expires, max_animals, max_fields FROM platform_users WHERE id = ?');
            $stmt->execute([$userId]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($sub) {
                $subStatus = $sub['subscription_status'] ?? 'trial';
                $subExpires = $sub['subscription_expires'] ?? '';
                $maxAnimals = (int)($sub['max_animals'] ?? 5);
                $maxFields = (int)($sub['max_fields'] ?? 5);
                
                if ($subStatus === 'active') {
                    if ($maxAnimals >= 200) $subPlan = 'Plus';
                    elseif ($maxAnimals >= 5) $subPlan = 'Pro';
                    else $subPlan = 'Free';
                } elseif ($subStatus === 'trial') {
                    $subPlan = 'Free Trial';
                } else {
                    $subPlan = ucfirst($subStatus);
                }
            }
        }
        
        $statusColors = [
            'active' => ['bg' => '#DCFCE7', 'text' => '#166534', 'border' => '#BBF7D0'],
            'trial' => ['bg' => '#FEF3C7', 'text' => '#92400E', 'border' => '#FDE68A'],
            'expired' => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'border' => '#FECACA'],
            'past_due' => ['bg' => '#FEF3C7', 'text' => '#92400E', 'border' => '#FDE68A'],
        ];
        $sc = $statusColors[$subStatus] ?? $statusColors['trial'];
        ?>

        <!-- Subscription Status Card -->
        <div style="background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); padding: 24px; margin-bottom: 24px; box-shadow: var(--admin-shadow);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #166534, #22C55E); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="credit-card" style="width: 24px; height: 24px; color: #fff;"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.78rem; color: var(--admin-text-muted); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; margin-bottom: 4px;">Subscription</div>
                        <div style="font-size: 1.25rem; font-weight: 700; color: var(--admin-text-heading);"><?php echo htmlspecialchars($subPlan); ?> Plan</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="text-align: right;">
                        <div style="background: <?php echo $sc['bg']; ?>; color: <?php echo $sc['text']; ?>; border: 1px solid <?php echo $sc['border']; ?>; padding: 6px 14px; border-radius: 999px; font-size: 0.82rem; font-weight: 700; text-transform: capitalize; display: inline-flex; align-items: center; gap: 6px;">
                            <?php if ($subStatus === 'active'): ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php elseif ($subStatus === 'trial'): ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?php else: ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($subStatus); ?>
                        </div>
                    </div>
                    <?php if ($subStatus !== 'active'): ?>
                    <a href="/Frontend/pages/pricing.php" style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #166534, #22C55E); color: #fff; padding: 10px 20px; border-radius: 999px; font-weight: 700; font-size: 0.88rem; text-decoration: none;">
                        <i data-lucide="zap" style="width: 16px; height: 16px;"></i>
                        Upgrade Plan
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--admin-border); display: flex; gap: 32px; flex-wrap: wrap; font-size: 0.88rem;">
                <div>
                    <span style="color: var(--admin-text-muted);">Animals Limit:</span>
                    <strong style="color: var(--admin-text-heading); margin-left: 6px;"><?php echo $maxAnimals > 0 ? $maxAnimals : 'Unlimited'; ?></strong>
                </div>
                <div>
                    <span style="color: var(--admin-text-muted);">Fields Limit:</span>
                    <strong style="color: var(--admin-text-heading); margin-left: 6px;"><?php echo $maxFields > 0 ? $maxFields : 'Unlimited'; ?></strong>
                </div>
                <?php if ($subExpires): ?>
                <div>
                    <span style="color: var(--admin-text-muted);"><?php echo $subStatus === 'trial' ? 'Trial Expires:' : 'Next Billing:'; ?></span>
                    <strong style="color: var(--admin-text-heading); margin-left: 6px;"><?php echo date('M j, Y', strtotime($subExpires)); ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            <!-- Profile Details Form -->
            <div style="background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); padding: 24px; box-shadow: var(--admin-shadow);">
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--admin-text-heading); margin: 0 0 18px 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="user-pen" style="width: 20px; height: 20px; color: var(--admin-primary);"></i>
                    <span>Personal Details</span>
                </h3>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--admin-text-main);">First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--admin-text-main);">Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--admin-text-main);">Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem; background: #F8FAFC; color: var(--admin-text-muted); box-sizing: border-box;">
                        <small style="color: var(--admin-text-muted); font-size: 0.78rem;">Email address is tied to your login authentication.</small>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--admin-text-main);">Phone Number</label>
                        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="+254 114 971 070" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--admin-text-main);">Farm / Business Name</label>
                        <input type="text" name="farm_name" value="<?php echo htmlspecialchars($user['farm_name'] ?? ''); ?>" placeholder="Wangari Smart Farm" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>

                    <button type="submit" name="update_profile" value="1" style="background: var(--admin-primary); color: #fff; border: none; border-radius: 8px; padding: 12px 20px; font-size: 0.95rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: opacity 0.2s;">
                        <i data-lucide="save" style="width: 18px; height: 18px;"></i>
                        <span>Save Profile Changes</span>
                    </button>
                </form>
            </div>

            <!-- Connected Accounts & Security -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Google Account Connection -->
                <div style="background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); padding: 24px; box-shadow: var(--admin-shadow);">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--admin-text-heading); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="link" style="width: 20px; height: 20px; color: var(--admin-primary);"></i>
                        <span>Linked Accounts</span>
                    </h3>

                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid var(--admin-border); border-radius: 12px; background: #FAFBFC;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="24" height="24" viewBox="0 0 24 24"><path fill="#EA4335" d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.114-5.136 4.114A5.99 5.99 0 0 1 8 12.5a5.99 5.99 0 0 1 5.99-6.015c1.558 0 2.973.597 4.05 1.576l3.078-3.078A9.97 9.97 0 0 0 13.99 2C8.472 2 4 6.472 4 12s4.472 10 9.99 10c5.305 0 9.774-3.842 10.01-9h-11.76Z"/></svg>
                            <div>
                                <div style="font-weight: 600; font-size: 0.95rem; color: var(--admin-text-heading);">Google Account</div>
                                <div style="font-size: 0.8rem; color: var(--admin-text-muted);">
                                    <?php echo $hasGoogle ? 'Linked with ' . htmlspecialchars($user['email']) : 'Sign in faster with single click'; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($hasGoogle): ?>
                            <span style="display: flex; align-items: center; gap: 6px; background: #DCFCE7; color: #15803D; font-weight: 600; font-size: 0.8rem; padding: 6px 12px; border-radius: 999px;">
                                <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                Connected
                            </span>
                        <?php else: ?>
                            <a href="/Frontend/auth/google/login.html" style="background: #000000; border: 1px solid #334155; color: #ffffff; font-weight: 600; font-size: 0.85rem; padding: 8px 14px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
                                <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                                <span>Connect Google</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Password Update Card -->
                <div style="background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); padding: 24px; box-shadow: var(--admin-shadow);">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--admin-text-heading); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="key" style="width: 20px; height: 20px; color: var(--admin-primary);"></i>
                        <span>Security & Password</span>
                    </h3>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--admin-text-main);">Current Password</label>
                            <input type="password" name="current_password" placeholder="••••••••" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--admin-text-main);">New Password</label>
                            <input type="password" name="new_password" placeholder="••••••••" required class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--admin-text-main);">Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="••••••••" required class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>

                        <button type="submit" name="change_password" value="1" style="background: #334155; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                            <span>Update Password</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
