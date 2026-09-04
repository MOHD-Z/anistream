<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$toggleKeys = ['sidebar_enabled', 'show_episode_badge', 'show_genre_tag', 'show_type_badge', 'show_views', 'show_score'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'general') {
    $upsert = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $upsert->execute(['site_name', trim($_POST['site_name'] ?? 'AniStream')]);
    foreach ($toggleKeys as $key) {
        $upsert->execute([$key, isset($_POST[$key]) ? '1' : '0']);
    }
    admin_flash('Settings saved.');
    header('Location: settings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'api_keys') {
    $upsert = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $upsert->execute(['tmdb_api_key', trim($_POST['tmdb_api_key'] ?? '')]);
    $upsert->execute(['mal_client_id', trim($_POST['mal_client_id'] ?? '')]);
    admin_flash('API keys saved.');
    header('Location: settings.php');
    exit;
}

$settings = [];
foreach ($pdo->query("SELECT * FROM site_settings") as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$labels = [
    'sidebar_enabled' => ['Homepage Sidebar', 'Show the blog sidebar on the homepage (4 cards/row). Off = full-width grid (6 cards/row).'],
    'show_episode_badge' => ['Episode/Runtime Badge', 'Show the episode count or runtime badge on media cards.'],
    'show_genre_tag' => ['Genre Tag', 'Show the genre label on media cards.'],
    'show_type_badge' => ['Type Badge', 'Show the SERIES/MOVIE badge on the card thumbnail.'],
    'show_views' => ['View Count', 'Show the view counter on media cards.'],
    'show_score' => ['Score', 'Show the star score on media cards.'],
];

$admin_page_title = 'Site Settings';
$admin_active = 'settings';
include __DIR__ . '/includes/layout_top.php';
?>
  <div class="panel">
    <div class="panel-head"><h2>General</h2></div>
    <div class="panel-body">
      <form method="post">
        <input type="hidden" name="form" value="general">
        <div class="field" style="margin-bottom:20px;max-width:400px;">
          <label>Site Name</label>
          <input type="text" name="site_name" value="<?= h($settings['site_name'] ?? 'AniStream') ?>">
        </div>

        <div class="section-title" style="margin-top:0;">Layout & Media Cards</div>
        <?php foreach ($labels as $key => [$title, $desc]): ?>
          <div class="switch">
            <div>
              <strong><?= h($title) ?></strong>
              <small><?= h($desc) ?></small>
            </div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="<?= $key ?>" <?= ($settings[$key] ?? '1') === '1' ? 'checked' : '' ?> style="width:18px;height:18px;">
            </label>
          </div>
        <?php endforeach; ?>

        <div style="margin-top:20px;">
          <button type="submit" class="btn primary">Save Settings</button>
        </div>
      </form>
    </div>
  </div>

  <div class="panel" style="margin-top:20px;">
    <div class="panel-head"><h2>API Keys — TMDB / MyAnimeList</h2></div>
    <div class="panel-body">
      <p class="muted" style="margin-bottom:16px;">
        Used by the <a href="import.php">Import from TMDB/MAL</a> page to pull in
        series/movie data automatically. Keys are stored in your database, not shared
        anywhere else.
      </p>
      <form method="post">
        <!-- separate save action from the general settings form above -->
        <input type="hidden" name="form" value="api_keys">

        <div class="form-grid">
          <div class="field">
            <label>TMDB API Key (v3 auth)</label>
            <input type="text" name="tmdb_api_key" placeholder="e.g. 3a1b2c3d4e5f..." value="<?= h($settings['tmdb_api_key'] ?? '') ?>">
          </div>
          <div class="field">
            <label>MAL Client ID</label>
            <input type="text" name="mal_client_id" placeholder="Your MyAnimeList API Client ID" value="<?= h($settings['mal_client_id'] ?? '') ?>">
          </div>
        </div>
        <div style="margin-top:16px;">
          <button type="submit" class="btn primary">Save API Keys</button>
        </div>
      </form>
    </div>
  </div>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
