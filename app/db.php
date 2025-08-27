<?php
// === DB CONNECTION (MySQLi) ===
$DB_HOST = '127.0.0.1';
$DB_NAME = 'pdf_saas';
$DB_USER = 'root';
$DB_PASS = '';
$DB_PORT = 3306;

$mysqli = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if (!$mysqli) { exit('database not connect'); }
mysqli_set_charset($mysqli, 'utf8mb4');

function db_all(string $sql, array $params=[]): array {
  global $mysqli;
  if (empty($params)) {
    $res = mysqli_query($mysqli, $sql);
    return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
  }
  $stmt = mysqli_prepare($mysqli, $sql);
  if (!$stmt) return [];
  $types = str_repeat('s', count($params));
  mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
  mysqli_stmt_close($stmt);
  return $rows;
}

function db_row(string $sql, array $params=[]): ?array {
  $rows = db_all($sql, $params);
  return $rows[0] ?? null;
}

function db_one(string $sql, array $params=[]): ?string {
  $row = db_row($sql, $params);
  if (!$row) return null;
  return array_shift($row);
}

function db_exec(string $sql, array $params=[]): bool {
  global $mysqli;
  if (empty($params)) return (bool)mysqli_query($mysqli, $sql);
  $stmt = mysqli_prepare($mysqli, $sql);
  if (!$stmt) return false;
  $types = str_repeat('s', count($params));
  mysqli_stmt_bind_param($stmt, $types, ...$params);
  $ok = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  return $ok;
}

function db_last_id(): int {
  global $mysqli;
  return (int)mysqli_insert_id($mysqli);
}
