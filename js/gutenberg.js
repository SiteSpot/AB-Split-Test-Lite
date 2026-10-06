(function() {
  if (window.abstConsoleGateLoaded) return;
  window.abstConsoleGateLoaded = true;

  try {
    var params = new URLSearchParams(window.location.search);
    if (params.get('abstdebug') === '1') {
      localStorage.setItem('debug', 'true');
    }
  } catch (e) {}

  var originalLog = console.log ? console.log.bind(console) : function() {};
  console.log = function() {
    try {
      if (localStorage.getItem('debug') !== 'true') return;
    } catch (e) {
      return;
    }

    var args = Array.prototype.slice.call(arguments);
    if (typeof args[0] === 'string') {
      args[0] = args[0].replace(/^\s*ABST\s*:\s*/i, '');
      args[0] = 'ABST: ' + args[0];
    } else {
      args.unshift('ABST:');
    }
    originalLog.apply(console, args);
  };
})();

(function (blocks, editor, element, components) {

  const el = element.createElement;
  const { addFilter } = wp.hooks;
  const { InspectorAdvancedControls } = wp.blockEditor;
  const { Fragment, useState, useEffect } = element;

  const {
    TextControl,
    PanelBody,
    ComboboxControl
  } = components;

  var controls_xhr = null;
  var experimentsCache = window.btExperimentsCache || (window.btExperimentsCache = {});
  var pendingRequests = window.btPendingRequests || (window.btPendingRequests = {});

  const buildSearchCacheKey = (searchTerm) => `search:${searchTerm || '__empty__'}`;
  const buildIdCacheKey = (id) => `id:${id}`;

  const getCachedExperiments = (key) => (
    Object.prototype.hasOwnProperty.call(experimentsCache, key)
      ? experimentsCache[key]
      : null
  );

  const saveExperimentsToCache = (key, data) => {
    experimentsCache[key] = Array.isArray(data) ? data : [];
  };

  const clearExperimentsCache = () => {
    console.log('ABST: Clearing experiments cache');
    Object.keys(experimentsCache).forEach((cacheKey) => {
      delete experimentsCache[cacheKey];
    });
  };

  document.addEventListener('click', (event) => {
    const button = event.target && event.target.closest('.new-on-page-test-button');
    if (button) {
      clearExperimentsCache();
    }
  });

  // Clear cache when browser tab loses focus to ensure fresh data when user returns
  document.addEventListener('visibilitychange', (event) => {
    if (document.hidden) {
      // Tab is now hidden, clear the cache to ensure fresh data when user returns
      clearExperimentsCache();
    }
  });

  const addExperimentControlAttribute = (settings, name) => {
    if (settings.attributes && settings.attributes['bt-eid']) {
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

      if (props.name === 'bt-experiments/gutenberg-ab-redirect') {
        return el(BlockEdit, props);
      }

      const experiment = props.attributes['bt-eid'];
      const variation = props.attributes['bt-variation'];

      const [filteredExperiments, setFilteredExperiments] = useState([]);
      const initializedRef = element.useRef(false);
      const fetchingRef = element.useRef(false);
      const lastFetchedIdRef = element.useRef(null);

      useEffect(() => {
        // Run only once on mount to prevent feedback loops
        if (initializedRef.current) {
          return;
        }
        initializedRef.current = true;

        // If we have a saved experiment, fetch it by ID so it's displayed as selected on load.
        if (experiment) {
          fetchExperimentById(experiment);
          // Ensure the experiment is properly selected in the UI
          const eidInput = document.querySelector('.bt-eid-input input');
          if (eidInput && eidInput.value !== experiment) {
            eidInput.value = experiment;
            // Removed manual input event dispatch to prevent triggering onFilterValueChange loop
          }
        } else {
          // No saved experiment, fetch default list
          fetchExperiments('');
        }
      }, []); // Empty deps - run only once on mount

      // Watch for external changes to experiment (e.g., from builderhelper.js)
      // NOTE: We intentionally exclude filteredExperiments from deps to prevent feedback loops
      useEffect(() => {
        if (experiment && initializedRef.current && !fetchingRef.current) {
          // Only fetch if this is a different experiment than we last fetched
          if (lastFetchedIdRef.current !== experiment) {
            fetchExperimentById(experiment);
          }
        }
      }, [experiment]); // Only run when experiment changes, NOT when filteredExperiments changes

      const fetchExperiments = (search) => {
        const cacheKey = buildSearchCacheKey(search);
        const cached = getCachedExperiments(cacheKey);
        if (cached !== null) {
          setFilteredExperiments(cached);
          return;
        }

        // Check if there's already a pending request for this cache key
        if (pendingRequests[cacheKey]) {
          console.log('ABST: fetchExperiments subscribing to pending request for:', cacheKey);
          // Subscribe to the pending request instead of making a new one
          pendingRequests[cacheKey].then((response) => {
            setFilteredExperiments(response);
          }).catch(() => {
            setFilteredExperiments([]);
          });
          return;
        }

        if (controls_xhr !== null) {
          controls_xhr.abort();
          controls_xhr = null;
        }

        // Create a promise that other callers can subscribe to
        pendingRequests[cacheKey] = new Promise((resolve, reject) => {
          controls_xhr = jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
              action: 'abst_blocks_experiment_list',
              search: search,
              nonce: abst_gutenberg.blocks_list_nonce
            },
            success: function (response) {
              console.log('ABST: fetchExperiments AJAX success for:', cacheKey, 'results:', response.length);
              saveExperimentsToCache(cacheKey, response);
              setFilteredExperiments(response);
              delete pendingRequests[cacheKey];
              resolve(response);
            },
            error: function (jqXHR, textStatus) {
              // Don't log or reject for aborted requests - that's expected behavior when user types quickly
              if (textStatus === 'abort') {
                delete pendingRequests[cacheKey];
                return;
              }
              console.log('ABST: fetchExperiments AJAX error for:', cacheKey);
              setFilteredExperiments([]);
              delete pendingRequests[cacheKey];
              reject();
            }
          });
        });
      };

      const fetchExperimentById = (id) => {
        // Check cache first
        const cacheKey = buildIdCacheKey(id);
        const cached = getCachedExperiments(cacheKey);
        if (cached !== null) {
          lastFetchedIdRef.current = id;
          setFilteredExperiments(cached);
          return;
        }
        
        // Check if there's already a pending request for this cache key
        if (pendingRequests[cacheKey]) {
          pendingRequests[cacheKey].then((response) => {
            lastFetchedIdRef.current = id;
            setFilteredExperiments(response);
          }).catch(() => {
            setFilteredExperiments([]);
          });
          return;
        }
        
        // Prevent duplicate in-flight fetches for the same ID
        if (fetchingRef.current && lastFetchedIdRef.current === id) {
          return;
        }
        
        fetchingRef.current = true;
        lastFetchedIdRef.current = id;

        // Create a promise that other callers can subscribe to
        pendingRequests[cacheKey] = new Promise((resolve, reject) => {
          jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
              action: 'abst_blocks_experiment_list',
              exact_id: id,
              nonce: abst_gutenberg.blocks_list_nonce
            },
            success: function (response) {
              fetchingRef.current = false;
              saveExperimentsToCache(cacheKey, response);
              setFilteredExperiments(response);
              delete pendingRequests[cacheKey];
              resolve(response);
            },
            error: function () {
              fetchingRef.current = false;
              setFilteredExperiments([]);
              delete pendingRequests[cacheKey];
              reject();
            }
          });
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
        el(BlockEdit, { ...props, key: 'abst-block-edit' }),
        el(InspectorAdvancedControls, { key: 'abst-inspector-controls' },
          el(PanelBody, { title: 'AB Split Test', initialOpen: true, className: 'abst-split-test-attributes' },
            el(ComboboxControl, {
              label: 'Test',
              value: String(experiment), // This ensures that if experiment is in filteredExperiments, it's selected
              help: el(
  'a',
  {
    className: 'new-on-page-test-button modern-abst-button',
    href: abst_gutenberg.admin_url + 'edit.php?post_type=bt_experiments',
    rel: 'noopener noreferrer',
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '0.5em',
      padding: '6px 12px',
      background: '#007cbad0',
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
  el('span', { style: { fontSize: '1.2em', marginRight: '0.3em' , fontWeight: 'bold'} }, '+'),
  'Create new Test'
),
              options: filteredExperiments,
              onChange: (newVal) => {
                // Predefined default variation names that should be preserved when changing tests
                const defaultVariationNames = ['original', 'one', '1', 'default', 'standard', 'a', 'control'];
                const currentVariation = props.attributes['bt-variation'] || '';
                const isDefaultName = defaultVariationNames.includes(currentVariation.toLowerCase());
                
                // Clear variation if it's not a predefined default (prevents cross-test contamination)
                if (currentVariation && !isDefaultName) {
                  props.setAttributes({
                    'bt-eid': newVal,
                    'bt-variation': ''
                  });
                } else {
                  props.setAttributes({
                    'bt-eid': newVal
                  });
                }
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


})( 
  window.wp.blocks,
  window.wp.blockEditor,
  window.wp.element,
  window.wp.components
);
