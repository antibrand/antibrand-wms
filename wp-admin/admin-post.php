<?php
/**
 * Generic Request (POST/GET) Handler
 *
 * Intended for form submission handling in themes and plugins.
 *
 * @package App_Package
 * @subpackage Administration
 */

/**
 * In administration screens
 *
 * @since Previous 2.3.2
 */
if ( ! defined( 'APP_ADMIN' ) ) {
	define( 'APP_ADMIN', true );
}

if ( defined( 'ABSPATH' ) ) {
	require_once( ABSPATH . 'app-load.php' );
} else {
	require_once( dirname( dirname( __FILE__ ) ) . '/app-load.php' );
}

// Allow for cross-domain requests (from the front end).
send_origin_headers();

require_once( APP_INC_PATH . '/backend/admin.php');

nocache_headers();

// This action is documented in APP_ADMIN_DIR/admin.php.
do_action( 'admin_init' );

$action = empty( $_REQUEST['action'] ) ? '' : $_REQUEST['action'];

if ( ! wp_validate_auth_cookie() ) {

	if ( empty( $action ) ) {

		/**
		 * Fires on a non-authenticated admin post request where no action was supplied.
		 *
		 * @since 2.6.0
		 */
		do_action( 'admin_post_nopriv' );

	} else {

		/**
		 * Fires on a non-authenticated admin post request for the given action.
		 *
		 * The dynamic portion of the hook name, `$action`, refers to the given
		 * request action.
		 *
		 * @since 2.6.0
		 */
		do_action( "admin_post_nopriv_{$action}" );
	}

} else {

	if ( empty( $action ) ) {

		/**
		 * Fires on an authenticated admin post request where no action was supplied.
		 *
		 * @since 2.6.0
		 */
		do_action( 'admin_post' );

	} else {

		/**
		 * Fires on an authenticated admin post request for the given action.
		 *
		 * The dynamic portion of the hook name, `$action`, refers to the given
		 * request action.
		 *
		 * @since 2.6.0
		 */
		do_action( "admin_post_{$action}" );
	}
}
