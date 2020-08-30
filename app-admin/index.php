<?php
/**
 * Dashboard administration screen
 *
 * @package App_Package
 * @subpackage Administration
 */

// Alias namespaces.
use \AppNamespace\Backend as Backend;

// Get the system environment constants from the root directory.
require_once( dirname( dirname( __FILE__ ) ) . '/app-environment.php' );

// Load the administration environment.
require_once( APP_INC_PATH . '/backend/app-admin.php' );

// Instance of the dashboard class.
$dashboard = Backend\Dashboard :: instance();

// Page identification.
$parent_file = $dashboard->parent;
$title       = $dashboard->title();
$description = $dashboard->description();

// Get the admin page header.
include_once( APP_VIEWS_PATH . '/backend/header/admin-header.php' );

// Begin page content.
?>
	<div class="wrap">

		<h1><?php echo esc_html( $title ); ?></h1>
		<?php echo $description; ?>

		<div id="dashboard-tabs">
			<?php

			// Tabbed content.
			echo get_current_screen()->render_content_tabs();

			?>
		</div>

	<?php

		if ( has_action( 'dashboard_add_content' ) || has_action( 'welcome_panel' ) ) :

			// Get the user preference.
			$option  = get_user_meta( get_current_user_id(), 'show_top_panel', true );

			// Top panel base class.
			$classes = 'dashboard-add-content';

			/**
			 * Hidden class
			 *
			 * Add `.hidden` class if the user wants to hide the top panel.
			 * 0 = hide, 1 = toggled to show or single site creator, 2 = network site owner.
			 */
			$hide = '0' === $option || ( '2' === $option && wp_get_current_user()->user_email != get_option( 'admin_email' ) );

			if ( $hide ) {
				$classes .= ' hidden';
			}

			?>
			<div id="top-panel" class="<?php echo esc_attr( $classes ); ?>">
				<?php

				// User display preference nonce for the top panel.
				wp_nonce_field( 'top-panel-nonce', 'toppanelnonce', false );
				/**
				 * Additional dashboard content
				 *
				 * Plugins & themes may use this to add content below
				 * the included top panel.
				 *
				 * @since 1.0.0
				 */
				// do_action( 'dashboard_add_content' );

				/**
				 * Deprecated hook
				 *
				 * @since      WP 3.5.0
				 * @deprecated 1.0.0 Not used by this website management system.
				 */
				do_action( 'welcome_panel' );
				?>
			</div>
		<?php endif; ?>
	</div><!-- .wrap -->
<?php

// Get the admin page footer.
include_once( APP_VIEWS_PATH . '/backend/footer/admin-footer.php' );
