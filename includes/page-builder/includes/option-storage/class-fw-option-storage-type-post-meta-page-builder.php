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

	public function get_storage_key( $id ) {
		return $this->get_meta_prefix( $id ) . 'json';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function _save( $id, array $option, $value, array $params ) {
		if ($post_id = $this->get_post_id($option, $params)) {
			fw_update_post_meta(
				$post_id,
				$this->get_storage_key( $id ),
				$value['json']
			);

			$val = fw()->backend->option_type($option['type'])->get_value_from_input(
				array('type' => $option['type']), null
			);

			if (isset($value['builder_active'])) {
				$val['builder_active'] = $value['builder_active']; // don't store this in separate meta
			} else {
				$value['builder_active'] = false;
			}

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
			$meta_value = array(
				'json' => get_post_meta(
					$post_id,
					$this->get_storage_key( $id ),
					true
				),
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

	private function get_meta_prefix( $id ) {
		return 'fw:opt:ext:pb:' . $id . ':';
	}
}
