(function (wp) {
  const { __ } = wp.i18n;
  const { registerBlockType } = wp.blocks;
  const { PanelBody, ComboboxControl } = wp.components;
  const { InspectorControls } = wp.blockEditor || wp.editor;
  const { useState, useRef, useEffect, Fragment, createElement: h } = wp.element;
  const apiFetch = wp.apiFetch;

  registerBlockType('xyz-map-gallery/big-map', {
    title: __('XYZ Big Map','xyz-map-gallery'),
    icon: 'location-alt',
    category: 'xyz-map-gallery',
    attributes: { map_id: { type:'number', default:0 } },

    edit: (props) => {
      const { attributes:{ map_id }, setAttributes } = props;
      const [options, setOptions] = useState([]);
      const t = useRef();

      const onFilter = (q) => {
        clearTimeout(t.current);
        if (!q || q.length < 2) { setOptions([]); return; }
        t.current = setTimeout(async () => {
          const data = await apiFetch({ path: `/xyz/v1/search?type=map&q=${encodeURIComponent(q)}` });
          setOptions((data||[]).map(i => ({ value: i.id, label: i.label })));
        }, 180);
      };
      
      useEffect(() => {
        if (map_id && !options.length) {
          apiFetch({ path: `/xyz/v1/map?id=${map_id}` })
            .then(r => { if (r && r.id) setOptions([{ value:r.id, label:r.label }]); })
            .catch(()=>{});
        }
      }, [map_id]);


      return h(Fragment, null,
        h(InspectorControls, {},
          h(PanelBody, { title: __('Settings','xyz-map-gallery'), initialOpen:true },
            h(ComboboxControl, {
              label: __('Map','xyz-map-gallery'),
              value: map_id || '',
              options,
              onFilterValueChange: onFilter,
              onChange: (val) => setAttributes({ map_id: val ? parseInt(val,10) : 0 }),
              help: __('Type map title to search…','xyz-map-gallery'),
            })
          )
        ),
        h('div', {
          className: 'xyz-big-map-placeholder',
          style: { padding:'12px', border:'1px dashed #ccd', background:'#fafafa', cursor:'pointer' },
          onClick: () => {/* klik pomaga zaznaczyć blok */},
        },
          map_id
            ? __('Big Map will render on the front-end. Change map in the sidebar.','xyz-map-gallery')
            : __('Pick a map in the sidebar.','xyz-map-gallery')
        )
      );
    },

    save: () => null
  });
})(window.wp);
