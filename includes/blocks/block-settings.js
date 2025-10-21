(function(){
  if (typeof window.xyzBlockSettings === 'undefined') window.xyzBlockSettings = {};
  // if wp.i18n is available, translate the msgid on the client so the editor sees
  // the localized string even if PHP localized it too early.
  try {
    if (typeof window.wp !== 'undefined' && window.wp.i18n && window.wp.i18n.__ && window.xyzBlockSettings.sidebarHintMsgid){
      window.xyzBlockSettings.sidebarHint = window.wp.i18n.__(window.xyzBlockSettings.sidebarHintMsgid, 'xyz-map-gallery');
    }
  } catch (e) {
    // ignore
  }
  // ensures xyzBlockSettings exists for debugging in the editor console
  console.log('xyzBlockSettings available', window.xyzBlockSettings);
})();
