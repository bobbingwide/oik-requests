<?php // (C) Copyright Bobbing Wide 2016

/**
 * Implement [bw_sql] shortcode to display saved queries
 * 
 * `
 * [bw_sql # elapsed function]SQL[/bw_sql]
 * `
 * 
 * @param array $atts shortcode attributes
 * @param string $content
 * @param string $tag
 * @return string generated HTML
 *
 */
function bw_sql( $atts=null, $content=null, $tag="bw_sql" ) {
	$number = bw_array_get( $atts, 0, null );
	$elapsed = bw_array_get( $atts, 1, null );
	$function = bw_array_get( $atts, 2, null );
	sdiv( "bw_sql" );
	span( "number ");
	
  //bw_register_field( "numeric", "text", "Number" );
	bw_theme_field( "numeric", $number, "Number" );
	epan( "number" );
	
	span( "time" );
	bw_theme_field( "time", $elapsed, "Elapsed" );
	epan();
	bw_sql_theme_apis( $function );
	bw_theme_field_sql( $content ); 
	ediv();
	return( bw_ret() );
}

/**
 * Theme the function links 
 * 
 * @param string $functions string of functions separated by ;
 */
function bw_sql_theme_apis( $functions ) {
	$function_array = explode( ";", $functions );
	if ( count ( $function_array ) ) {
		foreach ( $function_array as $function ) {
			bw_sql_theme_api( $function ); 
		}
	}	
}

/**
 * Theme the function link
 *
 * @param string $function
 */
function bw_sql_theme_api( $function ) {
	span( "api" );
	$url = site_url( "/oik_api/$function" );
	alink( null, $url, $function );
	epan();
}

/**
 * Theme a field containing SQL
 * 
 * @TODO Check if the [bw_geshi] shortcode exists and supports mysql ?
 * Note: See oik-css
 * 
 * @param string $content
 */
function bw_theme_field_sql( $content ) {
	span( "sql" );
	$sql = bw_do_shortcode( "[bw_geshi mysql]{$content}[/bw_geshi]" );
	//$sql = $content;
	e( $sql );
	epan();
}
