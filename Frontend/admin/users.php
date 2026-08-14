<?php
/**
 * Admin - User Management & Profile Settings
 * Clean SaaS Minimalist Design
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}
session_start();

$path_prefix = '../../';
$page_title = 'Manage Users - Admin';

include __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../../Backend/api/dropdowns.php';

// Check admin access
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager','sales_staff'], true)) {
    header("Location: /wangariadmin");
    exit;
}

$pdo = getDB();
$error_message = '';
$success_message = '';
$current_admin_id = (int)$_SESSION['user_id'];

// Process actions (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    // Action 1: Update Logged-In Admin's Profile
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $new_password = $_POST['new_password'] ?? '';

        if (empty($username) || empty($email)) {
            $error_message = "Username and Email are required fields.";
        } else {
            try {
                // Check if username/email already taken by someone else
                $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $current_admin_id]);
                if ($stmt->fetch()) {
                    $error_message = "Username or Email is already in use by another account.";
                } else {
                    if (!empty($new_password)) {
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, password_hash = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $first_name, $last_name, $hash, $current_admin_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $first_name, $last_name, $current_admin_id]);
                    }

                    // Update session variables
                    $_SESSION['username'] = $username;
                    $_SESSION['first_name'] = $first_name;

                    $success_message = "Your profile details have been updated successfully.";
                }
            } catch (Exception $e) {
                $error_message = "Failed to update profile: " . $e->getMessage();
            }
        }
    }

    // Action 2: Update another user's role
    if ($action === 'update_role') {
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);
        $new_role = $_POST['role'] ?? 'customer';

        if ($target_user_id === $current_admin_id) {
            $error_message = "You cannot modify your own role from this panel.";
        } elseif (in_array($new_role, ['super_admin', 'farm_manager', 'stock_manager', 'sales_staff', 'customer'], true)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $target_user_id]);
                $success_message = "User role updated successfully.";
            } catch (Exception $e) {
                $error_message = "Failed to update user role: " . $e->getMessage();
            }
        }
    }

    // Action 3: Delete user
    if ($action === 'delete_user') {
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);

        if ($target_user_id === $current_admin_id) {
            $error_message = "You cannot delete your own logged-in account.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$target_user_id]);
                $success_message = "User account deleted successfully.";
            } catch (Exception $e) {
                $error_message = "Failed to delete user: " . $e->getMessage();
            }
        }
    }

    // Action 4: Reset user's password (by admin)
    if ($action === 'reset_password') {
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);
        $new_pass = $_POST['new_password'] ?? '';

        if (empty($new_pass)) {
            $error_message = "New password cannot be empty.";
        } else {
            try {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hash, $target_user_id]);
                $success_message = "Password reset successfully for user ID #{$target_user_id}.";
            } catch (Exception $e) {
                $error_message = "Failed to reset password: " . $e->getMessage();
            }
        }
    }

    // Action 5: Add New User
    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'customer';

        if (empty($username) || empty($email) || empty($password)) {
            $error_message = "All fields are required.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error_message = "Username or Email already exists.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hash, $role]);
                    $success_message = "New user account created successfully.";
                }
            } catch (Exception $e) {
                $error_message = "Failed to add user: " . $e->getMessage();
            }
        }
    }
}

// Fetch current admin details
$admin_profile = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$current_admin_id]);
        $admin_profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        error_log("Failed to fetch admin profile: " . $e->getMessage());
    }
}

// Fetch users list
$users_list = [];
if ($pdo) {
    try {
        $search = $_GET['search'] ?? '';
        $role_filter = $_GET['role'] ?? '';

        $query = "SELECT id, username, email, first_name, last_name, role, created_at FROM users WHERE id != ?";
        $params = [$current_admin_id];

        if (!empty($search)) {
            $query .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $search_param = "%$search%";
            array_push($params, $search_param, $search_param, $search_param, $search_param);
        }

        if (!empty($role_filter)) {
            $query .= " AND role = ?";
            $params[] = $role_filter;
        }

        $query .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Admin users query error: " . $e->getMessage());
    }
}
?>

<!-- Alerts -->
<?php if ($success_message): ?>
<div style="padding: 12px 20px; background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 4px; color: #15803d; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
    <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
    <?php echo htmlspecialchars($success_message); ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding: 12px 20px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 4px; color: #b91c1c; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
    <i data-lucide="alert-circle" style="width: 16px; height: 16px;"></i>
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<!-- Title & Main Content Section -->
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start; margin-bottom: 32px;">

    <!-- Left Column: My Profile Edit (For currently logged in admin) -->
    <div class="admin-card">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">
            <i data-lucide="user-cog" style="width: 20px; height: 20px; color: var(--admin-primary);"></i>
            <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: var(--admin-text-heading);">My Admin Account</h3>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="admin-form-group">
                <label class="admin-form-label">Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($admin_profile['username'] ?? ''); ?>" required class="admin-form-control">
            </div>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($admin_profile['email'] ?? ''); ?>" required class="admin-form-control">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="admin-form-group">
                    <label class="admin-form-label">First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($admin_profile['first_name'] ?? ''); ?>" class="admin-form-control">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($admin_profile['last_name'] ?? ''); ?>" class="admin-form-control">
                </div>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Change Password (leave blank to keep current)</label>
                <input type="password" name="new_password" placeholder="••••••••" class="admin-form-control">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; border-radius: 4px; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;">
                <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                <span>Save Profile Settings</span>
            </button>
        </form>
    </div>

    <!-- Right Column: Other User registry and password control -->
    <div>
        <div class="admin-card" style="padding: 0; overflow: hidden; border-radius: 4px;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--admin-border); background: #fafafa; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: var(--admin-text-heading);">Registered Users & Accounts</h3>
                <button class="btn btn-primary btn-sm" onclick="openAddUserModal()">
                    <i data-lucide="plus"></i> Add User
                </button>
            </div>
            
            <!-- Filters & Search -->
            <form method="GET" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #ffffff; border-bottom: 1px solid var(--admin-border); flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 200px;">
                    <i data-lucide="search" style="width: 18px; height: 18px; color: #94a3b8;"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search customers..." style="background: transparent; border: none; outline: none; font-size: 0.9rem; width: 100%;">
                </div>
                <div style="display: flex; gap: 8px;">
                    <select name="role" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; outline: none; background: #ffffff;">
                        <?php echo renderDropdownOptions('user_roles', $_GET['role'] ?? '', 'All Roles'); ?>
                    </select>
                    <button type="submit" class="btn btn-outline" style="border-radius: 4px; padding: 6px 16px; font-size: 0.85rem;">Filter</button>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Role Status</th>
                            <th style="text-align: right;">Action Tools</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users_list)): ?>
                            <?php foreach ($users_list as $usr): ?>
                            <?php 
                                $role_badge = 'badge-pill-warning';
                                if ($usr['role'] === 'super_admin') {
                                    $role_badge = 'badge-pill-danger';
                                } elseif ($usr['role'] === 'farm_manager') {
                                    $role_badge = 'badge-pill-success';
                                }
                            ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 600; color: var(--admin-primary);">
                                    #<?php echo $usr['id']; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--admin-text-heading);">
                                        <?php echo htmlspecialchars(trim(($usr['first_name'] ?? '') . ' ' . ($usr['last_name'] ?? '')) ?: 'N/A'); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #475569;">@<?php echo htmlspecialchars($usr['username']); ?></div>
                                </td>
                                <td style="font-size: 0.9rem; font-weight: 500; color: var(--admin-text-heading);">
                                    <?php echo htmlspecialchars($usr['email']); ?>
                                </td>
                                <td>
                                    <form method="POST" style="margin: 0; display: inline-flex; gap: 4px; align-items: center;">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="target_user_id" value="<?php echo $usr['id']; ?>">
                                        <select name="role" onchange="this.form.submit()" style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem; outline: none; background: #ffffff;">
                                            <?php echo renderDropdownOptions('user_roles', $usr['role'], ''); ?>
                                        </select>
                                    </form>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                        <!-- Reset Password Button -->
                                        <button title="Reset Password" onclick="openResetModal(<?php echo $usr['id']; ?>, '<?php echo htmlspecialchars($usr['username']); ?>')" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; transition: color 0.2s;" onmouseover="this.style.color='var(--admin-primary)'" onmouseout="this.style.color='#94a3b8'">
                                            <i data-lucide="key-round" style="width: 16px; height: 16px;"></i>
                                        </button>
                                        
                                        <!-- Delete User Form -->
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" style="margin: 0;">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="target_user_id" value="<?php echo $usr['id']; ?>">
                                            <button type="submit" title="Delete User" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; transition: color 0.2s;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">
                                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #64748b;">No matching accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ========== RESET PASSWORD MODAL ========== -->
<div id="reset-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 4px; width: 100%; max-width: 400px; padding: 32px; box-shadow: 0 24px 48px rgba(0,0,0,0.15); position: relative;">
        <button onclick="document.getElementById('reset-modal').style.display='none'" style="position: absolute; top: 16px; right: 16px; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 1.2rem;">✕</button>
        <h3 style="margin: 0 0 8px 0; font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--admin-text-heading);">Reset User Password</h3>
        <p style="margin: 0 0 20px 0; font-size: 0.85rem; color: #475569;" id="reset-modal-subtitle">Reset password for user.</p>
        
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="target_user_id" id="reset-target-id">
            
            <div class="admin-form-group">
                <label class="admin-form-label">New Password</label>
                <input type="password" name="new_password" required minlength="6" placeholder="Enter new password" class="admin-form-control">
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="document.getElementById('reset-modal').style.display='none'" class="btn btn-outline" style="border-radius: 4px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 4px;">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== ADD USER MODAL ========== -->
<div id="add-user-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 4px; width: 100%; max-width: 450px; padding: 32px; box-shadow: 0 24px 48px rgba(0,0,0,0.15); position: relative;">
        <button onclick="document.getElementById('add-user-modal').style.display='none'" style="position: absolute; top: 16px; right: 16px; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 1.2rem;">✕</button>
        <h3 style="margin: 0 0 20px 0; font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--admin-text-heading);">Create New User</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            
            <div class="admin-form-group">
                <label class="admin-form-label">Username</label>
                <input type="text" name="username" required placeholder="e.g. john_doe" class="admin-form-control">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Email Address</label>
                <input type="email" name="email" required placeholder="e.g. john@example.com" class="admin-form-control">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Initial Password</label>
                <input type="password" name="password" required minlength="6" placeholder="••••••••" class="admin-form-control">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Assigned Role</label>
                <select name="role" class="admin-form-control">
                    <option value="customer">Customer</option>
                    <option value="sales_staff">Sales Staff (Sales &amp; Finance)</option>
                    <option value="stock_manager">Stock Manager (Limited to Stock)</option>
                    <option value="farm_manager">Farm Manager (Full Access)</option>
                    <option value="super_admin">Super Admin (System Owner)</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="document.getElementById('add-user-modal').style.display='none'" class="btn btn-outline" style="border-radius: 4px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 4px;">Create Account</button>
            </div>
        </form>
    </div>
</div>

<script>
function openResetModal(userId, username) {
    document.getElementById('reset-target-id').value = userId;
    document.getElementById('reset-modal-subtitle').textContent = "Set a new password for account @" + username;
    document.getElementById('reset-modal').style.display = 'flex';
}

function openAddUserModal() {
    document.getElementById('add-user-modal').style.display = 'flex';
}
</script>

<?php
include __DIR__ . '/includes/admin_footer.php';
?>
