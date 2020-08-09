
// inspired by: https://jeffreycarandang.com/extending-gutenberg-core-blocks-with-custom-attributes-and-controls/


const { __ } = wp.i18n;
const { addFilter } = wp.hooks;
const { Fragment }	= wp.element;

const {
  InspectorControls,
  // InspectorAdvancedControls,
} = wp.blockEditor;

const {
  createHigherOrderComponent
} = wp.compose;

const {
  ToggleControl,
  // TextControl,
  PanelBody,
  PanelRow,
} = wp.components;

// restrict to specific block names
const allowedBlocks = [
  'core/gallery'
];

/**
 * Add custom attribute for title.
 *
 * @param {Object} settings Settings for the block.
 * @return {Object} settings Modified settings.
 */
function addAttributes( settings ) {

	// check if object exists for old Gutenberg version compatibility
	// add allowedBlocks restriction
	if( typeof settings.attributes !== 'undefined' && allowedBlocks.includes( settings.name ) ){

		settings.attributes = Object.assign( settings.attributes, {
      captionsToTitles: {
        type: 'boolean',
        defautl: false
      }
		});

	}

	return settings;
}

/**
 * Add title on Advanced Block Panel.
 *
 * @param {function} BlockEdit Block edit component.
 * @return {function} BlockEdit Modified block edit component.
 */
const withAdvancedControls = createHigherOrderComponent( ( BlockEdit ) => {

	return ( props ) => {

		const {
			name,
			attributes,
			setAttributes,
		} = props;

		const {
			captionsToTitles,
		} = attributes;

		return (
			<Fragment>
				<BlockEdit {...props} />
				{ allowedBlocks.includes( name ) &&
					<InspectorControls>
            <PanelBody>
              <PanelRow>
                <ToggleControl
                  label={ __('Use captions as titles') }
                  help={ __('experimental') }
                  checked={captionsToTitles}
                  onChange={ () => { setAttributes({captionsToTitles: !captionsToTitles}) } }
                />
              </PanelRow>
            </PanelBody>
					</InspectorControls>
				}

			</Fragment>
		);
	};

}, 'withAdvancedControls');

/**
 * Add custom element class in save element.
 *
 * @param {Object} extraProps     Block element.
 * @param {Object} blockType      Blocks object.
 * @param {Object} attributes     Blocks attributes.
 *
 * @return {Object} extraProps Modified block element.
 */

function applyGalleryTitles( extraProps, blockType, attributes ) {

	const { captionsToTitles } = attributes;

// console.log(attributes);
// console.log(extraProps);
// console.log(extraProps.children.props.children);

	if ( typeof captionsToTitles !== 'undefined' && allowedBlocks.includes( blockType.name ) ) {

    extraProps.captionsToTitles = captionsToTitles;

	}

	return extraProps;
}

//add filters
addFilter(
	'blocks.registerBlockType',
	'rlj/gallery-titles',
	addAttributes
);

addFilter(
	'editor.BlockEdit',
	'rlj/gallery-titles-control',
	withAdvancedControls
);

addFilter(
	'blocks.getSaveContent.extraProps',
	'rlj/applyGalleryTitles',
	applyGalleryTitles
);
