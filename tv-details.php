<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT s.* FROM series s WHERE s.slug = ? AND s.archived = 0");
$stmt->execute([$slug]);
$series = $stmt->fetch();

if (!$series) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$pdo->prepare("UPDATE series SET views = views + 1 WHERE id = ?")->execute([$series['id']]);

$isFav = is_logged_in() && is_in_list($pdo, current_user()['id'], $series['id'], 0, 'favorite');
$isWatchlist = is_logged_in() && is_in_list($pdo, current_user()['id'], $series['id'], 0, 'watchlist');
$seriesGenres = get_item_genres($pdo, 'series', $series['id']);

$seasons = $pdo->prepare("SELECT * FROM seasons WHERE series_id = ? ORDER BY season_number");
$seasons->execute([$series['id']]);
$seasons = $seasons->fetchAll();

foreach ($seasons as &$season) {
    $eps = $pdo->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number");
    $eps->execute([$season['id']]);
    $season['episodes'] = $eps->fetchAll();
}
unset($season);

$page_title = 'AniStream | ' . $series['title'];
$page_description = mb_strimwidth(strip_tags($series['synopsis'] ?? ''), 0, 160, '...');
$page_image = $series['poster'];
include __DIR__ . '/includes/header.php';
?>
    <section class="media-details spad">
      <div class="container">
        <div class="media__details__content">
          <div class="row">
            <div class="col-lg-3">
              <div class="media__details__pic set-bg" style="background-image:url('<?= h($series['poster']) ?>')">
                <div class="view"><i class="fa fa-eye"></i> <?= (int)$series['views'] ?></div>
              </div>
            </div>
            <div class="col-lg-9">
              <div class="media__details__text">
                <div class="media__details__title">
                  <h3><?= h($series['title']) ?></h3>
                </div>
                <div class="media__details__rating">
                  <span><?= h($series['score']) ?> / 10</span>
                </div>
                <p><?= nl2br(h($series['synopsis'])) ?></p>
                <div class="media__details__widget">
                  <div class="row">
                    <div class="col-lg-6 col-md-6">
                      <ul>
                        <li><span>Type:</span> TV Series</li>
                        <li><span>Status:</span> <?= h($series['status']) ?></li>
                        <li><span>Genre:</span> <?= h($seriesGenres ? implode(', ', array_column($seriesGenres, 'name')) : 'General') ?></li>
                        <li><span>Seasons:</span> <?= count($seasons) ?></li>
                      </ul>
                    </div>
                    <div class="col-lg-6 col-md-6">
                      <ul>
                        <li><span>Score:</span> <?= h($series['score']) ?></li>
                        <li><span>Views:</span> <?= (int)$series['views'] ?></li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="media__details__btn">
                  <?php if ($seasons && $seasons[0]['episodes']): ?>
                  <a href="watching.php?slug=<?= h($seasons[0]['episodes'][0]['slug']) ?>" class="watch-btn">
                    <span>Watch Now</span> <i class="fa fa-angle-right"></i>
                  </a>
                  <?php endif; ?>
                  <?php if (is_logged_in()): ?>
                    <?php $backUrl = 'tv-details.php?slug=' . urlencode($series['slug']); ?>
                    <a href="list-action.php?do=<?= $isFav ? 'remove' : 'add' ?>&series_id=<?= (int)$series['id'] ?>&list_type=favorite&back=<?= urlencode($backUrl) ?>" class="primary-btn" style="margin-left:10px;">
                      <?= $isFav ? '♥ Remove Favorite' : '♡ Add to Favorites' ?>
                    </a>
                    <a href="list-action.php?do=<?= $isWatchlist ? 'remove' : 'add' ?>&series_id=<?= (int)$series['id'] ?>&list_type=watchlist&back=<?= urlencode($backUrl) ?>" class="primary-btn" style="margin-left:10px;">
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

        <div class="row mt-5">
          <div class="col-12">
            <div class="section-title"><h4>Seasons & Episodes</h4></div>
            <?php foreach ($seasons as $season): ?>
              <h5 class="mt-3">Season <?= (int)$season['season_number'] ?></h5>
              <div class="row">
                <?php foreach ($season['episodes'] as $ep): ?>
                  <div class="col-lg-2 col-md-3 col-4 mb-3">
                    <a href="watching.php?slug=<?= h($ep['slug']) ?>" class="primary-btn" style="display:block;text-align:center;">
                      EP <?= (int)$ep['episode_number'] ?>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
            <?php if (!$seasons): ?><p>No episodes added yet.</p><?php endif; ?>
          </div>
        </div>
      </div>
    </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
