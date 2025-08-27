<?php
// === CONFIG ===
const APP_BASE_URL = 'http://localhost/pdf-saas-starter/app'; // change if different
const STORAGE_DIR  = __DIR__ . '/storage/users';

// Upload limits
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '55M');

// License guard (optional)
const LICENSE_KEY = 'YOUR_PRIVATE_LICENSE_KEY_ONLY_YOU_HAVE';
const LICENSE_ENFORCE = false; // set true in prod

// Token secret
const STREAM_SECRET = 'replace-with-32-bytes-random-secret';
