jQuery(function($) {

  $('.fm-posts').on('click', '.pin-unpin a', function(e) {
    e.preventDefault();

    var stub = $(this).closest('.stub');

    if ( stub.hasClass('fm-pinned') ) {
      stub.removeClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', false);
      $(this).text('Pin');
      $('.fm-posts').trigger('reflow');
    } else {
      stub.addClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', true);
      $(this).text('Unpin');
    }
  });

  $('.fm-posts').on('click', '.hide a', function(e) {
    e.preventDefault();

    var stub = $(this).closest('.stub');

    if ( stub.hasClass('fm-hidden') ) {
      stub.removeClass('fm-hidden');
      stub.find('.fm-hide-checkbox').prop('checked', false);
      $(this).text('Hide');
    } else {
      stub.addClass('fm-hidden');
      stub.find('.fm-hide-checkbox').prop('checked', true);
      $(this).text('Unhide');
    }
  });

  $('.fm-feed-rows').sortable({
    update: function(event, ui) {
      console.log(this, event, ui);
      $(ui.item)
        .addClass('fm-pinned')
        .find('.fm-pin-checkbox').prop('checked', true);
      $(ui.item).find('.pin-unpin a').text('Unpin');
    }
  });




  function reflow() {
    var stubs = $('.stub');

    // Default ID sorting
    var ids = $('.fm-posts').attr('data-ids').split(",");

    var pinned = [];
    var unpinned = {};
    var sorted = [];

    stubs.each(function(i) {
      if ($(this).hasClass('fm-pinned')) {
        pinned.push({
          id: $(this).attr('data-id'),
          obj: this,
          pos: i
        });
      } else {
        unpinned[$(this).attr('data-id')] = this;
      }
    });

    // Properly sort unpinned items
    for (i in ids) {
      if (unpinned[ids[i]]) sorted.push(unpinned[ids[i]]);
    }

    // Put the pinned items back in
    for (i in pinned) {
      sorted.splice(pinned[i].pos, 0, pinned[i].obj);
    }

    $('.fm-feed-rows').empty().append(sorted);

  }

  $('.fm-posts').on('reflow', function() {
    reflow();
  });

});