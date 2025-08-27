<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/auth_mock.php';
license_check();
header('Content-Type: application/json');
if($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }

$userId = current_user_id();
$slug = current_user_folder_slug();

if(empty($_FILES['pdf']['name'])){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'No file']); exit; }
if($_FILES['pdf']['error']!==UPLOAD_ERR_OK){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Upload error']); exit; }
if($_FILES['pdf']['size'] > 50*1024*1024){ http_response_code(413); echo json_encode(['ok'=>false,'error'=>'File too large (>50MB)']); exit; }

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $_FILES['pdf']['tmp_name']);
if($mime!=='application/pdf'){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Only PDF']); exit; }

$orig = $_FILES['pdf']['name'];
$store = time().'_'.bin2hex(random_bytes(4)).'.pdf';

$dest = STORAGE_DIR.'/'.$slug.'/'.$store;
@mkdir(STORAGE_DIR.'/'.$slug, 0775, true);
move_uploaded_file($_FILES['pdf']['tmp_name'], $dest);

$sha = hash_file('sha256', $dest);
db_exec("INSERT INTO documents(user_id,filename,original_name,size_bytes,mime,sha256) VALUES(?,?,?,?,?,?)",
  [$userId,$store,$orig,$_FILES['pdf']['size'],$mime,$sha]);

echo json_encode(['ok'=>true]);
