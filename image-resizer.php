<?php
/**
 * Plugin name: Image Resizer
 * Author: Radoslav Georgiev
 * License: GPL v2
 * Description: Generates image sizes only when needed, instead of the 404 page.
 */

define( 'DYNAMIC_RESIZER_DIR', __DIR__ . '/' );

include_once( __DIR__ . '/classes/Dynamic_Resizer.php' );
Dynamic_Resizer\Dynamic_Resizer::init();