<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

function slugify_ep($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function get_or_create_season($pdo, $series_id, $season_number) {
    $stmt = $pdo->prepare("SELECT id FROM seasons WHERE series_id = ? AND season_number = ?");
    $stmt->execute([$series_id, $season_number]);
    $season = $stmt->fetch();
    if ($season) return $season['id'];
    $pdo->prepare("INSERT INTO seasons (series_id, season_number, title) VALUES (?, ?, ?)")
        ->execute([$series_id, $season_number, 'Season ' . $season_number]);
    return $pdo->lastInsertId();
}

// Single create (auto-creates the season row if it doesn't exist yet)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $series_id = (int)$_POST['series_id'];
    $season_number = (int)$_POST['season_number'];
    $episode_number = (int)$_POST['episode_number'];
    $title = trim($_POST['title'] ?? '');

    $season_id = get_or_create_season($pdo, $series_id, $season_number);
    $slug = slugify_ep($title) . '-' . $series_id . '-s' . $season_number . 'e' . $episode_number;
    $pdo->prepare("INSERT INTO episodes (season_id, episode_number, title, slug) VALUES (?,?,?,?)")
        ->execute([$season_id, $episode_number, $title, $slug]);

    admin_flash('Episode added.');
    header('Location: episodes.php' . ($series_id ? '?series_id=' . $series_id : ''));
    exit;
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id = (int)$_POST['id'];
    $series_id = (int)$_POST['series_id'];
    $season_number = (int)$_POST['season_number'];
    $episode_number = (int)$_POST['episode_number'];
    $title = trim($_POST['title'] ?? '');

    $season_id = get_or_create_season($pdo, $series_id, $season_number);
    $pdo->prepare("UPDATE episodes SET season_id=?, episode_number=?, title=? WHERE id=?")
        ->execute([$season_id, $episode_number, $title, $id]);

    admin_flash('Episode updated.');
    header('Location: episodes.php?series_id=' . $series_id);
    exit;
}

// Bulk create — N episodes for one series in one go
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_create') {
    $series_id = (int)$_POST['bulk_series_id'];
    $season_number = (int)$_POST['bulk_season_number'];
    $count = max(1, min(200, (int)$_POST['bulk_count']));
    $startFrom = max(1, (int)($_POST['bulk_start'] ?? 1));
    $titleLines = array_values(array_filter(array_map('trim', explode("\n", $_POST['bulk_titles'] ?? '')), fn($l) => $l !== ''));
    $urlLines = array_values(array_filter(array_map('trim', explode("\n", $_POST['bulk_urls'] ?? '')), fn($l) => $l !== ''));
    $quality = trim($_POST['bulk_quality'] ?? 'HD');

    $season_id = get_or_create_season($pdo, $series_id, $season_number);
    $insEp = $pdo->prepare("INSERT INTO episodes (season_id, episode_number, title, slug) VALUES (?,?,?,?)");
    $insSrc = $pdo->prepare("INSERT INTO video_sources (episode_id, name, quality, url, priority, enabled) VALUES (?,?,?,?,1,1)");

    $created = 0;
    for ($i = 0; $i < $count; $i++) {
        $epNum = $startFrom + $i;
        $title = $titleLines[$i] ?? ('Episode ' . $epNum);
        $slug = slugify_ep($title) . '-' . $series_id . '-s' . $season_number . 'e' . $epNum . '-' . substr(md5(uniqid()), 0, 5);
        $insEp->execute([$season_id, $epNum, $title, $slug]);
        $newId = $pdo->lastInsertId();
        if (!empty($urlLines[$i])) {
            $insSrc->execute([$newId, 'Server 1', $quality, $urlLines[$i]]);
        }
        $created++;
    }

    admin_flash("Created $created episodes.");
    header('Location: episodes.php?series_id=' . $series_id);
    exit;
}

if (isset($_GET['archive'])) {
    $pdo->prepare("UPDATE episodes SET archived = 1 WHERE id = ?")->execute([(int)$_GET['archive']]);
    admin_flash('Episode archived.');
    header('Location: episodes.php' . (isset($_GET['series_id']) ? '?series_id=' . (int)$_GET['series_id'] : ''));
    exit;
}
if (isset($_GET['restore'])) {
    $pdo->prepare("UPDATE episodes SET archived = 0 WHERE id = ?")->execute([(int)$_GET['restore']]);
    admin_flash('Episode restored.');
    header('Location: episodes.php' . (isset($_GET['series_id']) ? '?series_id=' . (int)$_GET['series_id'] : ''));
    exit;
}

$series_id = $_GET['series_id'] ?? null;
$allSeries = $pdo->query("SELECT id, title FROM series WHERE archived = 0 ORDER BY title")->fetchAll();
$q = trim($_GET['q'] ?? '');
$perPage = in_array((int)($_GET['per_page'] ?? 25), [25, 50, 75, 100], true) ? (int)$_GET['per_page'] : 25;
$view = $_GET['view'] ?? 'active';

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT e.*, se.season_number, se.series_id FROM episodes e JOIN seasons se ON e.season_id = se.id WHERE e.id = ?");
    $stmt->execute([$_GET['edit']]);
    $editing = $stmt->fetch();
}

$where = ['e.archived = ' . ($view === 'archived' ? 1 : 0)];
$params = [];
if ($series_id) { $where[] = 's.id = ?'; $params[] = $series_id; }
if ($q !== '') { $where[] = '(e.title LIKE ? OR s.title LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$whereSql = implode(' AND ', $where);

$sql = "SELECT e.*, se.season_number, s.title AS series_title, s.id AS series_id
    FROM episodes e JOIN seasons se ON e.season_id = se.id JOIN series s ON se.series_id = s.id
    WHERE $whereSql ORDER BY e.created_at DESC LIMIT $perPage";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$episodes = $stmt->fetchAll();

$admin_page_title = 'Episodes';
$admin_active = 'episodes';
include __DIR__ . '/includes/layout_top.php';
?>
  <div class="panel" style="margin-bottom:20px;">
    <div class="panel-head"><h2><?= $editing ? 'Edit Episode' : 'Add Episode' ?></h2></div>
    <div class="panel-body">
      <form method="post" class="form-grid">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
        <div class="field">
          <label>Series</label>
          <select name="series_id" required>
            <?php foreach ($allSeries as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= ($editing['series_id'] ?? $series_id) == $s['id'] ? 'selected' : '' ?>><?= h($s['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Season Number</label>
          <input type="number" name="season_number" min="1" value="<?= h($editing['season_number'] ?? '1') ?>" required>
        </div>
        <div class="field">
          <label>Episode Number</label>
          <input type="number" name="episode_number" min="1" value="<?= h($editing['episode_number'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label>Episode Title</label>
          <input type="text" name="title" required value="<?= h($editing['title'] ?? '') ?>">
        </div>
        <div class="field full">
          <button type="submit" class="btn primary"><?= $editing ? 'Save Changes' : 'Add Episode' ?></button>
          <?php if ($editing): ?><a href="episodes.php?series_id=<?= (int)$editing['series_id'] ?>" class="btn">Cancel</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px;">
    <div class="panel-head"><h2>Bulk Add Episodes</h2></div>
    <div class="panel-body">
      <p class="muted" style="margin-bottom:12px;">
        Creates several episodes for one series at once. Leave the title/link boxes
        blank for any episode and it falls back to "Episode N" / no video source —
        you can fill those in individually afterward.
      </p>
      <form method="post" class="form-grid">
        <input type="hidden" name="action" value="bulk_create">
        <div class="field">
          <label>Series</label>
          <select name="bulk_series_id" required>
            <?php foreach ($allSeries as $s): ?>
              <option value="<?= (int)$s['id'] ?>"><?= h($s['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Season Number</label>
          <input type="number" name="bulk_season_number" min="1" value="1" required>
        </div>
        <div class="field">
          <label>Start Episode #</label>
          <input type="number" name="bulk_start" min="1" value="1" required>
        </div>
        <div class="field">
          <label>How Many Episodes</label>
          <input type="number" name="bulk_count" min="1" max="200" value="12" required>
        </div>
        <div class="field">
          <label>Quality (applied to all)</label>
          <select name="bulk_quality">
            <?php foreach (['4K','Blu-ray','FHD','HD','MD','SD','CAM'] as $q2): ?><option><?= $q2 ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field full">
          <label>Episode Titles (one per line, first line = start episode #, in order)</label>
          <textarea name="bulk_titles" placeholder="Episode 1 title&#10;Episode 2 title&#10;..." style="min-height:100px;"></textarea>
        </div>
        <div class="field full">
          <label>Video links (one per line, matching the same order as titles above)</label>
          <textarea name="bulk_urls" placeholder="videos/ep1.mp4&#10;videos/ep2.mp4&#10;..." style="min-height:100px;"></textarea>
        </div>
        <div class="field full">
          <button type="submit" class="btn primary" onclick="return confirm('Create these episodes now?')">Create Episodes</button>
        </div>
      </form>
    </div>
  </div>

  <div class="panel" style="margin-bottom:16px;">
    <div class="panel-body" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <form method="get" style="display:flex;gap:10px;flex:1;min-width:220px;">
        <?php if ($series_id): ?><input type="hidden" name="series_id" value="<?= (int)$series_id ?>"><?php endif; ?>
        <input type="hidden" name="view" value="<?= h($view) ?>">
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search episode or series title..."
               style="flex:1;background:#0f111b;border:1px solid var(--border);color:#e9ebf4;border-radius:8px;padding:9px 10px;">
        <button type="submit" class="btn primary">Search</button>
      </form>
      <form method="get">
        <?php if ($series_id): ?><input type="hidden" name="series_id" value="<?= (int)$series_id ?>"><?php endif; ?>
        <input type="hidden" name="view" value="<?= h($view) ?>">
        <input type="hidden" name="q" value="<?= h($q) ?>">
        <label class="muted">Show:</label>
        <select name="per_page" onchange="this.form.submit()">
          <?php foreach ([25, 50, 75, 100] as $pp): ?>
            <option value="<?= $pp ?>" <?= $perPage === $pp ? 'selected' : '' ?>><?= $pp ?> per page</option>
          <?php endforeach; ?>
        </select>
      </form>
      <div>
        <a href="episodes.php?view=active<?= $series_id ? '&series_id=' . (int)$series_id : '' ?>" class="btn <?= $view === 'active' ? 'primary' : '' ?>">Active</a>
        <a href="episodes.php?view=archived<?= $series_id ? '&series_id=' . (int)$series_id : '' ?>" class="btn <?= $view === 'archived' ? 'primary' : '' ?>">Archived</a>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2><?= $series_id ? h($allSeries[array_search($series_id, array_column($allSeries, 'id'))]['title'] ?? '') : "Episodes (showing up to $perPage)" ?></h2>
      <?php if ($series_id): ?><a href="episodes.php" class="btn">Show All</a><?php endif; ?>
    </div>
    <div class="panel-body table-wrap">
      <table class="table">
        <thead><tr><th>Series</th><th>Season</th><th>Ep</th><th>Title</th><th>Views</th><th></th></tr></thead>
        <tbody>
          <?php if (!$episodes): ?><tr><td colspan="6" class="empty">No episodes found.</td></tr><?php endif; ?>
          <?php foreach ($episodes as $e): ?>
            <tr>
              <td><?= h($e['series_title']) ?></td>
              <td><?= (int)$e['season_number'] ?></td>
              <td><?= (int)$e['episode_number'] ?></td>
              <td><?= h($e['title']) ?></td>
              <td><?= (int)$e['views'] ?></td>
              <td>
                <a href="episodes.php?edit=<?= (int)$e['id'] ?>&series_id=<?= (int)$e['series_id'] ?>" class="btn">Edit</a>
                <a href="video_sources.php?episode_id=<?= (int)$e['id'] ?>" class="btn">Sources</a>
                <?php if ($view === 'archived'): ?>
                  <a href="episodes.php?restore=<?= (int)$e['id'] ?>&series_id=<?= (int)$e['series_id'] ?>" class="btn primary">Restore</a>
                <?php else: ?>
                  <a href="episodes.php?archive=<?= (int)$e['id'] ?>&series_id=<?= (int)$e['series_id'] ?>" class="btn danger" onclick="return confirm('Archive this episode?')">Archive</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
