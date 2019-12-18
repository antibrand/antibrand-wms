<?php
/**
 * About This Version administration panel.
 *
 * @package App_Package
 * @subpackage Administration
 */

/** Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

wp_enqueue_script( 'underscore' );

$title   = __( 'About' );
$version = get_bloginfo( 'version' );

include( ABSPATH . 'wp-admin/admin-header.php' );
?>
<div class="wrap not-using__about-wrap not-using__full-width-layout">

	<h1>
	<?php echo sprintf(
		'%1s %2s %3s',
		__( 'About ' ),
		APP_NAME,
		$version
	); ?>
	</h1>
	<p class="description not-using__about-text"><?php printf( __( 'Tell folks about your website management system.' ) ); ?></p>

	<h2 class="nav-tab-wrapper wp-clearfix">
		<a href="about.php" class="nav-tab nav-tab-active"><?php _e( 'Features' ); ?></a>
		<a href="privacy-notice.php" class="nav-tab"><?php _e( 'Privacy' ); ?></a>
	</h2>

	<h3>
	<?php echo sprintf(
		'%1s %2s %3s',
		__( 'The Features of' ),
		APP_NAME,
		$version
	); ?>
	</h3>
	<p><?php _e( 'Add your content here.' ); ?></p>

</div>
<?php

include( ABSPATH . 'wp-admin/admin-footer.php' );