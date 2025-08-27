<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/auth_mock.php';
license_check();
if($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }

$userId = current_user_id();
$slug = current_user_folder_slug();

if(empty($_FILES['pdf']['name'])){ http_response_code(400); exit('No file'); }
if($_FILES['pdf']['error']!==UPLOAD_ERR_OK){ http_response_code(400); exit('Upload error'); }
if($_FILES['pdf']['size'] > 50*1024*1024){ http_response_code(413); exit('File too large (>50MB)'); }

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $_FILES['pdf']['tmp_name']);
if($mime!=='application/pdf'){ http_response_code(400); exit('Only PDF'); }

$orig = $_FILES['pdf']['name'];
$store = time().'_'.bin2hex(random_bytes(4)).'.pdf';

$dest = STORAGE_DIR.'/'.$slug.'/'.$store;
@mkdir(STORAGE_DIR.'/'.$slug, 0775, true);
move_uploaded_file($_FILES['pdf']['tmp_name'], $dest);

$sha = hash_file('sha256', $dest);
db_exec("INSERT INTO documents(user_id,filename,original_name,size_bytes,mime,sha256) VALUES(?,?,?,?,?,?)",
  [$userId,$store,$orig,$_FILES['pdf']['size'],$mime,$sha]);

header('Content-Type: application/json');
echo json_encode(['ok'=>true]);
