<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/upload.php';

function slugify_movie($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

$id = $_GET['id'] ?? null;
$movie = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    $movie = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $synopsis = trim($_POST['synopsis'] ?? '');
    $poster = handle_poster_upload('poster_file', trim($_POST['poster'] ?? ''));
    $genreIds = $_POST['genre_ids'] ?? [];
    $runtime = (int)($_POST['runtime'] ?? 90);
    $score = $_POST['score'] ?: 0;
    $slug = slugify_movie($title);

    if ($id) {
        $stmt = $pdo->prepare("UPDATE movies SET title=?, slug=?, synopsis=?, poster=?, runtime=?, score=? WHERE id=?");
        $stmt->execute([$title, $slug, $synopsis, $poster, $runtime, $score, $id]);
        set_item_genres($pdo, 'movie', $id, $genreIds);
        admin_flash('Movie updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO movies (title, slug, synopsis, poster, runtime, score) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$title, $slug, $synopsis, $poster, $runtime, $score]);
        set_item_genres($pdo, 'movie', $pdo->lastInsertId(), $genreIds);
        admin_flash('Movie created.');
    }
    header('Location: movies.php');
    exit;
}

$genres = $pdo->query("SELECT * FROM genres ORDER BY name")->fetchAll();
$selectedGenreIds = $id ? array_column(get_item_genres($pdo, 'movie', $id), 'id') : [];

$admin_page_title = $movie ? 'Edit Movie' : 'Add Movie';
$admin_active = 'movies';
include __DIR__ . '/includes/layout_top.php';
?>
  <div class="panel">
    <div class="panel-body">
      <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
          <div class="field">
            <label>Title</label>
            <input type="text" name="title" required value="<?= h($movie['title'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Runtime (minutes)</label>
            <input type="number" name="runtime" value="<?= h($movie['runtime'] ?? '90') ?>">
          </div>
          <div class="field">
            <label>Score (0-10)</label>
            <input type="number" step="0.1" min="0" max="10" name="score" value="<?= h($movie['score'] ?? '0') ?>">
          </div>
          <div class="field full">
            <label>Genres (select one or more)</label>
            <div style="display:flex;flex-wrap:wrap;gap:6px 16px;background:#0f111b;border:1px solid var(--border);border-radius:8px;padding:12px;max-height:160px;overflow-y:auto;">
              <?php foreach ($genres as $g): ?>
                <label style="display:flex;align-items:center;gap:6px;font-weight:normal;min-width:140px;">
                  <input type="checkbox" name="genre_ids[]" value="<?= (int)$g['id'] ?>" <?= in_array($g['id'], $selectedGenreIds) ? 'checked' : '' ?> style="width:15px;height:15px;">
                  <?= h($g['name']) ?>
                </label>
              <?php endforeach; ?>
              <?php if (!$genres): ?><span class="muted">No genres yet — add some on the Genres page first.</span><?php endif; ?>
            </div>
          </div>
          <div class="field">
            <label>Poster — paste a URL</label>
            <input type="text" name="poster" placeholder="img/popular/popular-1.jpg or https://..." value="<?= h($movie['poster'] ?? '') ?>">
          </div>
          <div class="field">
            <label>...or upload a file (overrides the URL above)</label>
            <input type="file" name="poster_file" accept="image/*">
          </div>
          <?php if (!empty($movie['poster'])): ?>
            <div class="field full">
              <label>Current poster</label>
              <img src="<?= (strpos($movie['poster'], 'http') === 0) ? h($movie['poster']) : '../' . h($movie['poster']) ?>" alt="" style="max-height:120px;border-radius:6px;">
            </div>
          <?php endif; ?>
          <div class="field full">
            <label>Synopsis</label>
            <textarea name="synopsis"><?= h($movie['synopsis'] ?? '') ?></textarea>
          </div>
        </div>
        <div style="margin-top:16px;">
          <button type="submit" class="btn primary"><?= $movie ? 'Save Changes' : 'Create Movie' ?></button>
          <a href="movies.php" class="btn">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
