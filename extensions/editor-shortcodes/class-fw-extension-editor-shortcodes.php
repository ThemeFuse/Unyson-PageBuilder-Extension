<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Class FW_Extension_Editor_Shortcodes
 * Integrate shortcodes with wp_editor
 *
 * Completely deprecated since Page Builder 1.6.0.
 * It's purpose now is to correctly render posts which still may have
 * [shortcode_name fw_shortcode_id="1"][/shortcode_name] in them.
 * The rest of it is completely removed.
 *
 * Editor Shortcodes V2 will do it's job next.
 */
class FW_Extension_Editor_Shortcodes extends FW_Extension {
	private $meta_key = 'fw-shortcode-settings';
	private $meta_key_defaults = 'fw-shortcode-default-values';

	/**
	 * @internal
	 */
	protected function _init() {
		if ( is_admin() ) {
			$this->add_admin_actions();
		} else {
			$this->add_theme_filters();
		}
	}


	private function add_admin_actions() {
		add_action( 'edit_form_after_editor', array( $this, '_action_admin_render_hidden' ) );
	}

	private function add_theme_filters() {
		/**
		 * @deprecated Since Shortcodes 1.3.0
		 */
		add_filter(
			'fw_shortcode_atts',
			array( $this, '_theme_filter_fw_shortcode_atts' ),
			10, 3
		);
	}

	public function decode_shortcode_atts($atts, $tag, $post_id) {
		if ( ! isset( $atts['fw_shortcode_id'] ) ) { return $atts; }

		$option_values  = json_decode( get_post_meta( $post_id, $this->meta_key, true ), true );
		$default_values = json_decode( get_post_meta( $post_id, $this->meta_key_defaults, true ), true );

		$id   = $atts['fw_shortcode_id'];
		$atts = $default_values[ $tag ];

		if ( is_array( $option_values ) and false === empty( $option_values ) ) {
			if ( preg_match( '/^[A-Za-z0-9]+$/', $id ) ) {
				if ( isset( $option_values[ $tag ][ $id ] ) ) {
					$atts = $option_values[ $tag ][ $id ];
				}
			}
		}

		return $atts;
	}

	/**
	 * Replace shortcode atts with saved options
	 * @deprecated Since Shortcodes 1.3.0
	 */
	public function _theme_filter_fw_shortcode_atts( $atts, $content, $tag ) {
		global $post;

		return $this->decode_shortcode_atts($atts, $tag, $post->ID);
	}

	public function _action_admin_render_hidden() {
		global $post;
		if ( ! $this->is_supported_post( $post ) ) {
			return;
		}

		$value = get_post_meta( $post->ID, $this->meta_key, true );
		echo '<input id="' . $this->meta_key . '" type="hidden" name="' . $this->meta_key . '" value="' . fw_htmlspecialchars( $value ) . '">';
	}

	/**
	 * Checks if a post was built with shortcode editor
	 *
	 * @param null|int $post_id
	 *
	 * @return false|WP_Post
	 */
	public function is_supported_post( $post_id = null ) {
		if ( ! $post_id ) {
			global $post;
		} else {
			$post = get_post( $post_id );
		}

		$page_builder_feature = $this->get_parent()->get_supports_feature_name();

		return $post && post_type_supports( $post->post_type, $page_builder_feature );
	}
}
