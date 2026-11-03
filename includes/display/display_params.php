<?php
// includes/display/display_params.php

function conboard_display_param_schema() {
    return [
        'display'     => ['default' => 'screen'],
        'orientation' => ['default' => 'horizontal'],
        'rotate'      => ['default' => 'none'], // none | left | right
        'flip'        => ['default' => 'none'], // none | horizontal | vertical
    ];
}
