<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/auth_mock.php';
license_check();

header('Content-Type: application/json');
if($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }
$docId = (int)($_POST['doc_id']??0);
$kind  = $_POST['kind']??'view';
$allow_view = isset($_POST['allow_view']) ? 1 : 0;
$allow_download = isset($_POST['allow_download']) ? 1 : 0;
$allow_search = isset($_POST['allow_search']) ? 1 : 0;
$expire_raw = trim($_POST['expire_at'] ?? '');
$expire_at = null;
if($expire_raw !== ''){
  $dt = DateTime::createFromFormat('d-m-Y', $expire_raw);
  $errors = DateTime::getLastErrors();
  if(!$dt || $errors['warning_count'] || $errors['error_count']){
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid date (dd-mm-yyyy)']); exit; }
  $expire_at = $dt->format('Y-m-d 00:00:00');
}

$doc = db_row("SELECT d.*, u.folder_slug FROM documents d JOIN users u ON u.id=d.user_id WHERE d.id=?", [$docId]);
if(!$doc){ http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Doc not found']); exit; }

if(!in_array($kind, ['view','embed'], true)){
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Bad kind']); exit; }

$slug = slug(10);
db_exec("INSERT INTO links(doc_id,kind,slug,allow_view,allow_download,allow_search,expire_at) VALUES(?,?,?,?,?,?,?)",
  [$docId,$kind,$slug,$allow_view,$allow_download,$allow_search,$expire_at]);

$url = ($kind==='view') ? APP_BASE_URL.'/view.php?s='.$slug : APP_BASE_URL.'/embed.php?s='.$slug;

echo json_encode(['ok'=>true,'url'=>$url]);
