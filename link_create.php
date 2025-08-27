<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/auth_mock.php';
license_check();

if($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
$docId = (int)($_POST['doc_id']??0);
$kind  = $_POST['kind']??'view';
$allow_view = isset($_POST['allow_view']) ? 1 : 0;
$allow_download = isset($_POST['allow_download']) ? 1 : 0;
$allow_search = isset($_POST['allow_search']) ? 1 : 0;

$doc = db_row("SELECT d.*, u.folder_slug FROM documents d JOIN users u ON u.id=d.user_id WHERE d.id=?", [$docId]);
if(!$doc){ http_response_code(404); exit('Doc not found'); }

$slug = slug(10);
db_exec("INSERT INTO links(doc_id,kind,slug,allow_view,allow_download,allow_search) VALUES(?,?,?,?,?,?)",
  [$docId,$kind,$slug,$allow_view,$allow_download,$allow_search]);

$url = ($kind==='view') ? APP_BASE_URL.'/view.php?s='.$slug : APP_BASE_URL.'/embed.php?s='.$slug;

echo "<div style='padding:20px;font-family:system-ui'>";
echo "<p><b>New Link:</b> <a href='$url' target='_blank'>$url</a></p>";
if($kind==='embed'){
  $iframe = '<iframe src="'.$url.'" width="100%" height="800" style="border:0" allowfullscreen></iframe>';
  echo "<p><b>Embed code:</b></p><textarea style='width:100%;height:120px'>".$iframe."</textarea>";
}
echo "</div>";
