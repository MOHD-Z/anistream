<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = get_setting('site_name', 'AniStream') . ' | Home';
$active = 'home';

$sidebarOn = setting_on('sidebar_enabled');

$sections = $pdo->query("SELECT * FROM homepage_sections WHERE visible = 1 ORDER BY sort_order")->fetchAll();

function fetch_section_items($pdo, $section) {
    $orderMap = [
        'newest' => 'created_at DESC',
        'oldest' => 'created_at ASC',
        'score'  => 'score DESC',
        'views'  => 'views DESC',
    ];
    $order = $orderMap[$section['sort_by']] ?? 'created_at DESC';
    $limit = (int)$section['posts_count'];

    if ($section['content_type'] === 'blog') {
        return ['blog', $pdo->query("SELECT * FROM blog_posts WHERE published = 1 ORDER BY created_at DESC LIMIT $limit")->fetchAll()];
    }
    if ($section['content_type'] === 'movies') {
        return ['movie', $pdo->query("SELECT m.*,
                (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') FROM movie_genres mg JOIN genres g ON mg.genre_id=g.id WHERE mg.movie_id=m.id) AS genre_names
            FROM movies m WHERE m.archived = 0 ORDER BY m.$order LIMIT $limit")->fetchAll()];
    }
    if (in_array($section['content_type'], ['series', 'trending', 'popular'], true)) {
        return ['series', $pdo->query("SELECT s.*,
                (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') FROM series_genres sg JOIN genres g ON sg.genre_id=g.id WHERE sg.series_id=s.id) AS genre_names,
                (SELECT COUNT(*) FROM episodes e JOIN seasons se ON e.season_id=se.id WHERE se.series_id=s.id) AS episode_count
            FROM series s WHERE s.archived = 0 ORDER BY s.$order LIMIT $limit")->fetchAll()];
    }
    return [null, []];
}

$heroSlides = $pdo->query("SELECT s.* FROM series s WHERE s.archived = 0 ORDER BY s.score DESC LIMIT 3")->fetchAll();
$blogPosts = $pdo->query("SELECT * FROM blog_posts WHERE published = 1 ORDER BY created_at DESC LIMIT 3")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
    <!-- Hero Section Begin -->
    <section class="hero">
      <div class="container">
        <div class="hero__slider owl-carousel">
          <?php foreach ($heroSlides as $slide): ?>
          <div class="hero__items set-bg" style="background-image: url('<?= h($slide['poster']) ?>')">
            <div class="row">
              <div class="col-lg-6">
                <div class="hero__text">
                  <div class="label"><?= h($slide['genre_name'] ?? 'Featured') ?></div>
                  <h2><?= h($slide['title']) ?></h2>
                  <p><?= h(mb_strimwidth($slide['synopsis'] ?? '', 0, 140, '...')) ?></p>
                  <a href="tv-details.php?slug=<?= h($slide['slug']) ?>"><span>Watch Now</span> <i class="fa fa-angle-right"></i></a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <!-- Hero Section End -->

    <section class="product spad">
      <div class="container-fluid">
        <div class="row">
          <div class="<?= $sidebarOn ? 'col-lg-9' : 'col-lg-12' ?> index1">

            <?php foreach ($sections as $section):
                [$type, $items] = fetch_section_items($pdo, $section);
                if (!$type) continue;
                $viewAllLink = $section['content_type'] === 'movies' ? 'movies.php' : ($section['content_type'] === 'blog' ? 'blog.php' : 'series.php');
            ?>
              <div class="trending__product mt-5">
                <div class="row">
                  <div class="col-lg-8 col-md-8 col-sm-8">
                    <div class="section-title"><h4><?= h($section['title']) ?></h4></div>
                  </div>
                  <div class="col-lg-4 col-md-4 col-sm-4">
                    <div class="btn__all"><a href="<?= $viewAllLink ?>" class="primary-btn">View All <span class="arrow_right"></span></a></div>
                  </div>
                </div>
                <div class="row">
                  <?php if (!$items): ?>
                    <div class="col-12"><p class="muted">Nothing to show here yet.</p></div>
                  <?php endif; ?>
                  <?php if ($type === 'blog'): ?>
                    <?php foreach ($items as $post): ?>
                      <div class="col-lg-3 col-md-4 col-6 inp3">
                        <div class="blog__item">
                          <div class="blog__item__pic"><img src="<?= h($post['image']) ?>" alt="" style="width:100%;"></div>
                          <div class="blog__item__text">
                            <h6><a href="blog-details.php?slug=<?= h($post['slug']) ?>"><?= h($post['title']) ?></a></h6>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <?php foreach ($items as $item) render_card($item, $type); ?>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if (!$sections): ?>
              <p>No homepage sections are enabled. Add some from the admin panel.</p>
            <?php endif; ?>
          </div>

          <?php if ($sidebarOn): ?>
          <div class="col-lg-3">
            <div class="sidebar">
              <div class="section-title"><h4>Our Blog</h4></div>
              <?php foreach ($blogPosts as $post): ?>
                <div class="sidebar__item" style="margin-bottom:20px;">
                  <a href="blog-details.php?slug=<?= h($post['slug']) ?>">
                    <img src="<?= h($post['image']) ?>" alt="" style="width:100%;border-radius:6px;">
                    <h6 style="margin-top:8px;"><?= h($post['title']) ?></h6>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
