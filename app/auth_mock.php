<?php
require_once __DIR__.'/db.php';
function current_user_id(){ return 1; }
function current_user_folder_slug(){
  $row = db_row("SELECT folder_slug FROM users WHERE id=1");
  if (!$row) {
    // ensure demo user exists
    db_exec("INSERT IGNORE INTO users (id,name,email,password_hash,folder_slug) VALUES (1,'Demo','demo@example.com','-','')");
    $row = ['folder_slug'=>''];
  }
  $slug = $row['folder_slug'] ?? '';
  if (!$slug) {
    $slug = 'usr_'.bin2hex(random_bytes(5));
    db_exec("UPDATE users SET folder_slug=? WHERE id=1", [$slug]);
  }
  @mkdir(STORAGE_DIR.'/'.$slug, 0775, true);
  return $slug;
}
