<?php
$admin_page_title = $admin_page_title ?? 'Dashboard';
$admin_active = $admin_active ?? '';
function nav_active($key, $active) { return $key === $active ? ' active' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin | <?= h($admin_page_title) ?></title>
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="app">
<aside class="sidebar">
  <div class="brand"><div class="brand-mark">SC</div><div><strong>StreamCMS</strong><small>Administration</small></div></div>

  <div class="nav-group">
    <div class="nav-title">Overview</div>
    <a class="nav-item<?= nav_active('dashboard', $admin_active) ?>" href="index.php"><span class="nav-left"><span class="ico">⌂</span>Dashboard</span></a>
  </div>

  <div class="nav-group">
    <div class="nav-title">Content</div>
    <a class="nav-item<?= nav_active('movies', $admin_active) ?>" href="movies.php"><span class="nav-left"><span class="ico">🎬</span>Movies</span></a>
    <a class="nav-item<?= nav_active('series', $admin_active) ?>" href="series.php"><span class="nav-left"><span class="ico">▣</span>Series</span></a>
    <a class="nav-item<?= nav_active('episodes', $admin_active) ?>" href="episodes.php"><span class="nav-left"><span class="ico">▶</span>Episodes</span></a>
    <a class="nav-item<?= nav_active('genres', $admin_active) ?>" href="genres.php"><span class="nav-left"><span class="ico">◇</span>Genres</span></a>
    <a class="nav-item<?= nav_active('blog', $admin_active) ?>" href="blog.php"><span class="nav-left"><span class="ico">▱</span>Blog</span></a>
    <a class="nav-item<?= nav_active('import', $admin_active) ?>" href="import.php"><span class="nav-left"><span class="ico">⇩</span>Import (TMDB/MAL)</span></a>
  </div>

  <div class="nav-group">
    <div class="nav-title">Media</div>
    <a class="nav-item<?= nav_active('sources', $admin_active) ?>" href="video_sources.php"><span class="nav-left"><span class="ico">⇄</span>Video Sources</span></a>
  </div>

  <div class="nav-group">
    <div class="nav-title">Appearance</div>
    <a class="nav-item<?= nav_active('homepage', $admin_active) ?>" href="homepage_sections.php"><span class="nav-left"><span class="ico">▥</span>Homepage Sections</span></a>
    <a class="nav-item<?= nav_active('menus', $admin_active) ?>" href="menus.php"><span class="nav-left"><span class="ico">☰</span>Header & Footer Menus</span></a>
    <a class="nav-item<?= nav_active('settings', $admin_active) ?>" href="settings.php"><span class="nav-left"><span class="ico">✦</span>Site Settings</span></a>
  </div>

  <div class="nav-group">
    <div class="nav-title">Users & Languages</div>
    <a class="nav-item<?= nav_active('users', $admin_active) ?>" href="users.php"><span class="nav-left"><span class="ico">♟</span>Users</span></a>
    <a class="nav-item<?= nav_active('languages', $admin_active) ?>" href="languages.php"><span class="nav-left"><span class="ico">文</span>Languages</span></a>
  </div>

  <div class="nav-group">
    <div class="nav-title">Operations</div>
    <a class="nav-item<?= nav_active('reports', $admin_active) ?>" href="reports.php"><span class="nav-left"><span class="ico">⚠</span>Reports</span></a>
  </div>

  <div class="nav-group">
    <div class="nav-title">System</div>
    <a class="nav-item<?= nav_active('backup', $admin_active) ?>" href="backup.php"><span class="nav-left"><span class="ico">⇊</span>Backups</span></a>
  </div>

  <div class="nav-group">
    <div class="nav-title">Account</div>
    <a class="nav-item" href="../index.php" target="_blank"><span class="nav-left"><span class="ico">↗</span>View Site</span></a>
    <a class="nav-item" href="logout.php"><span class="nav-left"><span class="ico">●</span>Logout</span></a>
  </div>
</aside>

<main class="main">
<header class="topbar">
  <div class="search"></div>
  <div class="top-actions">
    <span class="muted"><?= h($_SESSION['admin_name'] ?? 'Admin') ?></span>
    <div class="avatar">A</div>
  </div>
</header>
<div class="content">
  <div class="page-head">
    <div>
      <h1><?= h($admin_page_title) ?></h1>
    </div>
    <?php if (!empty($admin_page_actions)): ?>
      <div class="actions"><?= $admin_page_actions ?></div>
    <?php endif; ?>
  </div>

  <?php $flash = admin_flash(); if ($flash): ?>
    <div class="notice"><strong>✓</strong> <?= h($flash) ?></div>
  <?php endif; ?>
