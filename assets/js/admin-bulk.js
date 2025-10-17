(function(){
  'use strict';
  document.addEventListener('DOMContentLoaded', function(){
    try {
      var forms = document.querySelectorAll('form#posts-filter');
      if(!forms || !forms.length) return;
      forms.forEach(function(form){
        var bulk1 = form.querySelector('select[name="action"]');
        if(!bulk1) return;
        var wrap = document.createElement('span');
        wrap.style.marginLeft = '8px';
        // build select from localized maps
        var html = '<label style="margin-left:6px;">' + (window.xyzBulkAdmin && xyzBulkAdmin.i18nMapLabel ? xyzBulkAdmin.i18nMapLabel : 'Map:') + ' ';
        html += '<select name="xyz_target_map" id="xyz_target_map">';
        html += '<option value="">' + (window.xyzBulkAdmin && xyzBulkAdmin.chooseText ? xyzBulkAdmin.chooseText : '— choose —') + '</option>';
        if (window.xyzBulkAdmin && Array.isArray(window.xyzBulkAdmin.maps)){
          window.xyzBulkAdmin.maps.forEach(function(m){
            html += '<option value="'+(m.id||0)+'">'+(m.label||('#'+(m.id||0)))+'</option>';
          });
        }
        html += '</select></label>';
        wrap.innerHTML = html;
        bulk1.after(wrap);
      });

      document.addEventListener('submit', function(e){
        var form = e.target;
        if(!form || form.id !== 'posts-filter') return;
        var actionSel = form.querySelector('select[name="action"]') || form.querySelector('select[name="action2"]');
        var actionVal = actionSel ? actionSel.value : '';
        if(actionVal === 'xyz_assign_map'){
          var mapSel = form.querySelector('#xyz_target_map');
          if(!mapSel || !mapSel.value){
            e.preventDefault();
            alert(window.xyzBulkAdmin && xyzBulkAdmin.pleaseChoose ? xyzBulkAdmin.pleaseChoose : 'Please choose a map first.');
          }
        }
      }, true);
    } catch (e) { console.error('admin-bulk init failed', e); }
  });
})();
