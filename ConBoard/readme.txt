/*
==========
TO-DO List
==========

Presenter → sessions cross-links

CSS and layout

More archives

Hidden display settings popup menu, GUI for URL

Stream to TVs over Roku, Chromecast, etc?

Featured speakers section

Filter by session type

Day tabs using Conference Day taxonomy

Display-mode reuse (archives double as signage sources)

Display Board export to images


========
Overview
========

ConBoard is a WordPress plugin for managing content for a conference event website. It adds custom post types for things like Presenters, Sessions, and Rooms. All of these posts are automatically aware of each other and fill out the event details on your website, cross connected with links.

Attendees can browse the schedule, learn more about each session, read more about the speakers, see what other sessions that presenter is hosting, or what other sessions will be held in the same room.

ConBoard is time aware, offering special features during the conference itself. It can automatically highlight what sessions are currently in progress, and which ones are happening soon.

=====
Setup
=====

A good way to start is by creating a Session. Give it a title, summary, set a time, and choose a room. All fields are optional, but the more you add, the more useful ConBoard becomes. Custom fields that are connected to other post types can be created as stubs from within the item you are currently creating, or added separately and linked later.

Add a Presenter. Include their bio, headshot, and contact info. Now you can attach them to the session you just created. On the Session page, they will be listed as the presenter, with a link to their Presenter page. On the Presenter page, ConBoard will automatically list all Sessions they are a part of.

You can follow this process a few more times, in any order you wish. It's very common to add a Presenter first, before you know their session topic or have a schedule worked out yet. Speakers can appear on your website to attract attendees without having all the logistical details yet.

===========
Screen Mode
===========

Every page can become an info screen. This plugin is not just a website, but also a digital signage system! Any web connected device can be used as signage to show attendees what's happening now, what's coming up next, or an overview of main attractions. For example, display the Presenter screen when introducing a speaker, or display the Sponsors carousel on a loop between sessions.

Quick and easy digital signage already exists by default for all rooms, speakers, sessions, and more! Reasonable defaults let you present useful information to attendees immediately simply by navigating to a URL from any  connected TV, projector, or other web-accessible display. No admin access necessary.

Simply pull up any ConBoard powered page and add "?display=screen" after the web address. This will change the display template to be info board-friendly, with simplified details and a layout designed for a 16x9 screen.

https://example.com/room/main-hall/?display=screen

From the admin dashboard, you can open any ConBoard custom post and press the "Get Display Screen" button to retrieve the display link.

Any of the display parameters that show up in the URL can be changed to aid in orienting the information for the screen type. For example, if a screen is mounted in a vertical orientation.

?display=screen&orientation=portrait&rotate=90&flip=none

==============
Display Boards
==============

You can also create constom Display Board content to have even more control over what is shown. A Display Board is a named, reusable configuration for a digital screen. It defines what content the screen shows, how the content behaves, and the default physical display settings.

Display Board content is more customizable and can be configured much like a slide show, combining various information types. They are independent of Rooms, Sessions, etc. and can be shown on any number of displays simultaneously with your live data automatically inserted. A Display Board is basically a custom web page that you can tailor with whatever conference information you want to display using the simple built-in editor tools.

Content types that can be shown on a Display Board include:

	Room schedule
	Now / Next
	Announcements
	Sponsor loop
	Welcome slide
	Full schedule grid
	Carousel vs static content

================
Config Overrides
================

As a convenience for AV teams, Display Board properties may be temporarily overridden via URL parameters without requiring a backend login. For example, if you show up to the venue and discover one of the screens is rotated the opposite direction from the others. No need to have admin access and create a new Display Board definition just for that one screen when the content should stay the same. Just change the rotation flag in the URL. URL overrides take precedence over Display Board settings for that one screen only and do not persist.

==============
Screen Casting
==============

The easiest way to present ConBoard info screens on a TV or projector is directly from a laptop or desktop computer over an HDMI cable. It is recommended to use "Extend" mode and treat that screen as a second monitor rather than "Mirror" mode. Then simply navigate to the desired URL and enter the browser's fullscreen mode. Alternatively, there are inexpensive wireless video transmitters. The number of screens you can stream to depends on your computer hardware and how many video outputs are available.

	$20
	https://www.walmart.com/ip/Google-TV-Full-HD-Streaming-Device-NEW-2023/2262757145

	$20	Roku Streaming Stick HD
	https://www.amazon.com/dp/B0DXXYS4BJ/

	$30	Roku Streaming Stick Plus
	https://www.amazon.com/dp/B0DXY833HV/

	$30	EZCast PRO Dongle Wireless HD
	https://www.amazon.com/dp/B01N551MI6/r

	$35	Wireless HDMI Transmitter and Receiver
	https://www.amazon.com/dp/B0FR4K4VQJ/
	
	$35	WEKEFON Wireless HDMI Transmitter and Receiver
	https://www.amazon.com/dp/B0FR4G43YR/

	$35	Wireless HDMI Display Adapter
	https://www.walmart.com/ip/Wireless-HDMI-Transmitter-Receiver-2-in-1-Plug-Play-Transmitter-4K-Decode-1080P-Output-Video-Streaming-Laptop-PC-Tablet-Smartphone-TV-Monitor-Project/17345620407

	$35	TiVo Stream 4K
	https://www.amazon.com/dp/B089655HQV/

	$42	EZCast2 4K Wireless Display Receiver
	https://www.amazon.com/dp/B07YKGGTJL/

	$42	MT-VIKI 4K Wireless HDMI Display Dongle
	https://www.amazon.com/dp/B0DY7RDMVY/

	$50	Amazon Fire TV Stick 4K Plus
	https://www.amazon.com/dp/B0F7Z4QZTT/

	$70	XIAOMI TV Stick 4K Streaming Device
	https://www.amazon.com/dp/B0FFN6NRTZ

For Google Chrome (PC/Mac)
Open Chrome: Launch the browser on your computer. 
Open the Menu: Click the three vertical dots (More) in the top-right corner. 
Select Cast: Go to Save and share > Cast.... 
Choose Source: Click the "Sources" dropdown to select if you want to cast a specific Tab, your entire Screen, or a File. 
Select Device: Pick your Chromecast or smart TV from the list of available devices. 


Some venues may have a central routing hub to send video signals to various screens. Others may need a device to be directly attached at the back of the TV. In cases where it is not practical to have a laptop connected to each screen, a dedicated playback device may be more reasonable. You can also use the web browser feature in any existing digital signage playback units you may have (BrightSigns, OptiSigns, Yodeck, Screencloud Pixi). A Raspberry Pi is a good option as well!

	https://signage.amazon.com/

	https://screencloud.com/pixi

Many smart TVs allow streaming content to them wirelessly as a built in-feature. Check the TV's menus to see if it accepts AirPlay (Apple), GoogleCast (Chromecast) or Miracast (Windows). Otherwise, a small Roku, Chromecast, or Fire TV Stick can be a possible option as hardware receivers to connect to the screen's HDMI port. You could even use an old Xbox or PlayStation. Some TVs have a built-in web browser, but this is much less common and unlikely to find at an event venue.

Typically, you can only cast to one TV at a time from a single computer. However, the free cross-platform software Pigeoncast can handle multiple displays from a single machine.

	https://pigeoncast.com/

Please note that for any of these wireless options, your computer and the TV (or Chromecast device) must be connected to the same Wi-Fi network. Keep in mind that the free public wifi provided at most venues can potentially oversaturated by attendees and perform slowly or become unreliable. Thanfully, the info screens are not show-stopping misison critical, and dropped frames or lag will not be largely noticeable the way it would be with video.

============
Media Export
============

If screencasting a live webpage is not an option for your situation, ConBoard still has available options for you. Most smart TVs will have a USB port that can display pictures and videos like a slideshow. The hidden utility menu in the corner of the display screen has a download option. This will save the sceen as a 1920x1080 JPG image that can be copied to a flash drive. If your Display Board has animated content or is cycling through a content loop, each "slide" will be saved as separate image, numbered in sequence. Built in video export is not yet available, but can be captured manually through screen recording if desired.

The caveat with this method is that the info displays cannot be updated live if there are updates made to the online schedule, the clock option will not be included, and live announcements will not be present. Any changes will require you to manually update the files on the flash drive.

*/