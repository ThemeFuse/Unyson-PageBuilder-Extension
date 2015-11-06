<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$options = array(
	'general-tab' => array(
		'title'   => '',
		'type'    => 'box',
		'options' => array(
			'post_types' => array(
				'label'   => __( 'Activate for', 'fw' ),
				'type'    => 'checkboxes',
				'choices' => fw_ext_page_builder_get_supported_post_types(),
				'value'   => apply_filters(
					'fw_ext_page_builder_settings_options_post_types_default_value', 
					array( 'page' => true )
				),
				'desc'    => __( 'Select the posts you want the Page Builder extension to be activated for', 'fw' )
			),
			apply_filters('fw_ext_page_builder_settings_options', array())
		)
	)
);
