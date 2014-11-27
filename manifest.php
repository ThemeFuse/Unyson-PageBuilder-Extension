<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']          = __( 'Page Builder', 'fw' );
$manifest['description']   = __( "Lets you easily build countless pages with the help of the drag and drop visual page builder that comes with a lot of already created shortcodes.", 'fw' );
$manifest['version']       = '0.0.0';
$manifest['author']        = 'ThemeFuse';
$manifest['author_uri']    = 'http://themefuse.com/';
$manifest['display']       = true;
$manifest['standalone']    = true;
$manifest['requirements']  = array(
	'extensions' => array(
		'builder' => array(),
	),
);