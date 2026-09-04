<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!is_logged_in()) {
    redirect('login.php');
}
$userId = current_user()['id'];
$page_title = 'AniStream | My List';
$active = 'my-list';

$favSeries = $pdo->prepare("SELECT s.*, g.name AS genre_name, g.slug AS genre_slug,
        (SELECT COUNT(*) FROM episodes e JOIN seasons se ON e.season_id=se.id WHERE se.series_id=s.id) AS episode_count
    FROM favorites f JOIN series s ON f.series_id = s.id LEFT JOIN genres g ON s.genre_id=g.id
    WHERE f.user_id = ? AND f.list_type = 'favorite' AND f.series_id != 0");
$favSeries->execute([$userId]);
$favSeries = $favSeries->fetchAll();

$favMovies = $pdo->prepare("SELECT m.*, g.name AS genre_name, g.slug AS genre_slug
    FROM favorites f JOIN movies m ON f.movie_id = m.id LEFT JOIN genres g ON m.genre_id=g.id
    WHERE f.user_id = ? AND f.list_type = 'favorite' AND f.movie_id != 0");
$favMovies->execute([$userId]);
$favMovies = $favMovies->fetchAll();

$watchSeries = $pdo->prepare("SELECT s.*, g.name AS genre_name, g.slug AS genre_slug,
        (SELECT COUNT(*) FROM episodes e JOIN seasons se ON e.season_id=se.id WHERE se.series_id=s.id) AS episode_count
    FROM favorites f JOIN series s ON f.series_id = s.id LEFT JOIN genres g ON s.genre_id=g.id
    WHERE f.user_id = ? AND f.list_type = 'watchlist' AND f.series_id != 0");
$watchSeries->execute([$userId]);
$watchSeries = $watchSeries->fetchAll();

$watchMovies = $pdo->prepare("SELECT m.*, g.name AS genre_name, g.slug AS genre_slug
    FROM favorites f JOIN movies m ON f.movie_id = m.id LEFT JOIN genres g ON m.genre_id=g.id
    WHERE f.user_id = ? AND f.list_type = 'watchlist' AND f.movie_id != 0");
$watchMovies->execute([$userId]);
$watchMovies = $watchMovies->fetchAll();

$continueWatching = $pdo->prepare("SELECT wh.*, s.title AS series_title, s.slug AS series_slug, s.poster AS series_poster,
        m.title AS movie_title, m.slug AS movie_slug, m.poster AS movie_poster,
        e.title AS episode_title, e.slug AS episode_slug, e.episode_number, se.season_number
    FROM watch_history wh
    LEFT JOIN series s ON wh.series_id = s.id
    LEFT JOIN movies m ON wh.movie_id = m.id
    LEFT JOIN episodes e ON wh.episode_id = e.id
    LEFT JOIN seasons se ON e.season_id = se.id
    WHERE wh.user_id = ? ORDER BY wh.updated_at DESC");
$continueWatching->execute([$userId]);
$continueWatching = $continueWatching->fetchAll();

include __DIR__ . '/includes/header.php';
?>
    <section class="breadcrumb-option">
      <div class="container"><h2>My List</h2></div>
    </section>

    <section class="product spad">
      <div class="container-fluid">

        <?php if ($continueWatching): ?>
        <div class="section-title"><h4>Continue Watching</h4></div>
        <div class="row mb-5">
          <?php foreach ($continueWatching as $cw): ?>
            <div class="col-lg-3 col-md-4 col-6 mb-4">
              <?php if ($cw['episode_id']): ?>
                <a href="watching.php?slug=<?= h($cw['episode_slug']) ?>" class="primary-btn" style="display:block;">
                  <?= h($cw['series_title']) ?> — S<?= (int)$cw['season_number'] ?>E<?= (int)$cw['episode_number'] ?>
                </a>
              <?php elseif ($cw['movie_id']): ?>
                <a href="movie-watching.php?slug=<?= h($cw['movie_slug']) ?>" class="primary-btn" style="display:block;">
                  <?= h($cw['movie_title']) ?>
                </a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="section-title"><h4>Favorites</h4></div>
        <div class="row mb-5">
          <?php if (!$favSeries && !$favMovies): ?><div class="col-12"><p class="muted">No favorites yet.</p></div><?php endif; ?>
          <?php foreach ($favSeries as $item) render_card($item, 'series'); ?>
          <?php foreach ($favMovies as $item) render_card($item, 'movie'); ?>
        </div>

        <div class="section-title"><h4>Watchlist</h4></div>
        <div class="row">
          <?php if (!$watchSeries && !$watchMovies): ?><div class="col-12"><p class="muted">Your watchlist is empty.</p></div><?php endif; ?>
          <?php foreach ($watchSeries as $item) render_card($item, 'series'); ?>
          <?php foreach ($watchMovies as $item) render_card($item, 'movie'); ?>
        </div>

      </div>
    </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
