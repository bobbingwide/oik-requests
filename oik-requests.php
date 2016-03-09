<?php 
/**
Plugin Name: oik-requests
Plugin URI: http://www.oik-plugins.com/oik-plugins/oik-requests
Description: WP-a2z REQUEST_URIs hooks and files
Version: 0.0.0
Author: bobbingwide
Author URI: http://www.oik-plugins.com/author/bobbingwide
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

    Copyright 2016 Bobbing Wide (email : herb@bobbingwide.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/

oik_requests_loaded();

/**
 * Functions to invoke when oik-ajax is loaded
 * 
 * "oik_shortcode_result" is automatically invoked for each shortcode
 * but not necessarily invoked for each paginatable output.
 
 * If that's required you can invoke the filter yourself.
 * BUT we have to be able to convert whatever's been called into a shortcode
 * OR at least an AJAX/JSON request.
 * 
 */
function oik_requests_loaded() {
	add_action( "oik_fields_loaded", "oik_requests_oik_fields_loaded" );
	add_filter( "oik_query_libs", "oik_requests_oik_query_libs" );
}

																	
/**
 * Implement "oik_loaded" for oik_requests
 *
 * Register the "oik_request" CPT
 *
 */																	
function oik_requests_oik_fields_loaded() {
	oik_requests_register_oik_request();
}


/**
 * Register the oik_request post type
 *
 * 
 */
function oik_requests_register_oik_request() {
  $post_type = 'oik_request';
  $post_type_args = array();
  $post_type_args['label'] = 'Request';
  $post_type_args['description'] = 'Request URI';
  $post_type_args['has_archive'] = true;
	// Not using query_var for this post type
	// $post_type_args['query_var'] = "oik-shortcodes";
  bw_register_post_type( $post_type, $post_type_args );
	
}

/**
 * Register the "oik_request" post type fields
 * 
 *
 * We don't actually need to register the fields until we know we're going be to be dealing 
 * with an "oik_request"
 */
function oik_requests_register_fields() {

	$post_type = 'oik_request';
	
  //add_post_type_support( $post_type, 'publicize' );
  
  bw_register_field( "_oik_rq_files", "text", "Files loaded" ); 
	bw_register_field( "_oik_sc_shortcake_cb", "checkbox", "Compatible with shortcake?" );
  
	bw_register_field_for_object_type( "_oik_rq_uri", $post_type );
  bw_register_field_for_object_type( "_oik_rq_files", $post_type );
  bw_register_field_for_object_type( "_oik_rq_hooks", $post_type );
  
  //add_filter( "manage_edit-${post_type}_columns", "oik_shortcodes_columns", 10, 2 );
  add_action( "manage_${post_type}_posts_custom_column", "bw_custom_column_admin", 10, 2 );
}

/**
 * Return the libraries shared by this plugin
 * 
 * @param array $libraries array of OIK_libs
 * @return array updated array of OIK_libs
 */
function oik_requests_oik_query_libs( $libraries ) {
	$libs = array( "oik-requests" => null );
	$new_libraries = oik_lib_check_libs( $libraries, $libs, "oik-requests" );
	bw_trace2( $new_libraries, "new libraries", true, BW_TRACE_VERBOSE );
	return( $new_libraries );
}


/**
 * 
 * Read the compostor.json file to find shared libraries
 *
 * 
 */
function oik_lib_compost_libs( $libraries, $plugin ) {
	return( $libraries );

}
	

