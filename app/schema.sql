-- Database: pdf_saas
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120),
  email VARCHAR(190) UNIQUE,
  password_hash VARCHAR(255),
  folder_slug VARCHAR(64) UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS documents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  mime VARCHAR(80) DEFAULT 'application/pdf',
  sha256 CHAR(64),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id)
);

CREATE TABLE IF NOT EXISTS links (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  doc_id BIGINT UNSIGNED NOT NULL,
  kind ENUM('view','embed') NOT NULL,
  slug CHAR(10) UNIQUE,
  allow_view TINYINT(1) DEFAULT 1,
  allow_download TINYINT(1) DEFAULT 0,
  allow_search TINYINT(1) DEFAULT 1,
  expire_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS analytics (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  link_id BIGINT UNSIGNED NOT NULL,
  event ENUM('view','download','blocked_download') NOT NULL,
  ip VARBINARY(16) NULL,
  user_agent VARCHAR(255) NULL,
  referrer VARCHAR(255) NULL,
  country VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(link_id, created_at)
);

INSERT INTO users (id,name,email,password_hash,folder_slug) VALUES (1,'Demo','demo@example.com','-','') 
ON DUPLICATE KEY UPDATE name=VALUES(name);
