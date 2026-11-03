# ConBoard Roadmap

Derived from README.md, TODO.md and a read-through of the code (Nov 2, 2026). Not runtime-tested.

## Phase 0: Make the repo consistent and loadable
- `conboard.php` requires files missing from the repo: `includes/acf_fields.php`, `includes/post-types/cpt_room.php`, `includes/admin/admin_overview.php`, `includes/admin/admin_room_metaboxes.php`. Either restore them or update the requires. (The live install may be ahead of this repo.)
- Newer files (`includes/acf/*`, `includes/maps/*`, `admin_dashboard`, `admin_list_icons`, `admin_pages`, `admin_ui`, `admin_room_relationships`) are not required from `conboard.php`.
- Finish the Rooms -> Locations rename (`acf_session.php` still uses `room`; conflicts and caching use `location`).
- Fix the unterminated string in `shortcodes.php` (`'key => ...`).
- `templates.php` uses `conboard_*` post type names, but the CPTs are registered as `session`, `presenter`, etc.
- Display system mismatches: context keys vs. what hooks read, orientation values (`horizontal` vs `landscape`), rotation (`none/left/right` vs int), routing to missing `templates/display/*`, missing `assets/js/signage.js`.
- Empty stub templates: announcement, sponsor, staff, room, display board. `defaults.php` is a stub; activation hook registered twice.
- Remove the debug `error_log` on `acf/init`.

## Phase 1: Screen Mode
- `signage.js` and display templates for Location, Session, Presenter, Sponsor, Staff.
- "Get Display Screen" button in admin.
- QR codes pointing at the standard page (currently external service, wrong URL).
- Rotate / flip / orientation overrides; settings popup.

## Phase 2: Time-aware core
- Event days in global settings (Day 1 - Sunday - Jan 24 formats).
- Now / Next logic; in-progress and starting-soon highlighting; schedule views.
- Presenter repeater and column on Sessions (ACF free limits apply).

## Phase 3: Display Boards
- ACF fields: mode, slide interval, refresh interval, clock, physical defaults.
- Slide engine: Now/Next, room schedule, sponsor loop, announcements, welcome, schedule grid, wayfinding.
- Sponsor levels; default boards on activation.

## Phase 4: Polish and conflicts
- Open TODO items: live map preview from checkboxes, SVG-change handling, parent map fallback, Room Setup override on sessions, column widths, Add New titles.
- Conflicts: Sessions list column, visual report, alternate room suggestions, Display Board awareness.

## Phase 5: Later
- JPG export (1920x1080) and batch export.
- CSV import/export.
- Badges and printed programs.
- Registration and ticketing.
