<?php
// includes/admin/admin_pages.php

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CONBOARD_ADMIN_PAGES', [
	'conboard_dashboard'	=> [
		'title'				=> 'Dashboard',
		'icon_code'			=> 'f180', // dashicons-screenoptions
		'url'				=> 'admin.php?page=conboard_dashboard',
		'description'		=> 'You are here (hide this card later)',
	],
	'conboard_planner'		=> [
		'title'				=> 'Program Planner',
		'icon_code'			=> 'f489', // dashicons-schedule
		'url'				=> 'admin.php?page=conboard_planner',
		'description'		=> 'Drag sessions onto a day grid to build the program',
	],
	'conboard_screens'		=> [
		'title'				=> 'Screen Assignments',
		'icon_code'			=> 'f325', // dashicons-networking
		'url'				=> 'admin.php?page=conboard_screens',
		'description'		=> 'Choose which Display Board each physical screen shows',
	],
	'conboard_settings'		=> [
		'title'				=> 'Settings',
		'icon_code'			=> 'f108', // dashicons-admin-settings
		'url'				=> 'admin.php?page=conboard_settings',
		'description'		=> 'Global ConBoard options and controls',
	],
	'conboard_about'		=> [
		'title'				=> 'Documentation',
		'icon_code'			=> 'f348', // dashicons-info
		'url'				=> 'admin.php?page=conboard_about',
		'description'		=> 'Info and documentation on how to use ConBoard',
	],
] );


