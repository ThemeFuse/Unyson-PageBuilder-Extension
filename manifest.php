<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']          = __( 'Page Builder', 'fw' );
$manifest['description']   = __( "Lets you easily build countless pages with the help of the drag and drop visual page builder that comes with a lot of already created shortcodes.", 'fw' );
$manifest['version']       = '1.0.0';
$manifest['display']       = true;
$manifest['standalone']    = true;
$manifest['requirements']  = array(
	'extensions' => array(
		'builder' => array(),
	),
);

$manifest['github_update'] = 'ThemeFuse/Unyson-PageBuilder-Extension';
