<?php
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$in = json_decode($raw, true) ?: [];

$table = isset($in['table']) ? preg_replace('/[^0-9]/', '', (string)$in['table']) : '';
$note  = isset($in['note']) ? mb_substr(trim((string)$in['note']), 0, 120) : '';

if ($table === '' || (int)$table < 1 || (int)$table > 100) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Masa nömrəsi yanlışdır']);
  exit;
}

$logDir = __DIR__ . '/../data';
if (!is_dir($logDir)) mkdir($logDir, 0777, true);

$entry = [
  'time'  => date('Y-m-d H:i:s'),
  'ip'    => $_SERVER['REMOTE_ADDR'] ?? '-',
  'table' => (int)$table,
  'note'  => $note,
];

file_put_contents(
  $logDir . '/calls.log',
  json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL,
  FILE_APPEND | LOCK_EX
);

echo json_encode(['ok' => true]);
