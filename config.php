<?php
// === CONFIG (root install) ===
const APP_BASE_URL = 'http://localhost/pdf-saas-starter'; // <-- root (no /app)
const STORAGE_DIR  = __DIR__ . '/storage/users';

ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '55M');

const LICENSE_KEY = 'YOUR_PRIVATE_LICENSE_KEY_ONLY_YOU_HAVE';
const LICENSE_ENFORCE = false;

const STREAM_SECRET = 'replace-with-32-bytes-random-secret';
