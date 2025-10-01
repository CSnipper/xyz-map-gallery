jQuery(function ($) {
  var $input = $('#xyz_place_search');
  var $hidden = $('#xyz_place_id');

  if (!$input.length) return;

  $input.autocomplete({
    minLength: 2,
    delay: 150,
    source: function (request, response) {
      $.ajax({
        url: xyzPhotoAdmin.ajaxUrl,
        data: {
          action: 'xyz_search_places',
          nonce: xyzPhotoAdmin.nonce,
          term: request.term
        },
        dataType: 'json',
        success: function (data) {
          response($.isArray(data) ? data : []);
        },
        error: function () {
          response([]);
        }
      });
    },
    select: function (event, ui) {
      $input.val(ui.item.label);
      $hidden.val(ui.item.id);
      return false;
    },
    focus: function (event, ui) {
      $input.val(ui.item.label);
      return false;
    }
  });

  $('#xyz_place_clear').on('click', function () {
    $input.val('');
    $hidden.val('0');
  });
});
