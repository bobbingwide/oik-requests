<?php // (C) Copyright Bobbing Wide 2016
if ( !defined( "OIK_REQUESTS_LOADED" ) ) {
define( "OIK_REQUESTS_LOADED", "0.0.0" );

/**
 * Request library functions
 * 
 * 
 * Library: oik-requests
 * Provides: oik-requests
 * Type: Shared 
 *
 * Implements logic to automatically create records for the requests executed in the server.
 * 
 * The $_SERVER global variable contains information that we need to record as part of the post_meta data for an "oik_request"
 * 
 * These are the interesting values we need to record
 *
 * Key             | Example                        | Notes
 * --------------- | ------------------------------ | --------  
 * REQUEST_URI     | /wp-admin/?rank=top&dog=sosage | 
 * SCRIPT_NAME     | /wp-admin/index.php            |
 * QUERY_STRING    | rank=top&dog=sosage
 * SERVER_PROTOCOL | HTTP/1.1
 * REQUEST_METHOD  | GET
 *
 */
 
/**
 * Return the OIK_request class
 *
 * If the classes are being autoloaded can't we simply invoke a general purpose static method
 * to return the instance of the class?
 * This will save the number of tiny functions like this
 *
 * $class = "OIK_requests";
 * $instance = $class::instance();
 * 
 * 
 *
 */
function oik_requests_request() {
	$oik_request = OIK_requests::instance();
	return( $oik_request );
}
 

} /* endif */
 
 
