<?php
function license_check() {
  if (!defined('LICENSE_ENFORCE') || LICENSE_ENFORCE !== true) return;
  if (!isset($_SERVER['HTTP_X_LICENSE']) || $_SERVER['HTTP_X_LICENSE'] !== LICENSE_KEY) {
    http_response_code(403); exit('License invalid');
  }
}
function slug($len=10){
  $a='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  $s=''; for($i=0;$i<$len;$i++) $s.=$a[random_int(0,strlen($a)-1)];
  return $s;
}
function humanSize($b){
  $u=['B','KB','MB','GB']; $i=0;
  while($b>=1024 && $i<count($u)-1){ $b/=1024; $i++; }
  return round($b,1).$u[$i];
}
function ip_bin(){
  $ip = $_SERVER['REMOTE_ADDR'] ?? null; if(!$ip) return null;
  return inet_pton($ip);
}
function token_for($linkId, $docId, $expireSeconds=300){
  $exp = time() + $expireSeconds;
  $data = $linkId.'|'.$docId.'|'.$exp;
  $sig = hash_hmac('sha256', $data, STREAM_SECRET);
  return base64_encode($data.'|'.$sig);
}
function token_verify($b64){
  $raw = base64_decode($b64, true); if(!$raw) return false;
  $parts = explode('|',$raw);
  if(count($parts) !== 4) return false;
  [$linkId,$docId,$exp,$sig] = $parts;
  if (time()>intval($exp)) return false;
  $valid = hash_equals($sig, hash_hmac('sha256', "$linkId|$docId|$exp", STREAM_SECRET));
  return $valid ? ['link_id'=>(int)$linkId,'doc_id'=>(int)$docId] : false;
}
