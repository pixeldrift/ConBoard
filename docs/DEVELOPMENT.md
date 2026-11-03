# Development and testing

`bash dev/setup-wordpress.sh` builds a throwaway WordPress site next to the repo (default `~/wptest`),
using SQLite so no database server is needed. It installs ACF (free), links this plugin, activates it,
adds sample locations, sessions, boards and screens (`dev/seed.php`), and serves the site at
`http://localhost:8080` (login `admin` / `admin`). Re-running it is safe.

Test with Playwright against `http://localhost:8080` (use `localhost`, not `127.0.0.1`: cookies are tied to the site address).
Useful addresses: `/wp-admin/admin.php?page=conboard_screens` (Screen Assignments), `/screen/lobby-tv/` (a screen's own address).

Needs: PHP 8.2+ with sqlite3 and zip, curl, unzip, and network access to wordpress.org, downloads.wordpress.org and github.com.
