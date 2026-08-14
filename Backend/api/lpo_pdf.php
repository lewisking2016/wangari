<?php
/**
 * LPO PDF — download a quotation / LPO / invoice as a real PDF file.
 *
 * Pure-PHP PDF writer (no composer, no external libraries) so it works on
 * shared hosting. Includes the farm logo and contact details (from App
 * Settings) so the document is presentable for customers.
 *
 * Usage: /Backend/api/lpo_pdf.php?id=DOC_ID
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auto_migrate.php';
require_once __DIR__ . '/../../Frontend/includes/config.php';

// ── Auth: must be a logged-in staff member with LPO view permission ──
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager', 'sales_staff'], true)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}
$pdo = getDatabaseConnection();
if (!$pdo) { http_response_code(500); exit('Database unavailable'); }
$role = $_SESSION['role'];
if ($role !== 'super_admin') {
    $perms = function_exists('busiaRolePermissions') ? busiaRolePermissions($pdo) : [];
    if (!(($perms[$role]['lpo']['view'] ?? 0) || ($perms[$role]['lpo']['edit'] ?? 0))) {
        http_response_code(403);
        exit('You do not have permission to view LPO documents');
    }
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit('Invalid document id');

$stmt = $pdo->prepare('SELECT * FROM lpo_documents WHERE id=?');
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) { http_response_code(404); exit('Document not found'); }
$itemsStmt = $pdo->prepare('SELECT * FROM lpo_items WHERE doc_id=? ORDER BY id');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$farmName  = getSetting('farm_name', 'Busia Chicken Farm');
$farmEmail = getSetting('farm_email', 'info@busiachicken.com');
$farmPhone = getSetting('farm_phone', '+254 700 000 000');
$farmAddr  = getSetting('farm_address', 'Busia, Kenya');
$currency  = getSetting('currency', 'KES');
$typeLabel = ['quotation' => 'QUOTATION', 'lpo' => 'LOCAL PURCHASE ORDER', 'invoice' => 'INVOICE'][$doc['doc_type']] ?? strtoupper($doc['doc_type']);

/* ════════════════════════════════════════════════════════════════
   Minimal pure-PHP PDF builder (A4, Helvetica core fonts)
   ════════════════════════════════════════════════════════════════ */

/** Convert a UTF-8 string to PDF-literal-safe Windows-1252 bytes. */
function pdfText(string $s): string {
    $s = mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    return $s;
}

/** Approximate Helvetica glyph widths (1/1000 em) for alignment/wrapping. */
function pdfWidth(string $s): float {
    static $w = [
        32=>278,33=>278,34=>355,35=>556,36=>556,37=>889,38=>667,39=>191,40=>333,41=>333,42=>389,43=>584,44=>278,45=>333,46=>278,47=>278,
        48=>556,49=>556,50=>556,51=>556,52=>556,53=>556,54=>556,55=>556,56=>556,57=>556,58=>278,59=>278,60=>584,61=>584,62=>584,63=>556,64=>1015,
        65=>667,66=>667,67=>722,68=>722,69=>667,70=>611,71=>778,72=>722,73=>278,74=>500,75=>667,76=>556,77=>833,78=>722,79=>778,80=>667,81=>778,82=>722,83=>667,84=>611,85=>722,86=>667,87=>944,88=>667,89=>667,90=>611,
        91=>278,92=>278,93=>278,94=>469,95=>556,96=>333,97=>556,98=>556,99=>500,100=>556,101=>556,102=>278,103=>556,104=>556,105=>222,106=>222,107=>500,108=>222,109=>833,110=>556,111=>556,112=>556,113=>556,114=>333,115=>500,116=>278,117=>556,118=>500,119=>722,120=>500,121=>500,122=>500,123=>334,124=>260,125=>334,126=>584,
    ];
    $len = 0.0;
    $bytes = mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    for ($i = 0, $n = strlen($bytes); $i < $n; $i++) {
        $c = ord($bytes[$i]);
        $len += $w[$c] ?? 556;
    }
    return $len / 1000;
}

function pdfWrap(string $s, float $maxWidth, float $size = 9): array {
    $out = [];
    $words = preg_split('/\s+/', trim($s)) ?: [];
    $line = '';
    foreach ($words as $word) {
        $candidate = $line === '' ? $word : $line . ' ' . $word;
        // pdfWidth() returns ems; convert to points with the font size.
        if (pdfWidth($candidate) * $size <= $maxWidth || $line === '') {
            $line = $candidate;
        } else {
            $out[] = $line;
            $line = $word;
        }
    }
    if ($line !== '') $out[] = $line;
    return $out ?: [''];
}

/** Convert the farm logo PNG → JPEG. The Wangari logo is an opaque cream
 *  square, so it is composited onto its own background colour so the tile
 *  sits cleanly on the green header band. */
function pdfLogoJpeg(): ?string {
    $png = dirname(__DIR__, 2) . '/Frontend/images/wangari-logo.png';
    $cache = sys_get_temp_dir() . '/wangari_logo_577.jpg';
    if (!is_file($png) || !function_exists('imagecreatefrompng')) return null;
    if (is_file($cache) && filemtime($cache) >= filemtime($png)) return $cache;
    try {
        $src = @imagecreatefrompng($png);
        if (!$src) return null;
        $w = imagesx($src); $h = imagesy($src);
        $canvas = imagecreatetruecolor($w, $h);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);
        $cream = imagecolorallocate($canvas, 245, 242, 235); // logo's own cream background
        imagefill($canvas, 0, 0, $cream);
        imagecopy($canvas, $src, 0, 0, 0, 0, $w, $h);
        imagejpeg($canvas, $cache, 90);
        imagedestroy($src); imagedestroy($canvas);
        return $cache;
    } catch (Throwable $e) {
        return null;
    }
}

$pdf = new class($doc, $items) {
    public array $doc; public array $items;
    public float $W = 595.28, $H = 841.89, $margin = 40;
    public float $y;
    public array $pages = [];     // ['ops' => string]
    public int $pageNo = 0;
    public array $fonts = [];
    public ?array $logo = null;   // ['file','w','h'] in px

    public function __construct(array $doc, array $items) {
        $this->doc = $doc; $this->items = $items;
        $this->newPage();
    }
    public function op(string $s): void { $this->pages[$this->pageNo - 1]['ops'] .= $s . "\n"; }
    public function newPage(): void {
        $this->pages[] = ['ops' => ''];
        $this->pageNo = count($this->pages);
        $this->y = $this->margin; // top-down: content starts at the top margin
    }
    /** The y cursor is top-down and grows as content is drawn. Paginate when
     *  the next block would cross the bottom margin (H - margin). */
    public function ensure(float $h): void { if ($this->y + $h > $this->H - $this->margin) $this->newPage(); }

    public function setFill(array $c): void { $this->op(sprintf('%.3f %.3f %.3f rg', $c[0], $c[1], $c[2])); }
    public function rect(float $x, float $y, float $w, float $h, array $c): void {
        $this->setFill($c);
        $this->op(sprintf('%.2f %.2f %.2f %.2f re f', $x, $this->H - $y - $h, $w, $h));
    }
    public function line(float $x1, float $y1, float $x2, float $y2, float $lw, array $c): void {
        $this->setFill($c);
        $this->op(sprintf('%.2f w %.2f %.2f m %.2f %.2f l S', $lw, $x1, $this->H - $y1, $x2, $this->H - $y2));
    }

    public function text(float $x, float $y, string $s, float $size = 9, string $font = 'F1', array $color = [0.12, 0.16, 0.24], string $align = 'left', float $maxWidth = 0): void {
        $fontRes = $this->fontRes($font);
        $this->setFill($color);
        $this->op('BT /' . $fontRes . ' ' . sprintf('%.1f', $size) . ' Tf');
        if ($align !== 'left' || $maxWidth > 0) {
            $txt = $s;
            if ($maxWidth > 0) $txt = pdfWrap($s, $maxWidth, $size)[0];
            $tw = pdfWidth($txt) * $size;
            if ($align === 'right') $x -= $tw;
            if ($align === 'center') $x -= $tw / 2;
            $s = $txt;
        }
        $this->op(sprintf('%.2f %.2f Td (%s) Tj ET', $x, $this->H - $y, pdfText($s)));
    }

    public function textWrap(float $x, float $y, string $s, float $size, float $maxWidth, string $font = 'F1', array $color = [0.12, 0.16, 0.24]): float {
        $lines = pdfWrap($s, $maxWidth, $size);
        $lh = $size * 1.35;
        foreach ($lines as $i => $line) {
            if ($i > 0) $this->ensure($lh);
            $this->text($x, $y + $i * $lh, $line, $size, $font, $color, 'left');
        }
        return count($lines) * $lh;
    }

    public function fontRes(string $name): string {
        $this->fonts[$name] = true;
        static $map = ['F1' => 'F1', 'F2' => 'F2', 'F3' => 'F3', 'F4' => 'F4', 'F5' => 'F5'];
        return $map[$name] ?? 'F1';
    }
};

$G = $pdf; // short alias
$logoPath = pdfLogoJpeg();
if ($logoPath) {
    $info = @getimagesize($logoPath);
    if ($info) { $G->logo = ['file' => $logoPath, 'w' => $info[0], 'h' => $info[1]]; }
}
$green  = [0.106, 0.369, 0.125];   // #1B5E20 brand green
$greenD = [0.05, 0.22, 0.08];      // deep green for the total bar
$dark   = [0.07, 0.11, 0.17];      // #12202F near-black
$gray   = [0.39, 0.43, 0.48];      // #64748B
$amber  = [0.93, 0.76, 0.20];      // accent rule under the band
$bgSoft = [0.965, 0.98, 0.99];     // #F8FAFC
$white  = [1, 1, 1];

/** Format an ISO date as "12 Aug 2026" (fall back to the raw value). */
function pdfDate(string $s): string {
    $t = strtotime($s);
    return $t ? date('j M Y', $t) : $s;
}

/* ── Header band: logo + farm contact details on brand green ── */
$bandH = 96;
$G->rect(0, 0, $G->W, $bandH, $green);
$G->line(0, $bandH, $G->W, $bandH, 3, $amber);

$logoH = 60; $logoW = 0; $txtX = $G->margin;
if ($G->logo) {
    $logoW = $logoH * ($G->logo['w'] / $G->logo['h']);
    $G->images[] = $G->logo;
    $G->op(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /Im1 Do Q', $logoW, $logoH, $G->margin, $G->H - $bandH + 18));
    $txtX = $G->margin + $logoW + 18;
}
$G->text($txtX, 28, $farmName, 18, 'F2', $white);
$G->text($txtX, 52, $farmAddr, 9, 'F1', [0.90, 0.95, 0.92]);
$G->text($txtX, 67, 'Phone: ' . $farmPhone, 9, 'F1', [0.90, 0.95, 0.92]);
$G->text($txtX, 82, 'Email: ' . $farmEmail, 9, 'F1', [0.90, 0.95, 0.92]);

/* ── Title block: type label, doc number, dates, status chip (right) ── */
$G->y = $bandH + 24;
$G->text($G->W - $G->margin, $G->y, $typeLabel, 20, 'F2', $green, 'right');
$G->y += 27;
$G->text($G->W - $G->margin, $G->y, $doc['doc_number'], 11, 'F2', $dark, 'right');
$G->y += 18;
$G->text($G->W - $G->margin, $G->y, 'Issue Date: ' . pdfDate($doc['issue_date']), 9, 'F1', $gray, 'right');
$G->y += 14;
if (!empty($doc['due_date'])) {
    $G->text($G->W - $G->margin, $G->y, 'Due Date: ' . pdfDate($doc['due_date']), 9, 'F1', $gray, 'right');
    $G->y += 14;
}
// status chip
$statusLabel = strtoupper($doc['status']);
$chipW = pdfWidth($statusLabel) * 8.5 + 20;
$chipX = $G->W - $G->margin - $chipW;
$paidChip = in_array($doc['status'], ['paid', 'completed'], true);
$G->rect($chipX, $G->y - 10, $chipW, 17, $paidChip ? $green : [0.92, 0.94, 0.96]);
$G->text($chipX + $chipW / 2, $G->y - 1, $statusLabel, 8.5, 'F2', $paidChip ? $white : $dark, 'center');
$G->y += 20;

/* ── Bill To panel (auto-height) ── */
$billLines = [[$doc['customer_name'], 'F2', 11, $dark]];
if (!empty($doc['customer_phone']))   $billLines[] = ['Phone: ' . $doc['customer_phone'], 'F1', 9, $gray];
if (!empty($doc['customer_email']))   $billLines[] = ['Email: ' . $doc['customer_email'], 'F1', 9, $gray];
if (!empty($doc['customer_address'])) $billLines[] = [$doc['customer_address'], 'F1', 9, $gray];
$boxTop = $G->y + 4;
$boxH = 17 + count($billLines) * 13 + 10;
$G->rect($G->margin, $boxTop - 4, 250, $boxH, $bgSoft);
$G->line($G->margin, $boxTop + 11, $G->margin + 250, $boxTop + 11, 0.8, [0.85, 0.89, 0.93]);
$G->text($G->margin + 12, $boxTop, 'BILL TO', 8.5, 'F2', $green);
$yy = $boxTop + 18;
foreach ($billLines as [$t, $f, $s, $c]) {
    $G->text($G->margin + 12, $yy, $t, $s, $f, $c);
    $yy += 13;
}
$G->y = $boxTop + $boxH + 12;

/* ── Items table ── */
$right  = $G->W - $G->margin;      // right content edge (555.28)
$colQty = $right - 240;            // QTY right edge
$colUnt = $right - 185;            // UNIT right edge
$colPrc = $right - 120;            // UNIT PRICE right edge
$colAmt = $right;                  // AMOUNT right edge
$tableW = $right - $G->margin;     // table width (515.28)
$lh     = 12.15;                   // 9pt line height for wrapping

$G->rect($G->margin, $G->y - 4, $tableW, 22, $green);
$G->text($G->margin + 10, $G->y + 1, 'DESCRIPTION', 8.5, 'F2', $white);
$G->text($colQty - 8, $G->y + 1, 'QTY', 8.5, 'F2', $white, 'right');
$G->text($colUnt - 8, $G->y + 1, 'UNIT', 8.5, 'F2', $white, 'right');
$G->text($colPrc - 8, $G->y + 1, 'UNIT PRICE', 8.5, 'F2', $white, 'right');
$G->text($colAmt - 8, $G->y + 1, 'AMOUNT', 8.5, 'F2', $white, 'right');
$G->y += 26;

$alt = false;
foreach ($items as $it) {
    $descLines = pdfWrap((string)$it['description'], $colQty - $G->margin - 24, 9);
    $cellH = max(24, count($descLines) * $lh + 10);
    $G->ensure($cellH + 8);
    if ($alt) $G->rect($G->margin, $G->y - 4, $tableW, $cellH, [0.97, 0.98, 0.99]);
    $G->textWrap($G->margin + 10, $G->y + 3, (string)$it['description'], 9, $colQty - $G->margin - 24);
    $G->text($colQty - 8, $G->y + 3, number_format((float)$it['quantity'], 2), 9, 'F1', $dark, 'right');
    $G->text($colUnt - 8, $G->y + 3, (string)($it['unit'] ?: 'pcs'), 9, 'F1', $dark, 'right');
    $G->text($colPrc - 8, $G->y + 3, number_format((float)$it['unit_price'], 2), 9, 'F1', $dark, 'right');
    $G->text($colAmt - 8, $G->y + 3, number_format((float)$it['line_total'], 2), 9, 'F2', $dark, 'right');
    $G->line($G->margin, $G->y + $cellH - 1, $right, $G->y + $cellH - 1, 0.5, [0.88, 0.91, 0.94]);
    $G->y += $cellH + 1;
    $alt = !$alt;
}

/* ── Totals block ── */
$totW = 215;
$totX = $right - $totW;
$G->ensure(130); // keep subtotal + tax + total bar together on one page
$G->y += 10;
$totals = [['Subtotal', number_format((float)$doc['subtotal'], 2)]];
if ((float)$doc['tax_rate'] > 0) $totals[] = ['Tax (' . rtrim(rtrim(number_format((float)$doc['tax_rate'], 2), '0'), '.') . '%)', number_format((float)$doc['tax_amount'], 2)];
if ((float)$doc['discount'] > 0) $totals[] = ['Discount', '- ' . number_format((float)$doc['discount'], 2)];
foreach ($totals as [$lab, $val]) {
    $G->text($totX, $G->y, $lab, 9, 'F1', $gray);
    $G->text($totX + $totW, $G->y, $val, 9, 'F1', $dark, 'right');
    $G->y += 16;
}
$G->y += 4;
$G->rect($totX, $G->y - 13, $totW, 24, $greenD);
$G->text($totX + 12, $G->y - 4, 'TOTAL (' . $currency . ')', 11, 'F2', $white);
$G->text($totX + $totW - 12, $G->y - 4, number_format((float)$doc['total_amount'], 2), 12, 'F2', $white, 'right');
$G->y += 24;

/* ── Notes / terms (auto-height) ── */
if (!empty($doc['notes'])) {
    $noteLines = pdfWrap((string)$doc['notes'], $tableW - 24, 9);
    $noteH = count($noteLines) * $lh + 34;
    $G->ensure($noteH + 10);
    $G->rect($G->margin, $G->y - 4, $tableW, $noteH, [1.0, 0.98, 0.91]);
    $G->line($G->margin, $G->y - 4, $right, $G->y - 4, 0.8, [0.99, 0.91, 0.63]);
    $G->text($G->margin + 12, $G->y, 'NOTES / TERMS', 8.5, 'F2', [0.72, 0.45, 0.05]);
    $yy = $G->y + 16;
    foreach ($noteLines as $ln) {
        $G->text($G->margin + 12, $yy, $ln, 9, 'F1', [0.42, 0.32, 0.14]);
        $yy += $lh;
    }
    $G->y += $noteH + 8;
}

/* ── Footer (contact CTA) on every page ── */
foreach ($G->pages as $idx => $p) {
    $pageH = $G->H;
    $G->pages[$idx]['footer'] = [
        'y' => $pageH - 28,
        'line' => 'Thank you for your business! For orders and inquiries call ' . $farmPhone . ' or email ' . $farmEmail,
        'sub' => $farmName . ' • ' . $farmAddr,
        'page' => 'Page ' . ($idx + 1) . ' of ' . count($G->pages),
    ];
}

/* ════════════════════════════════════════════════════════════════
   Assemble the PDF objects + xref
   ════════════════════════════════════════════════════════════════ */
$objects = [];
$objCount = 0;
function pdfObj(array &$objects, string $body): int {
    $objects[] = $body;
    return count($objects);
}

$fontObjs = [
    'F1' => "<</Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding>>",
    'F2' => "<</Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding>>",
    'F3' => "<</Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding>>",
    'F4' => "<</Type /Font /Subtype /Type1 /BaseFont /Helvetica-BoldOblique /Encoding /WinAnsiEncoding>>",
];
$fontObjIds = [];
foreach ($fontObjs as $name => $body) {
    if (isset($G->fonts[$name])) $fontObjIds[$name] = pdfObj($objects, $body);
}

// Logo image object (DCTDecode JPEG)
$imageObjId = null;
if ($G->logo) {
    $data = base64_encode((string)file_get_contents($G->logo['file']));
    $imageObjId = pdfObj($objects, "<</Type /XObject /Subtype /Image /Width {$G->logo['w']} /Height {$G->logo['h']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen(base64_decode($data)) . ">>\nstream\n" . base64_decode($data) . "\nendstream");
}

// Content streams per page (with footer ops)
$pageObjIds = [];
foreach ($G->pages as $i => $p) {
    $ops = $p['ops'];
    $f = $p['footer'];
    // footer
    $ops .= "BT /F1 " . sprintf('%.1f', 8.5) . " Tf 0.49 0.53 0.58 rg " . sprintf('%.2f %.2f Td', $G->margin, $G->H - $f['y']) . " (" . pdfText($f['line']) . ") Tj ET\n";
    $ops .= "BT /F1 " . sprintf('%.1f', 8.5) . " Tf 0.49 0.53 0.58 rg " . sprintf('%.2f %.2f Td', $G->margin, $G->H - ($f['y'] - 13)) . " (" . pdfText($f['sub']) . ") Tj ET\n";
    $ops .= "BT /F1 " . sprintf('%.1f', 8.5) . " Tf 0.39 0.43 0.48 rg " . sprintf('%.2f %.2f Td', $G->W - $G->margin, $G->H - $f['y']) . " (" . pdfText($f['page']) . ") Tj ET\n";
    $ops .= sprintf('%.2f %.2f m %.2f %.2f l S', $G->margin, $G->H - ($f['y'] - 8), $G->W - $G->margin, $G->H - ($f['y'] - 8)) . "\n";
    $ops .= "0.85 0.89 0.93 RG 0.6 w\n";

    $stream = "q\n" . $ops . "\nQ";
    $streamLen = strlen($stream);
    $contentId = pdfObj($objects, "<</Length {$streamLen}>>\nstream\n" . $stream . "\nendstream");
    $resources = "<< /Font <<";
    foreach ($fontObjIds as $name => $id) $resources .= " /" . $name . " {$id} 0 R";
    $resources .= " >>";
    if ($imageObjId) $resources .= " /XObject << /Im1 {$imageObjId} 0 R >>";
    $resources .= " >>";
    $pageObjIds[] = pdfObj($objects, "<</Type /Page /Parent 2 0 R /MediaBox [0 0 {$G->W} {$G->H}] /Resources {$resources} /Contents {$contentId} 0 R>>");
}

$pagesRef = implode(' ', array_map(fn($id) => "{$id} 0 R", $pageObjIds));
$pageTreeId = pdfObj($objects, "<</Type /Pages /Kids [{$pagesRef}] /Count " . count($pageObjIds) . ">>");
$catalogId = pdfObj($objects, "<</Type /Catalog /Pages {$pageTreeId} 0 R>>");

$pdfBody = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $i => $body) {
    $offsets[] = strlen($pdfBody);
    $pdfBody .= ($i + 1) . " 0 obj\n" . $body . "\nendobj\n";
}
$xrefStart = strlen($pdfBody);
$pdfBody .= "xref\n0 " . (count($objects) + 1) . "\n";
$pdfBody .= "0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) {
    $pdfBody .= sprintf("%010d 00000 n \n", $offsets[$i]);
}
$pdfBody .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalogId} 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: ' . (($_GET['inline'] ?? '') === '1' ? 'inline' : 'attachment') . '; filename="' . $doc['doc_number'] . '.pdf"');
header('Content-Length: ' . strlen($pdfBody));
echo $pdfBody;
