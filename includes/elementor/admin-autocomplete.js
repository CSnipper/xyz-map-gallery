(function($){
  const api = (t,q)=> wp.apiFetch({ path: `/xyz/v1/search?type=${t}&q=${encodeURIComponent(q)}` });

  function attach($input, type, $idField){
    let $list = $('<ul class="xyz-ac-list" />').hide().insertAfter($input);
    $input.attr('autocomplete','off');

    $input.on('input', async function(){
      const q = $(this).val().trim();
      if (q.length < 2) { $list.empty().hide(); return; }
      const res = await api(type, q);
      $list.empty();
      res.forEach(r=>{
        $('<li/>').text(r.label).data('id', r.id).appendTo($list);
      });
      $list.toggle(res.length>0);
    });

    $list.on('click','li', function(){
      $input.val($(this).text());
      $idField.val($(this).data('id')).trigger('input');
      $list.hide();
    });

    $(document).on('click', (e)=>{
      if(!$(e.target).closest($input).length && !$(e.target).closest($list).length){ $list.hide(); }
    });
  }

  $(window).on('elementor/init', function(){
    // Big Map
    elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view){
      const $wrap = panel.$el;
      // map: pole tytułu + hidden id
      const $title = $wrap.find('input[data-setting="map_title"]');
      const $id    = $wrap.find('input[data-setting="map_id"]');
      if ($title.length && $id.length) attach($title, 'map', $id);

      // place (Mini/Photos Grid): dostosuj nazwy
      const $ptitle = $wrap.find('input[data-setting="place_title"]');
      const $pid    = $wrap.find('input[data-setting="place_id"]');
      if ($ptitle.length && $pid.length) attach($ptitle, 'place', $pid);
    });
  });
})(jQuery);
