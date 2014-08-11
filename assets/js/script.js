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

  var feed = {

    ////////////////////////////////////////////
    // 
    //  Setup
    // 
    ////////////////////////////////////////////

    init: function () {
      this.$feed = $('.fm-posts');

      this.set_defaults();
      this.bind_events();
    },

    set_defaults: function () {
      this.tmp_ids    = this.$feed.attr('data-ids');
      this.tmp_pinned = this.$feed.attr('data-pinned');

      // Post queue
      this.$queue = $('.post-queue-alert');
      this.allow_submit = true;
      // `submit` event gets called twice, keep track with this:
      this.submit_flag = true;

      // Search
      this.search_query = '';
      this.search_timer = null;
      this.$results = $('.fm-results');
    },

    bind_events: function () {
      // Feed Events
      $(document).on('heartbeat-tick',        $.proxy(this.on_heartbeat,   this));
      this.$feed.on('click',    '.pin-unpin', $.proxy(this.on_stub_pin,    this));
      this.$feed.on('dblclick', '.stub',      $.proxy(this.on_stub_pin,    this));
      this.$feed.on('click',    '.remove',    $.proxy(this.on_stub_remove, this));
      this.$feed.sortable({
        start  : $.proxy(this.on_sortable_start,  this),
        change : $.proxy(this.on_sortable_change, this),
        items  : '.stub:not(.fm-meta)',
        revert : 200,
        axis   : 'y'
      });

      // Feed Update Notifications
      $(document).on('fm/feed_update', $.proxy(this.on_feed_update, this));
      this.$queue.on('click',          $.proxy(this.on_apply_queue, this));
      $('form#post').on('submit.fm',   $.proxy(this.on_form_submit, this));

      // Search
      $('.fm-search').on({
        input:   $.proxy(this.on_search_input,   this),
        keydown: $.proxy(this.on_search_keydown, this),
        focus:   $.proxy(this.on_show_results,   this)
      });
      this.$results.on({
        mouseover:         $.proxy(this.on_result_hover,  this),
        'click fm/select': $.proxy(this.on_result_select, this)
      }, '.fm-result');
      $('body').on('mousedown', $.proxy(this.on_hide_results, this));
    },


    ////////////////////////////////////////////
    // 
    //  Heartbeat
    // 
    //  Avoid feed collisions by loading feed
    //  updates from the database. For the purpose
    //  of more accurate placements, pinned posts
    //  are excluded from the list of IDs that
    //  are passed around.
    //
    //  Note that the purpose of this isn't to keep
    //  the feed in sync among multiple editors;
    //  instead, it's meant to ensure that no
    //  published posts are left behind, in addition
    //  to making sure that removed posts do not
    //  interfere with the feed's sorting.
    // 
    ////////////////////////////////////////////

    on_heartbeat: function(e, data) {
      var that = this;

      this.tmp_ids = data.fm_feed_ids;
      var front = this.$feed.attr('data-ids').split(',')
          back  = data.fm_feed_ids.split(',');

      // Published posts
      _.each( _.difference(back, front), function(id) {
        that.add_to_queue( 'insert', id, _.indexOf( back, id ) );
      });

      // Deleted posts
      _.each( _.difference(front, back), function(id) {
        that.add_to_queue( 'remove', id );
      });

      // Deleted pinned posts
      this.tmp_pinned = data.fm_feed_pinned;
      var front_pinned = this.$feed.attr('data-pinned').split(','),
          back_pinned  = data.fm_feed_pinned.split(',');

      _.each( _.difference(front_pinned, back_pinned), function(id) {
        that.add_to_queue( 'remove', id );
      });
    },


    ////////////////////////////////////////////
    // 
    //  Feed Manipulation
    // 
    ////////////////////////////////////////////

    on_stub_pin: function(e) {
      e.preventDefault();

      var stub = $(e.target);
      if (!stub.hasClass('stub')) stub = stub.closest('.stub');

      if ( stub.hasClass('fm-pinned') ) {
        stub.removeClass('fm-pinned');
        stub.find('.fm-pin-checkbox').prop('checked', false);
      } else {
        stub.addClass('fm-pinned');
        stub.find('.fm-pin-checkbox').prop('checked', true);
      }
    },

    on_stub_remove: function(e) {
      e.preventDefault();

      var id = $(e.target).closest('.stub').attr('data-id');
      this.remove_single( id );
    },

    on_sortable_start: function (event, ui) {
      $(document).trigger('fm/sortable_start', ui.item);
      $(ui.placeholder).height($(ui.item).height());
      if (ui.item.hasClass('fm-pinned')) {
        this.inventory_pinned('fm-meta');
      } else {
        this.inventory_pinned();
      }
    },

    on_sortable_change: function (event, ui) {
      this.remove_pinned();
      this.insert_pinned();
    },



    ////////////////////////////////////////////
    // 
    //  Post queue UI
    //
    //  Listens for the feed events to update
    //  the user interface, letting the end user
    //  know when there are changes.
    // 
    ////////////////////////////////////////////

    on_feed_update: function (e) {
      var insert = this.insert_queue,
          remove = this.remove_queue;

      if ( (insert.length + remove.length) > 0 ) {
        this.$queue.show();
        var text = ['<span class="dashicons dashicons-plus"></span> '];

        if ( insert.length == 1 ) {
          text.push('There is 1 new post. ');
        } else if ( insert.length > 1 ) {
          text.push('There are ' + insert.length + ' new posts. ');
        }

        if ( remove.length == 1 ) {
          text.push('There is 1 removed post.');
        } else if ( remove.length > 1 ) {
          text.push('There are ' + remove.length + ' removed posts.');
        }

        this.$queue.html(text.join(""));
        this.allow_submit = false;
      } else {
        this.$queue.hide();
        this.allow_submit = true;
      }
    },

    on_apply_queue: function(e) {
      this.apply_queues();
      this.$feed.attr('data-ids',    this.tmp_ids);
      this.$feed.attr('data-pinned', this.tmp_pinned);
      this.allow_submit = true;
    },

    // @TODO: force one more heartbeat
    on_form_submit: function(e) {
      this.submit_flag = !this.submit_flag;
      if ( !this.submit_flag && !this.allow_submit ) return;

      if ( !this.allow_submit ) {
        if ( ! window.confirm('New posts have been published or removed since you began editing the feed. \n\nPress Cancel to go back, or OK to save the feed without them.') ) {
          e.preventDefault();
        } else {
          this.allow_submit = true;
        }
      }
    },


    ////////////////////////////////////////////
    // 
    //  Post Queue
    // 
    //  API for modifying the posts in the feed
    //  user interface, including adding and
    //  removing by post IDs. Used by the collision
    //  management system (with the WordPress
    //  heartbeat API) and the post search.
    //
    //  -----------------------------------------
    //
    //  Usage: (where queue_name is 'insert' or 'remove')
    //  > feed.add_to_queue( queue_name, post_id, position );
    //  > feed.remove_from_queue( queue_name, post_id );
    // 
    //  Apply changes:
    //  > feed.apply_insert( queue_override );
    //  > feed.apply_remove( queue_override );
    //
    //  Add a single post without invoking the queues:
    //  > feed.insert_single( id, position );
    //  > feed.remove_single( id );
    // 
    ////////////////////////////////////////////

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
    apply_insert: function ( queue_override, animate ) {
      var queue = queue_override ? queue_override : this.insert_queue;
      if (queue.length < 1) return;
      var that = this;

      $.post(ajaxurl, {
          action: 'fm_feed_request',
          queue: queue
        }, function (response) {
          response = JSON.parse(response);
          if ( response.status && response.status == 'error' ) return;
          that.ui_insert( response.data, animate );
          $(document).trigger( 'fm/feed_update' );
        }
      );
    },
    apply_remove: function ( queue_override ) {
      var queue = queue_override ? queue_override : this.remove_queue;
      if (queue.length < 1) return;
      this.ui_remove( queue );
      $(document).trigger( 'fm/feed_update' );
    },
    apply_queues: function () {
      this.apply_remove();
      this.apply_insert();
    },



    /**
     * Insert/remove post(s) in the UI
     */
    ui_insert: function ( insert_data, animate ) {
      if ( !insert_data ) return;

      this.inventory_pinned();
      this.remove_pinned();

      for ( id in insert_data ) {
        if ( insert_data[id]['object'] ) {
          this.inject( insert_data[id]['position'], insert_data[id]['object'], animate );
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
        this.$feed.find('#post-' + id).remove();
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
      }], true);
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
    inject: function (position, object, animate) {
      object = $( object );
      if ( position == 0 ) {
        this.$feed.prepend( object );
      } else {
        var $object_before = this.$feed.find( '.stub:nth-child(' + position + ')' );
        if ( $object_before.length ) {
          $object_before.after( object );
        } else {
          this.$feed.append( object );
        }
      }

      if ( animate ) {
        object.addClass('inserted');
        setTimeout(function() {
          object.removeClass('inserted');
        }, 2000);
      }
    },


    /**
     * Check if a post exists in the feed
     */
    find_post: function (id) {
      return this.$feed.find('#post-' + id);
    },
    post_exists: function (id) {
      return this.find_post(id).length;
    },


    /**
     * Helpers for keeping pinned stubs in place
     */
    pinned_inventory: [],
    inventory_pinned: function (className) {
      if ( !className ) className = 'fm-pinned';
      var that = this;
      this.pinned_inventory = [];
      this.$feed.find('.stub').each( function (i) {
        if ( $(this).hasClass( className ) ) {
          that.pinned_inventory.push({
            id: $(this).attr('data-id'),
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


    ////////////////////////////////////////////
    // 
    //  Search
    // 
    ////////////////////////////////////////////

    on_search_input: function(e) {
      var that = this;

      clearTimeout(this.search_timer);
      this.search_timer = setTimeout(function() {
        if ( $(e.target).val() !== that.search_query ) {
          that.search_query = $(e.target).val();

          if ( that.search_query.length > 2 ) {

            var request = {
              action: 'fm_feed_search',
              query: that.search_query
            };

            $.post(ajaxurl, request, function(results) {
              var data = JSON.parse(results);

              that.$results.empty();
              that.$results.show();

              for (i in data.data) {
                var post = data.data[i];
                that.$results.append([
                  '<li>',
                    '<a class="fm-result" href="#" data-id="' + post.id + '">',
                      post.title,
                      ' <span class="fm-result-date" title="' + post.date + '">',
                        post.human_date + ' ago',
                        that.post_exists( post.id ) ? ' - Already in feed' : '',
                      '</span>',
                    '</a>',
                  '</li>'].join('')
                );

                that.$results.find('li:nth-child(1) .fm-result').addClass('active');
              }
            });
          } else {
            that.$results.empty();
            that.$results.hide();
          }
        }
      }, 200);
    },
    
    on_search_keydown: function (e) {
      if (e.keyCode == 38) {
        // up
        e.preventDefault();
        this.$results.show();
        var $active = this.$results.find('.active');
        var $prev = $active.parent().prev().find('.fm-result');

        if (!$prev.length) return;

        $active.removeClass('active');
        $prev.addClass('active');
      } else if (e.keyCode == 40) {
        // down
        e.preventDefault();
        this.$results.show();
        var $active = this.$results.find('.active');
        var $next = $active.parent().next().find('.fm-result');

        if (!$next.length) return;

        $active.removeClass('active');
        $next.addClass('active');
      } else if (e.keyCode == 13) {
        // enter
        e.preventDefault();
        if ( !this.$results.is(':visible') ) return;
        this.$results.find('.active').trigger('fm/select');
      }
    },

    on_show_results: function(e) {
      if ( !this.$results.is(':empty') ) {
        this.$results.show();
      }
    },

    on_result_hover: function (e) {
      if ( $(e.currentTarget).hasClass('active') ) return;
      this.$results.find('.active').removeClass('active');
      $(e.currentTarget).addClass('active');
    },

    // only move non-pinned item
    on_result_select: function (e) {
      e.preventDefault();
      var id = $(e.currentTarget).attr('data-id');
      var current = this.find_post( id );
      if ( current && current.length ) {
        if ( current.hasClass('fm-pinned') ) {
          setTimeout(function() { alert('This post is already pinned in the feed. To move it, please unpin it first.'); }, 0);
          return false;
        }
        this.remove_single( id );
      }
      this.insert_single( id, 0 );
      this.$results.hide();
    },

    on_hide_results: function(e) {
      if ( !$(e.target).closest('.fm-search-container').length ) {
        this.$results.hide();
      }
    },

  };

  feed.init();
});
