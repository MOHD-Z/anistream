# AniStream — PHP/MySQL Conversion

Your original HTML/CSS/JS template, converted into a full working PHP + MySQL
site: real database-driven content, working search, genre filtering,
multi-server video playback with problem reporting, real accounts
(Sign Up/Login), Favorites/Watchlist/Continue Watching, and a complete
login-protected admin panel that controls the live site.

## Setup (XAMPP / Laragon / any local PHP + MySQL stack)

1. Copy the whole `php-project` folder into your web root, e.g.
   `C:\xampp\htdocs\anistream` or `htdocs/anistream` in Laragon.
2. Start Apache and MySQL.
3. In phpMyAdmin (`http://localhost/phpmyadmin`), click **Import**, choose
   `database/schema.sql` (browse to it in the file picker), and click **Go**.
   This creates the `anistream` database, every table, and sample content.
4. Check `config/db.php` — defaults (`localhost` / `root` / no password)
   match a stock XAMPP/Laragon install. Edit if yours differs.
5. Visit `http://localhost/anistream/index.php`.

**Deploying to a live host:** upload everything the same way via
FTP/hosting file manager, import the same `schema.sql` through your host's
phpMyAdmin (or `mysql` CLI), and update `config/db.php` with the
credentials your host gives you. The included `.htaccess` blocks direct
access to the `database/` folder and disables directory listing.

## Public site

| Page | What it does |
|---|---|
| `index.php` | Homepage — sections are fully admin-configurable |
| `series.php` / `movies.php` | Full listings |
| `tv-details.php?slug=` / `movie-details.php?slug=` | Detail pages, with Favorite/Watchlist buttons |
| `episodes.php` | All episodes, sortable |
| `genrs.php` | Genre index + per-genre results |
| `search.php?q=` | Searches series, movies, **and** episodes |
| `watching.php?slug=` / `movie-watching.php?slug=` | Multi-server player, switch servers live, "Report a Problem" form, records Continue Watching for logged-in users |
| `blog.php` / `blog-details.php?slug=` | Blog |
| `login.php` / `signup.php` / `logout.php` | Real auth — hashed passwords, PHP sessions |
| `my-list.php` | Logged-in users: Favorites, Watchlist, Continue Watching |

Every page now sets `<meta name="description">` and Open Graph tags
(title/description/image) automatically from the content being shown.

## Admin Panel — `admin/login.php`

Default seed login: **admin@anistream.test** / **admin123** — change this
once you've confirmed everything works (edit the row in `admin_users`, or
create your own and delete the seed one).

| Section | What it does |
|---|---|
| Dashboard | Counts + recent video reports |
| Series / Movies | Full add, **edit**, delete |
| Episodes | Add, **edit**, delete — auto-creates the season row; move an episode to a different season number from the edit form |
| Genres | Add, delete |
| Blog | Add, edit, delete |
| Video Sources | Add/enable/disable/delete servers per episode or movie, from each item's "Sources" button |
| Reports | All video reports, plus a per-server report-count rollup so you can spot a bad server |
| Homepage Sections | Add/rename/reorder/hide/delete the homepage's content blocks, choose what feeds each one (series/movies/trending/popular), how many posts, and sort order — the public homepage reads this table directly |
| Site Settings | Site name, homepage sidebar on/off (auto-switches the grid between 4 and 6 cards per row), and toggles for what shows on media cards (episode badge, genre tag, type badge, views, score) |
| Users | View/remove visitor accounts |
| Languages | Add/enable languages, set the default, flag RTL |

The admin panel has its own login (`admin_users` table) — separate from
visitor accounts created via the public Sign Up page.

## What was checked before delivery

- Every `.php` file passed a manual PHP-tag balance check (`<?php`/`<?=`
  vs `?>`) and a brace/parenthesis balance scan — no truncated blocks.
- Every helper function (`render_card`, `is_in_list`,
  `record_watch_progress`, `get_setting`, etc.) is defined exactly once and
  every call site matches its argument order.
- Traced every admin form's POST handler against its HTML `name=` attributes.
- Traced every "Favorite / Watchlist" and "Continue Watching" link through
  `list-action.php` and the `watch_history`/`favorites` tables end-to-end.
- Confirmed cascading deletes (deleting a series removes its seasons,
  episodes, and their video sources; deleting a video source removes its
  reports) via the schema's `ON DELETE CASCADE` foreign keys.
- No PHP interpreter is available in this sandbox to actually execute the
  code, so this was static review, not a live test run — please do a
  first-run smoke test after importing the database (create an account,
  favorite something, submit a video report, add a homepage section) and
  tell me if anything misbehaves so I can fix it directly.

## Deliberately not built

Full per-field translation (auto-translate draft → manual admin
correction), roles/permissions, SEO tooling beyond meta tags, in-app
notifications, backups, and audit logs. Each is a substantial feature in
its own right — tell me which matters most and I'll build it properly
rather than stub it out.
