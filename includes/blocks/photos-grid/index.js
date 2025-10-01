(function(wp){
  const { __ } = wp.i18n;
  const { registerBlockType } = wp.blocks;
  const { PanelBody, ComboboxControl } = wp.components;
  const { InspectorControls } = wp.blockEditor || wp.editor;
  const { useState, useRef, useEffect, Fragment, createElement: h } = wp.element;
  const apiFetch = wp.apiFetch;

  registerBlockType('xyz-map-gallery/photos-grid', {
    title: __('XYZ Photos Grid','xyz-map-gallery'),
    icon: 'format-gallery',
    category: 'xyz-map-gallery',
    attributes: { place_id: { type:'number', default:0 } },

    edit: (props) => {
      const { attributes:{ place_id }, setAttributes } = props;
      const [options, setOptions] = useState([]);
      const t = useRef();

      const onFilter = (q) => {
        clearTimeout(t.current);
        if (!q || q.length < 2) { setOptions([]); return; }
        t.current = setTimeout(async () => {
          const data = await apiFetch({ path: `/xyz/v1/search?type=place&q=${encodeURIComponent(q)}` });
          setOptions((data||[]).map(i => ({ value:i.id, label:i.label })));
        }, 180);
      };

      useEffect(() => {
        if (place_id && !options.length) {
          apiFetch({ path: `/xyz/v1/place?id=${place_id}` })
            .then(r => { if (r && r.id) setOptions([{ value:r.id, label:r.label }]); })
            .catch(()=>{});
        }
      }, [place_id]);

      return h(Fragment, null,
        h(InspectorControls, {},
          h(PanelBody, { title: __('Settings','xyz-map-gallery'), initialOpen:true },
            h(ComboboxControl, {
              label: __('Place','xyz-map-gallery'),
              value: place_id || '',
              options,
              onFilterValueChange: onFilter,
              onChange: (val) => setAttributes({ place_id: val ? parseInt(val,10) : 0 }),
              help: __('Type place title to search…','xyz-map-gallery'),
            })
          )
        ),
        h('div', { style:{ padding:'12px', border:'1px dashed #ccd', background:'#fafafa' } },
          place_id ? __('Photos grid will render on the front-end.','xyz-map-gallery')
                   : __('Pick a place in the sidebar.','xyz-map-gallery')
        )
      );
    },

    save: () => null
  });
})(window.wp);
