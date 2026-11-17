<?php
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$in = json_decode($raw, true) ?: [];

$allowed = ['Dad', 'Keyfiyyət', 'Porsiya', 'Təmizlik', 'Digər'];

$dish   = isset($in['dish']) ? (int)$in['dish'] : 0;
$name   = isset($in['name']) ? mb_substr(trim((string)$in['name']), 0, 80) : '';
$reason = isset($in['reason']) ? trim((string)$in['reason']) : '';
$text   = isset($in['text']) ? mb_substr(trim((string)$in['text']), 0, 200) : '';
$table  = isset($in['table']) ? preg_replace('/[^0-9]/', '', (string)$in['table']) : '';

if ($dish < 1 || !in_array($reason, $allowed, true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Səbəb seçilməyib']);
    exit;
}

$logDir = __DIR__ . '/../data';
if (!is_dir($logDir)) mkdir($logDir, 0777, true);

$entry = [
    'time'   => date('Y-m-d H:i:s'),
    'ip'     => $_SERVER['REMOTE_ADDR'] ?? '-',
    'table'  => $table === '' ? null : (int)$table,
    'dish'   => $dish,
    'name'   => $name,
    'reason' => $reason,
    'text'   => $text,
];

file_put_contents(
    $logDir . '/reports.log',
    json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

echo json_encode(['ok' => true]);
