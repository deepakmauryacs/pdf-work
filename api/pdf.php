<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../helpers.php';
license_check();
$tok = $_GET['tok'] ?? '';
$data = $tok ? token_verify($tok) : false;
if(!$data){ http_response_code(403); exit('Bad token'); }
$link = db_row("SELECT l.*, d.filename, d.original_name, u.folder_slug
                  FROM links l
                  JOIN documents d ON d.id=l.doc_id
                  JOIN users u ON u.id=d.user_id
                  WHERE l.id=? AND d.id=?", [$data['link_id'], $data['doc_id']]);
if(!$link){ http_response_code(404); exit; }
if((int)$link['allow_view']!==1){ http_response_code(403); exit('Viewing disabled'); }
$event = (isset($_GET['dl']) && (int)$link['allow_download']===1) ? 'download' : (isset($_GET['dl']) ? 'blocked_download' : 'view');
db_exec("INSERT INTO analytics(link_id,event,ip,user_agent,referrer) VALUES(?,?,?,?,?)",
  [$link['id'],$event,ip_bin(),substr($_SERVER['HTTP_USER_AGENT']??'',0,255),substr($_SERVER['HTTP_REFERER']??'',0,255)]);
$path = STORAGE_DIR.'/'.$link['folder_slug'].'/'.$link['filename'];
if(!is_file($path)){ http_response_code(404); exit; }
$dl = (isset($_GET['dl']) && (int)$link['allow_download']===1);
header('Content-Type: application/pdf');
header('Accept-Ranges: bytes');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
$disposition = $dl ? 'attachment' : 'inline';
header('Content-Disposition: '.$disposition.'; filename="'.addslashes($link['original_name']).'"');
$size = filesize($path);
$start = 0; $length = $size; $end = $size-1;
if(isset($_SERVER['HTTP_RANGE'])){
  if(preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $m)){
    $start = intval($m[1]);
    if(isset($m[2]) && $m[2] !== '') $end = intval($m[2]);
    $length = ($end - $start) + 1;
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$size");
  }
}
header("Content-Length: $length");
$fp = fopen($path,'rb');
fseek($fp,$start);
$chunk = 8192;
while(!feof($fp) && $length>0){
  $read = ($length>$chunk)?$chunk:$length;
  echo fread($fp,$read);
  $length -= $read;
  @ob_flush(); flush();
}
fclose($fp);
