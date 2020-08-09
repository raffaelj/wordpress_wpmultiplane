
// Notes and research:
// https://javascriptforwp.com/how-to-use-inspectorcontrols/
// https://github.com/WordPress/gutenberg/blob/master/packages/block-library/src/cover/edit.js
// https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
// https://developer.wordpress.org/resource/dashicons/
// https://wordpress.org/gutenberg/handbook/block-api/

// <pre>{JSON.stringify(props, null, 2)}</pre>


//  Import CSS.
import './editor.scss';

const { __ } = wp.i18n;

const {
  registerBlockType,
} = wp.blocks;

const {
  BlockControls,
  BlockIcon,
  InnerBlocks,
  InspectorControls,
  MediaPlaceholder,
  MediaUpload,
  MediaUploadCheck,
	withColors,
  PanelColorSettings,
} = wp.blockEditor;

const {
  // IconButton,
  // Toolbar,
  Button,
  FormToggle,
  // Panel,
  PanelBody,
  PanelRow,
  SelectControl,
} = wp.components;

const ALLOWED_MEDIA_TYPES = [ 'image' ];
const IMAGE_BACKGROUND_TYPE = 'image';


// fix to prevent a crash when clicking on "Styles" in the sidebar
// if using without Gutenberg addon version >= 6.0.0
// source: https://github.com/WordPress/gutenberg/issues/9897#issuecomment-478362380
var el = wp.element.createElement;
var allowSectionStyles = wp.compose.createHigherOrderComponent( function( BlockEdit ) {

  return function( props ) {
    var content = el( BlockEdit, props );

    if( props.name === 'rlj/section' && typeof props.insertBlocksAfter === 'undefined' ) {
      content = el( 'div', {} );
    }

    return el(
      wp.element.Fragment, {}, content
    );
  };

}, 'allowSectionStyles' );
wp.hooks.addFilter( 'editor.BlockEdit', 'rlj/section', allowSectionStyles );
// end of styles crash fix


registerBlockType( 'rlj/section', {

  title: __( 'Section' ),
  icon: 'layout',
  category: 'common', // common, formatting, layout widgets, embed.
  keywords: [
    __( 'rlj-blocks' ),
    __( 'Background' ),
    __( 'Wrapper' ),
  ],
  supports: {
    // align: true,
    align: [
        'full',
        'wide'
    ],
    anchor: true,
  },
  styles: [
    {
      name: 'default',
      label: __( 'Default' ),
      isDefault: true
    },
    {
      name: 'rounded',
      label: __( 'Rounded' ),
    },
    {
      name: 'shadow',
      label: __( 'Shadow' )
    },
    {
      name: 'shadow-rounded',
      label: __( 'Shadow + Rounded' )
    },
  ],
  attributes: {
    'url': {
      'type': 'string'
    },
    'size': {
      'type': 'string',
      'default': 'large'
    },
    'sizes': {
      'type': 'object'
    },
    'id': {
      'type': 'number'
    },
    'hasParallax': {
      'type': 'boolean',
      'default': false
    },
    'overlay': {
      'type': 'boolean',
      'default': false
    },
    'hideBackgroundWhileEditing': {
      'type': 'boolean',
      'default': false
    },
    'backgroundColor': {
      'type': 'string'
    },
    'customBackgroundColor': {
      'type': 'string'
    },
    // 'textColor': {
      // 'type': 'object'
    // },
    // 'dimRatio': {
      // 'type': 'number',
      // 'default': 50
    // },
    // 'overlayColor': {
      // 'type': 'string'
    // },
    // 'customOverlayColor': {
      // 'type': 'string'
    // },
    // 'backgroundType': {
      // 'type': 'string',
      // 'default': 'image'
    // },
    // 'focalPoint': {
      // 'type': 'object'
    // }
  },

  // edit: withColors( 'backgroundColor', { textColor: 'color' } )(function( props ) {
  edit: withColors( 'backgroundColor')(function( props ) {

    function getImageSizeOptions() {

      if (typeof props.attributes.sizes == 'object') {
        return Object.keys(props.attributes.sizes)
                .map(function(k) { return {label:k, value:k}; });
      }

      return [];

    }

    var style = {};
    var classes = props.className;

    var image_preview_style = {
      maxHeight: '150px',
      margin: '0 auto',
    }

    var image_hide_style = {
      maxWidth: '40px',
      maxHeight: '40px',
      margin: '0',
      position: 'absolute',
      right: '0',
      opacity: '.3'
    }

    if (props.attributes.url) {

      if (!props.attributes.hideBackgroundWhileEditing) {
        style.backgroundImage = 'url(' + props.attributes.url + ')';
      }
      
      classes += ' has-background';
      
      if (props.attributes.hasParallax) {
          classes += ' has-parallax';
      }

    }

    if (props.backgroundColor.color) {

      if (props.backgroundColor.color) {
        style.backgroundColor = props.backgroundColor.color
      }

    }

    if (props.attributes.overlay) {
      classes += ' has-background-dim';
    }

    return [
      <InspectorControls>

            <PanelBody>
              <PanelRow>
              { !props.attributes.url && (
                <MediaPlaceholder
                  labels={ {
                    title: __( 'Background' ),
                    instructions: __( 'Upload an image or pick one from your media library.' ),
                  } }
                  onSelect={ media => {
                    // console.log(media);
                    props.setAttributes({
                      id: media.id,
                      url: media.url,
                      sizes: media.sizes
                      });
                    if (props.attributes.size && media.sizes[props.attributes.size]) {
                      props.setAttributes({
                        url: media.sizes[props.attributes.size].url
                      })
                    }
                  } }
                  accept='image/*'
                  allowedTypes={ ALLOWED_MEDIA_TYPES }
                  notices={ props.noticeUI }
                  onError={ props.onUploadError }
                />
              ) }
              </PanelRow>

              { props.attributes.url && (
              <PanelRow>
                <img src={ props.attributes.url } style={ image_preview_style } />
              </PanelRow>
              ) }

              { props.attributes.url && (
              <PanelRow>
                <MediaUploadCheck>
                  <MediaUpload
                    onSelect={ media => {
                      props.setAttributes({
                        id: media.id,
                        url: media.url,
                        sizes: media.sizes });
                      if (props.attributes.size && media.sizes[props.attributes.size]) {
                        props.setAttributes({
                          url: media.sizes[props.attributes.size].url
                        })
                      }
                    } }
                    allowedTypes={ ALLOWED_MEDIA_TYPES }
                    value={ props.attributes.id }
                    render={ ( { open } ) => (
                      <Button
                        onClick={ open }
                        className="button button-large"
                      >
                      { __('Change background') }
                      </Button>
                    ) }
                  />
                </MediaUploadCheck>
              </PanelRow>
              ) }

              { props.attributes.url && (
              <PanelRow>
                <Button 
                  onClick={ () => { props.setAttributes({ id: null, url: null }); } }
                  className="button button-large"
                >
                { __('Remove background') }
                </Button>
              </PanelRow>
              ) }

              { props.attributes.url && (
              <PanelRow>
                <SelectControl
                  label={ __( 'Image Size' ) }
                  value={ props.attributes.size || '' }
                  options={ getImageSizeOptions() }
                  onChange={ (size) => { props.setAttributes({ size: size, url: props.attributes.sizes[size].url }); } }
                />
              </PanelRow>
              ) }

              { props.attributes.url && (
              <PanelRow>
                <label
                  htmlFor="rlj-section-has-parallax"
                >
                    { __( 'Fixed background', 'rlj' ) }
                </label>
                <FormToggle
                  id="rlj-section-has-parallax"
                  label={ __( 'Fixed background', 'rlj' ) }
                  checked={ props.attributes.hasParallax }
                  onChange={ () => { props.setAttributes({ hasParallax: !props.attributes.hasParallax }); } }
                />
              </PanelRow>
              ) }

              { props.attributes.url && (
              <PanelRow>
                <label
                  htmlFor="rlj-section-hide-background"
                >
                    { __( 'Hide background while editing', 'rlj' ) }
                </label>
                <FormToggle
                  id="rlj-section-hide-background"
                  label={ __( 'Hide background while editing', 'rlj' ) }
                  checked={ props.attributes.hideBackgroundWhileEditing }
                  onChange={ () => { props.setAttributes({ hideBackgroundWhileEditing: !props.attributes.hideBackgroundWhileEditing }); } }
                />
              </PanelRow>
              ) }
            </PanelBody>
            <PanelColorSettings
              title={ __( 'Color Settings' ) }
              initialOpen={ false }
              colorSettings={ [
                {
                  value: props.backgroundColor.color,
                  onChange: props.setBackgroundColor,
                  label: __( 'Background Color' ),
                },
                // {
                  // value: props.textColor.color,
                  // onChange: props.setTextColor,
                  // label: __( 'Text Color' ),
                // },
              ] }
            >
            {/*<PanelRow>
              <label
                htmlFor="rlj-section-overlay"
              >
                  { __( 'Overlay', 'rlj' ) }
              </label>
              <FormToggle
                id="rlj-section-overlay"
                label={ __( 'Overlay', 'rlj' ) }
                checked={ props.attributes.overlay }
                onChange={ () => { props.setAttributes({ overlay: !props.attributes.overlay }); } }
              />
            </PanelRow>*/}
            </PanelColorSettings>

      </InspectorControls>
      ,
      <div
        className={ classes }
        style={ style }
      >
        { (props.attributes.url && props.attributes.hideBackgroundWhileEditing) && ( 
          <img
            src={ props.attributes.url }
            style={ image_hide_style }
          />
         )
        }
        <InnerBlocks />
      </div>
    ];

  }),

  save: function( props ) {

    var style = {};
    var classes = '';

    if (props.attributes.url) {

      style.backgroundImage = 'url(' + props.attributes.url + ')';

      classes += ' has-background';

      if (props.attributes.hasParallax) {
          classes += ' has-parallax';
      }

    }

    if (props.attributes.backgroundColor) {
      classes += ' has-' + props.attributes.backgroundColor + '-background-color';
    }

    if (props.attributes.customBackgroundColor) {
      style.backgroundColor = props.attributes.customBackgroundColor;
    }

    if (props.attributes.overlay) {
      classes += ' has-background-dim';
    }

    return (
      <div
        className={ classes }
        style={ style }
      >
        <InnerBlocks.Content />
      </div>
    );
  },

} );
