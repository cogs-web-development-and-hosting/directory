<?php

global $bp, $wp_query, $post,$paged;

$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

$original_query = $wp_query;

$query_args = array(
'post_type' => 'directory_listing',
'post_status' => 'publish',
'paged' => $paged,
);

//setup taxonomy if applicable
$tax_key = (empty($wp_query->query_vars['taxonomy'])) ? '' : $wp_query->query_vars['taxonomy'];
$taxonomies = array_values(get_object_taxonomies($query_args['post_type'], 'names') );

if ( in_array($tax_key, $taxonomies) ) {
	$query_args['tax_query'] = array(
	array(
	'taxonomy' => $tax_key,
	'field' => 'slug',
	'terms' => get_query_var( $tax_key),
	)
	);
}
/*
$query_args['meta_key'] = '_sr_post_rating';
$query_args['orderby'] = 'meta_value_num';
$query_args['order'] = 'DESC';
*/

//Remove the archive title filter for the individual listings
remove_filter( 'the_title', array( &$this, 'page_title_output' ), 10 , 2 );
remove_filter( 'the_content', array( &$this, 'listing_list_theme' ) );

query_posts($query_args);

if ( file_exists( get_template_directory() . "/loop-author.php" ) )
get_template_part( 'loop', 'author' );
else
include DR_PLUGIN_DIR . 'ui-front/general/loop-taxonomy.php';

wp_reset_query();