<?php
/*
Plugin Name: Admin Page Library (APL)
Plugin URI: 
Description: 
Version: 1.0
Author: Crystal Barton
Author URI: http://www.crystalbarton.com
*/


define( 'APL', 'Admin Page Library' );
define( 'APL_VERSION', '1.0' );
define( 'APL_PATH', dirname(__FILE__).'/apl' );

require_once( APL_PATH.'/functions.php' );
require_once( APL_PATH.'/admin-menu.php' );
require_once( APL_PATH.'/admin-page.php' );
require_once( APL_PATH.'/tab-admin-page.php' );
require_once( APL_PATH.'/tab-link.php' );
require_once( APL_PATH.'/handler.php' );

