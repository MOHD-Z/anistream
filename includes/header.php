<?php
// Expects (optionally) $page_title, $page_description, $page_image set by the including page.
$page_title = $page_title ?? get_setting('site_name', 'AniStream') . ' | Home';
$page_description = $page_description ?? 'Watch series, movies and episodes on ' . get_setting('site_name', 'AniStream') . '.';
$page_image = $page_image ?? 'img/logo.png';
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="zxx">
  <head>
    <meta charset="UTF-8" />
    <meta name="description" content="<?= h($page_description) ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title><?= h($page_title) ?></title>

    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= h($page_title) ?>" />
    <meta property="og:description" content="<?= h($page_description) ?>" />
    <meta property="og:image" content="<?= h($page_image) ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <link rel="icon" href="img/logo.png" type="image/png" />


    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="css/font-awesome.min.css" type="text/css" />
    <link rel="stylesheet" href="css/elegant-icons.css" type="text/css" />
    <link rel="stylesheet" href="css/plyr.css" type="text/css" />
    <link rel="stylesheet" href="css/nice-select.css" type="text/css" />
    <link rel="stylesheet" href="css/owl.carousel.min.css" type="text/css" />
    <link rel="stylesheet" href="css/slicknav.min.css" type="text/css" />
    <link rel="stylesheet" href="css/style.css" type="text/css" />
  </head>

  <body>
    <header class="header">
      <div class="container">
        <div class="row">
          <div class="col-lg-2">
            <div class="header__logo">
              <a href="index.php"><img src="img/logo.png" alt="" /></a>
            </div>
          </div>
          <div class="col-lg-8">
            <div class="header__nav">
              <nav class="header__menu mobile-menu">
                <ul>
                  <?php foreach (get_nav_links($pdo, 'header') as $link):
                      if (!nav_link_visible_now($link)) continue;
                      $activeKey = nav_link_active_key($link['url']);
                      $label = ($link['url'] === 'logout.php') ? 'Logout (' . h(current_user()['name']) . ')' : h($link['label']);
                  ?>
                    <?php if ($link['url'] === 'genrs.php'): ?>
                      <li class="<?= $active === 'genres' ? 'active' : '' ?>">
                        <a href="genrs.php">Genres <span class="arrow_carrot-down"></span></a>
                        <ul class="dropdown">
                          <li><a href="movies.php">Movies</a></li>
                          <li><a href="series.php">Series</a></li>
                        </ul>
                      </li>
                    <?php else: ?>
                      <li class="<?= ($activeKey && $active === $activeKey) ? 'active' : '' ?>"><a href="<?= h($link['url']) ?>"><?= $label ?></a></li>
                    <?php endif; ?>
                  <?php endforeach; ?>
                  <?php if (is_logged_in()): ?>
                    <li><a href="logout.php">Logout (<?= h(current_user()['name']) ?>)</a></li>
                  <?php endif; ?>
                </ul>
              </nav>
            </div>
          </div>
          <div class="col-lg-2">
            <div class="header__right">
              <a href="#" class="search-switch"><span class="icon_search"></span></a>
              <a href="<?= is_logged_in() ? 'logout.php' : 'login.php' ?>"><span class="icon_profile"></span></a>
            </div>
          </div>
        </div>
      </div>
    </header>
    <!-- Header End -->
