<?php 
/**
Plugin Name: oik-requests
Plugin URI: http://www.oik-plugins.com/oik-plugins/oik-requests
Description: WP-a2z REQUEST_URIs - hooks, files, queries, parms
Version: 0.0.0-alpha.0320
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
	add_action( "run_oik-requests.php", "oik_requests_run_oik_requests" );
	add_filter( "oik_query_autoload_classes" , "oik_requests_oik_query_autoload_classes" );
	//add_filter( "posts_orderby", "oik_requests_posts_orderby", 10, 2 ); 
	//add_filter( "post_limits", "oik_requests_post_limits", 10, 2 );
	//add_filter( "pre_option_posts_per_page", "oik_requests_pre_option_posts_per_page", 10, 2 );
	add_action( "pre_get_posts", "oik_requests_pre_get_posts" );
	add_action( "oik_add_shortcodes", "oik_requests_oik_add_shortcodes" );
}

																	
/**
 * Implement "oik_loaded" for oik_requests
 *
 * Register the "oik_request" CPT
 *
 */																	
function oik_requests_oik_fields_loaded() {
	oiksc_autoload();
	oik_requests_register_oik_request();
	oik_requests_register_fields();
	
	$oik_requests = oik_require_lib( "oik-requests" );
	if ( $oik_requests && !is_wp_error( $oik_requests) ) {
		add_filter( "admin_url", "oik_requests_admin_url", 10, 3 );
		add_action( "shutdown", "oik_requests_shutdown" );
	} else {
		gob();
	}
	
	
}


/**
 * Register the 'oik_request' post type
 *
 * 
 */
function oik_requests_register_oik_request() {
  $post_type = 'oik_request';
  $post_type_args = array();
  $post_type_args['label'] = 'Requests';
  $post_type_args['description'] = 'Request URI';
  $post_type_args['has_archive'] = true;
	$post_type_args['hierarchical'] = true;
	// Not using query_var for this post type
	// $post_type_args['query_var'] = "oik-shortcodes";
	$post_type_args['supports'] = array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'page-attributes' );
  $post_type_args['has_archive'] = true;
  $post_type_args['menu_icon'] = 'dashicons-index-card';
  bw_register_post_type( $post_type, $post_type_args );
	
}

/**
 * Register the "oik_request" post type fields
 * 
 *
 * We don't actually need to register the fields until we know we're going to be dealing 
 * with an "oik_request"... however we haven't quite worked out when we do need them
 * 
 */
function oik_requests_register_fields() {

	oik_require_lib( "oik-requests" );

	$post_type = 'oik_request';
	
	$oik_requests = oik_requests_request();
	$oik_requests->register_fields();
	
  
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
 * Read the composter.json file to find shared libraries
 *
 * 
 */
function oik_lib_compost_libs( $libraries, $plugin ) {
	$file = oik_path( "composter.json", $plugin );
	 
	if ( file_exists( $file ) ) {
		$json = file_get_contents( $file );
		$stuff = json_decode( $json );
		print_r( $stuff );
		$stuff2 = json_decode( $json, true );
		print_r( $stuff2 );
	}	else {
		echo "Composter file $file does not exist" . PHP_EOL;
	}
	return( $libraries );

}

	
/**
 * Implement "run_oik-requests" in batch
 * 
 * This is intended to gather a whole suite of requests
 * which we can then run at our leisure.
 */	
function oik_requests_run_oik_requests() {
	//$libraries = null;
	//$plugin = "oik-requests";
	//oik_lib_compost_libs( $libraries, $plugin );
	
	add_action( "shutdown", "oik_requests_run_shutdown" );
	
}


/**
 * Implement 'admin_url' filter
 * 
 * Record all the admin_url filter requests, for post processing at shutdown
 *
 * 
 */
function oik_requests_admin_url( $url, $path, $blog_id ) {
	if ( function_exists( "oik_requests_request" ) ) {
		$oik_requests = oik_requests_request();
		$oik_requests->admin_url( $url, $path, $blog_id );
	}
	return( $url );
}


/**
 * Implement "oik_query_autoload_classes" for oik-requests
 *
 * Respond with our set of classes that can be autoloaded
 *
 * @param array $classes {@see OIK_Autoload::}
 */

function oik_requests_oik_query_autoload_classes( $classes ) {
	bw_trace2();
	$classes[] = array( "class" => "OIK_requests"
										, "plugin" => "oik-requests"
										, "path" => "libs" 
                    , "file" => "libs/class-oik-requests.php" 
										);
	return( $classes );								
}

/**
 * Implement the "shutdown" action for oik-requests 
 *
 * Create a new "oik_request" record for the REQUEST_URI if we've not seen it before
 * for this particular site
 */
function oik_requests_shutdown() {
	$oik_requests = oik_requests_request();
	$oik_requests->record_request();
	
	//$oik_requests->run_shutdown();
}

/**
 * Implement the "shutdown" action for oik-requests "run_oik-requests.php"
 * 
 */
function oik_requests_run_shutdown() {
	
	$oik_requests = oik_requests_request();
	$oik_requests->run_shutdown();
	gob();


}

/**
 * Order archives by post title 
 * 
 * @TODO Consider what to do for "posts"
 *
 * @param string $orderby - current value of orderby
 * @param object $query - a WP_Query object
 * @return string the orderby we want
 */
function oik_requests_posts_orderby( $orderby, $query ) {
	bw_trace2();
	bw_backtrace();
	global $wpdb;
	if ( $query->is_post_type_archive ) {
		$orderby = "$wpdb->posts.post_title asc";  
	}
	return( $orderby );
}

/**
 * Increase the posts displayed on archives
 * 
 * This doesn't appear to be the right way to do it
 * as we have to handle pagination as well
 * unless we can update $query->posts_per_page as well
 * 
 * 
 *
 * @param string $limit e.g. 'LIMIT 0, 10'
 * @param object $query the query object
 * @return string currently an arbitrary limit of 100 per page
 */
function oik_requests_post_limits( $limit, $query ) {

	bw_trace2();
	bw_backtrace();
	if ( $query->is_post_type_archive ) {
	//	$limit = "LIMIT 0, 100";
	}
	return( $limit );
}

/**
 * Implement 'pre_option_posts_per_page' filter
 * 
 * Don't do this as we don't know the context
 */
function oik_requests_pre_option_posts_per_page( $pre, $option ) {
	bw_trace2();
	bw_backtrace();
	return( 20 );
}

/**
 * Implement "pre_get_posts" action for oik-requests
 * 
 * This filter is not needed if we're using oik-types to override the posts_per_page 
 *
 * 
 */

function oik_requests_pre_get_posts( $query ) {
	if ( $query->is_main_query() ) {
	
		if ( $query->is_archive() ) { 
			bw_trace2();
			switch ( $query->query['post_type'] ) {
	
				case "oik-plugins":
					$query->set( 'posts_per_page', 20 );
					break;
				
				default: 
					$query->set( 'posts_per_page', 50 );
			}	
		} else {
		}	
	}	
}

function oik_requests_oik_add_shortcodes() {
	bw_add_shortcode( "bw_sql", "bw_sql", oik_path( "shortcodes/oik-sql.php", "oik-requests" ), false );
}	
				
	

