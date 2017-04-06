/**
 * Stream Manager Admin JavaScript
 *
 * @package   StreamManager
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 */

jQuery(function($) {

  var stream = {

    ////////////////////////////////////////////
    // 
    //  Setup
    // 
    ////////////////////////////////////////////

    $stream:  $('.sm-posts'),
    $queue:   $('.sm-alert'),
    $search:  $('.sm-search'),
    $results: $('.sm-results'),
    $form:    $('form#post'),

    init: function () {

      // stream Events
      $(document).on('heartbeat-tick.sm', $.proxy(this.on_heartbeat,   this));
      this.$stream
        .on('click',    '.pin-unpin',  $.proxy(this.on_stub_pin,    this))
        .on('dblclick', '.stub',       $.proxy(this.on_stub_pin,    this))
        .on('click',    '.remove',     $.proxy(this.on_stub_remove, this))
        .sortable({
          start  : $.proxy(this.on_sortable_start,  this),
          stop   : $.proxy(this.on_sortable_stop,   this),
          change : $.proxy(this.on_sortable_change, this),
          revert : 150,
          axis   : 'y'
        });
      $('.reload-stream').on('click', $.proxy(this.on_stream_reload, this));

      // stream Update Notifications
      $(document).on('sm/stream_update', $.proxy(this.on_stream_update, this));
      this.$queue.on('click',          $.proxy(this.on_apply_queue, this));
      this.$form.on('submit.sm',       $.proxy(this.on_form_submit, this));

      // Search
      this.$search.on({
        input:   $.proxy(this.on_search_input,   this),
        keydown: $.proxy(this.on_search_keydown, this),
        focus:   $.proxy(this.on_show_results,   this)
      });
      this.$results.on({
        mouseover:         $.proxy(this.on_result_hover,  this),
        'click sm/select': $.proxy(this.on_result_select, this)
      }, '.sm-result');
      $('body').on('mousedown', $.proxy(this.on_hide_results, this));

    },


    ////////////////////////////////////////////
    // 
    //  Heartbeat
    // 
    //  Avoid stream collisions by loading stream
    //  updates from the database. For the purpose
    //  of more accurate placements, pinned posts
    //  are excluded from the list of IDs that
    //  are passed around.
    //
    //  Note that the purpose of this isn't to keep
    //  the stream in sync among multiple editors;
    //  instead, it's meant to ensure that no
    //  published posts are left behind, in addition
    //  to making sure that removed posts do not
    //  interfere with the stream's sorting.
    // 
    ////////////////////////////////////////////

    on_heartbeat: function(e, data) {
      var that = this;

      var front = this.$stream.attr('data-ids').split(',')
          back  = data.sm_ids.split(',');

      // Published posts
      _.each( _.difference(back, front), function(id) {
        that.add_to_queue( 'insert', id, _.indexOf( back, id ) );
      });

      // Deleted posts
      _.each( _.difference(front, back), function(id) {
        that.add_to_queue( 'remove', id );
      });

      // Deleted pinned posts
      var front_pinned = this.$stream.attr('data-pinned').split(','),
          back_pinned  = data.sm_pinned.split(',');

      _.each( _.difference(front_pinned, back_pinned), function(id) {
        that.add_to_queue( 'remove', id );
      });

      this.$stream.prop({
        'data-ids'    : data.sm_ids,
        'data-pinned' : data.sm_pinned
      });
    },


    ////////////////////////////////////////////
    // 
    //  Stream Manipulation
    // 
    ////////////////////////////////////////////

    on_stub_pin: function(e) {
      e.preventDefault();

      var $stub = $(e.target);
      if ( !$stub.is('.stub') ) $stub = $stub.closest('.stub');

      if ( $stub.hasClass('zone') ) return;

      if ( $stub.hasClass('pinned') ) {
        $stub.removeClass('pinned');
        $stub.find('.sm-pin-checkbox').prop('checked', false);
        $stub.find('.pin-unpin').prop('title', 'Pin this post')
      } else {
        $stub.addClass('pinned');
        $stub.find('.sm-pin-checkbox').prop('checked', true);
        $stub.find('.pin-unpin')
          .prop('title', 'Unpin this post')
          .addClass('animating')
          .one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
            $(this).removeClass('animating');
          });
      }
    },

    on_stub_remove: function(e) {
      e.preventDefault();
      var $stub = $(e.target).closest('.stub'),
          that  = this;

      if ( $stub.hasClass('zone') ) {
        $stub.remove();
        $(document).trigger('sm/zone_update');
        return;
      }
      if ( $stub.hasClass('removed') ) return;

      $stub.addClass('removed');

      setTimeout(function() {
        that.remove_single( $stub.attr('data-id') );
      }, 500);
    },

    on_sortable_start: function (event, ui) {
      $(document).trigger('sm/sortable_start', ui.item);
      $(ui.placeholder).height($(ui.item).height());
      if ( ui.item.hasClass('pinned') ) {
        if ( !ui.item.hasClass('zone') ) {
          this.inventory_pinned('zone');
        } else {
          this.pinned_inventory = [];
        }
      } else {
        this.inventory_pinned();
      }
    },

    on_sortable_stop: function (event, ui) {
      if ( ui.item.hasClass('zone') ) {
        $(document).trigger('sm/zone_update');
      }
    },

    on_sortable_change: function (event, ui) {
      this.remove_pinned();
      this.insert_pinned();
    },


    // Remove all unpinned items, get new posts
    // from the database based on categories and tags
    // selected under Rules
    on_stream_reload: function (e) {
      e.preventDefault();
      var that = this;

      // Clear queues
      this.insert_queue = [];
      this.remove_queue = [];
      $(document).trigger('sm/stream_update');

      // Disable heartbeat checking
      $(document).off('heartbeat-tick.sm');


      // Setup the ajax request
      var categories = [];
      $('#categorychecklist input:checked').each(function() {
        categories.push( $(this).val() );
      })
      var exclude = [];
      $('.stub.pinned').each(function() {
        exclude.push( $(this).attr('data-id') );
      });
      var request = {
        action: 'sm_reload',
        stream_id: $('#post_ID').val(),
        taxonomies: {
          category: categories,
          post_tag: $('#tax-input-post_tag').val(),
        },
        exclude: exclude
      };
      
      $.post(ajaxurl, request, function(response) {
        response = JSON.parse(response);
        if ( !response.status || response.status == 'error' ) return;

        that.inventory_pinned();
        that.remove_pinned();
        that.$stream.empty();
        $(response.data).each(function() {
          that.$stream.append(this);
        });
        that.insert_pinned();
      });
    },



    ////////////////////////////////////////////
    // 
    //  Post queue UI
    //
    //  Listens for the stream events to update
    //  the user interface, letting the end user
    //  know when there are changes.
    // 
    ////////////////////////////////////////////

    on_stream_update: function (e) {
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
      this.allow_submit = true;
    },

    // @TODO: force one more heartbeat
    on_form_submit: function(e) {
      this.submit_flag = !this.submit_flag;
      if ( !this.submit_flag && !this.allow_submit ) return;

      if ( !this.allow_submit ) {
        if ( ! window.confirm('New posts have been published or removed since you began editing the stream. \n\nPress Cancel to go back, or OK to save the stream without them.') ) {
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
    //  API for modifying the posts in the stream
    //  user interface, including adding and
    //  removing by post IDs. Used by the collision
    //  management system (with the WordPress
    //  heartbeat API) and the post search.
    //
    //  -----------------------------------------
    //
    //  Usage: (where queue_name is 'insert' or 'remove')
    //  > stream.add_to_queue( queue_name, post_id, position );
    //  > stream.remove_from_queue( queue_name, post_id );
    // 
    //  Apply changes:
    //  > stream.apply_insert( queue_override );
    //  > stream.apply_remove( queue_override );
    //
    //  Add a single post without invoking the queues:
    //  > stream.insert_single( id, position );
    //  > stream.remove_single( id );
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
      $(document).trigger('sm/stream_update');
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
      $(document).trigger('sm/stream_update');
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
          action: 'sm_request',
          queue: queue
        }, function (response) {
          response = JSON.parse(response);
          if ( response.status && response.status == 'error' ) return;
          that.ui_insert( response.data, animate );
          $(document).trigger( 'sm/stream_update' );
        }
      );
    },
    apply_remove: function ( queue_override ) {
      var queue = queue_override ? queue_override : this.remove_queue;
      if (queue.length < 1) return;
      this.ui_remove( queue );
      $(document).trigger( 'sm/stream_update' );
    },
    apply_queues: function () {
      this.apply_remove();
      this.apply_insert(null, true);
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
        this.$stream.find('#post-' + id).remove();
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
     * Inserts one post object into the stream
     */
    inject: function (position, object, animate) {
      object = $( object );
      if ( position == 0 ) {
        this.$stream.prepend( object );
      } else {
        var $object_before = this.$stream.find( '.stub:nth-child(' + position + ')' );
        if ( $object_before.length ) {
          $object_before.after( object );
        } else {
          this.$stream.append( object );
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
     * Check if a post exists in the stream
     */
    find_post: function (id) {
      return this.$stream.find('#post-' + id);
    },
    post_exists: function (id) {
      return this.find_post(id).length;
    },


    /**
     * Helpers for keeping pinned stubs in place
     */
    pinned_inventory: [],
    inventory_pinned: function (className) {
      if ( !className ) className = 'pinned';
      var that = this;
      this.pinned_inventory = [];
      this.$stream.find('.stub').each( function (i) {
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

    search_query: '',
    search_timer: null,

    allow_submit: true,
    submit_flag:  true, // because `submit` event gets called twice

    on_search_input: function(e) {
      var that = this;

      clearTimeout(this.search_timer);
      this.search_timer = setTimeout(function() {
        if ( $(e.target).val() !== that.search_query ) {
          that.search_query = $(e.target).val();

          if ( that.search_query.length > 2 ) {

            var request = {
              action: 'sm_search',
              query: that.search_query,
              stream_id: $('#post_ID').val()
            };

            $.post(ajaxurl, request, function(results) {
              var data = JSON.parse(results);

              that.$results.empty();
              that.$results.show();

              for (i in data.data) {
                var post = data.data[i];
                that.$results.append([
                  '<li>',
                    '<a class="sm-result" href="#" data-id="' + post.id + '">',
                      post.title,
                      ' <span class="sm-result-date" title="' + post.date + '">',
                        post.human_date + ' ago',
                        that.post_exists( post.id ) ? ' - Already in stream' : '',
                      '</span>',
                    '</a>',
                  '</li>'].join('')
                );

                that.$results.find('li:nth-child(1) .sm-result').addClass('active');
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
        var $prev = $active.parent().prev().find('.sm-result');

        if (!$prev.length) return;

        $active.removeClass('active');
        $prev.addClass('active');
      } else if (e.keyCode == 40) {
        // down
        e.preventDefault();
        this.$results.show();
        var $active = this.$results.find('.active');
        var $next = $active.parent().next().find('.sm-result');

        if (!$next.length) return;

        $active.removeClass('active');
        $next.addClass('active');
      } else if (e.keyCode == 13) {
        // enter
        e.preventDefault();
        if ( !this.$results.is(':visible') ) return;
        this.$results.find('.active').trigger('sm/select');
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

    on_result_select: function (e) {
      e.preventDefault();
      var id = $(e.currentTarget).attr('data-id');
      var current = this.find_post( id );
      if ( current && current.length ) {
        // only move non-pinned item
        // @TODO: revisit this in the future
        if ( current.hasClass('pinned') ) {
          setTimeout(function() { alert('This post is already pinned in the stream. To move it, please unpin it first.'); }, 0);
          return false;
        }
        this.remove_single( id );
      }
      this.insert_single( id, 0 );
      this.$results.hide();
    },

    on_hide_results: function(e) {
      if ( !$(e.target).closest('.sm-search-container').length ) {
        this.$results.hide();
      }
    },

  };




  stream.layouts = {

    data: {},

    init: function() {
      this.$data_field = $('.layouts-data');
      this.$container = $('#stream_box_zones');
      this.data = JSON.parse( this.$data_field.val() );
      $(document).on('sm/zone_update', $.proxy( this.on_zone_update, this ));

      // this.$container.find('.add-zone, .add-layout').on('click', this.on_toggle_add );
      this.$container.find('.add-zone-input').on('keydown', $.proxy( this.on_add_keydown, this ));
      this.$container.find('.add-zone-button').on('click', $.proxy( this.on_click_add_button, this ));
      // this.$container.find('.active-layout').on('change', $.proxy( this.on_change_layout, this ));
      stream.$stream.on({
        input   : $.proxy( this.on_zone_update, this ),
        keydown : this.on_zone_keydown
      }, '.zone .zone-header');
    },

    on_toggle_add: function(e) {
      e.preventDefault();
      // if ( $(this).hasClass('add-zone') ) {
        $(this).siblings('.add-zone-container').toggle();
      // }
      // if ( $(this).hasClass('add-layout') ) {
      //   $(this).siblings('.add-layout-container').toggle();
      // }
    },

    on_zone_update: function() {
      var that = this;
      // Update the internal data
      var active = this.data.active;
      this.data.layouts[active].zones = [];

      stream.$stream.find('.zone').each(function(index, el) {
        that.data.layouts[active].zones.push({
          position: $(this).index(),
          title: $(this).find('.zone-header').val()
        });
      });

      var $select = this.$container.find('.active-layout');
      $select.empty();
      for ( i in this.data.layouts ) {
        $select.append([
          '<option value="' + i + '"' + (i == this.data.active ? ' selected' : '') + '>',
            this.data.layouts[i].name,
          '</option>'
        ].join(""));
      }

      this.$data_field.val( JSON.stringify(this.data) );
    },

    on_zone_keydown: function (e) {
      // disable enter
      if ( e.keyCode == '13' ) {
        e.preventDefault();
        this.blur();
      }
    },

    on_click_add_button: function (e) {
      e.preventDefault();
      //if ( $(e.currentTarget).hasClass('add-zone-button') ) {
        var $input = $(e.currentTarget).siblings('.layouts-input');
        this.insert_zones([{
          position: 0,
          title: $input.val()
        }]);
        $input.val('');
      //}

      // if ( $(e.currentTarget).hasClass('add-layout-button') ) {
      //   var $input = $(e.currentTarget).siblings('.layouts-input');
      //   var slug = this.slugify( $input.val() );
      //   this.data.layouts[ slug ] = {
      //     name: $input.val(),
      //     zones: {}
      //   }
      //   this.data.active = slug;
      //   $(document).trigger('sm/zone_update');
      //   $input.val('');
      // }
    },
    on_add_keydown: function (e) {
      if ( e.keyCode == '13' ) {
        e.preventDefault();
        $(e.currentTarget).siblings('.button').trigger('click');
      }
    },

    // on_change_layout: function (e) {
    //   this.data.active = $(e.currentTarget).val();
    //   this.remove_zones();
    //   this.insert_zones( this.data.layouts[ this.data.active ].zones );
    // },

    insert_zones: function( zones ) {
      for ( i in zones ) {
        stream.inject( zones[i].position, $([
          '<div class="stub zone pinned" data-position="' + i + '">',
            '<a href="#" title="Remove this zone" class="remove stub-action dashicons dashicons-no"></a>',
            '<input type="text" class="zone-header" value="' + zones[i].title.replace(/\"/g,'&quot;') + '">',
          '</div>'
        ].join("")) );
      }
      $(document).trigger('sm/zone_update');
    },

    remove_zones: function() {
      stream.$stream.find('.zone').remove();
    },

    // slugify: function(name) {
    //   return name.toLowerCase().replace(/ /g,'-').replace(/[-]+/g, '-').replace(/[^\w-]+/g,'');
    // }
  };




  stream.init();
  stream.layouts.init();
  
  window.stream = stream;
});
