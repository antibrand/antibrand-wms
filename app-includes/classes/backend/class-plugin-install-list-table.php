<?php
/**
 * List Table API: Plugin_Install_List_Table class
 *
 * @package App_Package
 * @subpackage Administration
 * @since Previous 3.1.0
 */

namespace AppNamespace\Backend;

/**
 * Core class used to implement displaying plugins to install in a list table.
 *
 * @since Previous 3.1.0
 * @access private
 *
 * @see List_Table
 */
class Plugin_Install_List_Table extends List_Table {

	public $order   = 'ASC';
	public $orderby = null;
	public $groups  = [];

	private $error;

	/**
	 * AJAX user permission
	 *
	 * @access public
	 * @return bool Return tru if the current user can install plugins.
	 */
	public function ajax_user_can() {
		return current_user_can( 'install_plugins' );
	}

	/**
	 * List of known plugins
	 *
	 * Uses the transient data from the updates API to determine the known
	 * installed plugins.
	 *
	 * @since  WP 4.9.0
	 * @access protected
	 * @return array Returns the list of known plugins.
	 */
	protected function get_installed_plugins() {

		$plugins = [];

		$plugin_info = get_site_transient( 'update_plugins' );

		if ( isset( $plugin_info->no_update ) ) {

			foreach ( $plugin_info->no_update as $plugin ) {
				$plugin->upgrade = false;
				$plugins[ $plugin->slug ] = $plugin;
			}
		}

		if ( isset( $plugin_info->response ) ) {

			foreach ( $plugin_info->response as $plugin ) {
				$plugin->upgrade = true;
				$plugins[ $plugin->slug ] = $plugin;
			}
		}

		return $plugins;
	}

	/**
	 * Return a list of installed plugin slugs
	 *
	 * Uses the transient data from the updates API to determine the slugs of
	 * known installed plugins. This might be better elsewhere, perhaps even
	 * within get_plugins().
	 *
	 * @since  WP 4.0.0
	 * @access protected
	 * @return array Returns a list of slugs of installed plugins.
	 */
	protected function get_installed_plugin_slugs() {
		return array_keys( $this->get_installed_plugins() );
	}

	/**
	 * Prepare list items
	 *
	 * @access public
	 * @global array  $tabs
	 * @global string $tab
	 * @global int    $paged
	 * @global string $type
	 * @global string $term
	 */
	public function prepare_items() {

		include( ABSPATH . 'wp-admin/includes/plugin-install.php' );

		// Access global variables.
		global $tabs, $tab, $paged, $type, $term;

		wp_reset_vars( [ 'tab' ] );

		$paged    = $this->get_pagenum();
		$per_page = 30;

		// These are the tabs which are shown on the page.
		$tabs = [];

		if ( 'search' === $tab ) {
			$tabs['search'] = __( 'Search Results' );
		}

		if ( $tab === 'beta' || false !== strpos( get_app_info( 'version' ), '-' ) ) {
			// $tabs['beta'] = _x( 'Beta Testing', 'Plugin Installer' );
		}

		// Disabled featured plugins tab. Uncomment to enable.
		// $tabs['featured']    = _x( 'Featured', 'Plugin Installer' );

		$tabs['popular']     = _x( 'Popular', 'Plugin Installer' );

		// Disabled recommended plugins tab. Uncomment to enable.
		// $tabs['recommended'] = _x( 'Recommended', 'Plugin Installer' );
		$tabs['favorites']   = _x( 'Favorites', 'Plugin Installer' );

		if ( current_user_can( 'upload_plugins' ) ) {
			// No longer a real tab. Here for filter compatibility.
			// Gets skipped in get_views().
			$tabs['upload'] = __( 'Upload Plugin' );
		}

		// Valid actions to perform which do not have a Menu item.
		$nonmenu_tabs = array( 'plugin-information' );

		/**
		 * Filters the tabs shown on the Plugin Install screen.
		 *
		 * @since Previous 2.7.0
		 * @param array $tabs The tabs shown on the Plugin Install screen. Defaults include 'featured', 'popular',
		 *                    'recommended', 'favorites', and 'upload'.
		 */
		$tabs = apply_filters( 'install_plugins_tabs', $tabs );

		/**
		 * Filters tabs not associated with a menu item on the Plugin Install screen.
		 *
		 * @since Previous 2.7.0
		 * @param array $nonmenu_tabs The tabs that don't have a Menu item on the Plugin Install screen.
		 */
		$nonmenu_tabs = apply_filters( 'install_plugins_nonmenu_tabs', $nonmenu_tabs );

		// If a non-valid menu tab has been selected, And it's not a non-menu action.
		if ( empty( $tab ) || ( ! isset( $tabs[ $tab ] ) && ! in_array( $tab, (array) $nonmenu_tabs ) ) ) {
			$tab = key( $tabs );
		}

		$installed_plugins = $this->get_installed_plugins();

		$args = [
			'page'     => $paged,
			'per_page' => $per_page,
			'fields'   => [
				'last_updated'    => true,
				'icons'           => true,
				'active_installs' => true
			],

			// Send the locale and installed plugin slugs to the API so it can provide context-sensitive results.
			'locale'            => get_user_locale(),
			'installed_plugins' => array_keys( $installed_plugins ),
		];

		switch ( $tab ) {

			case 'search':

				$type = isset( $_REQUEST['type'] ) ? wp_unslash( $_REQUEST['type'] ) : 'term';
				$term = isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '';

				switch ( $type ) {
					case 'tag':
						$args['tag'] = sanitize_title_with_dashes( $term );
						break;
					case 'term':
						$args['search'] = $term;
						break;
					case 'author':
						$args['author'] = $term;
						break;
				}

				break;

			case 'featured':

				$args['fields']['group'] = true;
				$this->orderby = 'group';

				// No break!

			case 'popular':
			case 'new':
			case 'beta':
			case 'recommended':

				$args['browse'] = $tab;

				break;

			case 'favorites':

				$action = 'save_wporg_username_' . get_current_user_id();

				if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), $action ) ) {

					$user = isset( $_GET['user'] ) ? wp_unslash( $_GET['user'] ) : get_user_option( 'wporg_favorites' );

					update_user_meta( get_current_user_id(), 'wporg_favorites', $user );
				} else {
					$user = get_user_option( 'wporg_favorites' );
				}

				if ( $user ) {
					$args['user'] = $user;
				} else {
					$args = false;
				}

				add_action( 'install_plugins_favorites', 'install_plugins_favorites_form', 9, 0 );

				break;

			default:
				$args = false;

				break;
		}

		/**
		 * Filters API request arguments for each Plugin Install screen tab.
		 *
		 * The dynamic portion of the hook name, `$tab`, refers to the plugin install tabs.
		 * Default tabs include 'featured', 'popular', 'recommended', 'favorites', and 'upload'.
		 *
		 * @since Previous 3.7.0
		 * @param array|bool $args Plugin Install API arguments.
		 */
		$args = apply_filters( "install_plugins_table_api_args_{$tab}", $args );

		if ( ! $args ) {
			return;
		}

		$api = plugins_api( 'query_plugins', $args );

		if ( is_wp_error( $api ) ) {
			$this->error = $api;
			return;
		}

		$this->items = $api->plugins;

		if ( $this->orderby ) {
			uasort( $this->items, [ $this, 'order_callback' ] );
		}

		$this->set_pagination_args( [
			'total_items' => $api->info['results'],
			'per_page'    => $args['per_page'],
		] );

		if ( isset( $api->info['groups'] ) ) {
			$this->groups = $api->info['groups'];
		}

		if ( $installed_plugins ) {

			$js_plugins = array_fill_keys(
				[
					'all',
					'search',
					'active',
					'inactive',
					'recently_activated',
					'extension',
					'dropins'
				],
				[]
			);

			$js_plugins['all'] = array_values( wp_list_pluck( $installed_plugins, 'plugin' ) );
			$upgrade_plugins   = wp_filter_object_list( $installed_plugins, [ 'upgrade' => true ], 'and', 'plugin' );

			if ( $upgrade_plugins ) {
				$js_plugins['upgrade'] = array_values( $upgrade_plugins );
			}

			wp_localize_script( 'updates', '_wpUpdatesItemCounts', [
				'plugins' => $js_plugins,
				'totals'  => wp_get_update_data(),
			] );
		}
	}

	/**
	 * No plugins notice
	 *
	 * @return mixed Returns the markup of the "no plugins" notice.
	 */
	public function no_items() {

		if ( isset( $this->error ) ) { ?>
			<div class="inline error">
				<h2><?php _e( 'No plugins found' ); ?></h2>
				<p><?php echo $this->error->get_error_message(); ?></p>
			</div>
		<?php } else { ?>
			<div class="no-plugin-results">
				<h2><?php _e( 'No plugins found' ); ?></h2>
				<p><?php _e( 'Try a different search.' ); ?></p>
			</div>
		<?php
		}
	}

	/**
	 * Get filtered plugins list
	 *
	 * @access protected
	 * @global array $tabs
	 * @global string $tab
	 * @return array Returns an array of plugins for the filtered type.
	 */
	protected function get_views() {

		global $tabs, $tab;

		$display_tabs = [];

		foreach ( (array) $tabs as $action => $text ) {

			$current_link_attributes = ( $action === $tab ) ? ' class="current" aria-current="page"' : '';
			$href = self_admin_url('plugin-install.php?tab=' . $action);
			$display_tabs['plugin-install-'.$action] = "<a href='$href'$current_link_attributes>$text</a>";
		}

		// No longer a real tab.
		unset( $display_tabs['plugin-install-upload'] );

		return $display_tabs;
	}

	/**
	 * Override parent views
	 *
	 * @access public
	 */
	public function views() {

		$views = $this->get_views();

		// This filter is documented in wp-admin/includes/class-wp-list-table.php
		$views = apply_filters( "views_{$this->screen->id}", $views );

		$this->screen->render_screen_reader_content( 'heading_views' );

		// Print the add new link only if the current user can upload plugins.
		if ( 'plugins' == $this->screen->id && current_user_can( 'upload_plugins' ) ) {

			$add_new = sprintf(
				'<li class="list-table-add-new"><a href="%1s" class="page-title-action">%2s</a></li>',
				esc_url( admin_url( $post_new_file ) ),
				esc_html( $post_type_object->labels->add_new )
			);
		}

?>
<div class="wp-filter">

	<ul class="filter-links">
		<?php
		if ( ! empty( $views ) ) {
			foreach ( $views as $class => $view ) {
				$views[ $class ] = "\t<li class='$class'>$view";
			}
			echo implode( " </li>\n", $views ) . "</li>\n";
		}
		?>
	</ul>

	<?php install_search_form(); ?>

</div>
<?php

	}

	/**
	 * Override the parent display
	 *
	 * So we can provide a different container.
	 *
	 * @access public
	 * @return mixed
	 */
	public function display() {

		$singular = $this->_args['singular'];

		$data_attr = '';

		if ( $singular ) {
			$data_attr = " data-wp-lists='list:$singular'";
		}

		$this->display_tablenav( 'top' );

?>

	<div class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">

		<?php $this->screen->render_screen_reader_content( 'heading_list' ); ?>

		<div id="the-list"<?php echo $data_attr; ?>>
			<?php $this->display_rows_or_placeholder(); ?>
		</div>

	</div>

<?php $this->display_tablenav( 'bottom' );

	}

	/**
	 * Display table navigation
	 *
	 * @access protected
	 * @global string $tab
	 * @param  string $which
	 */
	protected function display_tablenav( $which ) {

		if ( $GLOBALS['tab'] === 'featured' ) {
			return;
		}

		if ( 'top' === $which ) {
			wp_referer_field();
		?>
			<div class="tablenav top">
				<div class="alignleft actions">
					<?php
					/**
					 * Fires before the Plugin Install table header pagination is displayed.
					 *
					 * @since Previous 2.7.0
					 */
					do_action( 'install_plugins_table_header' ); ?>
				</div>
				<?php $this->pagination( $which ); ?>
			</div>
		<?php } else { ?>
			<div class="tablenav bottom">
				<?php $this->pagination( $which ); ?>
			</div>
		<?php
		}
	}

	/**
	 * Get the list table classes
	 *
	 * @access protected
	 * @return array
	 */
	protected function get_table_classes() {
		return [ 'widefat', $this->_args['plural'] ];
	}

	/**
	 * Get the list table columns
	 *
	 * @access public
	 * @return array
	 */
	public function get_columns() {
		return [];
	}

	/**
	 * Order the plugins
	 *
	 * @access private
	 * @param  object $plugin_a
	 * @param  object $plugin_b
	 * @return int
	 */
	private function order_callback( $plugin_a, $plugin_b ) {

		$orderby = $this->orderby;

		if ( ! isset( $plugin_a->$orderby, $plugin_b->$orderby ) ) {
			return 0;
		}

		$a = $plugin_a->$orderby;
		$b = $plugin_b->$orderby;

		if ( $a == $b ) {
			return 0;
		}

		if ( 'DESC' === $this->order ) {
			return ( $a < $b ) ? 1 : -1;
		} else {
			return ( $a < $b ) ? -1 : 1;
		}
	}

	public function display_rows() {

		$plugins_allowedtags = [
			'a' => [
				'href'   => [],
				'title'  => [],
				'target' => []
			],
			'abbr' => [
				'title' => []
			],
			'acronym' => [
				'title' => []
			],
			'code'   => [],
			'pre'    => [],
			'em'     => [],
			'strong' => [],
			'ul'     => [],
			'ol'     => [],
			'li'     => [],
			'p'      => [],
			'br'     => []
		];

		$plugins_group_titles = [
			'Performance' => _x( 'Performance', 'Plugin installer group title' ),
			'Social'      => _x( 'Social',      'Plugin installer group title' ),
			'Tools'       => _x( 'Tools',       'Plugin installer group title' ),
		];

		$group = null;

		foreach ( (array) $this->items as $plugin ) {

			if ( is_object( $plugin ) ) {
				$plugin = (array) $plugin;
			}

			// Display the group heading if there is one.
			if ( isset( $plugin['group'] ) && $plugin['group'] != $group ) {

				if ( isset( $this->groups[ $plugin['group'] ] ) ) {

					$group_name = $this->groups[ $plugin['group'] ];

					if ( isset( $plugins_group_titles[ $group_name ] ) ) {
						$group_name = $plugins_group_titles[ $group_name ];
					}

				} else {
					$group_name = $plugin['group'];
				}

				// Starting a new group, close off the divs of the last one.
				if ( ! empty( $group ) ) {
					echo '</div></div>';
				}

				echo '<div class="plugin-group"><h3>' . esc_html( $group_name ) . '</h3>';

				// Needs an extra wrapping div for nth-child selectors to work.
				echo '<div class="plugin-items">';

				$group = $plugin['group'];
			}

			$title = wp_kses( $plugin['name'], $plugins_allowedtags );

			// Remove any HTML from the description.
			$description = strip_tags( $plugin['short_description'] );
			$version     = wp_kses( $plugin['version'], $plugins_allowedtags );
			$name        = strip_tags( $title . ' ' . $version );
			$author      = wp_kses( $plugin['author'], $plugins_allowedtags );

			if ( ! empty( $author ) ) {
				$author = ' <cite>' . sprintf( __( 'By %s' ), $author ) . '</cite>';
			}

			$action_links = [];

			if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {

				$status = install_plugin_install_status( $plugin );

				switch ( $status['status'] ) {

					case 'install':

						if ( $status['url'] ) {

							// Translators: 1: Plugin name and version.
							$action_links[] = '<a class="install-now button" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Install %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . __( 'Install Now' ) . '</a>';
						}

						break;

					case 'update_available':

						if ( $status['url'] ) {

							// Translators: 1: Plugin name and version.
							$action_links[] = '<a class="update-now button aria-button-if-js" data-plugin="' . esc_attr( $status['file'] ) . '" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Update %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . __( 'Update Now' ) . '</a>';
						}

						break;

					case 'latest_installed':
					case 'newer_installed':

						if ( is_plugin_active( $status['file'] ) ) {
							$action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x( 'Active', 'plugin' ) . '</button>';

						} elseif ( current_user_can( 'activate_plugin', $status['file'] ) ) {

							$button_text  = __( 'Activate' );

							// Translators: %s: Plugin name.
							$button_label = _x( 'Activate %s', 'plugin' );
							$activate_url = add_query_arg(
								[
									'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $status['file'] ),
									'action'   => 'activate',
									'plugin'   => $status['file'],
								],
								network_admin_url( 'plugins.php' )
							);

							if ( is_network_admin() ) {

								$button_text  = __( 'Network Activate' );

								// Translators: %s: Plugin name.
								$button_label = _x( 'Network Activate %s', 'plugin' );
								$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
							}

							$action_links[] = sprintf(
								'<a href="%1$s" class="button activate-now" aria-label="%2$s">%3$s</a>',
								esc_url( $activate_url ),
								esc_attr( sprintf( $button_label, $plugin['name'] ) ),
								$button_text
							);

						} else {
							$action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x( 'Installed', 'plugin' ) . '</button>';
						}

						break;
				}
			}

			$details_link = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $plugin['slug'] . '&amp;TB_iframe=true&amp;width=600&amp;height=550' );

			// Translators: 1: Plugin name and version.
			$action_links[] = '<a href="' . esc_url( $details_link ) . '" class="thickbox open-plugin-details-modal" aria-label="' . esc_attr( sprintf( __( 'More information about %s' ), $name ) ) . '" data-title="' . esc_attr( $name ) . '">' . __( 'More Details' ) . '</a>';

			if ( ! empty( $plugin['icons']['svg'] ) ) {
				$plugin_icon_url = $plugin['icons']['svg'];
			} elseif ( !empty( $plugin['icons']['2x'] ) ) {
				$plugin_icon_url = $plugin['icons']['2x'];
			} elseif ( !empty( $plugin['icons']['1x'] ) ) {
				$plugin_icon_url = $plugin['icons']['1x'];
			} else {
				$plugin_icon_url = $plugin['icons']['default'];
			}

			/**
			 * Filters the install action links for a plugin.
			 *
			 * @since Previous 2.7.0
			 * @param array $action_links An array of plugin action hyperlinks. Defaults are links to Details and Install Now.
			 * @param array $plugin       The plugin currently being listed.
			 */
			$action_links = apply_filters( 'plugin_install_action_links', $action_links, $plugin );

			$last_updated_timestamp = strtotime( $plugin['last_updated'] );

		?>
		<div class="plugin-card plugin-card-<?php echo sanitize_html_class( $plugin['slug'] ); ?>">

			<div class="plugin-card-top">

				<div class="name column-name">
					<h3>
						<a href="<?php echo esc_url( $details_link ); ?>" class="thickbox open-plugin-details-modal">
						<?php echo $title; ?>
						<img src="<?php echo esc_attr( $plugin_icon_url ) ?>" class="plugin-icon" alt="">
						</a>
					</h3>
				</div>

				<div class="action-links">
					<?php
						if ( $action_links ) {
							echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
						}
					?>
				</div>

				<div class="desc column-description">
					<p><?php echo $description; ?></p>
					<p class="authors"><?php echo $author; ?></p>
				</div>

			</div>

			<div class="plugin-card-bottom">

				<div class="vers column-rating">
					<?php wp_star_rating( [ 'rating' => $plugin['rating'], 'type' => 'percent', 'number' => $plugin['num_ratings'] ] ); ?>
					<span class="num-ratings" aria-hidden="true">(<?php echo number_format_i18n( $plugin['num_ratings'] ); ?>)</span>
				</div>

				<div class="column-updated">
					<strong><?php _e( 'Last Updated:' ); ?></strong> <?php printf( __( '%s ago' ), human_time_diff( $last_updated_timestamp ) ); ?>
				</div>

				<div class="column-downloaded">
					<?php
					if ( $plugin['active_installs'] >= 1000000 ) {
						$active_installs_text = _x( '1+ Million', 'Active plugin installations' );
					} elseif ( 0 == $plugin['active_installs'] ) {
						$active_installs_text = _x( 'Less Than 10', 'Active plugin installations' );
					} else {
						$active_installs_text = number_format_i18n( $plugin['active_installs'] ) . '+';
					}
					printf( __( '%s Active Installations' ), $active_installs_text );
					?>
				</div>

				<div class="column-compatibility">
					<?php
					$wp_version = get_bloginfo( 'version' );

					if ( ! empty( $plugin['tested'] ) && version_compare( substr( $wp_version, 0, strlen( $plugin['tested'] ) ), $plugin['tested'], '>' ) ) {
						echo '<span class="compatibility-untested">' . __( 'Untested with your version' ) . '</span>';
					} elseif ( ! empty( $plugin['requires'] ) && version_compare( substr( $wp_version, 0, strlen( $plugin['requires'] ) ), $plugin['requires'], '<' ) ) {
						echo '<span class="compatibility-incompatible">' . __( '<strong>Incompatible</strong> with your version' ) . '</span>';
					} else {
						echo '<span class="compatibility-compatible">' . __( '<strong>Compatible</strong> with your version' ) . '</span>';
					}
					?>
				</div>

			</div>

		</div>
		<?php
		}

		// Close off the group divs of the last one.
		if ( ! empty( $group ) ) {
			echo '</div></div>';
		}
	}
}