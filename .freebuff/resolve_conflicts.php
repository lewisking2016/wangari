<?php
/**
 * Surgically resolve rebase conflicts that are purely the admin role-check line.
 * Keeps our side (bd9f827: wangariadmin redirect + V2 design) but adds
 * 'sales_staff' to the role array when the remote side had it.
 * Reports anything that doesn't match the pattern.
 */
$files = array_slice($argv, 1);
$report = [];

foreach ($files as $f) {
    $src = file_get_contents($f);
    if ($src === false || strpos($src, '<<<<<<<') === false) continue;

    $pattern = '/<<<<<<< HEAD\r?\n(.*?)\r?\n=======\r?\n(.*?)\r?\n>>>>>>> bd9f827[^\n]*\r?\n/s';
    $count = preg_match_all($pattern, $src, $m, PREG_SET_ORDER);

    $changed = false;
    foreach ($m as $i => $match) {
        $remoteSide = $match[1];
        $oursSide   = $match[2];

        $isRoleConflict =
            strpos($remoteSide, "in_array(\$_SESSION['role']") !== false ||
            strpos($remoteSide, 'in_array($_SESSION["role"]') !== false;

        if (!$isRoleConflict) {
            $report[] = "MANUAL NEEDED in $f (block $i): not a role check";
            continue;
        }

        // Keep ours; ensure sales_staff present if remote had it
        if (strpos($remoteSide, "'sales_staff'") !== false &&
            strpos($oursSide, "'sales_staff'") === false) {
            // Insert into our role array right after 'farm_manager'
            $oursSide = preg_replace(
                "/'farm_manager'(\s*)\]/",
                "'farm_manager','sales_staff'$1]",
                $oursSide,
                1,
                $replaced
            );
            if ($replaced === 0) {
                // try spaced variant
                $oursSide = preg_replace(
                    "/'farm_manager',\s*\]/",
                    "'farm_manager', 'sales_staff']",
                    $oursSide,
                    1,
                    $replaced
                );
            }
            if ($replaced === 0) {
                $report[] = "MANUAL NEEDED in $f (block $i): could not insert sales_staff";
                continue;
            }
        }

        $src = str_replace($match[0], $oursSide . "\n", $src);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($f, $src);
        echo "RESOLVED $f (" . count($m) . " blocks)\n";
    } else {
        echo "NO CHANGE $f\n";
    }
}

if ($report) {
    echo "\n===== MANUAL REVIEW NEEDED =====\n";
    foreach ($report as $r) echo "$r\n";
}
