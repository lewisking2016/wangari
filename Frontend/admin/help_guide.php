<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . chr(47) . "Backend/config/database.php";
require_once dirname(__DIR__, 2) . chr(47) . "Backend/config/session.php";
wangariStartSession();
if (empty([chr(117) . "ser_id"])) { header("Location: /Frontend/pages/login.php"); exit; }

// Handle profile update
if (["REQUEST_METHOD"] === "POST" && isset(["update_profile"])) {
    $pdo = getDatabaseConnection();
    $u = trim($_POST["new_username"] ?? "");
    $f = trim($_POST["new_full_name"] ?? "");
    $id = (int)$_SESSION["user_id"];
    if ($u !== "" && strlen($u) >= 3) {
        $s = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $s->execute([$u, $id]);
        if (!$s->fetch()) {
            $pdo->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$u, $id]);
            $_SESSION["username"] = $u;
        }
    }
    if ($f !== "") {
        $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?")->execute([$f, $id]);
        $_SESSION["first_name"] = $f;
    }
    header("Location: /Frontend/admin/help_guide.php?updated=1");
    exit;
}
?>

include __DIR__ . "/includes/admin_header.php";
?>