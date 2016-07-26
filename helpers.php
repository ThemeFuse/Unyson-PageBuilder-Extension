<?php if (!defined('FW')) die('Forbidden');

/**
 * Returns whether or not a post was built with the page builder
 */
function fw_ext_page_builder_is_builder_post($post_id = '') {
	return fw()->extensions->get('page-builder')->is_builder_post($post_id);
}

/**
 * Returns all post types that can be integrated with the page builder
 */
function fw_ext_page_builder_get_supported_post_types() {
	$cache_key = fw()->extensions->get('page-builder')->get_cache_key('/supported_post_types');

	try {
		return FW_Cache::get($cache_key);
	} catch (FW_Cache_Not_Found_Exception $e) {
		$post_types = get_post_types(array('public' => true), 'objects');

		$result = array();
		foreach ($post_types as $key => $post_type) {
			if (post_type_supports($key, 'editor')) {
				$result[$key] = $post_type->labels->name;
			}
		}

		$result = apply_filters('fw_ext_page_builder_supported_post_types', $result);

		FW_Cache::set($cache_key, $result);

		return $result;
	}
}

/**
 * @param int|WP_Post $post
 * @return string Shortcodes generated from post meta json builder value
 * @since 1.5.1
 */
function fw_ext_page_builder_get_post_content($post) {
	static $access_key = null;

	if (is_null($access_key)) {
		$access_key = new FW_Access_Key('fw:ext:page-builder:helper:get-post-content');
	}

	return fw_ext('page-builder')->_get_post_content($access_key, $post);
}

