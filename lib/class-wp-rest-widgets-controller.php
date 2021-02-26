<?php
/**
 * REST API: WP_REST_Widgets_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 5.6.0
 */

/**
 * Core class to access widgets via the REST API.
 *
 * @since 5.6.0
 *
 * @see WP_REST_Controller
 */
class WP_REST_Widgets_Controller extends WP_REST_Controller {

	/**
	 * Widgets controller constructor.
	 *
	 * @since 5.6.0
	 */
	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'widgets';
	}

	/**
	 * Registers the widget routes for the controller.
	 *
	 * @since 5.6.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema(),
				),
				'allow_batch' => array( 'v1' => true ),
				'schema'      => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>[\w\-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'description' => __( 'Whether to force removal of the widget, or move it to the inactive sidebar.', 'gutenberg' ),
							'type'        => 'boolean',
						),
					),
				),
				'allow_batch' => array( 'v1' => true ),
				'schema'      => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks if a given request has access to get widgets.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $this->permissions_check();
	}

	/**
	 * Retrieves a collection of widgets.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$prepared = array();

		foreach ( wp_get_sidebars_widgets() as $sidebar_id => $widget_ids ) {
			if ( isset( $request['sidebar'] ) && $sidebar_id !== $request['sidebar'] ) {
				continue;
			}

			foreach ( $widget_ids as $widget_id ) {
				$response = $this->prepare_item_for_response( compact( 'sidebar_id', 'widget_id' ), $request );

				if ( ! is_wp_error( $response ) ) {
					$prepared[] = $this->prepare_response_for_collection( $response );
				}
			}
		}

		return new WP_REST_Response( $prepared );
	}

	/**
	 * Checks if a given request has access to get a widget.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $this->permissions_check();
	}

	/**
	 * Gets an individual widget.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$widget_id  = $request['id'];
		$sidebar_id = $this->find_widgets_sidebar( $widget_id );

		if ( is_wp_error( $sidebar_id ) ) {
			return $sidebar_id;
		}

		return $this->prepare_item_for_response( compact( 'sidebar_id', 'widget_id' ), $request );
	}

	/**
	 * Checks if a given request has access to create widgets.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $this->permissions_check();
	}

	/**
	 * Creates a widget.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$sidebar_id = $request->get_param( 'sidebar' );

		if ( ! $sidebar_id ) {
			return new WP_Error(
				'rest_invalid_widget',
				__( 'Sidebar (sidebar) is required.', 'gutenberg' ),
				array( 'status' => 400 )
			);
		}

		$widget_id = $this->save_widget( $request );

		if ( is_wp_error( $widget_id ) ) {
			return $widget_id;
		}

		$this->assign_widget_to_sidebar( $widget_id, $sidebar_id );

		$request->set_param( 'context', 'edit' );

		$response = $this->prepare_item_for_response( compact( 'sidebar_id', 'widget_id' ), $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Checks if a given request has access to update widgets.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $this->permissions_check();
	}

	/**
	 * Updates an existing widget.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$widget_id  = $request->get_param( 'id' );
		$sidebar_id = $this->find_widgets_sidebar( $widget_id );

		if ( $request->has_param( 'instance' ) ) {
			$maybe_error = $this->save_widget( $request );
			if ( is_wp_error( $maybe_error ) ) {
				return $maybe_error;
			}
		}

		if ( $request->has_param( 'sidebar' ) ) {
			$new_sidebar_id = $request->get_param( 'sidebar' );
			if ( $sidebar_id !== $new_sidebar_id ) {
				$this->assign_widget_to_sidebar( $widget_id, $new_sidebar_id );
				$sidebar_id = $new_sidebar_id;
			}
		}

		$request->set_param( 'context', 'edit' );

		return $this->prepare_item_for_response( compact( 'sidebar_id', 'widget_id' ), $request );
	}

	/**
	 * Checks if a given request has access to delete widgets.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $this->permissions_check();
	}

	/**
	 * Deletes a widget.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$widget_id  = $request['id'];
		$sidebar_id = $this->find_widgets_sidebar( $widget_id );

		if ( is_wp_error( $sidebar_id ) ) {
			return $sidebar_id;
		}

		$request['context'] = 'edit';

		if ( $request['force'] ) {
			$prepared = $this->prepare_item_for_response( compact( 'sidebar_id', 'widget_id' ), $request );
			$this->assign_widget_to_sidebar( $widget_id, '' );
			$prepared->set_data(
				array(
					'deleted'  => true,
					'previous' => $prepared->get_data(),
				)
			);
		} else {
			$this->assign_widget_to_sidebar( $widget_id, 'wp_inactive_widgets' );
			$prepared = $this->prepare_item_for_response(
				array(
					'sidebar_id' => 'wp_inactive_widgets',
					'widget_id'  => $widget_id,
				),
				$request
			);
		}

		return $prepared;
	}

	/**
	 * Performs a permissions check for managing widgets.
	 *
	 * @since 5.6.0
	 *
	 * @return true|WP_Error
	 */
	protected function permissions_check() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new WP_Error(
				'rest_cannot_manage_widgets',
				__( 'Sorry, you are not allowed to manage widgets on this site.', 'gutenberg' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Prepares the widget for the REST response.
	 *
	 * @since 5.6.0
	 *
	 * @global array $wp_registered_sidebars        The registered sidebars.
	 * @global array $wp_registered_widgets         The registered widgets.
	 * @global array $wp_registered_widget_controls The registered widget controls.
	 *
	 * @param array           $item    An array containing a widget_id and sidebar_id.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		global $wp_registered_widgets, $wp_registered_sidebars, $wp_registered_widget_controls;

		$widget_id  = $item['widget_id'];
		$sidebar_id = $item['sidebar_id'];

		if ( ! isset( $wp_registered_widgets[ $widget_id ] ) ) {
			return new WP_Error(
				'rest_invalid_widget',
				__( 'The requested widget is invalid.', 'gutenberg' ),
				array( 'status' => 500 )
			);
		}

		$widget    = $wp_registered_widgets[ $widget_id ];
		$parsed_id = $this->parse_widget_id( $widget_id );
		$fields    = $this->get_fields_for_response( $request );

		$prepared = array(
			'id'          => $widget_id,
			'id_base'     => $parsed_id['id_base'],
			'id_number'   => isset( $parsed_id['number'] ) ? $parsed_id['number'] : null,
			'name'        => $widget['name'],
			'description' => isset( $widget['description'] ) ? $widget['description'] : '',
			'rendered'    => '',
			'instance'    => null,
			'sidebar'     => $sidebar_id,
		);

		if (
			rest_is_field_included( 'rendered', $fields ) &&
			'wp_inactive_widgets' !== $sidebar_id
		) {
			$prepared['rendered'] = $this->render_widget( $widget_id, $sidebar_id );
		}

		if ( rest_is_field_included( 'instance', $fields ) ) {
			$instance = $this->get_widget_instance( $widget_id );
			if ( $instance ) {
				$serialized_instance             = serialize( $instance );
				$prepared['instance']['encoded'] = base64_encode( $serialized_instance );
				$prepared['instance']['hash']    = wp_hash( $serialized_instance );

				if (
					isset( $widget['show_instance_in_rest'] ) &&
					$widget['show_instance_in_rest']
				) {
					$prepared['instance']['raw'] = $instance;
				}
			}
		}

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$prepared = $this->add_additional_fields_to_object( $prepared, $request );
		$prepared = $this->filter_response_by_context( $prepared, $context );

		$response = rest_ensure_response( $prepared );

		$response->add_links( $this->prepare_links( $prepared ) );

		/**
		 * Filters the REST API response for a widget.
		 *
		 * @since 5.6.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $widget   The registered widget data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'rest_prepare_widget', $response, $widget, $request );
	}

	/**
	 * Saves the widget in the request object.
	 *
	 * @since 5.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return string The saved widget ID.
	 */
	protected function save_widget( $request ) {
		global $wp_registered_widget_updates;

		$id             = $request->get_param( 'id' );
		$id_base        = $request->get_param( 'id_base' );
		$instance_param = $request->get_param( 'instance' );

		if ( $id ) {
			$parsed_id = $this->parse_widget_id( $id );
			$id_base   = $parsed_id['id_base'];
		}

		if ( ! $id_base ) {
			return new WP_Error(
				'rest_invalid_widget',
				__( 'Widget type (id_base) is required.', 'gutenberg' ),
				array( 'status' => 400 )
			);
		}

		if ( ! isset( $wp_registered_widget_updates[ $id_base ] ) ) {
			return new WP_Error(
				'rest_invalid_widget',
				__( 'The provided widget type (id_base) cannot be updated.', 'gutenberg' ),
				array( 'status' => 400 )
			);
		}

		if ( $instance_param ) {
			if ( isset( $instance_param['raw'] ) ) {
				$instance = $instance_param['raw'];
			} elseif ( isset( $instance_param['encoded'], $instance_param['hash'] ) ) {
				$serialized_instance = base64_decode( $instance_param['encoded'] );
				if ( wp_hash( $serialized_instance ) !== $instance_param['hash'] ) {
					return new WP_Error(
						'rest_invalid_widget',
						__( 'The provided instance is malformed.', 'gutenberg' ),
						array( 'status' => 400 )
					);
				}
				$instance = unserialize( $serialized_instance );
			} else {
				return new WP_Error(
					'rest_invalid_widget',
					__( 'The provided instance is invalid. Must contain raw OR encoded and hash.', 'gutenberg' ),
					array( 'status' => 400 )
				);
			}
		} else {
			$instance = array();
		}

		$original_post    = $_POST;
		$original_request = $_REQUEST;

		if ( isset( $parsed_id['number'] ) ) {
			$key                           = 'widget-' . $id_base;
			$value                         = array();
			$value[ $parsed_id['number'] ] = $instance;
			$slashed_value                 = wp_slash( $value );
			$_POST[ $key ]                 = $slashed_value;
			$_REQUEST[ $key ]              = $slashed_value;
		} else {
			foreach ( $instance as $key => $value ) {
				$slashed_value    = wp_slash( $value );
				$_POST[ $key ]    = $slashed_value;
				$_REQUEST[ $key ] = $slashed_value;
			}
		}

		$callback = $wp_registered_widget_updates[ $id_base ]['callback'];
		$params   = $wp_registered_widget_updates[ $id_base ]['params'];

		if ( is_callable( $callback ) ) {
			ob_start();
			call_user_func_array( $callback, $params );
			ob_end_clean();
		}

		$_POST    = $original_post;
		$_REQUEST = $original_request;

		if ( ! $id ) {
			$widget_object = $this->get_widget_object( $id_base );
			if ( $widget_object ) {
				$id = $id_base . '-' . count( $widget_object->get_settings() );
			} else {
				$id = $id_base;
			}
		}

		return $id;
	}

	/**
	 * Prepares links for the widget.
	 *
	 * @since 5.6.0
	 *
	 * @param array $prepared Widget.
	 * @return array Links for the given widget.
	 */
	protected function prepare_links( $prepared ) {
		return array(
			'self'                      => array(
				'href' => rest_url( sprintf( '%s/%s/%s', $this->namespace, $this->rest_base, $prepared['id'] ) ),
			),
			'collection'                => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
			'about'                     => array(
				'href'       => rest_url( sprintf( 'wp/v2/widget-types/%s', $prepared['id_base'] ) ),
				'embeddable' => true,
			),
			'https://api.w.org/sidebar' => array(
				'href' => rest_url( sprintf( 'wp/v2/sidebars/%s/', $prepared['sidebar'] ) ),
			),
		);
	}

	/**
	 * Gets the list of collection params.
	 *
	 * @since 5.6.0
	 *
	 * @return array[]
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			'sidebar' => array(
				'description' => __( 'The sidebar to return widgets for.', 'gutenberg' ),
				'type'        => 'string',
			),
		);
	}

	/**
	 * Retrieves the widget's schema, conforming to JSON Schema.
	 *
	 * @since 5.6.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'widget',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the widget.', 'gutenberg' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'id_base'     => array(
					'description' => __( 'Type of widget for the object.', 'gutenberg' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'id_number'   => array(
					'description' => __( 'Number of the widget.', 'gutenberg' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'name'        => array(
					'description' => __( 'Name of the widget.', 'gutenberg' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Description of the widget.', 'gutenberg' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'rendered'    => array(
					'description' => __( 'HTML representation of the widget.', 'gutenberg' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'instance'    => array(
					'description' => __( 'Settings of the widget.', 'gutenberg' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'sidebar'     => array(
					'description' => __( 'The sidebar the widget belongs to.', 'gutenberg' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}

	//
	// Widget helper functions. Probably these don't belong in the controller.
	//

	protected function parse_widget_id( $widget_id ) {
		if ( ! $widget_id ) {
			return array();
		}

		$parsed = array();

		if ( preg_match( '/^(.+)-(\d+)$/', $widget_id, $matches ) ) {
			$parsed['id_base'] = $matches[1];
			$parsed['number']  = (int) $matches[2];
		} else {
			// Likely an old single widget.
			$parsed['id_base'] = $widget_id;
		}

		return $parsed;
	}

	// This is mostly copied from dynamic_sidebar().
	protected function render_widget( $widget_id, $sidebar_id ) {
		global $wp_registered_widgets, $wp_registered_sidebars;

		if ( ! isset( $wp_registered_widgets[ $widget_id ] ) ) {
			return '';
		}

		if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
			$sidebar = $wp_registered_sidebars[ $sidebar_id ];
		} elseif ( 'wp_inactive_widgets' === $sidebar_id ) {
			$sidebar = array();
		} else {
			return '';
		}

		$params = array_merge(
			array(
				array_merge(
					$sidebar,
					array(
						'widget_id'   => $widget_id,
						'widget_name' => $wp_registered_widgets[ $widget_id ]['name'],
					)
				),
			),
			(array) $wp_registered_widgets[ $widget_id ]['params']
		);

		// Substitute HTML `id` and `class` attributes into `before_widget`.
		$classname_ = '';
		foreach ( (array) $wp_registered_widgets[ $widget_id ]['classname'] as $cn ) {
			if ( is_string( $cn ) ) {
				$classname_ .= '_' . $cn;
			} elseif ( is_object( $cn ) ) {
				$classname_ .= '_' . get_class( $cn );
			}
		}
		$classname_                 = ltrim( $classname_, '_' );
		$params[0]['before_widget'] = sprintf( $params[0]['before_widget'], $widget_id, $classname_ );

		/** This filter is documented in wp-includes/widgets.php */
		$params = apply_filters( 'dynamic_sidebar_params', $params );

		$callback = $wp_registered_widgets[ $widget_id ]['callback'];

		ob_start();

		/** This filter is documented in wp-includes/widgets.php */
		do_action( 'dynamic_sidebar', $wp_registered_widgets[ $widget_id ] );

		if ( is_callable( $callback ) ) {
			call_user_func_array( $callback, $params );
		}

		return ob_get_clean();
	}

	protected function get_widget_instance( $widget_id ) {
		$parsed_id     = $this->parse_widget_id( $widget_id );
		$widget_object = $this->get_widget_object( $parsed_id['id_base'] );

		if ( ! isset( $parsed_id['number'] ) || ! $widget_object ) {
			return null;
		}

		$all_instances = $widget_object->get_settings();
		return $all_instances[ $parsed_id['number'] ];
	}

	protected function get_widget_object( $id_base ) {
		global $wp_widget_factory;

		foreach ( $wp_widget_factory->widgets as $widget_object ) {
			if ( $widget_object->id_base === $id_base ) {
				return $widget_object;
			}
		}

		return null;
	}

	/**
	 * Finds the sidebar a widget belongs to.
	 *
	 * @since 5.6.0
	 *
	 * @param string $widget_id The widget id to search for.
	 * @return string|null The found sidebar id, or null instance if it does not exist.
	 */
	protected function find_widgets_sidebar( $widget_id ) {
		foreach ( wp_get_sidebars_widgets() as $sidebar_id => $widget_ids ) {
			foreach ( $widget_ids as $maybe_widget_id ) {
				if ( $maybe_widget_id === $widget_id ) {
					return (string) $sidebar_id;
				}
			}
		}

		return null;
	}

	/**
	 * Assigns a widget to the given sidebar.
	 *
	 * @since 5.6.0
	 *
	 * @param string $widget_id  The widget id to assign.
	 * @param string $sidebar_id The sidebar id to assign to. If empty, the widget won't be added to any sidebar.
	 */
	protected function assign_widget_to_sidebar( $widget_id, $sidebar_id ) {
		$sidebars = wp_get_sidebars_widgets();

		foreach ( $sidebars as $maybe_sidebar_id => $widgets ) {
			foreach ( $widgets as $i => $maybe_widget_id ) {
				if ( $widget_id === $maybe_widget_id && $sidebar_id !== $maybe_sidebar_id ) {
					unset( $sidebars[ $maybe_sidebar_id ][ $i ] );
					// We could technically break 2 here, but continue looping in case the id is duplicated.
					continue 2;
				}
			}
		}

		if ( $sidebar_id ) {
			$sidebars[ $sidebar_id ][] = $widget_id;
		}

		wp_set_sidebars_widgets( $sidebars );
	}
}
