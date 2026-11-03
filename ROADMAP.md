# ConBoard Roadmap

Updated Nov 3, 2026. Phases 0-3 are built (on `main`); the sections below list what is left.

## Done
- Repo loads cleanly; Locations rename finished; templates and shortcodes for every post type.
- Screen Mode (`?display=screen`) for every page and list, with URL overrides, live refresh, clock and local QR codes.
- Time-aware core: Now/Next, starting-soon and in-progress states, schedule views, Event Days in Settings.
- Display Boards: slide registry, Now/Next, daily and room schedules, sponsors, presenters, sessions, generated lists, general info, wayfinding; starter board on activation.
- Native row editor, Session Presenters box, unified Links list; sponsor levels in Settings.
- JPG export per screen or board, plus ZIP export: one board ("Save all pages as ZIP" in the screen menu) or all boards from the Display Boards list (folder per board, or everything in the root).
- Maps: live layer preview, parent-map fallback, Show Map toggle, overlay + labels stack for image maps.
- Admin polish: Add New titles, column widths, per-session Room Setup, global defaults, Markdown bullets.
- Conflict column in the Sessions list.
- Sync indicator on screens (dead man's switch): crossed-out Wi-Fi when no live confirmation for 75 s; optional green dot while current (`?sync=offline|always|off`, also in the screen menu); hidden in JPG exports.
- Related sessions on the session page ("You might also like": shared presenter, then shared Session Type, then same room and day).
- `[conboard_schedule]` shortcode: live schedule on any page (day, room or flat grouping, filters, in-progress highlighting).
- Program Planner (ConBoard > Program Planner): unscheduled cards, day grid with rooms as columns, drag to schedule, move, resize, unschedule (length kept), day arrows with prev/next strips; map pane (layers as drop zones, room list for maps without layers, room boxes with no map) with a red time line that follows the grid scroll, room highlights and title/time pills.

## Next
- Conflicts: visual report page, alternate room suggestions, Display Board awareness, prevent at save (optional).
- Display Boards Phase 4: layouts, ticker, interruptions, scheduled slides, board editor live preview.
- Split first/last name for Presenters and Staff (decision pending).


## Later
- PSD layers, CSV import/export, badges and printed programs, registration and ticketing.
