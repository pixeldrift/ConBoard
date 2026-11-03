TO DO

x	Logo icons on sponsors Admin
x	Portrait images on Speaker/Staff admin list
x	Add Title/Role for presenter/staff pages
x	Add session counts and make sortable
x	Make custom fields in post lists sortable
x	Fix menu ordering
x	Icon system only supports CPTs
x	Be able to manually add overview cards or menu items with icons
x	Fix Presenter Metaboxes
x	Consolidate duplicate CPT lists across files
x	Wrapper class for Dashboard and About
x	Dashboard menu item appears when on a post type page
x	Put thumbnails after checkbox
x	Icon before Page Title on admin pages
x	Dashboard card buttons not clicking (wrapped in anchor tag)
x	Be able to exclude columns from sorting, like thumnails. sortable' => false,
x	Change "rooms" to locations
x	Add SVG map capabilities for Locations
x	If assigned, show map from parent post, with the ability to change it.
x	Shop map preview in post editor
x	Make SVG layers a checkbox list to assign layers to locations
x	Add Location column to admin Sessions list
x	Add Location type, Room type, and Room setup to admin Locations list
x	Make Location, Location Type, Room Type, and Room setup sortable
x	Update sortable columns whitelist
x	Make helper function for adding columns, clean up and refactor admin_columns.php
x	Add Day and Time columns to Sessions list (should this be derived and stored, or on the fly?)
x	Dashboard card buttons don't work (nested in A tag), use onclick javascript instead
x	Page/CPT icons before page name

	Show live map preview from checkboxes using javascript

	Title for ADD NEW pages so we know what kind of post type we are adding

	Parse PSD layers the same as SVG?
	Image based maps offer a checkbox to overlay parent, usefull for PNG highlights

	Fix column widths on CPT lists

	Add Room Setup to Sessions that overrides default setup in Location

	Handle when the SVG has changed (adding new rooms, for example) What happens to the data? Does it break? Does it default to unset if that layer no longer exists?

	If map deleted, revert to parent map

	Add "show map" toggle to CPT that we can later use in location templates?

	On main settings page, allow user to specify event dates so we can use that to convert datestamps to more human versions. Examples: (Day 1 - Sunday - Jan 24)(Day 1)(Day 1 - Sunday)
	Should Event Days be a CPT? I don't want to, but maybe?

	Let user define sponsor levels. Does that need to be a separate CPT?

	// Repeaters aren't available in the free version of ACF!
	Add Presenter repeater to Session metabox
	Add Presenter column to Sessions list
	Fix Socials repeater in metaboxes (done: native row editor)
	Fix weblinks repeater in metaboxes (done: native row editor)

	Sidebar VS metabox?

	Markdown: bullets for unordered lists
	Split first and last name for Presenters and Staff?

	Global settings
		Conference Days
		Default Location type
		Default Room type
		Default Room setup


	Prevent room conflicts at save 
	OR Visual conflict report
	Conflict summary column in Sessions list
	Auto-suggest alternate rooms
	Display Board conflict awareness
	Add settings popup box for display screens
	        // f108 controls
	        // f167 rotate right
	        // f166 rotate left
	        // f169 flip horizontal
	        // f168 flip vertical