<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = 'AniStream | Movies';
$active = 'movies';

$movies = $pdo->query("SELECT m.*,
        (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') FROM movie_genres mg JOIN genres g ON mg.genre_id=g.id WHERE mg.movie_id=m.id) AS genre_names
    FROM movies m WHERE m.archived = 0 ORDER BY m.created_at DESC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
    <section class="breadcrumb-option">
      <div class="container"><h2>All Movies</h2></div>
    </section>
    <section class="product spad">
      <div class="container-fluid">
        <div class="row">
          <?php if (!$movies): ?>
            <div class="col-12"><p>No movies in the database yet — add some in phpMyAdmin.</p></div>
          <?php endif; ?>
          <?php foreach ($movies as $item) render_card($item, 'movie', 'col-lg-2 col-md-4 col-6'); ?>
        </div>
      </div>
    </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
