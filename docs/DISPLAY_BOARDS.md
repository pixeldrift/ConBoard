# Display Boards: audit and plan

Display Boards are a reusable, ordered presentation ("a playlist of slides") built from live ConBoard data.
Screen Mode shows one existing page with no setup; a Display Board is a named configuration made of many slides.
Core principle: enter information once, display it anywhere.

## 1. What exists today (audit)

| Piece | State |
|---|---|
| `display_board` CPT (`cpt_display_board.php`) | Registered, public, title only, slug `display`. Works; no editor fields. |
| `acf_display_board.php` | Empty. No fields exist. |
| `conboard_context_from_display_board()` | Ready. Reads `orientation`, `rotation`, `flip`, `slide_interval`, `refresh_interval`, `show_clock`. Precedence is already defaults, then board, then URL. |
| Routing (`display_routing.php`, `conboard_screen_post_types()`) | `display_board` is not routed to the screen shell; no renderer in `conboard_screen_render_content()`. |
| `signage.js` | Page loop uses one global interval and `.cb-page` elements; refresh swaps `#cb-content` and keeps the page index; scaling, rotate/flip, clock, QR, menu all reusable. |
| Time awareness | Server-side only (`conboard_get_session_state()`); states are frozen between refreshes. Does not meet the spec. |
| `templates/single-conboard_display_board.php` | Empty stub, not mapped. Not needed in screen mode. |
| `defaults.php` / `conboard_create_default_display_boards()` | Empty stub. |
| `admin_display_board_actions.php` | Dead: uses `display_mode`/`display_location` fields that do not exist. Remove or replace. |

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
