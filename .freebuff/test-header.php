<?php
header('Content-Type: application/json');
echo json_encode(['xrw' => $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null, 'all' => array_keys(array_filter($_SERVER, fn($k)=>str_starts_with($k,'HTTP_'), ARRAY_FILTER_USE_KEY))]);
