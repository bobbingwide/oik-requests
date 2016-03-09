<?php // (C) Copyright Bobbing Wide 2016

/**
 * OIK_requests class
 *
 * @TODO Decide whether or not it's worth creating OIK_singleton so that other classes don't need to define function instance()
 * @TODO Decide whether or not it's worth creating OIK_post_type with basic CRUD methods
 * 
 * 
 *
 */
class OIK_requests /* extends OIK_singleton */ {

	public $admin_urls = null;

	/**
	 * @var $instance - the true instance
	 */
	private static $instance;
	
	/**
	 * Return a single instance of this class
	 *
	 * @return object 
	 */
	public static function instance() {
		if ( !isset( self::$instance ) && !( self::$instance instanceof self ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	
	/**
	 * Log another admin_url
	 *
	 * @param string $url URL including /subfolder/wp-admin/
	 * @param string $path what we actually need... ignoring the query parms
	 * @param integer $blog_id - for multisite
	 * @return
	
	 * (
    [0] => /wordpress/wp-admin/admin-ajax.php
    [1] => admin-ajax.php
    [2] => 
)

(
    [0] => http://qw/wordpress/wp-admin/customize.php
    [1] => customize.php
    [2] => 
		
		[0] => http://qw/wordpress/wp-admin/post-new.php?post_type=oik-themes
    [1] => post-new.php?post_type=oik-themes
    [2] => 
)

)
*/
	
	public function admin_url( $url, $path, $blog_id ) {
		bw_trace2();
		$this->admin_urls[] = $path;
	
		//$this->admin_urls[] =  #
		return( $url );
	
	}
	
	/**
	 * Get the path of the REQUEST_URI
	 *
	 * Note: Since the REQUEST_URI may get changed we need to access it early
	 * 
	 * Do we need to strip the $site_url when working in a sub-folder?
	 * 
	 * $site_url = site_url();
	 * 
	 */
	public function get_uri() {
		$request_uri = bw_array_get( $_SERVER, 'REQUEST_URI', null );
		$url = parse_url( $request_uri );
		$uri = $url['path'];
		$uri = $this->strip_siteurl_path( $uri );
		
		return( $uri );
	}
	
	 
	/** 
	 * Strip the leading folder information
	 *
	 * We expect uri to be prefixed with a /
	 *
	 * So what do we do for the default request for the home page?
	 *
	 */
	public function strip_siteurl_path( $uri ) {
		$siteurl = site_url();
		$site_url = parse_url( $siteurl );
		$strip_path = bw_array_get( $site_url, 'path', null );
		$strip_path .= "/";
		bw_trace2( $strip_path, "strip_path" );
		if ( 0 === strpos( $uri, $strip_path ) ) {
			$uri = substr( $uri, strlen( $strip_path ) );
		}
		return( $uri );
	}
	
	/**
	 * Can we get these from the 'request' filter? 
	 * 
	 * If the method is "POST" then we need to obtain the values from the $_REQUEST
	 * 
	 */
	public function get_query_parms() {
		$request_uri = bw_array_get( $_SERVER, 'REQUEST_URI', null );
		$url = parse_url( $request_uri );
		$query = bw_array_get( $url, 'query', null );
		//$query_parms = parse_args(
		$query = serialize( $_REQUEST );
		
		return( $query );
	}
	 
	/**
	 * Load the "oik_request" by name
	 * 
	 * @return object an "oik_request" post type object
	 */ 
	public function get_request_by_name() {
		oik_require( "includes/bw_posts.inc" );
		$atts = array( "post_type" => "oik_request" 
								 , "meta_key" => "_oik_rq_uri"
								 , "meta_value" => $this->get_uri()
								 , "numberposts" => 1
								 );
		$post = bw_get_posts( $atts );
		return( $post );
	}
	
	/**
	 * Create the oik_request post
	 * 
	 *  
	 */
	public function insert_post() {
		$uri = $this->get_uri();
		$post = array( "post_type" => "oik_request" 
								, "post_title" => $this->get_post_title( $uri )
								, "post_name" => $uri
								, "post_content" => $this->get_content()
								, "post_status" => "publish"
								, "comment_status" => "closed" 
								); 
		$_POST['_oik_rq_uri'] = $uri;
		$_POST['_oik_rq_parms'] = $this->get_query_parms();
		$_POST['_oik_fileref' ] = $this->get_fileref(); 
		$_POST['_oik_rq_method'] = $this->get_method();
		
		$_POST['_oik_rq_files'] = $this->get_files();
		$_POST['_oik_rq_hooks'] = $this->get_hooks();
		
		
	  $post_id = wp_insert_post( $post );
		$this->get_yoastseo( $post_id );
		return( $post_id );
	}
	
	/**
	 * Record the request
	 *
	 * 1. Take a snapshot of the current status for files and hooks - must be v. quick
	 * 2. See if the request has already been recorded
	 * 3. If not then create a new one
	 * 4. Else...
	 * 
	 * The oik_request consists of:
	 *
	 * Field        | Value
	 * ----------   | --------------
	 * post_title   | from wp_title? 
	 * post_parent  | see what we did for oik_files
	 * post_content |	see get_content()
	 *
	 * 
	 * 
	 */
	public function record_request() {
		$post = $this->get_request_by_name();	
		
		bw_trace2( $post, "post" );
		if ( $post ) {
			// perhaps we need to update it
			// We should only update if the option to update is set to true
			
		}	else {
			$this->insert_post();
		}
	}
	/**
	 * Implements logic to automatically create records for the requests executed in the server.
	 * 
	 * The $_SERVER global variable contains information that we need to record as part of the post_meta data for an "oik_request"
	 * 
	 * These are the interesting values we need to record
	 *
	 * Key             | Example                        | Notes
	 * --------------- | ------------------------------ | --------  
	 * REQUEST_URI     | /wp-admin/?rank=top&dog=sosage | 
	 * SCRIPT_NAME     | /wp-admin/index.php            |	Convert to _oik_fileref
	 * QUERY_STRING    | rank=top&dog=sosage						| Convert to array or CSV with values or serialized array
	 * REQUEST_METHOD  | GET
	 * 
	 * SERVER_PROTOCOL | HTTP/1.1
	 */
	public function register_fields() {
		$post_type = "oik_request";
	
		//add_post_type_support( $post_type, 'publicize' );
		bw_register_field( "_oik_rq_uri", "text", "Request URI" );
		bw_register_field( "_oik_rq_parms", "textarea", "Query parameters" );
		bw_register_field( "_oik_rq_method", "select", "Request method" );
		
		/*
		 * These need to be paginatable shortcode textarea fields.
		 * 
		 */
		bw_register_field( "_oik_rq_files", "textarea", "Files loaded", array( '#theme' => false) ); 
		bw_register_field( "_oik_rq_hooks", "sctextarea", "Hooks invoked", array( '#theme' => false ) );
		bw_register_field( "_oik_rq_queries", "sctextarea", "Saved queries" );
		
		//bw_register_field( "_oik_rq_fileref", "noderef", 	- registered by oik-shortcodes
  
		bw_register_field_for_object_type( "_oik_rq_uri", $post_type );
		bw_register_field_for_object_type( "_oik_rq_parms", $post_type );
		bw_register_field_for_object_type( "_oik_rq_method", $post_type );
		
		bw_register_field_for_object_type( "_oik_rq_files", $post_type );
		bw_register_field_for_object_type( "_oik_rq_hooks", $post_type );
		bw_register_field_for_object_type( "_oik_rq_queries", $post_type );
		bw_register_field_for_object_type( "_oik_fileref", $post_type );
	}
	
	
	/**
	 * Return the title for the post
	 *
	 * Can we get this from wp_title() ?
	 * @TODO Which way round should it be? 'nicename - uri' or 'uri - nicename'
	 * 
	 * @param string $uri optional request URI 
	 * @return string $post_title
	 */
	public function get_post_title( $uri=null ) {
		$post_title = wp_title();
    $post_title .= $uri;
		return( $post_title );
	}
	
	
	/**
	 * Return the content for the oik_request post type
	 * 
	 * @return string possibly quite a lot of content
	 * 
	 */
	public function get_content() {
		$content= null;
		$content .= $this->get_post_title();
		$content .= "<!-- more -->";
		$content .= "[bw_fields]";
		//$content .= /// whatever oik-bwtrace does for files
		//$content .= /// whatever oik-bwtrace does for hooks
		//$content .= /// Saved queries as well
		// 
		return $content;
	}
	
	/**
	 * Set fields specifically for Yoast SEO
	 * 
	 * WordPress SEO aka YoastSEO has a number of fields which, if not set
	 * it goes away and attempts to determine from the content and excerpt.
	 * This is time consuming at front-end runtime so we need to set the values ourselves.
	 *
	 */ 
	public function get_yoastseo( $id ) {
		$metadesc = $this->get_post_title();
		update_post_meta( $id, "_yoast_wpseo_metadesc", $metadesc );
	}
	
	/**
	 * Find the fileref for this request
	 * 
	 * If we don't find the file then should we abort creation of the request?
	 * OR is this needed for handling requests such as robots.txt and certain generated .css files?
	 * 
	 * @return ID $post_id if found else null
	 */
	public function get_fileref() {
		$name = $_SERVER['SCRIPT_NAME']; 	
		$name = $this->strip_siteurl_path( $name );
		oik_require( "admin/oik-files.php", "oik-shortcodes" );																					
		$post_id = oiksc_get_oik_fileref( null, $name ); 
		return( $post_id );
	}
	
	/**
	 * Return the REQUEST_METHOD
	 */
	public function get_method() {
		$method = bw_array_get( $_SERVER, "REQUEST_METHOD", null );
		return( $method );
	}
	
	/**
	 * Return the [files] shortcode for this instance
	 *
	 * See oik-bwtrace
	 */
	
	public function get_files() {
		if ( function_exists( "bw_trace_get_included_files" ) ) {

			$files = bw_trace_get_included_files();
		} else {
			$files = null;
		}
		return( $files );
	}
	
	
	/**
	 * Return the "hooks" shortcode
	 * 
	 */
	public function get_hooks() {
		if ( function_exists( "bw_trace_get_hook_links" ) ) {
			global $bw_action_counts_tree;
			$hooks = bw_trace_get_hook_links( $bw_action_counts_tree );
		} else {
			$hooks = null;
			gob();
		}
		return( $hooks );
	}
	
	
	/**
	 * Shutdown logic when processing oik-requests in batch
	 *
	 *  
	 * 
	 */
	public function run_shutdown() {
		
		$admin_urls = array_unique( $this->admin_urls );
		sort( $admin_urls );
		$admin_urls = PHP_EOL . implode( PHP_EOL, $admin_urls );
    bw_trace2( $admin_urls, "admin_urls", false );
	}
	

}
 
