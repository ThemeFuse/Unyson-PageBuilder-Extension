<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Registers support of feedback for post types checked in dashboard.
 */
function fw_ext_page_builder_add_support()
{
	$post_types   = fw_get_db_ext_settings_option('page-builder', 'post_types');
	$feature_name = fw_ext('page-builder')->get_supports_feature_name();
	foreach ($post_types as $slug => $value) {
		add_post_type_support($slug, $feature_name);
	}
}
add_action( 'init', 'fw_ext_page_builder_add_support' );