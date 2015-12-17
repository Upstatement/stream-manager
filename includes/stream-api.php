<?php 

/**
 * Check if a stream is in the database
 *
 * @param string $slug
 * 
 * @return bool 
 */
function sm_stream_exists( $slug ) {
	$posts = get_posts( array( 'post_type' => 'sm_stream', 'name' => $slug ) );
	if ( count( $posts ) ) { return true; }
	else { return false; }
}

/**
 * Insert a new stream, with the option to pass a wp_query array to filter the stream.
 * Returns false if the stream already exists.
 *
 * @param string $slug
 * @param string $title
 * @param array $query_array wp_query object
 *
 * @return int $pid ID of new stream
 */
function sm_insert_stream( $slug, $title = NULL, $query_array = NULL ) {

	if( sm_stream_exists( $slug ) ) {
		return false;
	}

	$post_title = $title?: $slug;
	$args = array(
		'post_type' 	=> 'sm_stream',
		'post_name' 	=> $slug,
		'post_title'	=> $post_title,
		'post_status'	=> 'publish'
	);
	$pid = wp_insert_post($args);

	if ( $query_array ) {
		add_filter('stream-manager/options/'.$slug, function($defaults) use ($query_array) {
		  $defaults['query'] = array_merge( $defaults['query'], $query_array );
		  return $defaults;
		});
	}	

	return $pid;
}

/**
 * Delete a stream by slug
 *
 * @param string $slug
 * 
 * @return int $deleted ID of deleted stream
 */
function sm_delete_stream( $slug ) {
	$posts = get_posts( array( 'post_type' => 'sm_stream', 'name' => $slug ) );
	if( $posts ) {
		$post = $posts[0];
		$deleted = wp_delete_post( $post->ID );
		return $deleted;
	}	
}




