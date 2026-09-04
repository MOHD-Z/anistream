<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT m.* FROM movies m WHERE m.slug = ? AND m.archived = 0");
$stmt->execute([$slug]);
$movie = $stmt->fetch();

if (!$movie) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$pdo->prepare("UPDATE movies SET views = views + 1 WHERE id = ?")->execute([$movie['id']]);

$isFav = is_logged_in() && is_in_list($pdo, current_user()['id'], 0, $movie['id'], 'favorite');
$isWatchlist = is_logged_in() && is_in_list($pdo, current_user()['id'], 0, $movie['id'], 'watchlist');
$movieGenres = get_item_genres($pdo, 'movie', $movie['id']);

$page_title = 'AniStream | ' . $movie['title'];
$page_description = mb_strimwidth(strip_tags($movie['synopsis'] ?? ''), 0, 160, '...');
$page_image = $movie['poster'];
include __DIR__ . '/includes/header.php';
?>
    <section class="media-details spad">
      <div class="container">
        <div class="media__details__content">
          <div class="row">
            <div class="col-lg-3">
              <div class="media__details__pic set-bg" style="background-image:url('<?= h($movie['poster']) ?>')">
                <div class="view"><i class="fa fa-eye"></i> <?= (int)$movie['views'] ?></div>
              </div>
            </div>
            <div class="col-lg-9">
              <div class="media__details__text">
                <div class="media__details__title"><h3><?= h($movie['title']) ?></h3></div>
                <div class="media__details__rating"><span><?= h($movie['score']) ?> / 10</span></div>
                <p><?= nl2br(h($movie['synopsis'])) ?></p>
                <div class="media__details__widget">
                  <div class="row">
                    <div class="col-lg-6 col-md-6">
                      <ul>
                        <li><span>Type:</span> Movie</li>
                        <li><span>Runtime:</span> <?= (int)$movie['runtime'] ?> min</li>
                        <li><span>Genre:</span> <?= h($movieGenres ? implode(', ', array_column($movieGenres, 'name')) : 'General') ?></li>
                      </ul>
                    </div>
                    <div class="col-lg-6 col-md-6">
                      <ul>
                        <li><span>Score:</span> <?= h($movie['score']) ?></li>
                        <li><span>Views:</span> <?= (int)$movie['views'] ?></li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="media__details__btn">
                  <a href="movie-watching.php?slug=<?= h($movie['slug']) ?>" class="watch-btn">
                    <span>Watch Now</span> <i class="fa fa-angle-right"></i>
                  </a>
                  <?php if (is_logged_in()): ?>
                    <?php $backUrl = 'movie-details.php?slug=' . urlencode($movie['slug']); ?>
                    <a href="list-action.php?do=<?= $isFav ? 'remove' : 'add' ?>&movie_id=<?= (int)$movie['id'] ?>&list_type=favorite&back=<?= urlencode($backUrl) ?>" class="primary-btn" style="margin-left:10px;">
                      <?= $isFav ? '♥ Remove Favorite' : '♡ Add to Favorites' ?>
                    </a>
                    <a href="list-action.php?do=<?= $isWatchlist ? 'remove' : 'add' ?>&movie_id=<?= (int)$movie['id'] ?>&list_type=watchlist&back=<?= urlencode($backUrl) ?>" class="primary-btn" style="margin-left:10px;">
                      <?= $isWatchlist ? '✓ In Watchlist' : '+ Add to Watchlist' ?>
                    </a>
                  <?php else: ?>
                    <a href="login.php" class="primary-btn" style="margin-left:10px;">Login to save</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
