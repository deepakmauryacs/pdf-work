<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/auth_mock.php';
license_check();
$userId = current_user_id();
$docs = db_all("SELECT * FROM documents WHERE user_id=? ORDER BY id DESC", [$userId]);
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>PDF Library</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">
<h3>My PDFs</h3>
<form class="my-3" action="upload.php" method="post" enctype="multipart/form-data">
  <input type="file" name="pdf" accept="application/pdf" required>
  <button class="btn btn-primary">Upload (max 50MB)</button>
</form>

<table class="table table-bordered align-middle">
  <thead><tr><th>PDF Name</th><th>Size</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach($docs as $d): ?>
    <tr>
      <td><?= htmlspecialchars($d['original_name']) ?></td>
      <td><?= humanSize($d['size_bytes']) ?></td>
      <td>
        <form class="d-inline" action="link_create.php" method="post">
          <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
          <input type="hidden" name="kind" value="view">
          <div class="d-inline-flex gap-2 align-items-center">
            <label class="form-check-label">View
              <input class="form-check-input ms-1" type="checkbox" name="allow_view" checked>
            </label>
            <label class="form-check-label">Download
              <input class="form-check-input ms-1" type="checkbox" name="allow_download">
            </label>
            <label class="form-check-label">Search
              <input class="form-check-input ms-1" type="checkbox" name="allow_search" checked>
            </label>
            <button class="btn btn-sm btn-dark">GENERATE LINK</button>
          </div>
        </form>

        <form class="d-inline ms-2" action="link_create.php" method="post">
          <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
          <input type="hidden" name="kind" value="embed">
          <div class="d-inline-flex gap-2 align-items-center">
            <label class="form-check-label">View
              <input class="form-check-input ms-1" type="checkbox" name="allow_view" checked>
            </label>
            <label class="form-check-label">Download
              <input class="form-check-input ms-1" type="checkbox" name="allow_download">
            </label>
            <label class="form-check-label">Search
              <input class="form-check-input ms-1" type="checkbox" name="allow_search" checked>
            </label>
            <button class="btn btn-sm btn-secondary">EMBEDDED WEBSITE</button>
          </div>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body></html>
