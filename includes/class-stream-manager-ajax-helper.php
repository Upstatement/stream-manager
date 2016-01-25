<?php
/**
 * Stream Manager.
 *
 * @package   StreamManagerAjaxHelper
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 */

/**
 * @package StreamManagerAjaxHelper
 * @author  Chris Voll + Upstatement
 */

class StreamManagerAjaxHelper { 

	public static function retrieve_posts($queue) {
		$output = array();

		foreach($queue as $i => $item) {
			$post = new TimberPost( $item['id'] );
			if ( !$post ) continue;
			$post->pinned = false;
			$output[ $item['id'] ] = array(
				'position' => $item['position'],
				'object' => Timber::compile('views/stub.twig', array(
					'post' => $post
				))
			);
		}
		return $output;
	}

	public static function search_posts($query) {
		$posts = Timber::get_posts(array(
			's' => $query,
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => 10
		));

		$output = array();

		foreach ( $posts as $post ) {
			$output[] = array(
				'id' => $post->ID,
				'title' => $post->title,
				'date' => $post->post_date,
				'human_date' => human_time_diff( strtotime( $post->post_date ) )
			);
		}
		return $output;
	}

}