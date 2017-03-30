<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

class _FW_Ext_Page_Builder_Wp_Import_Fix {
	private $posts = array();

	public static function get() {
		static $instance = null;

		return $instance === null ? $instance = new self() : $instance;
	}

	protected function __construct() {
		add_action( 'import_post_meta', array( $this, '_action_import_post_meta' ), 10, 3 );
		add_action( 'wp_import_post_meta', array( $this, '_filter_wp_import_post_meta' ), 22, 2 );
	}

	/**
	 * @param $post_id
	 * @param $key
	 * @param $value
	 *
	 * @internal
	 */
	public function _action_import_post_meta( $post_id, $key, $value ) {
		if (
			$key != ( method_exists( fw()->backend, 'get_options_name_attr_prefix' ) // this was added in Unyson 2.6.3
				? fw()->backend->get_options_name_attr_prefix()
				: 'fw_options'
			)
			||
			! isset( $value[ $this->page_builder()->get_option_key() ] )
		) {
			return;
		}

		if ( isset( $this->posts[ $post_id ] ) ) {
			$value[ $this
				->page_builder()
				->get_option_key() ]['json'] = $this->posts[ $post_id ];
		}

		$storage = new FW_Option_Storage_Type_Post_Meta_Page_Builder();

		fw_delete_post_meta(
			$post_id,
			$storage->get_storage_key( $this->page_builder()->get_option_key() )
		);

		fw_set_db_post_option(
			$post_id,
			$this
				->page_builder()
				->get_option_key(),
			$value[ $this
				->page_builder()
				->get_option_key() ]
		);
	}

	/**
	 * @internal
	 *
	 * @param array $meta
	 * @param int $id
	 *
	 * @return mixed
	 **/
	public function _filter_wp_import_post_meta( $meta, $id ) {
		$storage = new FW_Option_Storage_Type_Post_Meta_Page_Builder();
		foreach ( $meta as $item ) {
			if ( $item['key'] == $storage->get_storage_key(
					$this
						->page_builder()
						->get_option_key() )
			) {
				$this->posts[ $id ] = $item['value'];
				break;
			}
		}

		return $meta;
	}

	/**
	 * @return FW_Extension_Page_Builder
	 */
	protected function page_builder() {
		return fw_ext( 'page-builder' );
	}

}

add_action( 'import_start', array( '_FW_Ext_Page_Builder_Wp_Import_Fix', 'get' ) );