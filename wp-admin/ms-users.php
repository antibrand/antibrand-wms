<?php
/**
 * Multisite users administration panel.
 *
 * @package WMS
 * @subpackage Multisite
 * @since 3.0.0
 */

require_once( dirname( __FILE__ ) . '/admin.php' );

wp_redirect( network_admin_url('users.php') );
exit;
