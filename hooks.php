<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Call add_post_type_support('{post-type}', 'fw-page-builder')
 * for post types checked on Page Builder Settings page.
 */
function _action_fw_ext_page_builder_add_support() {
	$feature_name = fw_ext('page-builder')->get_supports_feature_name();

	foreach (
		array_keys(fw_get_db_ext_settings_option('page-builder', 'post_types', array()))
		as $slug
	) {
		add_post_type_support($slug, $feature_name);
	}
}
add_action( 'init', '_action_fw_ext_page_builder_add_support',
	/**
	 * Call this as late as possible to make sure all post types were registered.
	 *
	 * Calling this earlier, will cause some post types to not appear in the checkboxes list on Page Builder Settings page.
	 * That happens when fw_get_db_ext_settings_option('page-builder', ...) is called,
	 * there are no values in db and settings options are extracted from settings options array.
	 * In settings options is used fw_ext_page_builder_get_supported_post_types() which returns the registered post types,
	 * and because it will be called earlier than other post types has been registered,
	 * those post types will not be available.
	 */
	9999
);
