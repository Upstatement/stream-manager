<?php
/**
 * TimberStream
 *
 * @package   TimberStream
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 *
 * Usage:
 * > $stream = new TimberStream( $pid );
 * > foreach ( $stream->get_posts() as $post ) {
 * >   echo ( $post->title );
 * > }
 */

class TimberStream extends TimberPost {

  /**
   * Stream post cache.
   *
   * This will only be populated when TimberStream::get_posts
   * is run without a $query argument.
   *
   * @since     1.0.0
   *
   * @var       array
   */
  public $posts;

  /**
   * @since     1.0.0
   * @var       array
   */
  public $sm_query = array();

  /**
   * Default stream options, used when creating a
   * new stream.
   *
   * @since    1.0.0
   * @var      array
   */
  public $default_options = array(
    'query' => array(
      'post_type'           => 'post',
      'post_status'         => 'publish',
      'has_password'        => false,
      'ignore_sticky_posts' => true,
      'posts_per_page'      => 100,
      'orderby'             => 'post__in'
    ),

    'stream'  => array(),
    'layouts' => array(
      'active' => 'default',
      'layouts' => array(
        'default' => array(
          'name' => 'Default',
          'zones' => array()
        )
      )
    )
  );

  /**
   * Stream options.
   * This is set by __construct based on what is stored
   * in the database.
   *
   * @since    1.0.0
   *
   * @var      array
   */
  public $options = null;

  /**
   * Init Stream object
   *
   * @param integer|boolean|string  $pid  Post ID or slug
   *
   * @todo  allow creating a TimberStream w/out database
   */
  public function __construct($pid = null) {
    parent::__construct($pid);
    if ($this->post_type !== 'sm_stream') {
      throw new Exception("TimberStream of $pid is not of sm_stream post type");
    }
    if ( !$this->post_content ) $this->post_content = serialize(array());
    $this->options = array_merge( $this->default_options, unserialize($this->post_content) );
    $this->options['query'] = apply_filters('stream-manager/query', $this->options['query']);
    $this->options = apply_filters( 'stream-manager/options/id=' . $this->ID, $this->options, $this );
    $this->options = apply_filters( 'stream-manager/options/'.$this->slug, $this->options, $this );

    $taxes = apply_filters( 'stream-manager/taxonomy/'.$this->slug, array(), $this );
    if (is_array($taxes) && !empty($taxes)) {
      $taxes = StreamManagerUtilities::build_tax_query($taxes);
      if (isset($this->options['query']['tax_query'])) {
        $this->options['query']['tax_query'] = array_merge($this->options['query']['tax_query'], $taxes);
      } else {
        $this->options['query']['tax_query'] = $taxes;
      }
    }
  }

  /**
   * Get filtered & sorted collection of posts in the stream
   *
   * @since    1.0.0
   *
   * @param    array   $query      WP_Query query argument
   * @param    string  $PostClass  Timber post class
   *
   * @return   array   collection of TimberPost objects
   */
  public function get_posts($query = array(), $PostClass = 'TimberPost') {
    $cache = ( empty($query) || !is_array($query) ) ? true : false;

    if ( $cache && !empty($this->posts) ) return $this->posts;

    // Create an array of just post IDs
    $query = array_merge( $this->get('query'), $query );
    $query['post__in'] = array();
    foreach ( $this->get('stream') as $item ) {
      $query['post__in'][] = $item['id'];
    }
    if( isset( $query['post__not_in'] ) && is_array( $query['post__not_in'] ) ){
      $query['post__in'] = array_diff( $query['post__in'], $query['post__not_in'] );
      unset( $query['post__not_in'] );
    }
    
    $posts_orig = Timber::get_posts($query, $PostClass);
    $post_ids = array_map(create_function('$post', 'return $post->ID;'),$posts_orig);

    //get posts that have been added via search that fall outside the tax rules
    $saved_posts = $this->get_posts_without_tax_query($query);
    $extra = array_diff($saved_posts, $post_ids);
    $all_ids = array_merge($extra,$post_ids);

    //use the stream to put posts back in order
    foreach($this->get('stream') as $item) {
      if(in_array($item['id'], $all_ids)) {
        $posts[] = new $PostClass($item['id']);
      }
    }

    if (empty($posts)) {
      // if the user has re-configured the feed we might need to blow out the saved items to make way for the fresh query;
      unset($query['post__in']);
      $posts = Timber::get_posts($query, $PostClass);
    }
    $pinned = array_keys($this->filter_stream('pinned', true));

    foreach ($posts as &$post) {
      $post->pinned = in_array( $post->ID, $pinned );
    }

    if ( $cache ) $this->posts = $posts;

    return $posts;
  }

  /**
  * Get the ids of all saved posts, including any removed by the taxonomy query
  *
  */
  public function get_posts_without_tax_query($query, $PostClass = 'TimberPost') {

    // Remove any taxonomy limitations, since those would remove any
    // posts from the stream that were added by searching in the UI.
    unset($query['tax_query']);

    $all_posts = Timber::get_posts($query, $PostClass);
    $postids = array_map(create_function('$post', 'return $post->ID;'),$all_posts);

    return $postids;

  }

  /**
   * Filter posts in the stream, returning only the filtered
   * posts (including their position).
   *
   * @since     1.0.0
   *
   * @return    array  filtered posts
   */
  public function filter_stream($attribute, $value) {
    $items = array();

    foreach ( $this->get('stream') as $position => $item ) {
      $item['position'] = $position;
      if ( $item[$attribute] == $value ) $items[$item['id']] = $item;
    }

    return $items;
  }


  /**
   * Enforce the stream length.
   *
   * If there are fewer posts than allowed, add some from the base query.
   * If there are more, remove them.
   *
   * @since     1.0.0
   *
   * @todo      it's possible for a pinned item to go above the limit
   */
  public function repopulate_stream() {

    // Determine how many over/under we are
    $query = $this->get('query');
    $difference = count( $this->get('stream') ) - $query['posts_per_page'];

    if ( $difference < 0 ) {

      // Under -- add pinned posts to the end
      $query = $this->get('query');
      $ids = array();
      foreach ( $this->get('stream') as $post ) {
        $ids[] = $post['id'];
      }
      $query['post__not_in'] = $ids;
      $query['posts_per_page'] = $difference * -1;
      $posts = Timber::get_posts($query);

      $this->remove_pinned();

      foreach ( $posts as $post ) {
        $this->options['stream'][] = array(
          'id' => $post->ID,
          'pinned' => false
        );
      }

      $this->reinsert_pinned();

    } else if ( $difference > 0 ) {

      // Over -- remove non-pinned posts at the end
      $this->remove_pinned();
      for ( $i = 1; $i <= $difference; $i++ ) {
        array_pop( $this->options['stream'] );
      }
      $this->reinsert_pinned();

    }
  }


  /**
   * Checks if a post exists in a stream
   *
   * @since     1.0.0
   *
   * @param     integer  $post_id  Post ID
   *
   * @return    array    returns the data saved in the stream, plus its position
   */
  public function check_post ( $post_id ) {
    foreach ( $this->get('stream') as $position => $item ) {
      if ( $item['id'] == $post_id ) {
        $item['position'] = $position;
        return $item;
      }
    }
    return false;
  }

  /**
   * Removes a post from a stream and, by default, fills
   * in the empty space at the end.
   *
   * @since     1.0.0
   *
   * @param     integer  $post_id     Post ID
   * @param     boolean  $repopulate  add/remove posts to enforce stream length
   */
  public function remove_post ( $post_id, $repopulate = true ) {
    $post = $this->check_post( $post_id );
    if ( $post ) {
      $this->remove_pinned();

      // Remove non-pinned
      unset($this->options['stream'][ $post['position'] ]);

      // Remove pinned
      foreach ( $this->pinned as $i => $pinned ) {
        if ( $pinned['id'] == $post_id ) {
          unset( $this->pinned[$i] );
        }
      }
      $this->reinsert_pinned();
      if ( $repopulate ) $this->repopulate_stream();
      $this->save_stream();
    }
  }

  /**
   * Inserts a post in the stream
   *
   * @since     1.0.0
   *
   * @param     integer  $post_id  Post ID
   */
  public function insert_post ( $post_id ) {
    // Does it already exist? If so, remove it, and we'll reinsert it
    if ( $this->check_post( $post_id ) ) {
      $this->remove_post( $post_id, false );
    }

    // Determine where it is in the original query (if at all),
    // minus any pinned items
    $query = array_merge( $this->get('query'), array(
      'post__not_in' => array_keys($this->filter_stream('pinned', true))
    ));
    $posts = Timber::get_posts( $query );

    $in_stream = false;

    foreach ( $posts as $i => $post ) {
      if ( $post->ID == $post_id ) $in_stream = $i;
    }

    // If it's not in the stream, bail
    if ( $in_stream === false ) return;

    // Remove pinned items from the stream...
    $this->remove_pinned();

    // ... then insert this post ...
    array_splice( $this->options['stream'], $in_stream, 0, array( array (
      'id' => $post_id,
      'pinned' => false
    ) ) );

    // ... and then reinsert the pinned items
    $this->reinsert_pinned();
    $this->repopulate_stream();
    $this->save_stream();
  }

  /**
   * Temporarily removes pinned items from the stream, for the
   * purpose of modifying the auto-flowing stream.
   *
   * @since     1.0.0
   */
  public function remove_pinned() {
    $this->pinned = $this->filter_stream('pinned', true);
    foreach ( $this->pinned as $pin ) {
      unset ( $this->options['stream'][ $pin['position'] ] );
    }
  }

  /**
   * Place the pinned items back in the stream in their appropriate
   * locations
   *
   * @since     1.0.0
   */
  public function reinsert_pinned() {
    foreach ( $this->pinned as $pin ) {
      $position = $pin['position'];
      unset( $pin['position'] );
      array_splice( $this->options['stream'], $position, 0, array( $pin ) );
    }
  }


  public function get( $key ) {
    return apply_filters( 'stream-manager/get_option/id=' . $this->ID, $this->options[$key], $key, $this );
  }

  public function set( $key, $value ) {
    $this->options[$key] = apply_filters( 'stream-manager/set_option/id=' . $this->ID, $value, $key, $this );
  }


  /**
   * Save the stream metadata
   *
   * @since     1.0.0
   */
  public function save_stream() {
    $save_data = apply_filters( 'stream-manager/save/id=' . $this->ID, array(
      'ID' => $this->ID,
      'post_content' => serialize($this->options)
    ), $this);
    wp_update_post( $save_data );
  }

}
