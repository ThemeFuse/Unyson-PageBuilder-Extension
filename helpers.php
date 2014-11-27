<?php if (!defined('FW')) die('Forbidden');

/**
 * Returns whether or not a post was built with the page builder
 */
function fw_ext_page_builder_is_builder_post($post_id = '')
{
	return fw()->extensions->get('page-builder')->is_builder_post($post_id);
}

/**
 * Returns all post types that can be integrated with the page builder
 */
function fw_ext_page_builder_get_supported_post_types()
{
	$post_types = get_post_types(array('public' => true), 'objects');

	$result = array();
	foreach ($post_types as $key => $post_type) {
		if (post_type_supports($key, 'editor')) {
			$result[$key] = $post_type->labels->name;
		}
	}

	return $result;
}