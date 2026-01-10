<?php
// includes/admin/admin_about.php

if ( ! defined('ABSPATH') ) exit;

function conboard_admin_about() {
	
	echo '<div class="wrap"><center><img src="' . esc_url(CONBOARD_PLUGIN_URL . 'assets/images/icon-128x128.png') . '" style="swidth:128px;height:128px;margin-bottom:16px;" />';
	echo '<h1 class="header">ConBoard Settings</h1>';
	echo '<h2 class="header">"All your conference details in one place"</h2></center>';

	// Load the README and convert the markup to HTML
	$readme_path = plugin_dir_path(__FILE__) . '../../README.md';
	if (file_exists($readme_path)) {
	    $content = file_get_contents($readme_path);
	    echo '<div class="conboard-readme">' . conboard_markdown_to_html($content) . '</div>';
	} else {
	    echo '<p>README.md not found.</p>';
	}

	echo '</div>';
	add_action('admin_head', function () {
	    echo '<style>

	    h1.header {
	        font-size: 40px;
	        font-weight: bold;
	        margin: 0;
	    }
	    h2.header {
	        font-size: 20px;
	        margin: 0 0 30px 0;
	    }
		<style>';
	});
}