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

  var tmp_ids    = $feed.attr('data-ids');
  var tmp_pinned = $feed.attr('data-pinned');

  $(document).on( 'heartbeat-tick', function(e, data) {

    if ( data.fm_feed_ids !== $feed.attr('data-ids') || data.fm_feed_pinned !== $feed.attr('data-pinned') ) {
      tmp_ids    = data.fm_feed_ids;
      tmp_pinned = data.fm_feed_pinned;

      var front = $feed.attr('data-ids').split(',');
      var back  = data.fm_feed_ids.split(',');

      var front_pinned = $feed.attr('data-pinned').split(',');
      var back_pinned  = data.fm_feed_pinned.split(',');

      // Published posts
      for ( i in back ) {
        if ( $.inArray(back[i], front) < 0 ) {
          feed.add_to_queue( 'insert', back[i], i );
        }
      }

      // Deleted posts
      for ( i in front ) {
        if ( $.inArray(front[i], back) < 0 ) {
          feed.add_to_queue( 'remove', front[i] );
        }
      }

      // Deleted pinned posts
      for ( i in front_pinned ) {
        if ( $.inArray(front_pinned[i], back_pinned) < 0 ) {
          feed.add_to_queue( 'remove', front_pinned[i] );
        }
      }
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
  var remove_post = function(e) {
    e.preventDefault();

    var id = $(this).closest('.stub').attr('data-id');
    feed.remove_single( id );
  };

  $('.fm-posts').on('click', '.remove', remove_post);


  // Reorder Post
  $('.fm-posts').sortable({
    start: function (event, ui) {
      if (ui.item.hasClass('fm-pinned')) return;
      $(document).trigger('fm/sortable_start', ui.item);
      $(ui.placeholder).height($(ui.item).height());
      feed.inventory_pinned();
    },
    change: function (event, ui) {
      if (ui.item.hasClass('fm-pinned')) return;
      feed.remove_pinned();
      feed.insert_pinned();
    },
    axis: 'y'
  });


  ////////////////////////////////////////////
  // 
  // Feed Post Queue
  //
  // API for modifying the posts in the feed
  // user interface, including adding and
  // removing by post IDs. Used by the collision
  // management system (with the WordPress
  // heartbeat API) and the post search.
  //
  // -----------------------------------------
  //
  // Usage: (where queue_name is 'insert' or 'remove')
  // > feed.add_to_queue( queue_name, post_id, position );
  // > feed.remove_from_queue( queue_name, post_id );
  //
  // Apply changes:
  // > feed.apply_insert( queue_override );
  // > feed.apply_remove( queue_override );
  //
  // Add a single post without invoking the queues:
  // > feed.insert_single( id, position );
  // > feed.remove_single( id );
  // 
  ////////////////////////////////////////////

  var feed = {

    insert_queue: [],
    remove_queue: [],

    /**
     * Add a post to a queue
     */
    add_to_queue: function ( queue, id, position ) {
      if ( queue == 'insert' ) {
        if ( this.post_exists(id) || this.is_in_queue('insert', id) !== -1 ) return;
        this.insert_queue.push({
          id: id,
          position: position
        });
      } else if ( queue == 'remove' ) {
        if ( !this.post_exists(id) || this.is_in_queue('remove', id) !== -1 ) return;
        this.remove_queue.push({
          id: id
        });
      }
      $(document).trigger('fm/feed_update');
    },

    /**
     * Remove a post from a queue
     */
    remove_from_queue: function ( queue, id ) {
      var queue_name = queue + '_queue';

      for ( i in this[queue_name] ) {
        if ( this[queue_name][i].id == id ) {
          this[queue_name].splice(i, 1);
        }
      }
      $(document).trigger('fm/feed_update');
    },

    /**
     * Checks if a post is in a queue
     *
     * @return -1 if not found, position if found
     */
    is_in_queue: function ( queue, id ) {
      var queue_name = queue + '_queue';

      for ( i in this[queue_name] ) {
        if ( this[queue_name][i].id == id ) {
          return i;
        }
      }
      return -1;
    },


    /**
     * Apply queue changes
     */
    apply_insert: function ( queue_override ) {
      var queue = queue_override ? queue_override : this.insert_queue;
      if (queue.length < 1) return;
      var that = this;

      $.post(ajaxurl, {
          action: 'fm_feed_request',
          queue: queue
        }, function (response) {
          response = JSON.parse(response);
          if ( response.status && response.status == 'error' ) return;
          that.ui_insert(response.data);
          $(document).trigger('fm/feed_update');
        }
      );
    },
    apply_remove: function ( queue_override ) {
      var queue = queue_override ? queue_override : this.remove_queue;
      if (queue.length < 1) return;
      this.ui_remove( queue );
      $(document).trigger('fm/feed_update');
    },
    apply_queues: function () {
      this.apply_remove();
      this.apply_insert();
    },



    /**
     * Insert/remove post(s) in the UI
     */
    ui_insert: function ( insert_data ) {
      if ( !insert_data ) return;

      this.inventory_pinned();
      this.remove_pinned();

      for ( id in insert_data ) {
        if ( insert_data[id]['object'] ) {
          this.inject( insert_data[id]['position'], insert_data[id]['object'] );
        }
        this.remove_from_queue( 'insert', id );
      }

      this.insert_pinned();
    },
    ui_remove: function ( remove_queue ) {
      if ( !remove_queue ) return;

      this.inventory_pinned();
      this.remove_pinned();

      this.delete_pinned( remove_queue );

      for ( i in remove_queue ) {
        var id = remove_queue[i].id;
        $feed.find('#post-' + id).remove();
        this.remove_from_queue( 'remove', id );
      }

      this.insert_pinned();
    },



    /**
     * Insert or remove just one post without invoking the queues
     */
    insert_single: function ( id, position ) {
      if ( this.post_exists(id) ) return;
      this.apply_insert([{ 
        id: id,
        position: position
      }]);
    },
    remove_single: function ( id ) {
      if ( !this.post_exists(id) ) return;
      this.apply_remove([{
        id: id
      }]);
    },


    /**
     * Inserts one post object into the feed
     */
    inject: function (position, object) {
      if ( position == 0 ) {
        $feed.prepend( object );
      } else {
        $feed.find( '.stub:nth-child(' + position + ')' ).after( object );
      }
    },


    /**
     * Check if a post exists in the feed
     */
    post_exists: function (id) {
      return $feed.find('#post-' + id).length;
    },


    /**
     * Helpers for keeping pinned stubs in place
     */
    pinned_inventory: [],
    inventory_pinned: function () {
      var that = this;
      this.pinned_inventory = [];
      $feed.find('.stub').each( function (i) {
        if ( $(this).hasClass('fm-pinned') ) {
          var id = $(this).attr('data-id');
          that.pinned_inventory.push({
            id: id,
            obj: this,
            position: i
          });
        }
      });
    },
    remove_pinned: function () {
      for (i in this.pinned_inventory) {
        this.pinned_inventory[i].obj.remove();
      }
    },
    delete_pinned: function ( remove_queue ) {
      for ( i in this.pinned_inventory ) {
        var id = this.pinned_inventory[i].id;
        for ( j in remove_queue ) {
          if ( remove_queue[j].id == id ) {
            this.remove_from_queue( 'remove', id );
            this.pinned_inventory.splice(i, 1);
          }
        }
      }
    },
    insert_pinned: function () {
      for (i in this.pinned_inventory) {
        this.inject(
          this.pinned_inventory[i].position,
          this.pinned_inventory[i].obj
        );
      }
    },

  };

  window.feed = feed;
  

  ////////////////////////////////////////////
  // 
  // Post queue UI
  //
  // Listens for the feed events to update
  // the user interface, letting the end user
  // know when there are changes.
  // 
  ////////////////////////////////////////////

  var $queue = $('.post-queue-alert');
  var allow_submit = true;

  $(document).on('fm/feed_update', function( e ) {
    var insert_queue = feed.insert_queue;
    var remove_queue = feed.remove_queue;

    if ( (insert_queue.length + remove_queue.length) > 0 ) {
      $queue.show();
      var text = [
        '<span class="dashicons dashicons-plus"></span> '
      ];

      if ( insert_queue.length == 1 ) {
        text.push('There is 1 new post. ');
      } else if ( insert_queue.length > 1 ) {
        text.push('There are ' + insert_queue.length + ' new posts. ');
      }

      if ( remove_queue.length == 1 ) {
        text.push('There is 1 post that was deleted. ');
      } else if ( remove_queue.length > 1 ) {
        text.push('There are ' + remove_queue.length + ' posts that were deleted. ');
      }

      $queue.html(text.join(""));
      allow_submit = false;
    } else {
      $queue.hide();
      allow_submit = true;
    }
  });

  $queue.on('click', function(e) {
    feed.apply_queues();
    $feed.attr('data-ids',    tmp_ids);
    $feed.attr('data-pinned', tmp_pinned);
    allow_submit = true;
  });

  // the submit event gets called twice, so keep track with this
  var submit_flag = true;

  $('form#post').off('submit.fm').on('submit.fm', function(e) {
    submit_flag = !submit_flag;
    if ( !submit_flag && !allow_submit ) return;

    if ( !allow_submit ) {
      if ( ! window.confirm('New posts have been published or removed since you began editing the feed. \n\nPress Cancel to go back, or OK to save the feed without them.') ) {
        e.preventDefault();
      } else {
        allow_submit = true;
      }
    }
  });


  ////////////////////////////////////////////
  // 
  // Search
  // 
  ////////////////////////////////////////////

  var search_query = '';
  var search_timer = null;
  var $results = $('.fm-results');

  $('.fm-search').on({
    input: function(e) {
      var that = this;

      clearTimeout(search_timer);
      search_timer = setTimeout(function() {
        if ( $(that).val() !== search_query ) {
          search_query = $(that).val();

          if ( search_query.length > 2 ) {

            var request = {
              action: 'fm_feed_search',
              query: search_query
            };

            $.post(ajaxurl, request, function(results) {
              var data = JSON.parse(results);

              $results.empty();
              $results.show();

              for (i in data.data) {
                var post = data.data[i];
                $results.append('<li><a class="fm-result" href="#" data-id="' + post.id + '">' + post.title + '</a></li>');

                $results.find('li:nth-child(1) .fm-result').addClass('active');
              }
            });
          } else {
            $results.empty();
            $results.hide();
          }
        }
      }, 200);
    },
    keydown: function (e) {
      if (e.keyCode == 38) {
        // up
        e.preventDefault();
        var $active = $results.find('.active');
        var $prev = $active.parent().prev().find('.fm-result');

        if (!$prev.length) return;

        $active.removeClass('active');
        $prev.addClass('active');
      } else if (e.keyCode == 40) {
        // down
        e.preventDefault();
        var $active = $results.find('.active');
        var $next = $active.parent().next().find('.fm-result');

        if (!$next.length) return;

        $active.removeClass('active');
        $next.addClass('active');
      } else if (e.keyCode == 13) {
        // enter
        e.preventDefault();
        $results.find('.active').trigger('fm/select');
      }
    }
  });

  $('.fm-search').on('focus', function(e) {
    if ( !$results.is(':empty') ) {
      $results.show();
    }
  });

  $results.on('mouseover', '.fm-result', function (e) {
    if ( $(this).hasClass('active') ) return;
    $results.find('.active').removeClass('active');
    $(this).addClass('active');
  });

  $results.on('click fm/select', '.fm-result', function (e) {
    e.preventDefault();
    feed.insert_single( $(this).attr('data-id'), 0 );
    $results.hide();
  });

  $('body').on('mousedown', function(e) {
    if ( !$(e.target).closest('.fm-search-container').length ) {
      $results.hide();
    }
  });



});

// // Remove undo
// // @TODO: move this somewhere else
// var undo_cache = [];

// var undo_remove = function() {
//   var object = undo_cache.pop();
//   if (!object) return;
//   var container = jQuery('.fm-posts');

//   if (object.position == 0) {
//     container.prepend(object.object);
//   } else {
//     container.find('.stub:nth-child(' + object.position + ')').after(object.object);
//   }
// }
