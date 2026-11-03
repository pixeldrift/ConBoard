## Overview
*ConBoard* is a WordPress plugin for managing conferences, festivals, and multi-track events, and for powering live event info from the same data source. It connects sessions, presenters, rooms, and schedules into a time-aware system that works both on the website and on screens around the venue. This plugin is not just a website, but also a digital signage system!

It provides a structured content system for:

- Sessions
- Presenters
- Locations (campuses, buildings, floors and rooms)
- Sponsors
- Staff
- Announcements
- Display Boards

All *ConBoard* post types are interconnected and aware of time, location, and each other. *ConBoard* replaces the common patchwork of schedules, slide decks, and signage tools with a single, data-driven system. Instead of maintaining separate content for the website and on-site displays, ConBoard uses the same structured event data to power both. This helps avoid inconsistencies, reduces manual work, and allows displays to update automatically as the schedule changes.

Unlike slide-based signage tools or page-builder-driven event sites, *ConBoard* is built around structured event data. Content is defined once and rendered in multiple contexts, making it easier to maintain, extend, and integrate with other WordPress plugins while still supporting highly customized display layouts when needed.

Rather than treating event data as static pages, *ConBoard* models how conferences actually work. Sessions know when and where they happen. Presenters know which sessions they are part of. Rooms know what's happening in them now, next, and later. This allows attendees to naturally explore the schedule, drill into session details, discover speakers, and see related sessions without duplicate data entry or manual linking.

During the conference itself, *ConBoard* becomes time-aware, enabling features like highlighting sessions currently in progress or starting soon. Multiple schedule views make decision making a breeze, and can indicate how far along you are in the day. The same details that power your website can automatically drive on-site info screens, ensuring consistency between what attendees see online and what appears on display boards around the venue.

Screen mode is where *ConBoard* really shines. Any *ConBoard* page can instantly become a digital signage display optimized for TVs and projectors, pulling live data directly from your site and offering automatically generated QR codes that take attendees directly to the relevant page of your site. This makes *ConBoard* not just a conference website plugin, but a lightweight, web-based digital signage system tailored specifically for events.

Upcoming features are listed in the [To Do](#to-do) section at the end of this page.

*ConBoard* is great for:

#### Conference organizers who
- Run multi-session, multi-room events
- Need a structured schedule
- Want consistency between website and venue signage
- Don't want to maintain separate systems for web and screens

#### AV teams who
- Need URLs they can trust
- Want quick fixes without admin access
- Care about screen orientation, rotation, and legibility
- Are used to improvising on-site

## Setup

A good way to start is by creating a Session. Give it a title, summary, set a time, and choose a room. All fields are optional, but the more you add, the more useful *ConBoard* becomes. Custom fields that are connected to other post types can be created as stubs from within the item you are currently creating, or added separately and linked later.

Add a Presenter. Include their bio, headshot, and contact info. Now you can attach them to the session you just created. On the Session page, they will be listed as the presenter, with a link to their Presenter page. On the Presenter page, ConBoard will automatically list all Sessions they are a part of. Choose if you want to highlight them as a keynote speaker or a featured presenter on the home page and Presenters page.

You can follow this process a few more times, in any order you wish. It's very common to advertise confirmed presenters first, before you know their session topic or have a schedule worked out yet. Speakers can appear on your website to attract attendees without having all the logistical details yet.

Under **Settings** you can set your event days (first day, number of days, and how a day is written, such as "Day 1 – Sunday – Jan 24"), your sponsor levels, and global defaults for location type, room type, and room setup.

### Program Planner

Once you have sessions and rooms, **ConBoard > Program Planner** helps you build the schedule visually. Unscheduled sessions sit in a pile of cards. Drag them onto a day grid with rooms as columns, move or resize them, and drag them back to unschedule (their length is kept). A map pane shows the rooms and highlights where each session will be. The Sessions list also has a Conflicts column that flags room overlaps and presenters who are double-booked.

## Screen Mode

Every page can become an info screen. Any web-connected device can be used as signage to show attendees what's happening now, what's coming up next, or an overview of main attractions. For example, display the Presenter screen when introducing a speaker, or display the Sponsors carousel on a loop between sessions.

Quick and easy digital signage already exists by default for all rooms, speakers, sessions, and more! Reasonable defaults let you present useful information to attendees immediately simply by navigating to a URL from any connected TV, projector, or other web-accessible display. No admin access necessary.

Simply pull up any ConBoard powered page and add [`?display=screen`](?display=screen) after the web address. This will change the display template to be info board-friendly, with simplified details and a layout designed for a 16x9 screen.

[`https://example.com/locations/main-hall/?display=screen`](https://example.com/locations/main-hall/?display=screen)

From the admin dashboard, you can open any ConBoard custom post and press the "Get Display Screen" button to retrieve the display link.

Any of the display parameters that show up in the URL can be changed to aid in orienting the information for the screen type. For example, if a screen is mounted in a vertical orientation:

[`?display=screen&orientation=portrait&rotate=90&flip=none`](?display=screen&orientation=portrait&rotate=90&flip=none)

The screen options you can put in the URL:

- `orientation=landscape|portrait`: the shape of the layout (landscape is 16x9, portrait is 9x16)
- `rotate=0|90|180|270`: turn the whole screen clockwise, for a TV that is mounted sideways or upside down
- `flip=none|horizontal|vertical`: mirror the screen
- `interval=10`: seconds each page stays up when a screen loops through several pages
- `refresh=60`: seconds between live data updates
- `clock=yes|no` and `qr=yes|no`: show or hide the clock and the QR code
- `sync=offline|always|off`: the sync indicator. By default a small crossed-out Wi-Fi symbol appears in the top corner if the screen has lost its live connection and is showing old content. `always` also shows a small green dot while the screen is current, and `off` hides it.

Move the mouse or touch the screen to reveal the gear in the corner. It opens a small menu to go fullscreen, rotate, flip, show or hide the clock and QR code, and save the screen as images. Changes made there only live in the address bar, so the screen is easy to fix on the spot and nothing is saved.

Screens refresh themselves, so schedule changes show up without touching the display. The clock follows your website's time zone, and the QR code is drawn on the screen itself, so no outside service is needed.

### Screen Mode or Display Board?

*Screen Mode* is a presentation layer applied to any existing ConBoard page. It takes a normal page like a Room, Session, Presenter, or Sponsor and renders it in a layout optimized for TVs and projectors, with simplified typography, large readable text, reduced navigation, and optional orientation adjustments. It can also include a QR code that takes you directly to the standard version of the page on your own device.

Screen Mode:
- Uses existing content
- Requires no setup
- Is activated via URL parameter (`?display=screen`)
- Always reflects live data
- Is ideal for quick, ad-hoc displays

Examples:
- Showing the current room schedule outside a session room
- Displaying a presenter info slide during their introduction
- Looping sponsor logos between sessions
- Showing "Now / Next" in a hallway

Screen Mode is intentionally lightweight and discoverable. Anyone with the URL can use it without website admin access, which is great for AV teams.

*Display Boards*, described next, are deliberate, named, reusable screen configurations that can mix many kinds of content.

## Display Boards

A Display Board is a named, reusable configuration for a digital screen. It defines what content the screen shows, how the content behaves, and the default physical display settings. Think of it as a playlist of slides, built from your live conference data.

Display Boards are independent of Rooms, Sessions, and so on, and can be shown on any number of displays at the same time, with your live data automatically inserted. Using the simple built-in editor you can choose:

- What content appears
- How long each slide stays up, and how slides transition (fade or none)
- Default physical screen properties (orientation, rotation, flip, clock)
- Which slides are turned on right now

Display Board characteristics:

- Explicitly created and named
- Can combine multiple content types
- Can cycle or loop content (a board with a single slide is a static screen)
- Has saved defaults
- Supports URL overrides for one-off situations

Slide types that can be shown on a Display Board include:

- Welcome
- Custom content (text, image, optional QR code)
- Announcements
- Now / Next
- Daily schedule
- Room schedule
- Coming up
- Full schedule grid (rooms across, time down)
- Sponsor showcase (logo grid, a page per level, or one at a time)
- Presenter
- Session
- Generated list (presenters, keynotes, staff, sponsors, or upcoming sessions)
- General information (text, Wi-Fi details with a join code)
- Directional sign (big arrows with a bold label, such as "Registration", for quick on-site signage instead of printed or handwritten signs)
- Location info (a location's directions text and its map)

Display Board examples:
- A welcome screen in the lobby
- A rotating sponsor carousel
- A full schedule overview
- A branded signage loop between sessions

A starter "Main Display" board is created for you when the plugin is activated.

### Config Overrides

As a convenience for AV teams, Display Board properties may be temporarily overridden via URL parameters without requiring a backend login. For example, if you show up to the venue and discover one of the screens is rotated the opposite direction from the others, there is no need to have admin access or create a new Display Board just for that one screen when the content should stay the same. Just change the rotation flag in the URL. URL overrides take precedence over Display Board settings for that one screen only and do not persist.

## Shortcodes

Drop ConBoard content into any page or post. All options are optional.

- `[conboard_sponsors]`: sponsor logos grouped under a heading per level.
  - `layout="levels|grid|carousel"`, `level="gold,silver"`, `columns="4"`, `limit="12"`
  - `link="page|website|none"` sets where a logo goes when clicked, `names="yes"` shows names under logos
  - `autoplay="yes"` and `speed="4"` (seconds) apply to the carousel
- `[conboard_presenters]`: a grid or carousel of presenters. Keynote speakers come first, then featured presenters.
  - `featured="yes"`, `keynote="yes"`, `layout="grid|carousel"`, `columns="3"`, `limit="6"`, `titles="yes|no"`
- `[conboard_featured_speaker]`: a spotlight on one presenter with photo, bio excerpt and their next session. Great for the home page.
  - `id="123"` picks a specific presenter. Otherwise it uses a keynote speaker (or a featured presenter), and `random="yes"` picks one at random.
- `[conboard_staff]`: staff photos and roles in a grid or carousel, in Display Order.
  - `layout="grid|carousel"`, `columns="4"`, `limit="8"`, `roles="yes|no"`
- `[conboard_announcements]`: the latest announcements, newest first.
  - `limit="3"`, `excerpt="yes"` for a short preview, `dates="yes|no"`, `urgent="yes"` for urgent ones only
  - Mark an announcement **Urgent** to show it first with a label, switch **Show on Screens** off to keep it off info screens, and set **Expires** to hide it automatically.
- `[conboard_schedule]`: the live schedule. Sessions highlight when they are in progress or starting soon, with a countdown and progress bar that update without reloading the page.
  - `date="all|today|2026-01-24|day2"`, `group="day|room|none"` for headings by event day or by location
  - `location="12,15"` (includes the rooms inside), `track="workshops,keynotes"` (Session Type slugs), `limit="10"`
  - `completed="show|dim|hide"` for finished sessions, `cancelled="show|hide"`, `presenters="yes|no"`, `links="yes|no"`, `live="yes|no"`
- `[current_session location="123"]`: what is happening now in a location.

Mark presenters as **Keynote Speaker** or **Featured Presenter** on their edit screen.

## Screen Casting

The easiest way to present *ConBoard* info screens on a TV or projector is connecting directly from a laptop or desktop computer over an HDMI cable. Because *ConBoard* is fully web-based, any device with a web browser should work. Alternatively, there are inexpensive wireless video transmitters that plug in to your HDMI port and are seen as a standard monitor. It is recommended to use "Extend" mode and treat that screen as a second monitor rather than "Mirror" mode. Then simply navigate to the desired URL and enter the browser's fullscreen mode. The number of screens you can stream to depends on your computer hardware and how many video outputs are available. If you want the same content going to multiple screens, you can use a simple HDMI splitter/duplicator.

#### Wireless HDMI Adapters:
- $15 [onn Full HD Streaming Device](https://www.walmart.com/ip/Google-TV-Full-HD-Streaming-Device-NEW-2023/2262757145)
- $20 [Roku Streaming Stick HD](https://www.amazon.com/dp/B0DXXYS4BJ/)
- $30 [Roku Streaming Stick Plus](https://www.amazon.com/dp/B0DXY833HV/)
- $30 [EZCast PRO Dongle Wireless HD](https://www.amazon.com/dp/B01N551MI6/)
- $35 [Wireless HDMI Transmitter and Receiver](https://www.amazon.com/dp/B0FR4K4VQJ/)
- $35 [WEKEFON Wireless HDMI Transmitter and Receiver](https://www.amazon.com/dp/B0FR4G43YR/)
- $35 [Wireless HDMI Display Adapter](https://www.walmart.com/ip/Wireless-HDMI-Transmitter-Receiver-2-in-1-Plug-Play-Transmitter-4K-Decode-1080P-Output-Video-Streaming-Laptop-PC-Tablet-Smartphone-TV-Monitor-Project/17345620407)
- $35 [TiVo Stream 4K](https://www.amazon.com/dp/B089655HQV/)
- $42 [EZCast2 4K Wireless Display Receiver](https://www.amazon.com/dp/B07YKGGTJL/)
- $42 [MT-VIKI 4K Wireless HDMI Display Dongle](https://www.amazon.com/dp/B0DY7RDMVY/)
- $50 [Amazon Fire TV Stick 4K Plus](https://www.amazon.com/dp/B0F7Z4QZTT/)
- $70 [XIAOMI TV Stick 4K Streaming Device](https://www.amazon.com/dp/B0FFN6NRTZ)

#### HDMI Splitters:
- $10 [1 in 2 Out HDMI Splitter Cable Adapter](https://www.amazon.com/dp/B07K2NZX8L/)
- $13 [1 in 4 Out HDMI Duplicator](https://www.amazon.com/dp/B0DCJ5PR11/)
- $17 [1 in 4 Out Powered HDMI Video Splitter](https://www.amazon.com/dp/B076JDXPRG/)
- $18 [1 in 4 Out HDMI Splitter Distributor Box](https://www.amazon.com/gp/aw/d/B081GL1CMV/)
- $32 [1 in 8 Out Powered HDMI Splitter](https://www.amazon.com/dp/B07T558FPG)

Some venues may have a central routing hub to send video signals to various screens. Others may need a device to be directly attached at the back of the TV. In cases where it is not practical to have a laptop connected to each screen, a dedicated playback device may be more reasonable. You can also use the web browser feature in any existing digital signage playback units you may have. For example:

- [Amazon Signage Stick](https://signage.amazon.com/)
- [BrightSign](https://www.brightsign.biz/)
- [OptiSigns](https://www.optisigns.com/)
- [Yodeck](https://www.yodeck.com/)
- [Screencloud](https://screencloud.com/pixi)
- A Raspberry Pi is a good option as well!

Many smart TVs allow streaming content to them over Wi-Fi as a built-in feature. Check the specific TV model to see if it supports [Apple AirPlay](https://en.wikipedia.org/wiki/AirPlay), [Google Cast](https://en.wikipedia.org/wiki/Google_Cast) or [Miracast](https://en.wikipedia.org/wiki/Miracast). Otherwise, a small [Roku](https://www.roku.com/products/players/roku-streaming-stick), [Chromecast](https://en.wikipedia.org/wiki/Chromecast), or [Fire TV Stick](https://www.amazon.com/firetv/streaming-media-players) can be a possible option as a hardware receiver to connect to the screen's HDMI port. They do not have to be the latest model, and can be found inexpensively on Amazon and eBay. Second-hand works perfectly fine.

#### Cast Wirelessly from Google Chrome
1. Open Chrome: Launch the browser on your computer.
1. Open the Menu: Click the three vertical dots (More) in the top-right corner.
1. Select Cast: Go to Save and share > Cast....
1. Choose Source: Click the "Sources" dropdown to select if you want to cast a specific Tab, your entire Screen, or a File.
1. Select Device: Pick your Chromecast or smart TV from the list of available devices.

Some TVs have a built-in web browser, but this is much less common and unlikely to be found at an event venue.

Typically, you can only wirelessly cast to one TV at a time from a single computer. However, the free cross-platform software PigeonCast can handle multiple displays from a single machine. This is highly recommended and will allow you to manage multiple info displays from one machine.

- [PigeonCast](https://pigeoncast.com/)

Please note that for any of these wireless options, your computer and the receiving device must be connected to the same Wi-Fi network. Keep in mind that the free public Wi-Fi provided at most venues can be oversaturated by attendees and perform slowly or become unreliable. Thankfully, the info screens are not show-stoppers, and dropped frames or lag will not be as noticeable as they would be with video playback during a keynote.

## Media Export

If screen casting a live webpage is not an option for your situation, *ConBoard* still has options for you. Most smart TVs have a USB port that can display pictures and videos from a storage device and play them like a slideshow. The gear menu in the corner of every display screen can save the screen as a 1920x1080 JPG image (1080x1920 in portrait) that can be copied to a flash drive. If your Display Board has animated content or is cycling through a content loop, each "slide" is saved as a separate image, numbered in sequence. Every Display Screen box in the admin also has an "Export JPG" button.

You can also export a whole board as a ZIP file, or export every board at once from the Display Boards list, either with a folder for each board or with everything in one flat folder for signage players that need it.

The caveat with the export method is that your content will be static. The info displays cannot be updated live if there are updates made to the online schedule, the clock will not be included, and live announcements will not be present. Any changes will require you to re-export and manually update the files on your flash drive. Images hosted on other websites cannot be included in exports. Built-in video export is not yet available for moving content, but can be captured manually through screen recording if desired.

## To Do

Everything in this README works today except the items below, which are described here but not built yet.

#### Display Boards
- **Multi-panel boards:** layouts that show several things at once, such as announcements, Now / Next, and sponsor logos on one screen, and a ticker along the edge.
- **Interruptions and scheduled slides:** priority messages that cut in, and slides that only run at certain times.
- **Alternate slide looks:** other presentation styles per slide type (for example, a timeline view of a room schedule), and weighted or random sponsor rotation.
- **Live preview** in the board editor.
- Pin the current session to the top of a schedule slide, group schedule slides by track, and place overlapping sessions side by side in the grid.

#### Scheduling
- **Conflicts:** a visual report page, suggestions for alternate rooms, conflict warnings on Display Boards, and an option to prevent conflicts when saving.

#### Data and print
- **CSV import and export** of sessions, presenters, and the rest of the event data.
- **Printed materials:** automatically generated conference badges and programs.
- **Registration and ticketing** integration for attendees.

#### Export
- **Video export** of moving content (screen recording works for now).
- **Batch export of Screen Mode pages** from the admin, alongside the existing batch export of Display Boards.

#### Other ideas
- Split first and last names for Presenters and Staff (decision pending).
- Read layers from PSD maps the way SVG layers are read.
