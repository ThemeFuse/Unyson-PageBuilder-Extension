<?php if (!defined('FW')) die('Forbidden');

/**
 * array(
 *  'post-id' => 3 // optional // hardcoded post id
 * )
 */
class FW_Option_Storage_Type_Post_Meta_Page_Builder extends FW_Option_Storage_Type {
	public function get_type() {
		return 'post-meta-page-builder';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function _save( $id, array $option, $value, array $params ) {
		global $wpdb; /** @var WPDB $wpdb */

		if ($post_id = $this->get_post_id($option, $params)) {
			$meta_prefix = $this->get_meta_prefix($id, $option, $params);

			fw_update_post_meta($post_id, $meta_prefix .'json', '["void"]'); // just make sure the row is created in db
			fw_db_update_big_data(
				$wpdb->postmeta,
				array('meta_value' => $value['json']),
				array('meta_key' => $meta_prefix .'json', 'post_id' => $post_id)
			);

			fw_update_post_meta($post_id, $meta_prefix .'sc_n', '[void]'); // just make sure the row is created in db
			fw_db_update_big_data(
				$wpdb->postmeta,
				array('meta_value' => $value['shortcode_notation']),
				array('meta_key' => $meta_prefix .'sc_n', 'post_id' => $post_id)
			);

			$val = fw()->backend->option_type($option['type'])->get_value_from_input(
				array('type' => $option['type']), null
			);

			$val['builder_active'] = $value['builder_active']; // don't store this in separate meta

			return $val;
		} else {
			return $value;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function _load( $id, array $option, $value, array $params ) {
		if ($post_id = $this->get_post_id($option, $params)) {
			$meta_prefix = $this->get_meta_prefix($id, $option, $params);

			$meta_value = array(
				'json' => get_post_meta($post_id, $meta_prefix .'json', true),
				'shortcode_notation' => get_post_meta($post_id, $meta_prefix .'sc_n', true),
			);

			if ($meta_value['json'] === '' && is_array($value)) {
				return $value;
			} else {
				$meta_value['builder_active'] = $value['builder_active'];
				return $meta_value;
			}
		} else {
			return $value;
		}
	}

	private function get_post_id($option, $params) {
		$post_id = null;

		if (!empty($option['fw-storage']['post-id'])) {
			$post_id = $option['fw-storage']['post-id'];
		} elseif (!empty($params['post-id'])) {
			$post_id = $params['post-id'];
		}

		$post_id = intval($post_id);

		if ($post_id > 0) {
			return $post_id;
		} else {
			return false;
		}
	}

	private function get_meta_prefix($id, $option, $params) {
		return 'fw:opt:ext:pb:'. $id .':';
	}
}
