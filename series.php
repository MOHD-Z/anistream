<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = 'AniStream | Series';
$active = 'series';

$series = $pdo->query("SELECT s.*,
        (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') FROM series_genres sg JOIN genres g ON sg.genre_id=g.id WHERE sg.series_id=s.id) AS genre_names,
        (SELECT COUNT(*) FROM episodes e JOIN seasons se ON e.season_id=se.id WHERE se.series_id=s.id) AS episode_count,
        (SELECT COUNT(DISTINCT season_number) FROM seasons WHERE series_id = s.id) AS season_count
    FROM series s WHERE s.archived = 0 ORDER BY s.created_at DESC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
    <section class="breadcrumb-option">
      <div class="container"><h2>All Series</h2></div>
    </section>
    <section class="product spad">
      <div class="container-fluid">
        <div class="row">
          <?php if (!$series): ?>
            <div class="col-12"><p>No series in the database yet — add some in phpMyAdmin.</p></div>
          <?php endif; ?>
          <?php foreach ($series as $item) render_card($item, 'series', 'col-lg-2 col-md-4 col-6'); ?>
        </div>
      </div>
    </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
