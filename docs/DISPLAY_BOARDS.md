# Display Boards: audit and plan

Display Boards are a reusable, ordered presentation ("a playlist of slides") built from live ConBoard data.
Screen Mode shows one existing page with no setup; a Display Board is a named configuration made of many slides.
Core principle: enter information once, display it anywhere.

## 1. What exists today (audit)

| Piece | State |
|---|---|
| `display_board` CPT (`cpt_display_board.php`) | Registered, public, title only, slug `display`. Works; no editor fields. |
| `acf_display_board.php` | Removed (boards use post meta, not ACF fields). |
| `conboard_context_from_display_board()` | Ready. Reads `orientation`, `rotation`, `flip`, `slide_interval`, `refresh_interval`, `show_clock`. Precedence is already defaults, then board, then URL. |
| Routing (`display_routing.php`, `conboard_screen_post_types()`) | `display_board` is not routed to the screen shell; no renderer in `conboard_screen_render_content()`. |
| `signage.js` | Page loop uses one global interval and `.cb-page` elements; refresh swaps `#cb-content` and keeps the page index; scaling, rotate/flip, clock, QR, menu all reusable. |
| Time awareness | Server-side only (`conboard_get_session_state()`); states are frozen between refreshes. Does not meet the spec. |
| `templates/single-conboard_display_board.php` | Removed. |
| `defaults.php` / `conboard_create_default_display_boards()` | Empty stub. |
| `admin_display_board_actions.php` | Removed (dead code). |

Reusable as is: scaling and orientation shell, URL overrides, refresh, QR, screen renderers (location now/next, session cards, sponsor tiles, announcements, tile pagination), sponsor levels, announcement flags.

## 2. Decisions

1. **Slides are rows on the board, not a second post type, and no ACF Pro.** ConBoard must work on free ACF. Slides are stored as one JSON list in a single post meta (`_conboard_slides`), edited by a small native row editor (PHP + vanilla JS metabox): add, drag reorder, duplicate, collapse, delete, enable toggle. Each row has `type`, `enabled`, `duration` (0 = board default), `template` and a `settings` object, matching the product spec's JSON shape. Fields per slide type come from the registry. The row editor is built as a generic reusable component, so it can later replace the Pro-only repeaters (social links, web links, session presenters).
2. **Slide type registry.** `conboard_register_slide_type( $key, [ label, fields, templates, render, live ] )`. The row editor's slide forms are generated from the registry, and the playback engine only calls `render`. New types never touch the engine.
3. **Time-aware rendering in the browser.** Schedule slides output each session with `data-start`/`data-end` (UTC timestamps) and the server time offset. `signage.js` computes upcoming / starting soon / in progress / completed every second, and updates progress bars and countdowns. Periodic refresh then only picks up schedule edits, and slide duration and data refresh stay independent.
4. **One schedule query layer.** `conboard_schedule_query( [ date, locations, tracks, window, completed, limit, group ] )` returns normalized rows (handles overlaps, cancelled, no location, multi-day). All schedule views and the existing location screen use it.
5. **Playback.** The engine reads a per-slide duration from `data-duration`, supports `fade|none`, skips disabled slides, loops, and shows a friendly empty-board message. Refresh preserves the current slide.
6. **Multi-region layouts later.** Each board gets a `layout` field (`full` only for now). Renderers return HTML for a region, so sidebar/ticker presets can reuse them.
7. **Preview.** Admin preview is an iframe of the board URL with `?display=screen&preview=1&slide=N` at the right aspect ratio. Simulated time (`now=`) is admin-only.

## 3. Phases (aligned with the product spec)

**Phase 1: foundation.** Board fields (description, orientation, rotation, flip, default duration, transition, refresh, clock, background); slide registry and flexible content with Welcome, Custom Content and Announcements to prove the engine; board routing to the screen shell; per-slide duration and fade in `signage.js`; empty-board fallback; Display Screen metabox and Dashboard link for boards; starter board on activation; remove dead code.
**Phase 2: live schedules.** Query layer; Now/Next, Daily Schedule, Room Schedule, Coming Up, Schedule Grid; client-side states and progress; room/track/date filters; refactor `conboard_screen_location()` onto the query layer.
**Phase 3: content.** Sponsor Showcase, Presenter, Session, Generated List, General Information, Wayfinding; QR on slides; presentation templates per type.
**Phase 4: layouts.** Region presets (sidebar, ticker, split); persistent Now/Next panel; priority interruptions; scheduled slides.
**Phase 5: operations.** Per-screen config, diagnostics, JPG/batch export, multi-display management.

## 4. Risks and open points

- **Existing Pro dependency:** the Session `presenters` field (`acf_session.php`) is an ACF repeater, which is Pro only, and the presenter helpers and caches read its `presenters_N_presenter` meta. On free ACF that field will not show. Fix (separate step): replace it with the native row editor, or a free relationship field plus a per-presenter role.
- Cancelled sessions need a status field on sessions (not present yet); Phase 2 adds one.
- Event "today" uses the site time zone; multi-day events rely on the Phase 2 event days setting.
- Nothing here has been run in real WordPress yet; Phase 1 should be tested on the live site early.

## 5. Phase 1 as built

- Settings in `_conboard_board` post meta; slides in `_conboard_slides` (list of `type, enabled, duration, settings`). No ACF fields or Pro features.
- `includes/display/display_slides.php`: registry (`conboard_register_slide_type`), storage, board rendering. Types so far: Welcome, Custom Content, Announcements.
- `includes/admin/admin_display_board.php`: Slides (row editor) and Board Settings boxes. The row editor gained textarea, checkbox, number and image fields, conditional fields, and duplicate.
- A board's plain link redirects to screen mode (other options in the address are kept). URL overrides still beat board settings.
- `signage.js` uses per-slide `data-duration`; transition is `fade` or `none`.
- A starter "Main Display" board is created on activation if there are no boards.

## 6. Phase 2 as built (live schedules)

- `includes/schedule.php`: `conboard_schedule_query()` finds sessions (date: today / a date / all; locations including nested rooms; tracks = Session Types; "starting within" window; finished: show/dim/hide; cancelled; limit) and works out each one's state: upcoming, soon (15 min), current, past. Times are real timestamps, converted from the site's local time. Missing end times assume 60 minutes (`conboard_default_session_minutes` filter). Overlaps, cancelled sessions, missing locations and multi-day events are handled. `conboard_now` filter lets previews and tests pretend it is another time.
- Session cards (`conboard_schedule_item_html()`) carry `data-start`/`data-end`. `signage.js` recalculates state, progress bar and countdown every second from server time, and refreshes the content right away when something changes state (at most every 10 seconds). Slide duration and data refresh stay independent.
- New slide types: Now / Next, Daily Schedule, Room Schedule, Coming Up, Schedule Grid (rooms across, 15-minute rows).
- Sessions have a Cancelled switch; cancelled sessions show struck through with a badge.
- The Location, Schedule and Presenter screens now use the same cards, so they are live too.
- Not yet: pinning the current session to the top, grouping by track, side-by-side handling of overlapping sessions in one grid column, simulated time in the editor preview (the `conboard_now` filter is the hook for it).

## 7. Phase 3 as built (content slides)

- New slide types: Sponsor Showcase (logo grid, a page per level, or one sponsor at a time), Presenter (a chosen one, or keynote/featured presenters), Session, Generated List (presenters, keynotes, staff, sponsors or upcoming sessions, as a photo grid or one slide each), General Information (text, Wi-Fi details), Wayfinding (location, directions, map), plus a QR address on Custom Content.
- These reuse the Screen Mode pages (`conboard_screen_presenter()`, `conboard_screen_session()`, sponsor and staff tiles), so a slide looks like the screen for that page.
- A slide can produce several screens (render returns a list of pages). Each page can set `url`; the corner QR code follows the page that is showing (a session's page, a sponsor's website, a Wi-Fi join code) and returns to the default when a page has none.
- Not yet: alternative presentation templates per type (for example a timeline view of a room schedule), randomized or weighted sponsor rotation.

## 8. Event days, Settings page and JPG export

- **Event days** (Settings): first day, number of days, and how a day is written (Day 1 / Day 1 – Sunday / Day 1 – Sunday – Jan 24 / Sunday / Sunday, January 24). `conboard_format_event_day()` writes dates that way (outside the event it drops the "Day N" part). Schedule slides accept `day1`, `day2`, ... as their date, and the Daily Schedule and Grid headings use the format.
- **Settings page repaired**: it had no form or Save button, loaded a script that did not exist, and could not save new sponsor levels. It now saves event days and sponsor levels, new levels get a slug from their label, and renaming a level moves its sponsors.
- **JPG export**: the screen menu has "Save this page as JPG" and "Save all pages as JPG"; `?export=jpg` in a screen address saves everything on load; every Display Screen box (and the Dashboard list) has an "Export JPG" button. Pictures are 1920x1080 (1080x1920 in portrait), ignore the screen's rotate/flip, hide the clock, and leave out a board's live announcement pages. Files are named `<title>-01.jpg`, `-02.jpg`, ... Built with the bundled html-to-image library (MIT, no outside service). Browsers may ask once to allow multiple downloads.
- Limits: images hosted on other websites cannot be included; batch export of many boards at once is still to do.
- Sponsor slides (one at a time) point their QR code to the sponsor's page on this site.
