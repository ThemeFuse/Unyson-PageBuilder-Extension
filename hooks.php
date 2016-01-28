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
		array_keys(fw_get_db_ext_settings_option('page-builder', 'post_types'))
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

function _action_fw_ext_page_builder_register_option_storage_types(_FW_Option_Storage_Type_Register $register) {
	require_once dirname(__FILE__) .'/includes/page-builder/includes/option-storage/class-fw-option-storage-type-post-meta-page-builder.php';
	$register->register(new FW_Option_Storage_Type_Post_Meta_Page_Builder());
}
add_action('fw:option-storage-types:register', '_action_fw_ext_page_builder_register_option_storage_types');
