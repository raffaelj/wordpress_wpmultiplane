
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
  // TextControl,
  TextareaControl,
  PanelBody,
  PanelRow,
} = wp.components;

// restrict to specific block names
const allowedBlocks = [
  'core/image'
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
      title: {
        type: 'string'
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
			title,
		} = attributes;

		return (
			<Fragment>
				<BlockEdit {...props} />
				{ allowedBlocks.includes( name ) &&
					<InspectorControls>
            <PanelBody>
              <PanelRow>
                <TextareaControl
                  label={ __('Title') }
                  value={props.attributes.title}
                  onChange={ (value) => { props.setAttributes({title:value}) } }
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

function applyImageTitle( extraProps, blockType, attributes ) {

	const { title } = attributes;

	if ( typeof title !== 'undefined' && allowedBlocks.includes( blockType.name ) ) {

    if (extraProps.children && extraProps.children.props
      && extraProps.children.props.children
      && extraProps.children.props.children[0]
      && extraProps.children.props.children[0].props) {

        extraProps.children.props.children[0].props.title = title;

    }

	}

	return extraProps;
}

//add filters
addFilter(
	'blocks.registerBlockType',
	'rlj/image-title',
	addAttributes
);

addFilter(
	'editor.BlockEdit',
	'rlj/image-title-control',
	withAdvancedControls
);

addFilter(
	'blocks.getSaveContent.extraProps',
	'rlj/applyImageTitle',
	applyImageTitle
);
