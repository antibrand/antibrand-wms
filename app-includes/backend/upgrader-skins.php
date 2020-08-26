<?php
/**
 * Upgrade API: WP_Upgrader class
 *
 * Requires skin classes and WP_Upgrader subclasses for backward compatibility.
 *
 * @package App_Package
 * @subpackage Upgrader
 * @since Previous 2.8.0
 */

/** WP_Ajax_Upgrader_Skin class */
require_once( ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php' );

/** WP_Automatic_Updater class */
require_once( ABSPATH . 'wp-admin/includes/class-wp-automatic-updater.php' );
