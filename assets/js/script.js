/**
 * Feed Manager Admin JavaScript
 *
 * @package   FeedManager
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 */

jQuery(function($) {

  // Setup
  var $feed = $('.fm-posts');


  ////////////////////////////////////////////
  // 
  // Heartbeat
  //
  // Avoid feed collisions by loading feed
  // updates from the database. For the purpose
  // of more accurate placements, pinned posts
  // are excluded from the list of IDs that
  // are passed around.
  // 
  ////////////////////////////////////////////

  // Hook into the heartbeat-send
  $(document).on('heartbeat-send', function(e, data) {
    
  });

  // Listen for the custom event "heartbeat-tick" on $(document).
  var tmp_ids = $feed.attr('data-ids');

  $(document).on( 'heartbeat-tick', function(e, data) {

    if ( data.fm_feed_ids !== $feed.attr('data-ids') ) {
      console.log('discrepency!!!!');
      tmp_ids = data.fm_feed_ids;

      var front = $feed.attr('data-ids').split(',');
      var back  = data.fm_feed_ids.split(',');

      // Published posts
      for (i in back) {
        if ( $.inArray(back[i], front) < 0 ) {
          console.log('new: ' + back[i]);
          post_queue.insert(back[i], i);
        }
      }

      // Deleted posts
      for (i in front) {
        if ( $.inArray(front[i], back) < 0 ) {
          console.log('deleted: ' + front[i]);
          post_queue.remove(front[i]);
        }
      }

    } else {
      console.log('no discrepency');
    }
  });


  ////////////////////////////////////////////
  // 
  // Post Manipulation
  // 
  ////////////////////////////////////////////

  // Pin Post
  var pin_post = function(e) {
    e.preventDefault();

    var stub = $(this);
    if (!stub.hasClass('stub')) stub = $(this).closest('.stub');

    if ( stub.hasClass('fm-pinned') ) {
      stub.removeClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', false);
    } else {
      stub.addClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', true);
    }
  };

  // (by clicking the pin, or double clicking on the stub)
  $('.fm-posts').on('click',    '.pin-unpin', pin_post);
  $('.fm-posts').on('dblclick', '.stub',      pin_post);


  // Remove Post
  // @TODO: shift this into post_queue to account for pinned posts
  var remove_post = function(e) {
    e.preventDefault();

    var object = $(this).closest('.stub');
    undo_cache.push({
      position: object.index(),
      object: object
    });
    $(object).remove();
  };

  $('.fm-posts').on('click', '.remove', remove_post);


  // Reorder Post
  $('.fm-posts').sortable({
    start: function(event, ui) {
      $(document).trigger('fm/sortable_start', ui.item);
      $(ui.placeholder).height($(ui.item).height());
    },
    stop: function(event, ui) {
      $(document).trigger('fm/sortable_stop', ui.item);
    },
    axis: 'y'
  });


  ////////////////////////////////////////////
  // 
  // Post insertion queue
  //
  // Provides a general API for use by the
  // collision management system (heartbeat)
  // and search feature.
  //
  // See the UI in the next section.
  //
  // -----------------------------------------
  //
  // Queue usage:
  // - post_queue.insert( post_id, position0 );
  // - post_queue.remove( post_id );
  //
  // Apply changes:
  // - post_queue.retrieve_posts();
  //
  // Add a single post without invoking the queues:
  // - post_queue.retrieve_posts({ [post_id]: [position0] }, false);
  // 
  ////////////////////////////////////////////

  var post_queue = {

    queue: {},
    remove_queue: {},

    /**
     * Add a post to the queue.
     * If it already exists, update the position
     */
    insert: function (id, position) {
      this.queue[id] = position;
      $(document).trigger('fm/post_queue_update', [ this.queue, this.remove_queue ]);
    },

    /**
     * Queue up a post for removal
     */
    remove: function (id) {
      this.remove_queue[id] = id;
      $(document).trigger('fm/post_queue_update', [ this.queue, this.remove_queue ]);
    },

    /**
     * Retrieve rendered post stubs HTML from the database
     *
     * - queue: optional, override the built-in queue. Useful
     *          for inserting single posts (e.g., from search).
     *          Set to false/null to use this.queue
     * - remove_queue: optional, override built-in removal queue.
     *          Set to false to not remove posts.
     */
    retrieve_posts: function (queue, remove_queue) {
      $(document).trigger('fm/post_queue_updating');

      if ( !queue ) queue = this.queue;
      if ( remove_queue === null ) remove_queue = this.remove_queue;

      if ( _.keys(queue).length > 0 ) {
        var request = {
          action: 'fm_feed_request',
          queue: queue
        };

        var that = this;

        $.post(ajaxurl, request, function (response) {
          var data = JSON.parse(response);
          if ( data.status && data.status == 'error' ) return;
          that.update_feed.call( that, data.data, remove_queue );
          $(document).trigger('fm/post_queue_update', [ that.queue, that.remove_queue ] );
        });
      } else {
        this.update_feed.call( this, false, remove_queue );
        $(document).trigger('fm/post_queue_update', [ this.queue, this.remove_queue ] );
      }
    },

    /**
     * Inserts/removes posts in feed
     *
     * insert_data: comes from retrieve_posts AJAX
     * remove_queue: comes from retrieve_posts
     *
     * @TODO: Post removal functionality
     * @TODO: Avoid duplication
     */
    update_feed: function (insert_data, remove_queue) {

      this.remove_pinned();

      // Insert new posts
      if ( insert_data ) {
        for ( id in insert_data ) {
          if ( insert_data[id]['object'] ) {
            this.inject( insert_data[id]['position'], insert_data[id]['object'] );
          }
          delete this.queue[id];
        }
      }

      // Remove deleted posts (+ pinned ones)
      if ( remove_queue ) {
        for ( id in remove_queue ) {
          $feed.find('#post-' + id).remove();
          delete this.remove_queue[id];
          delete this.pinned_cache[id];
        }
        this.delete_pinned( remove_queue );
      }

      this.insert_pinned();
    },

    /**
     * Inserts one post into the feed
     */
    inject: function (position, object) {
      if ( position == 0 ) {
        $feed.prepend( object );
      } else {
        $feed.find( '.stub:nth-child(' + position + ')' ).after( object );
      }
    },

    /**
     * Helpers for ensuring pinned items stay in place,
     * and for deleting pinned items altogether
     */
    pinned_cache: [],
    remove_pinned: function () {
      var that = this;
      $feed.find('.stub').each( function (i) {
        if ( $(this).hasClass('fm-pinned') ) {
          var id = $(this).attr('data-id');
          that.pinned_cache.push({
            id: id,
            obj: this,
            position: i
          });
          $(this).remove();
        }
      });
    },
    delete_pinned: function (remove_queue) {
      for (i in this.pinned_cache) {
        if ( remove_queue[ this.pinned_cache[i].id ] ) {
          console.log( remove_queue, this.pinned_cache, this.pinned_cache[i] );
          delete this.pinned_cache[i];
          delete this.remove_queue[ this.pinned_cache[i].id ];
        }
      }
    },
    insert_pinned: function () {
      for (i in this.pinned_cache) {
        this.inject(
          this.pinned_cache[i].position,
          this.pinned_cache[i].obj
        );
      }
      this.pinned_cache = [];
    }

  };

  window.post_queue = post_queue;


  ////////////////////////////////////////////
  // 
  // Post queue UI
  //
  // Listens for the post_queue events to update
  // the user interface, letting the end user
  // know when there are changes.
  // 
  ////////////////////////////////////////////

  var $queue = $('.post-queue-alert');

  $(document).on('fm/post_queue_update', function( e, queue, remove_queue ) {
    var queue_length        = _.keys(queue).length;
    var remove_queue_length = _.keys(remove_queue).length;

    if ( (queue_length + remove_queue_length) > 0 ) {
      $queue.show();
      var text = [
        '<span class="dashicons dashicons-plus"></span> '
      ];

      if ( queue_length == 1 ) {
        text.push('There is 1 new post. ');
      } else if ( queue_length > 1 ) {
        text.push('There are ' + queue_length + ' new posts. ');
      }

      if ( remove_queue_length == 1 ) {
        text.push('There is 1 post that was deleted. ');
      } else if ( remove_queue_length > 1 ) {
        text.push('There are ' + remove_queue_length + ' posts that were deleted. ');
      }

      $queue.html(text.join(""));
    } else {
      $queue.hide();
    }
  });

  $queue.on('click', function(e) {
    post_queue.retrieve_posts(post_queue.queue, post_queue.remove_queue);
    $feed.attr('data-ids', tmp_ids);
  });

});

// Remove undo
// @TODO: move this somewhere else
var undo_cache = [];

var undo_remove = function() {
  var object = undo_cache.pop();
  if (!object) return;
  var container = jQuery('.fm-posts');

  if (object.position == 0) {
    container.prepend(object.object);
  } else {
    container.find('.stub:nth-child(' + object.position + ')').after(object.object);
  }
}
