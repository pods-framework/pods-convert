<?php
namespace Pods\Convert\CLI;

/**
 * Class Convert_Command
 */
class Convert_Command extends \WP_CLI_Command {

	/**
	 * @var bool If verbose output should be sent
	 */
	protected $verbose = false;

	/**
	 * @var int How many items per page to process
	 */
	protected $per_page = 100;

	/**
	 * @var int Page offset
	 */
	protected $offset = 0;

	/**
	 * @var array Stats collected during migration
	 */
	protected $stats = array();

	/**
	 * @var array Migrated items array of old to new v ID
	 */
	protected $migrated_items = array();

	/**
	 * Migrate Pod data from one Pod Type / Storage Type to another.
	 *
	 * Set additional field mapping through --fields=description>post_excerpt,image>_thumbnail,etc
	 *
	 * @synopsis --pod=<pod> [--new_type=<new_type>] [--new_storage=<new_storage>] [--new_name=<new_name>] [--fields=<fields>] [--verbose] [--offset=<offset>]
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function migrate( $args, $assoc_args ) {

		if ( ! empty( $assoc_args['verbose'] ) ) {
			$this->verbose = true;
		}

		if ( ! empty( $assoc_args['offset'] ) ) {
			$this->offset = (int) $assoc_args['offset'];
		}

		try {
			$api = pods_api();
			$api->display_errors = false; // Use exceptions

			$original_pod = $pod = $api->load_pod( array(
				'name'       => $assoc_args['pod'],
				'table_info' => false
			) );

			if ( empty( $pod ) ) {
				new \Exception( __( 'Pod not found', 'pods' ) );
			} elseif ( in_array( $pod['type'], array( 'post_type', 'taxonomy' ) ) && 0 < strlen( $pod['object'] ) ) {
				$pod['object'] = '';
			}

			$original_object_fields = $api->get_wp_object_fields( $pod['type'], $pod );

			\WP_CLI::line( sprintf( __( 'Found pod %s (#%d - %s) to migrate.', 'pods' ), $pod['name'], $pod['id'], $pod['type'] ) );

			unset( $pod['id'] );

			if ( ! empty( $assoc_args['new_name'] ) && $pod['name'] != $assoc_args['new_name'] ) {
				$pod['name'] = $assoc_args['new_name'];

				// Don't delete original pod
				$original_pod = false;
			}

			if ( ! empty( $assoc_args['new_type'] ) ) {
				$pod['type'] = $assoc_args['new_type'];
			}

			if ( ! empty( $assoc_args['new_storage'] ) ) {
				$pod['storage'] = $assoc_args['new_storage'];
			}

			$mapped_fields = array();

			// Setup new pod
			if ( false === $original_pod || $pod['name'] != $original_pod['name'] || $pod['type'] != $original_pod['type'] || $pod['storage'] != $original_pod['storage'] ) {
				$try = 2;

				$check_name  = $pod['name'] . $try;
				$check_label = $pod['label'] . $try;

				while ( $api->load_pod( array( 'name' => $check_name, 'table_info' => false ), false ) ) {
					$try++;

					$check_name  = $pod['name'] . $try;
					$check_label = $pod['label'] . $try;
				}

				$pod['name']  = $check_name;
				$pod['label'] = $check_label;

				\WP_CLI::line( sprintf( __( 'Setting up new pod %s (%s).', 'pods' ), $pod['name'], $pod['type'] ) );

				$object_fields = $api->get_wp_object_fields( $assoc_args['new_type'] );

				foreach ( $pod['fields'] as $field => $field_data ) {
					unset( $pod['fields'][ $field ]['id'] );

					if ( ! empty( $object_fields ) ) {
						foreach ( $object_fields as $object_field ) {
							if ( $field == $object_field['name'] || in_array( $field, $object_field['alias'] ) ) {
								$mapped_fields[ $field ] = $object_field['name'];

								break;
							}
						}
					}
				}

				// @todo Add $original_object_fields fields into $pod['fields'] array
				// maybe $mapped_fields if they match name/alias to $object_fields
				// excluding ID fields

				$new_pod_id = $api->save_pod( $pod );

				if ( empty( $new_pod_id ) ) {
					new \Exception( __( 'Pod not saved.', 'pods' ) );
				}
			} elseif ( $original_pod ) {
				new \Exception( __( 'Pod not changed.', 'pods' ) );
			}

			$pod = pods( $pod['name'] );

			$old_pod = pods( $assoc_args['pod'] );

			$params = array(
				'limit'  => $this->per_page,
				'search' => false,
				'page'   => 1,
			);

			$old_pod->find( $params );

			$total_found = $old_pod->total_found();

			$pages = 0;

			if ( 0 < $total_found ) {
				$pages = ceil( $total_found / $this->per_page );
			}

			\WP_CLI::line( sprintf( __( 'Found %d items to migrate.', 'pods' ), $total_found ) );

			$progress_bar = \WP_CLI\Utils\make_progress_bar( __( 'Progress:', 'pods' ), $total_found );

			$page = 1;

			$this->stats = array(
				'total_found' => $total_found,
				'total_pages' => $pages,
				'page_offset' => $this->offset,
				'migrated'    => 0,
			);

			$id_field = $old_pod->pod_data['field_id'];

			while ( $page <= $pages ) {
				// Offset by page
				if ( 0 < $this->offset && $page <= $this->offset ) {
					$progress_bar->increment( $this->per_page );

					$page++;

					continue;
				}

				if ( 1 < $page ) {
					$params['page'] = $page;

					$old_pod->find( $params );
				}

				while ( $old_pod->fetch() ) {
					$data = $old_pod->export( array( 'depth' => 1 ) );

					$new_data = $data;

					// Remove ID field
					unset( $new_data[ $id_field ] );

					foreach ( $mapped_fields as $field => $new_field ) {
						if ( isset( $new_data[ $field ] ) ) {
							$new_data[ $new_field ] = $new_data[ $field ];

							unset( $new_data[ $field ] );
						}
					}

					$new_id = $pod->add( $new_data );

					if ( $this->verbose ) {
						\WP_CLI::line( sprintf( __( 'Item added #%d from old pod item #%d', 'pods' ), $new_id, $data[ $id_field ] ) );
					}

					$this->migrated_items[ $data[ $id_field ] ] = $new_id;

					$progress_bar->tick();
				}

				$this->stop_the_insanity( 1 );

				$page++;
			}

			$progress_bar->finish();

			var_dump( $this->stats );

			if ( $original_pod ) {
				\WP_CLI::confirm( sprintf( __( 'Are you sure you want to delete the old pod "%s" (#%d) (and all of it\'s data), then rename the new pod "%s" (#%d) to replace it?', 'pods' ), $original_pod['name'], $original_pod['id'], $pod->pod_data['name'], $pod->pod_data['id'] ) );

				$api->delete_pod( array( 'name' => $original_pod['name'] ) );

				$api->save_pod( array( 'id' => $pod->pod_data['id'], 'name' => $original_pod['name'] ) );
			}

			\WP_CLI::success( __( 'Migration completed: %s', 'pods' ), date_i18n( 'Y-m-d H:i:s e' ) );
		}
		catch ( \Exception $e ) {
			\WP_CLI::error( sprintf( __( 'Error: %s', 'pods' ), $e->getMessage() ) );
		}

	}

	/**
	 * Sleep and help avoid hitting memory limit
	 *
	 * @param int $sleep_time Amount of seconds to sleep
	 */
	private function stop_the_insanity( $sleep_time = 0 ) {

		if ( $sleep_time ) {
			sleep( $sleep_time );
		}

		/**
		 * @var $wpdb \wpdb
		 * @var $wp_object_cache \WP_Object_Cache
		 */
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array(); // or define( 'WP_IMPORTING', true );

		if ( ! is_object( $wp_object_cache ) ) {
			return;
		}

		$wp_object_cache->group_ops      = array();
		$wp_object_cache->stats          = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache          = array();

		if ( is_callable( $wp_object_cache, '__remoteset' ) ) {
			call_user_func( array( $wp_object_cache, '__remoteset' ) ); // important
		}

	}

}

\WP_CLI::add_command( 'pods-convert', '\Pods\Convert\CLI\Convert_Command' );