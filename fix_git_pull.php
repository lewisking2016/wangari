<?php
/**
 * Fix Git Pull Conflicts
 * Run this once in browser to fix pull errors
 */

header('Content-Type: text/html; charset=UTF-8');
echo "<!DOCTYPE html><html><head><title>Fix Git</title>";
echo "<style>body{font-family:monospace;padding:40px;background:#1e1e1e;color:#00ff00;max-width:900px;margin:0 auto;}";
echo ".ok{color:#00ff00;} .fail{color:#ff0000;} .info{color:#00aaff;}</style></head><body>";
echo "<h1>🔧 Git Pull Fixer</h1>";

$docRoot = $_SERVER['DOCUMENT_ROOT'];
echo "<p class='info'>Document Root: $docRoot</p>";

// Change to document root
chdir($docRoot);

// Step 1: Remove conflicting files from git tracking
echo "<h2>Step 1: Removing conflicting files from git...</h2>";
$files = ['.htaccess', 'wangariadmin.php', 'complete_data_import.php', 'import_data.php', 'update_admin_password.php', 'test_assets.php', 'status.php'];

foreach ($files as $file) {
    if (file_exists($file)) {
        exec("git rm --cached $file 2>&1", $output, $return);
        if ($return === 0) {
            echo "<p class='ok'>✓ Removed $file from git tracking</p>";
        } else {
            echo "<p class='info'>• $file not in git or already removed</p>";
        }
    }
}

// Step 2: Add updated .gitignore
echo "<h2>Step 2: Updating .gitignore...</h2>";
exec("git add .gitignore 2>&1", $output, $return);
if ($return === 0) {
    echo "<p class='ok'>✓ Added .gitignore</p>";
} else {
    echo "<p class='fail'>✗ Failed to add .gitignore</p>";
}

// Step 3: Commit changes
echo "<h2>Step 3: Committing changes...</h2>";
exec('git commit -m "Remove server files from tracking" 2>&1', $output, $return);
if ($return === 0) {
    echo "<p class='ok'>✓ Changes committed</p>";
} else {
    echo "<p class='info'>• Nothing to commit or already committed</p>";
}

// Step 4: Pull latest code
echo "<h2>Step 4: Pulling latest code...</h2>";
exec("git pull origin main 2>&1", $output, $return);
if ($return === 0) {
    echo "<p class='ok'>✓ Successfully pulled latest code!</p>";
    echo "<pre>" . implode("\n", $output) . "</pre>";
} else {
    echo "<p class='fail'>✗ Pull failed</p>";
    echo "<pre>" . implode("\n", $output) . "</pre>";
    
    // If still failing, try force pull
    echo "<h3>Trying force pull...</h3>";
    exec("git fetch origin 2>&1", $fetchOut);
    exec("git reset --hard origin/main 2>&1", $resetOut, $resetReturn);
    
    if ($resetReturn === 0) {
        echo "<p class='ok'>✓ Force reset successful!</p>";
    } else {
        echo "<p class='fail'>✗ Force reset failed</p>";
        echo "<pre>" . implode("\n", $resetOut) . "</pre>";
    }
}

echo "<h2>✅ Done!</h2>";
echo "<p class='ok'>You can now pull normally via cPanel Git interface.</p>";
echo "<p class='info'><strong>IMPORTANT: Delete this file (fix_git_pull.php) for security!</strong></p>";
echo "<p><a href='/status.php' style='color:#00aaff;'>Check Status</a> | <a href='/' style='color:#00aaff;'>Visit Homepage</a></p>";

echo "</body></html>";
?>
