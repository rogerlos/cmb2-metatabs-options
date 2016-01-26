<?php
/**
 * RNL AUTOLOADER
 * Looks in /code to see if a file matching the classname exists
 *
 * @param $class
 *
 * @since 1.0.0
 */
function rnl_autoloader( $class ) {
	$base_dir = __DIR__;
	$class = strtolower( $class );
	$file = $base_dir . '/code/' . $class . '.php';
	if ( file_exists( $file ) ) require $file;
}