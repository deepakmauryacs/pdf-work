<?php
require __DIR__.'/../config.php'; require __DIR__.'/../db.php'; require __DIR__.'/../helpers.php';
license_check();

$raw = file_get_contents('php://input'); $j = json_decode($raw,true);
$slug = $j['s'] ?? ''; $event = $j['event'] ?? 'view'; $ref = $j['ref'] ?? null;
$link_id = db_one("SELECT id FROM links WHERE slug=?", [$slug]);
if($link_id){
  db_exec("INSERT INTO analytics(link_id,event,ip,user_agent,referrer) VALUES(?,?,?,?,?)",
    [$link_id,$event,ip_bin(),substr($_SERVER['HTTP_USER_AGENT']??'',0,255),substr(($ref??''),0,255)]);
}
header('Content-Type: application/json'); echo json_encode(['ok'=>true]);
