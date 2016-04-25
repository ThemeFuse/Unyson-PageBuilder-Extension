<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

class FW_Extension_Page_Builder extends FW_Extension {
	private $builder_option_key = 'page-builder';
	private $supports_feature_name = 'fw-page-builder';

	/**
	 * @var _FW_Ext_Page_Builder_Shortcode_Atts_Coder $shortcode_atts_coder
	 * @deprecated Since Shortcodes 1.3.0
	 */
	private $shortcode_atts_coder;

	/**
	 * Alternative to remove_action() wp_update_post() add_action().
	 * I think this is better because the wp_update_post() may fire other post creation/updates
	 * and the action will not be executed for them.
	 * @var array { post_id: ~ }
	 */
	private $prevent_post_update_recursion = array();

	/**
	 * @var FW_Access_Key
	 */
	private static $access_key;

	private static function get_access_key() {
		if (empty(self::$access_key)) {
			self::$access_key = new FW_Access_Key('fw:ext:page-builder');
		}

		return self::$access_key;
	}

	public function get_supports_feature_name() {
		return $this->supports_feature_name;
	}

	/**
	 * @internal
	 */
	protected function _init() {
		add_action( 'import_post_meta', array( $this, '_action_import_post_meta' ), 10, 3 );

		$this->add_filters();
		$this->add_actions();
	}

	private function add_filters() {
		add_filter( 'fw_post_options', array( $this, '_admin_filter_fw_post_options' ), 10, 2 );
		add_filter( 'the_content', array( $this, '_theme_filter_prevent_autop' ), 1 );
		/**
		 * @since 1.5.0
		 */
		add_filter( 'the_posts', array( $this, '_filter_the_posts' ), 2, 2 );

		/**
		 * @deprecated Since Shortcodes 1.3.0
		 */
		add_filter( 'fw_shortcode_atts', array( $this, '_theme_filter_fw_shortcode_atts' ) );
	}

	private function add_actions() {
		add_action( 'fw_extensions_init', array( $this, '_admin_action_fw_extensions_init' ) );
		add_action( 'fw_post_options_update', array( $this, '_action_fw_post_options_update' ), 11, 3 );
	}

	/*
	 * when a builder modal window draws or saves
	 * options the shortcodes must be loaded
	 * because they may load their own custom option types
	 *
	 * NOTE: this checking is done at the `fw_extensions_init`
	 * at the moment when all the extensions are loaded the shortcode
	 * extension can begin collecting their shortcodes.
	 * We need this because the shortcodes can load their own option types
	 */
	public function _admin_action_fw_extensions_init() {
		if (
			defined( 'DOING_AJAX' ) &&
			DOING_AJAX === true &&
			(
				FW_Request::POST( 'action', '' ) === 'fw_backend_options_render' ||
				FW_Request::POST( 'action', '' ) === 'fw_backend_options_get_values'
			)
		) {
			$this->get_parent()->load_shortcodes();
		}
	}

	/*
	 * Adds the page builder metabox if the $post_type is supported
	 * @internal
	 */
	public function _admin_filter_fw_post_options( $post_options, $post_type ) {
		if ( post_type_supports( $post_type, $this->supports_feature_name ) ) {
			$this->get_parent()->load_shortcodes();
			$page_builder_options = array(
				'page-builder-box' => array(
					'title'    => false,
					'type'     => 'box',
					'priority' => 'high',
					'options'  => array(
						$this->builder_option_key => array(
							'label'              => false,
							'desc'               => false,
							'type'               => 'page-builder',
							'editor_integration' => true,
							'fullscreen'         => true,
							'template_saving'    => true,
							'history'            => true,
							'fw-storage'         => 'post-meta-page-builder',
						)
					)
				)
			);
			$post_options         = array_merge( $page_builder_options, $post_options );
		}

		return $post_options;
	}

	/**
	 * Replace post content with the generated builder shortcodes
	 * @internal
	 * @param int $post_id
	 * @param string $option_id
	 * @param array $sub_keys
	 */
	public function _action_fw_post_options_update( $post_id, $option_id, $sub_keys ) {
		if (
			empty($option_id) // all options were updated
			||
			$option_id === $this->builder_option_key // our option was updated
		) {
			//
		} else {
			return;
		}

		if ( wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) ) {
			return;
		}

		if ( ! post_type_supports( get_post_type($post_id), $this->supports_feature_name ) ) {
			return;
		}

		$builder_data = fw_get_db_post_option( $post_id, $this->builder_option_key );

		if ( ! $builder_data['builder_active'] ) {
			return;
		}

		/**
		 * Just to create a revision if content was changed
		 * Important: Must not contain " because it creates problems with slashes (" -> \")
		 */
		$fake_content = '<!-- ' . strlen($builder_data['json']) .'|'. md5($builder_data['json']) .' -->';

		if (
			!($post = get_post($post_id))
			&&
			$post->post_content === $fake_content
		) {
			return; // Do nothing if content has no changes
		}

		if (isset($this->prevent_post_update_recursion[$post_id])) {
			return;
		} else {
			$this->prevent_post_update_recursion[$post_id] = true;
		}

		wp_update_post(array(
			'ID' => $post_id,
			'post_content' => $fake_content,
		));

		unset($this->prevent_post_update_recursion[$post_id]);
	}

	/**
	 * @internal
	 *
	 * @param $atts
	 *
	 * @return mixed
	 *
	 * @deprecated Since Shortcodes 1.3.0
	 */
	public function _theme_filter_fw_shortcode_atts( $atts ) {
		return $this->get_shortcode_atts_coder()->decode_atts( $atts );
	}

	/**
	 * @internal
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function _theme_filter_prevent_autop( $content ) {
		if ( $this->is_builder_post() ) {
			$wrapper_class = apply_filters( 'fw_ext_page_builder_content_wrapper_class', 'fw-page-builder-content' );

			/**
			 * Wrap the content in a div to prevent wpautop change/break the html generated by shortcodes
			 */

			return
				'<div ' . ( empty( $wrapper_class ) ? '' : 'class="' . esc_attr( $wrapper_class ) . '"' ) . '>' .
				$content .
				'</div>';
		}

		return $content;
	}

	/**
	 * Checks if a post was built with builder
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public function is_builder_post( $post_id = null ) {
		if ( empty( $post_id ) ) {
			global $post;
		} else {
			$post = get_post( $post_id );
		}

		if ( ! $post ) {
			return false;
		}

		if ( post_type_supports( $post->post_type, $this->supports_feature_name ) ) {
			return (bool) fw_get_db_post_option( $post->ID, $this->builder_option_key . '/builder_active' );
		} else {
			return false;
		}
	}

	/**
	 * Solve the problem with striped backslashes by wordpress when doing add_post_meta
	 *
	 * @internal
	 *
	 * @param int $post_id
	 * @param string $key
	 * @param mixed $value
	 **/
	public function _action_import_post_meta( $post_id, $key, $value ) {
		if ( $key != FW_Option_Type::get_default_name_prefix() || ! isset( $value[ $this->builder_option_key ] ) ) {
			return;
		}

		fw_set_db_post_option( $post_id, $this->builder_option_key, $value[ $this->builder_option_key ] );
	}

	public function get_shortcode_atts_coder() {
		if ( ! $this->shortcode_atts_coder ) {
			// lazy init
			$this->shortcode_atts_coder = new _FW_Ext_Page_Builder_Shortcode_Atts_Coder();
		}

		return $this->shortcode_atts_coder;
	}

	/**
	 * @param WP_Post $post
	 * @return string
	 * @since 1.5.0
	 */
	private function get_post_content_shortcodes(WP_Post $post) {
		/**
		 * @var FW_Option_Type_Page_Builder $option_type
		 */
		$option_type = fw()->backend->option_type('page-builder');

		if (
			post_type_supports(
				get_post_type(
					($post_revision_id = wp_is_post_revision($post)) ? $post_revision_id : $post->ID
				),
				$this->supports_feature_name
			)
			&&
			($builder_data = fw_get_db_post_option($post->ID, $this->builder_option_key))
			&&
			$builder_data['builder_active']
		) {
			$builder_data = apply_filters(
				'fw:ext:page-builder:builder-data:before-shortcode-generate',
				$builder_data
			);

			/**
			 * We can't store in a post meta the shortcode notation [shortcode attr="&quot;hello..."]
			 * because it's much bigger than the json value.
			 * So we generate the shortcode notation before post display in frontend
			 */
			return str_replace('\\', '\\\\', // WordPress "fixes" the slashes
				$option_type->_get_shortcode_notation( self::get_access_key(), $builder_data['json'] )
			);
		}

		return $post->post_content;
	}

	/**
	 * @param WP_Post[] $posts
	 * @param WP_Query $query
	 *
	 * @return WP_Post[]
	 * @since 1.5.0
	 */
	public function _filter_the_posts($posts, $query) {
		if (is_admin()) {
			/**
			 * This filter is applied for every post in backend
			 * but we don't need post content in backend
			 */
			return $posts;
		}

		if (
			is_preview()
			&&
			count($posts) == 1
			&&
			is_object($preview = wp_get_post_autosave( $posts[0]->ID ))
		) {
			$posts[0]->post_content = $this->get_post_content_shortcodes($preview);
		} else {
			foreach ($posts as &$post) {
				$post->post_content = $this->get_post_content_shortcodes($post);
			}
		}

		return $posts;
	}

	/**
	 * @param FW_Access_Key $access_key
	 * @param int|WP_Post $post
	 * @return string
	 * @internal
	 * @since 1.5.1
	 */
	public function _get_post_content(FW_Access_Key $access_key, $post) {
		if ($access_key->get_key() !== 'fw:ext:page-builder:helper:get-post-content') {

		}

		if (!$post instanceof WP_Post) {
			$post = get_post($post);
		}

		if (!$post) {
			return null;
		}

		return $this->get_post_content_shortcodes($post);
	}
}
