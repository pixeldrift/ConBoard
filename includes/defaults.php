<?php
// includes/defaults.php

function conboard_create_default_display_boards() {
	// default board creation logic
}

register_activation_hook(
	CONBOARD_PLUGIN_FILE,
	'conboard_create_default_display_boards'
);
