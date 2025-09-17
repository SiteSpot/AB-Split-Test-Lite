( function( blocks, editor, element, components ) {

  const el = element.createElement;
  const { addFilter } = wp.hooks;
  const { registerBlockType } = blocks;
  const { RichText, InspectorControls } = editor;
  const { Fragment } = element;
  const {
    TextControl,
    SelectControl,
    Panel,
    PanelBody,
    PanelRow
  } = components;
  var controls_xhr = null;

  // the experiment posts
  const experimentControlOptions = JSON.parse(bt_gutenberg_ab_redirect.experiments);
  
  const bt_abr_conversion_attr = {
    bt_experiment: '',
    bt_variation: '',
    redirect_url: ''
  };

  const htmlToElem = ( html ) => wp.element.RawHTML( { children: html } );

  var bt_abr_sdata = {
    'action': bt_gutenberg_ab_redirect.actions.render_html,
    'data'  : bt_abr_conversion_attr,   
    'nonce' : bt_gutenberg_ab_redirect.nonce,
  };

  Object.keys(bt_gutenberg_ab_redirect.redirect_list).forEach(function(key) {
    var item = bt_gutenberg_ab_redirect.redirect_list[key];

      bt_gutenberg_ab_redirect.option_html += '<optgroup label="'+ key +'">';

      Object.keys(item).forEach(function(i) {            
        bt_gutenberg_ab_redirect.option_html += '<option value="'+ item[i].id +'">'+ item[i].post_title +'</option>';
      });

      bt_gutenberg_ab_redirect.option_html += '</optgroup>';
  });

  /*
   * Add ab test redirect block
   */
  registerBlockType( 'bt-experiments/gutenberg-ab-redirect', {
    title: 'AB Test Page Redirect',
    icon: 'plus',
    category: 'common',
    attributes: {
      bt_experiment:{
        type: 'string',
        default: ''
      },
      bt_variation: {
        type: 'string',
        default: ''
      },
      redirect_url: {
        type: 'string',
        default: ''
      },
      ab_test_html: {
        type: 'string',
        default: bt_gutenberg_ab_redirect.editor_html
      }
    },    
    edit: function(props) {

      bt_abr_conversion_attr['bt_experiment'] = props.attributes.bt_experiment;
      bt_abr_conversion_attr['bt_variation']  = props.attributes.bt_variation;
      bt_abr_conversion_attr['redirect_url']  = props.attributes.redirect_url;

      var abredirect_module = (
        el( Fragment, {},          
          el( InspectorControls, {},        
            el( PanelBody, { title: 'AB Test Page Redirect Module', initialOpen: false },           
              el( SelectControl,
                {
                  label: 'AB Test',
                  options : experimentControlOptions['experiments'],
                  onChange: ( eid ) => {  

                    bt_abr_conversion_attr['bt_experiment'] = eid;

                    props.setAttributes({ 
                      bt_experiment: eid
                    });

                    if( controls_xhr != null )
                    {
                      controls_xhr.abort();
                      controls_xhr = null;
                    }

                    controls_xhr = jQuery.ajax({
                      type: 'POST',
                      url: bt_gutenberg_ab_redirect.ajax_url,
                      data: bt_abr_sdata,
                      success: function(response) {
                        props.setAttributes({ 
                          ab_test_html: response
                        });
                      },
                      error: function(jqXHR, textStatus, errorThrown) {

                      }
                    }); 

                  },
                  value: props.attributes.bt_experiment,
                  help: htmlToElem('<a class="new-on-page-test-button" href="/wp-admin/edit.php?post_type=bt_experiments" target="_blank">View or create experiments. <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==" alt="opens in a new window"></a>')
                }
              ),
              el( TextControl, 
                {
                  label: 'Variation Name',
                  value: props.attributes.bt_variation,
                  help: htmlToElem('Using "default" will cause this version to run first, unless otherwise targeted. <a href="#">more info <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==" alt="opens in a new window"></a>'),
                  onChange: (variation) => {

                    bt_abr_conversion_attr['bt_variation'] = variation;

                    props.setAttributes({ 
                      bt_variation: variation
                    });

                    if( controls_xhr != null )
                    {
                      controls_xhr.abort();
                      controls_xhr = null;
                    }

                    controls_xhr = jQuery.ajax({
                      type: 'POST',
                      url: bt_gutenberg_ab_redirect.ajax_url,
                      data: bt_abr_sdata,
                      success: function(response) {
                        props.setAttributes({ 
                          ab_test_html: response
                        });
                      },
                      error: function(jqXHR, textStatus, errorThrown) {

                      }
                    }); 
                  }
                }
              ), 
              el( SelectControl,
                {
                  label: 'Redirect URL',
                  options : [
                    {
                      label : '',
                      value : ''
                    }
                  ],
                  onChange: ( url ) => {  

                    bt_abr_conversion_attr['redirect_url'] = url;

                    if( controls_xhr != null )
                    {
                      controls_xhr.abort();
                      controls_xhr = null;
                    }

                    props.setAttributes({ 
                      redirect_url: url
                    });

                    controls_xhr = jQuery.ajax({
                      type: 'POST',
                      url: bt_gutenberg_ab_redirect.ajax_url,
                      data: bt_abr_sdata,
                      success: function(response) {
                        props.setAttributes({ 
                          ab_test_html: response
                        });
                      },
                      error: function(jqXHR, textStatus, errorThrown) {

                      }
                    });   
                  },
                  // value: props.attributes.redirect_url,
                  className: 'bt-ab-page-redirect',
                  'bt-rid': props.attributes.redirect_url
                }
              ),            
            )           
          ),
          htmlToElem(props.attributes.ab_test_html)
        )
      );
      
      return abredirect_module;
    },
    save: function(props) {

      var attr = '';

      Object.keys(bt_abr_sdata.data).forEach(function(key) {
        attr += ' '+ key +'='+  props.attributes[key];
      });

      return htmlToElem('['+ bt_gutenberg_ab_redirect.shortcode_name + attr +' ]');
    },
  });

})(
  window.wp.blocks,
  window.wp.editor,
  window.wp.element,
  window.wp.components,
);