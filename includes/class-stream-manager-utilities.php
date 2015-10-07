<?php
/**
 * Stream Manager.
 *
 * @package   StreamManagerUtilities
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 */

/**
 * @package StreamManagerUtilities
 * @author  Chris Voll + Upstatement
 */
class StreamManagerUtilities { 

	public static function build_tax_query( $taxonomies ) {
		$output = array('relation' => 'OR');
		foreach ( $taxonomies as $taxonomy => $terms ) {
			if ( !$terms ) continue;

			$terms = is_array($terms) ? $terms : self::parse_terms( $taxonomy, $terms );
			foreach ( $terms as $i => $term ) {
				if ( empty( $term ) ) unset( $terms[$i] );
			}

			if ( !empty($terms) ) {
				$output[] = array(
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $terms
				);
			}
		}
		return $output;
	}

}