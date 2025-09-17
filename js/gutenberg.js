(function (blocks, editor, element, components) {

  const el = element.createElement;
  const { addFilter } = wp.hooks;
  const { registerBlockType } = blocks;
  const { RichText } = editor;
  const { InspectorControls } = wp.blockEditor;
  const { Fragment, useState, useEffect } = element;

  const {
    TextControl,
    PanelBody,
    ComboboxControl
  } = components;

  var controls_xhr = null;

  const conversion_fields = JSON.parse(bt_gutenberg.conversion_fields);
  const conversion_attr = set_attr();

  const htmlToElem = (html) => wp.element.RawHTML({ children: html });

  function bt_parse_attr() {
    var new_attr = {};
    Object.keys(conversion_fields).forEach(function (key) {
      var field_type = (conversion_fields[key].hasOwnProperty('bt_gutenberg_type')) ? conversion_fields[key]['bt_gutenberg_type'] : 'string';
      var field_default = (conversion_fields[key].hasOwnProperty('default')) ? conversion_fields[key]['default'] : '';

      new_attr[key] = {
        type: field_type,
        default: field_default
      };
    });

    new_attr['ab_test_html'] = {
      type: 'string',
      default: bt_gutenberg.editor_html
    };

    return new_attr;
  }

  function set_attr() {
    var attr = {};
    Object.keys(conversion_fields).forEach(function (key) {
      var field_default = (conversion_fields[key].hasOwnProperty('default')) ? conversion_fields[key]['default'] : '';
      attr[key] = field_default;
    });
    return attr;
  }

  var bt_sdata = {
    'action': bt_gutenberg.actions.render_ab_test_html,
    'data': conversion_attr,
    'nonce': bt_gutenberg.nonce,
  };

  const addExperimentControlAttribute = (settings, name) => {
    if (name === 'bt-experiments/gutenberg-conversion') {
      return settings;
    }

    settings.attributes = Object.assign(settings.attributes, {
      'bt-eid': {
        type: 'string',
        default: ''
      },
      'bt-variation': {
        type: 'string',
        default: ''
      }
    });

    return settings;
  };
  addFilter('blocks.registerBlockType', 'bt-experiments/attribute/gutenberg-experiment', addExperimentControlAttribute);

  const withExperimentControl = wp.compose.createHigherOrderComponent(function (BlockEdit) {
    return function (props) {

      if (props.name === 'bt-experiments/gutenberg-conversion' || props.name === 'bt-experiments/gutenberg-ab-redirect') {
        return el(BlockEdit, props);
      }

      const experiment = props.attributes['bt-eid'];
      const variation = props.attributes['bt-variation'];

      const [filteredExperiments, setFilteredExperiments] = useState([]);

      useEffect(() => {
        // If we have a saved experiment, fetch it by ID so it's displayed as selected on load.
        if (experiment) {
          fetchExperimentById(experiment);
          // Ensure the experiment is properly selected in the UI
          const eidInput = document.querySelector('.bt-eid-input input');
          if (eidInput && eidInput.value !== experiment) {
            eidInput.value = experiment;
            eidInput.dispatchEvent(new Event('input', { bubbles: true }));
          }
        } else {
          // No saved experiment, fetch default list
          fetchExperiments('');
        }
      }, [experiment]);

      const fetchExperiments = (search) => {
        if (controls_xhr !== null) {
          controls_xhr.abort();
          controls_xhr = null;
        }

        controls_xhr = jQuery.ajax({
          type: 'POST',
          url: ajaxurl,
          data: {
            action: 'blocks_experiment_list',
            search: search
          },
          success: function (response) {
            setFilteredExperiments(response);
          },
          error: function () {
            setFilteredExperiments([]);
          }
        });
      };

      const fetchExperimentById = (id) => {

        jQuery.ajax({
          type: 'POST',
          url: ajaxurl,
          data: {
            action: 'blocks_experiment_list',
            exact_id: id
          },
          success: function (response) {
            setFilteredExperiments(response);
          },
          error: function () {
            setFilteredExperiments([]);
          }
        });
      };

      const handleFilterValueChange = (inputValue) => {
        if (!inputValue && experiment) {
          // If no input and we have a saved experiment, fetch by ID again
          fetchExperimentById(experiment);
        } else {
          fetchExperiments(inputValue);
        }
      };

      return el(Fragment, {},
        el(BlockEdit, props),
        el(InspectorControls, {},
          el(PanelBody, { title: 'AB Split Test', initialOpen: true, className: 'abst-split-test-attributes' },
            el(ComboboxControl, {
              label: 'Test',
              value: String(experiment), // This ensures that if experiment is in filteredExperiments, it's selected
              help: el(
  'a',
  {
    className: 'new-on-page-test-button modern-abst-button',
    href: bt_gutenberg.admin_url + 'edit.php?post_type=bt_experiments',
    rel: 'noopener noreferrer',
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '0.5em',
      padding: '8px 16px',
      background: '#007cba',
      color: '#fff',
      borderRadius: '6px',
      fontWeight: 'bold',
      textDecoration: 'none',
      margin: '8px 0',
      transition: 'background 0.2s',
    },
    onMouseOver: (e) => e.currentTarget.style.background = '#005a9e',
    onMouseOut: (e) => e.currentTarget.style.background = '#007cba',
    tabIndex: 0,
  },
  [
    el('span', { style: { fontSize: '1.2em', marginRight: '0.3em' } }, '➕'),
    'Create new Test'
  ]
),
              options: filteredExperiments,
              onChange: (newVal) => {
                props.setAttributes({
                  'bt-eid': newVal
                });
              },
              onFilterValueChange: handleFilterValueChange,
              className: 'bt-eid-input'
            }),
            el(TextControl, {
              label: 'Variation Name',
              value: variation,
              help: 'Using "default, 1 or control" will cause this version to run first, unless otherwise targeted.',
              onChange: (VariationOption) => {
                props.setAttributes({
                  'bt-variation': VariationOption
                });
              }
            })
          )
        )
      );
    };
  }, 'withExperimentControl');
  addFilter('editor.BlockEdit', 'bt-experiments/gutenberg-with-experiment-control', withExperimentControl);


  const addExperimentExtraProps = (saveElementProps, blockType, attributes) => {
    if (blockType.name === 'bt-experiments/gutenberg-conversion') {
      return saveElementProps;
    }
    if (attributes['bt-eid'] || attributes['bt-variation']) {
      Object.assign(saveElementProps, {
        'bt-eid': attributes['bt-eid'] || '',
        'bt-variation': attributes['bt-variation'] || ''
      });
    }

    return saveElementProps;
  };
  addFilter('blocks.getSaveContent.extraProps', 'bt-experiments/get-save-content/gutenberg-extra-props', addExperimentExtraProps);

  window.newTestCreated = function (value, name) {
    const eidInput = document.querySelector('.bt-eid-input input');
    if (eidInput) {
      eidInput.value = value;
      eidInput.dispatchEvent(new Event('input'));
    }
  };

  registerBlockType('bt-experiments/gutenberg-conversion', {
    title: 'AB test conversion',
    icon: 'plus',
    category: 'common',
    attributes: bt_parse_attr(),
    edit: function (props) {
      const { Fragment, useState, useEffect } = wp.element;
      const { InspectorControls } = wp.blockEditor;
      const { PanelBody, ComboboxControl, TextControl } = wp.components;
  
      // Local state to store the experiments options
      const [experiments, setExperiments] = useState([]);
  
      // Variable to hold the AJAX request so we can cancel if needed
      let controls_xhr = null;
  
      // Function to fetch experiments list based on a search term
      const fetchExperiments = (search) => {
        if (controls_xhr !== null) {
          controls_xhr.abort();
          controls_xhr = null;
        }
        controls_xhr = jQuery.ajax({
          type: 'POST',
          url: bt_gutenberg.ajax_url,
          data: {
            action: 'blocks_experiment_list',
            search: search,
          },
          success: function (response) {
            setExperiments(response);
          },
          error: function () {
            setExperiments([]);
          },
        });
      };
  
      // Function to fetch a single experiment by its ID (saved value)
      const fetchExperimentById = (id) => {
        if (controls_xhr !== null) {
          controls_xhr.abort();
          controls_xhr = null;
        }
        controls_xhr = jQuery.ajax({
          type: 'POST',
          url: bt_gutenberg.ajax_url,
          data: {
            action: 'blocks_experiment_list',
            exact_id: id,
          },
          success: function (response) {
            setExperiments(response);
          },
          error: function () {
            setExperiments([]);
          },
        });
      };
  
      // On initial mount, if a saved experiment exists, fetch it by ID.
      // Otherwise, fetch the default experiments list.
      useEffect(() => {
        if (props.attributes.bt_experiment) {
          fetchExperimentById(props.attributes.bt_experiment);
          // Also trigger the onChange to ensure the HTML output is updated
          if (controls_xhr !== null) {
            controls_xhr.abort();
            controls_xhr = null;
          }
          controls_xhr = jQuery.ajax({
            type: 'POST',
            url: bt_gutenberg.ajax_url,
            data: {
              action: bt_gutenberg.actions.render_ab_test_html,
              data: {...bt_sdata.data, bt_experiment: props.attributes.bt_experiment},
              nonce: bt_gutenberg.nonce,
            },
            success: function (response) {
              props.setAttributes({
                ab_test_html: response
              });
            },
            error: function () {}
          });
        } else {
          fetchExperiments('');
        }
      }, []);
  
      // Sync conversion data into our AJAX data object
      Object.keys(conversion_attr).forEach(function (key) {
        var attr_val =
          props.attributes.hasOwnProperty(key) ? props.attributes[key] : conversion_attr[key];
        bt_sdata['data'][key] = attr_val;
      });
  
      var is_selector_hidden = props.attributes.bt_experiment_type === 'click' ? '' : 'hidden';
  
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'AB Test Conversion Module', initialOpen: false },
            // AB Test Dropdown
            el(ComboboxControl, {
              label: 'AB Test',
              // Ensure the saved value is a string
              value: String(props.attributes.bt_experiment),
              options: experiments,
              help: conversion_fields['bt_experiment']['description'],
              // Fetch matching experiments as the user types
              onFilterValueChange: (inputValue) => {
                fetchExperiments(inputValue);
              },
              onChange: (eid) => {
                conversion_attr['bt_experiment'] = eid;
                if (controls_xhr !== null) {
                  controls_xhr.abort();
                  controls_xhr = null;
                }
                controls_xhr = jQuery.ajax({
                  type: 'POST',
                  url: bt_gutenberg.ajax_url,
                  data: bt_sdata,
                  success: function (response) {
                    props.setAttributes({
                      bt_experiment: eid,
                      ab_test_html: response,
                    });
                  },
                  error: function () {},
                });
              }
            }),
            // Conversion Type Dropdown
            el(ComboboxControl, {
              label: 'Conversion Type',
              options: [
                { label: 'On Page Load', value: 'load' },
                { label: 'On Element Click', value: 'click' }
              ],
              value: props.attributes.bt_experiment_type,
              help: conversion_fields['bt_experiment_type']['description'],
              onChange: (type) => {
                conversion_attr['bt_experiment_type'] = type;
                jQuery('.bt_click_conversion_selector').toggleClass('hidden');
                props.setAttributes({
                  bt_experiment_type: type
                });
                if (controls_xhr !== null) {
                  controls_xhr.abort();
                  controls_xhr = null;
                }
                controls_xhr = jQuery.ajax({
                  type: 'POST',
                  url: bt_gutenberg.ajax_url,
                  data: bt_sdata,
                  success: function (response) {
                    props.setAttributes({
                      ab_test_html: response
                    });
                  },
                  error: function () {}
                });
              }
            }),
            // Selector input (only for "click" type)
            el(TextControl, {
              label: 'Selector',
              value: props.attributes.bt_click_conversion_selector,
              help: conversion_fields['bt_click_conversion_selector']['description'],
              onChange: (selector) => {
                conversion_attr['bt_click_conversion_selector'] = selector;
                props.setAttributes({
                  bt_click_conversion_selector: selector
                });
                if (controls_xhr !== null) {
                  controls_xhr.abort();
                  controls_xhr = null;
                }
                controls_xhr = jQuery.ajax({
                  type: 'POST',
                  url: bt_gutenberg.ajax_url,
                  data: bt_sdata,
                  success: function (response) {
                    props.setAttributes({
                      ab_test_html: response
                    });
                  },
                  error: function () {}
                });
              },
              className: 'bt_click_conversion_selector ' + is_selector_hidden
            })
          )
        ),
        htmlToElem(props.attributes.ab_test_html)
      );
    },
    save: function (props) {
      var attr = '';
      Object.keys(bt_sdata.data).forEach(function (key) {
        attr += ' ' + key + '=' + props.attributes[key];
      });
      return htmlToElem('[' + bt_gutenberg.shortcode_name + attr + ' ]');
    },
  });
    

})(
  window.wp.blocks,
  window.wp.editor,
  window.wp.element,
  window.wp.components
);
