<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/db_backup.php';

// Full site backup = database dump + a zip of the /uploads folder (posters,
// blog images the user has uploaded). Built on demand, not stored on disk.
if (($_GET['type'] ?? '') === 'full_zip') {
    $tmpSql = tempnam(sys_get_temp_dir(), 'anistream_') . '.sql';
    file_put_contents($tmpSql, generate_sql_dump($pdo, all_backup_tables($pdo)));

    $zipPath = tempnam(sys_get_temp_dir(), 'anistream_') . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFile($tmpSql, 'database.sql');

    $uploadsDir = __DIR__ . '/../uploads';
    if (is_dir($uploadsDir)) {
        foreach (new DirectoryIterator($uploadsDir) as $f) {
            if ($f->isFile()) {
                $zip->addFile($f->getPathname(), 'uploads/' . $f->getFilename());
            }
        }
    }
    $zip->close();

    $filename = 'anistream-full-backup-' . date('Y-m-d_His') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    unlink($tmpSql);
    unlink($zipPath);
    exit;
}

$tableCount = count(all_backup_tables($pdo));
$contentTableCount = count(content_backup_tables());
$uploadsDir = __DIR__ . '/../uploads';
$uploadsCount = is_dir($uploadsDir) ? count(glob($uploadsDir . '/*')) : 0;

$admin_page_title = 'Backups';
$admin_active = 'backup';
include __DIR__ . '/includes/layout_top.php';
?>
  <p class="muted" style="margin-bottom:16px;">
    Backups download directly to your computer — nothing is stored on the server.
    Each is a plain <code>.sql</code> file (or a <code>.zip</code> containing one) you can
    restore later by importing it in phpMyAdmin, the same way you imported
    <code>schema.sql</code>.
  </p>

  <div class="panel" style="margin-bottom:20px;">
    <div class="panel-head"><h2>Content Backup</h2></div>
    <div class="panel-body">
      <p class="muted">
        Just your movies, series, episodes, genres, video sources, reports, and blog posts
        (<?= $contentTableCount ?> tables). Safe to restore into a different install without
        overwriting its admin login, users, or settings.
      </p>
      <a href="backup-download.php?type=content" class="btn primary">Download Content Backup (.sql)</a>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Full Website Backup</h2></div>
    <div class="panel-body">
      <p class="muted">
        Everything: all content above, plus admin/user accounts, site settings, homepage
        sections, menus, languages, and your uploaded images (<?= $tableCount ?> tables,
        <?= $uploadsCount ?> uploaded file<?= $uploadsCount === 1 ? '' : 's' ?>). Use this for
        a complete restore point.
      </p>
      <a href="backup.php?type=full_zip" class="btn primary">Download Full Backup (.zip)</a>
      <span class="muted" style="margin-left:10px;">Database only, no uploads: <a href="backup-download.php?type=full">.sql</a></span>
    </div>
  </div>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
