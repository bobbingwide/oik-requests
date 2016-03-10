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
		$uri = bw_array_get( $_SERVER, 'REQUEST_URI', null );
		$uri = $this->strip_siteurl_path( $uri );
		if ( defined( 'DOING_AJAX') && DOING_AJAX ) {
			$uri .= "/";
			$uri .= bw_array_get( $_REQUEST, 'action', null );
		}
		return( $uri );
	}
	
	/** 
	 * Strip the leading folder information
	 *
	 * We expect uri to be prefixed with a /
	 * It may also contain the sub-directory of the installation e.g. /wordpress/wp-admin
	 *
	 * So what do we do for the default request for the home page? Make it a / ?
	 * 
	 * @param string $uri - request URI which may contain the query parameters
	 * @return string the stripped request URI
	 */
	public function strip_siteurl_path( $uri ) {
		$url = parse_url( $uri );
		$uri = $url['path'];
		bw_trace2( $url, "url" );
		$siteurl = site_url();
		$site_url = parse_url( $siteurl );
		$strip_path = bw_array_get( $site_url, 'path', null );
		//$strip_path .= "/";
		bw_trace2( $strip_path, "strip_path", false );
		
		$pos = strpos( $uri, $strip_path );
		if ( $pos === 0 ) {
			$len = strlen( $strip_path );
			$uri = substr( $uri, $len );
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
	public function get_request_by_name( $uri ) {
		oik_require( "includes/bw_posts.inc" );
		$atts = array( "post_type" => "oik_request" 
								 , "meta_key" => "_oik_rq_uri"
								 , "meta_value" => $uri
								 , "numberposts" => 1
								 );
		$post = bw_get_posts( $atts );
		$post = bw_array_get( $post, 0, null );
		return( $post );
	}
	
	/**
	 * Create the oik_request post
	 * 
	 *  
	 */
	public function insert_post( $uri, $parent ) {
		bw_trace2();
		$post = array( "post_type" => "oik_request" 
								, "post_title" => $this->get_post_title( $uri )
								, "post_name" => $uri
								, "post_content" => $this->get_content()
								, "post_status" => "publish"
								, "comment_status" => "closed" 
								, "post_parent" => $parent
								); 
		$_POST['_oik_rq_uri'] = $uri;
		$_POST['_oik_rq_parms'] = $this->get_query_parms();
		$_POST['_oik_fileref' ] = $this->get_fileref(); 
		$_POST['_oik_rq_method'] = $this->get_method();
		
		$_POST['_oik_rq_files'] = $this->get_files();
		$_POST['_oik_rq_hooks'] = $this->get_hooks();
		$_POST['_oik_rq_queries'] = $this->get_queries();
		
		
	  $post_id = wp_insert_post( $post );
		$this->get_yoastseo( $post_id );
		return( $post_id );
	}
	
	/**
	 * Update the oik_request post
	 * 
	 * We have to decide what we're going to update without doing too many queries.
	 * When it's all working fine then this may not be all that necessary
	 * except when something has changed which would cause the results to be different.
	 * 
	 * It's the parms that we need to merge with the existing parms
	 * And the method may also take multiple values: GET, POST, HEAD, etc..
	 * especially in the wonderful world of the REST API
   * 
	 * @param object $post the post object
	 */
	function update_post( $post ) {
		bw_trace2();
	
		//$_POST['_oik_rq_parms'] = $this->get_query_parms();
		//$_POST['_oik_fileref' ] = $this->get_fileref(); 
		$_POST['_oik_rq_method'] = $this->get_method();
		
		$_POST['_oik_rq_files'] = $this->get_files();
		$_POST['_oik_rq_hooks'] = $this->get_hooks();
		$_POST['_oik_rq_queries'] = $this->get_queries();
		$_POST['_oik_rq_hits'] = $this->get_hits( $post->ID ) +1;
		wp_update_post( $post );
		$this->get_yoastseo( $post->ID );
	}	
	
	
	
	/**
	 * Record the request
	 *
	 * 1. Take a snapshot of the current status for files and hooks - must be v. quick
	 * 2. See if the request has already been recorded
	 * 3. If not then create a new one
	 * 4. Else update it
	 * 
	 * The oik_request consists of:
	 *
	 * Field        | Value
	 * ----------   | --------------
	 * post_title   | from wp_title? 
	 * post_parent  | similar to oik_files
	 * post_content |	see get_content()
	 *
	 * 
	 * 
	 */
	public function record_request() {
	
		$uri = $this->get_uri();
		$post = $this->get_request_by_name( $uri );	
		$parent = $this->create_ancestry( $post, $uri );
		
		bw_trace2( $post, "post" );
		if ( $post ) {
			// perhaps we need to update it
			// We should only update if the option to update is set to true
			$post->post_parent = $parent;
			$this->update_post( $post );
			
		}	else {
			$this->insert_post( $uri, $parent );
		}
	}
	
	/**
	 * Create the post's parent
	 * 
	 * @param string $uri part of the request URI 
	 */
	public function create_parent( $uri ) {
		bw_trace2();
		$post = $this->get_request_by_name( $uri );
		$parent = $this->create_ancestry( $post, $uri );
		if ( !$post ) {
			$post_id = $this->insert_ancestor( $uri, $parent );	
		} else {
			$post_id = $post->ID;
		}
		return( $post_id );
	}
	
	/**
	 * Insert a dummy ancestor for a request
	 * 
	 * @param string $uri the part of the path
	 * @param 
	 */
	public function insert_ancestor( $uri, $parent ) {
		bw_trace2();
		$post = array( "post_type" => "oik_request" 
								, "post_title" => $this->get_post_title( $uri )
								, "post_name" => $uri
								, "post_content" => $uri
								, "post_status" => "publish"
								, "comment_status" => "closed" 
								, "post_parent" => $parent
								); 
		$_POST['_oik_rq_uri'] = $uri;
		// $_POST['_oik_rq_parms'] = $this->get_query_parms();
		// $_POST['_oik_fileref' ] = $this->get_fileref(); 
		// $_POST['_oik_rq_method'] = $this->get_method();
		
		//$_POST['_oik_rq_files'] = $this->get_files();
		//$_POST['_oik_rq_hooks'] = $this->get_hooks();
		//$_POST['_oik_rq_queries'] = $this->get_queries();
		
		
	  $post_id = wp_insert_post( $post );
		$this->get_yoastseo( $post_id );
		return( $post_id );
	
	
	}
	
	
	
	/**
	 * Determine the post_parent for an oik_request
	 *
	 * Here we will attempt to correct the hierarchy of oik_file posts
	 * so that they appear nicely structured.
	 * 
	 * Assuming we will eventually process every directory
	 * then we probably only need to handle one parent at a time
	 * BUT that assumes we'll process a file in each directory in the tree
	 * 
	 * Processing depends on the values of the parameters passed
	 * 
	 * $post  | $uri 			        | post_parent | Processing
	 * ------ | -------------     | ----------  | -------------- 
	 * 0      | filename.ext      | n/a         | The post_parent will be 0
	 * 0      | path/filename.ext |	n/a         | Find the post_id for dirname( $uri )
	 * set    | filename.ext      | should be 0 | force it to 0
	 * set    | path/filename.ext | 0           | Find the post_id for dirname( $uri ) 
	 *
	 * @param post|null $post an existing post object or null
	 * @param string $uri the request_uri 
	 * @return ID the post_parent ID, which may be 0
	 */
	function create_ancestry( $post, $uri ) {
		//bw_backtrace();
		//bw_trace2();
		$post_parent = 0;
		if ( $post ) {
			$found_parent = $this->request_should_have_parent( $uri, $post->post_parent );
		} else {
			$found_parent = $this->request_should_have_parent( $uri, null );
		} 
		if ( null !== $found_parent ) {
			$post_parent = $found_parent; 
		} else {
			$post_parent = $this->create_parent( dirname( $uri ) );
		}
		bw_trace2( $post_parent, "post_parent", false, BW_TRACE_DEBUG );
		return( $post_parent );
	}



	/**
	 * Deterimine the right value for post_parent
	 *
	 * @param string $uri - the request URI
	 * @param ID $current_parent 
	 * @return found_parent - null when need to find one otherwise the required ID
	 */
	function request_should_have_parent( $uri, $current_parent ) {
		//bw_trace2();
		$uri = ltrim( $uri, "/" );
		if ( false === strpos( $uri, "/" ) ) {
			$found_parent = 0;
		} elseif ( 0 == $current_parent ) {
			$found_parent = null;
		} else {
			$found_parent = $current_parent;
		}
		return( $found_parent );
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
		bw_register_field( "_oik_rq_parms", "serialized", "Query parameters" );
		bw_register_field( "_oik_rq_method", "select", "Request method" );
		
		/*
		 * These need to be paginatable shortcode textarea fields.
		 * 
		 */
		bw_register_field( "_oik_rq_files", "sctextarea", "Files loaded", array( '#theme' => false) ); 
		bw_register_field( "_oik_rq_hooks", "sctextarea", "Hooks invoked", array( '#theme' => false ) );
		bw_register_field( "_oik_rq_queries", "sctextarea", "Saved queries" );
		bw_register_field( "_oik_rq_hits", "numeric", "Hits" );
		
		//bw_register_field( "_oik_rq_fileref", "noderef", 	- registered by oik-shortcodes
  
		bw_register_field_for_object_type( "_oik_rq_uri", $post_type );
		bw_register_field_for_object_type( "_oik_rq_parms", $post_type );
		bw_register_field_for_object_type( "_oik_rq_method", $post_type );
		
		bw_register_field_for_object_type( "_oik_rq_files", $post_type );
		bw_register_field_for_object_type( "_oik_rq_hooks", $post_type );
		bw_register_field_for_object_type( "_oik_rq_queries", $post_type );
		bw_register_field_for_object_type( "_oik_fileref", $post_type );
		bw_register_field_for_object_type( "_oik_rq_hits", $post_type );
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
		//$post_title = wp_title( "-" , false );
		$post_title = $uri;
    //$post_title .= $uri;
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
	
	public function get_hits( $id, $increment=0 ) {
		$hits = get_post_meta( $id, "_oik_rq_hits", true );
		$hits += $increment;
		return( $hits );
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
	 * @return shortcode for all the hooks
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
	 * Return the saved queries
	 * 
	 * @return shortcode for all the queries
	 */
	public function get_queries() {
		if ( function_exists( "bw_trace_get_saved_queries" ) ) {
			$queries = bw_trace_get_saved_queries();
		} else {
			$queries = null;
		}
		return( $queries );
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
 
