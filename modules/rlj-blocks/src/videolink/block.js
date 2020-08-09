const { __ } = wp.i18n;

const {
  registerBlockType,
} = wp.blocks;

const {
  // BlockControls,
  // BlockIcon,
  // InnerBlocks,
  InspectorControls,
  // MediaPlaceholder,
  // MediaUpload,
  // MediaUploadCheck,
	// withColors,
  // PanelColorSettings,
} = wp.blockEditor;

const {
  // IconButton,
  // Toolbar,
  // Button,
  // FormToggle,
  // Panel,
  // PanelBody,
  // PanelRow,
  // SelectControl,
  TextControl,
} = wp.components;

registerBlockType( 'rlj/videolink', {

  title: __( 'Videolink' ),
  icon: 'format-video',
  category: 'embed', // common, formatting, layout widgets, embed
  keywords: [
    __( 'rlj-blocks' ),
    __( 'Video' ),
    __( 'YouTube' ),
    __( 'Vimeo' ),
  ],
  attributes: {
    'url': {
      'type': 'string'
    },
    'text': {
      'type': 'string'
    },
    'title': {
      'type': 'string'
    },
    'id': {
      'type': 'string'
    },
    'provider': {
      'type': 'string',
    },
    'asset_id': {
      'type': 'string'
    },
    'width': {
      'type': 'integer'
    },
    'height': {
      'type': 'integer'
    },
    'asset_url': {
      'type': 'string'
    },
  },

  edit: function( props ) {

    var MODULE_EXISTS  = typeof COCKPIT_VIDEOLINK_ROUTE != 'undefined',
        isWPMultiplane = typeof COCKPIT_UPLOAD_FOLDER != 'undefined';

    function updateUrl(value) {

      if (!MODULE_EXISTS) {
        console.log('module VideoLinkField is missing or not configured correctly');
        return;
      }

      var url   = value,
          video = parseVideoUrl(url),
          meta  = {
            video_id: video.id,
            video_provider: video.provider
          };

      if (video.id && video.id != 'none') {

        props.setAttributes({
          provider: video.provider,
          id: video.id,
          url: url
        });

        jQuery.ajax({
          url: COCKPIT_VIDEOLINK_ROUTE + '/getVideoLinkData',
          type: 'post',
          data: meta,
          success:function(data) {
            props.setAttributes(isWPMultiplane ? {
              width:      data.width,
              height:     data.height,
              asset_id:   data._id,
              text:       data.title,
              asset_url:  COCKPIT_UPLOAD_FOLDER + data.path
            } : {
              width:      data.meta.width,
              height:     data.meta.height,
              asset_id:   data.ID,
              text:       data.post_title,
              asset_url:  data.guid
            });
          },
          error: function(errorThrown){
            console.log(errorThrown);
          }
        });

      }
      else {
        props.setAttributes({
          provider: null,
          id: null,
          url: null,
          text: null,
          asset_id: null,
          asset_url: null,
          width: null,
          height: null
        });

      }

    }

    function parseVideoUrl(url) {

        var video = {};
        var regExpYouTube = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        var regExpVimeo   = /https?:\/\/(?:www\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)(?:$|\/|\?)/;

        var match = url.match(regExpYouTube);

        if (match && match[2].length == 11) {
            video.id       = match[2];
            video.provider = 'youtube';
        }

        else {
            match = url.match(regExpVimeo);
            if (match) {
                video.id       = match[3];
                video.provider = 'vimeo';
            }
            else {
                video.id       = 'none';
                video.provider = 'none';
            }
        }
        return video;
    }

    var style = {textAlign:'center'};
    var image_style = {display:'block',margin:'0 auto'};

    return [
      <InspectorControls>
          <TextControl
            label={ __('Url') }
            value={props.attributes.url}
            help={ __('Enter a YouTube or Vimeo link.') }
            onChange={updateUrl}
          />
          <p>{ __('The thumbnail will be saved in your media library.') }</p>
          <TextControl
            label={ __('Text') }
            value={props.attributes.text}
            onChange={ (value) => { props.setAttributes({text:value}) } }
          />
      </InspectorControls>
      ,
      <div style={style}>
        { (!props.attributes.id || props.attributes.id == 'none') && (
          <TextControl
            label={ __('Url') }
            value={props.attributes.url}
            help={ __('Enter a YouTube or Vimeo link.') }
            onChange={updateUrl}
          />
        ) }
        { (props.attributes.id && props.attributes.asset_url) && (
          <img
            src={props.attributes.asset_url}
            alt="video thumbnail"
            style={image_style}
          />
        ) }
        { (props.attributes.id && props.attributes.asset_url) && (
          <a
            href={props.attributes.url}
            title={props.attributes.title}
            data-video-provider={props.attributes.provider}
            data-video-id={props.attributes.id}
            data-video-width={props.attributes.width}
            data-video-height={props.attributes.height}
            data-video-thumb={props.attributes.asset_url}
          >
          {props.attributes.text}
          </a>
        ) }
      </div>
    ];

  },

  save: function( props ) {

    return (
      <a
        href={props.attributes.url}
        title={props.attributes.title}
        data-video-provider={props.attributes.provider}
        data-video-id={props.attributes.id}
        data-video-width={props.attributes.width}
        data-video-height={props.attributes.height}
        data-video-thumb={props.attributes.asset_url}
      >
      {props.attributes.text}
      </a>
    );
  },

} );
