!function(e){function t(l){if(r[l])return r[l].exports;var n=r[l]={i:l,l:!1,exports:{}};return e[l].call(n.exports,n,n.exports,t),n.l=!0,n.exports}var r={};t.m=e,t.c=r,t.d=function(e,r,l){t.o(e,r)||Object.defineProperty(e,r,{configurable:!1,enumerable:!0,get:l})},t.n=function(e){var r=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(r,"a",r),r},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=0)}([function(e,t,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var l=(r(1),r(3)),n=(r.n(l),r(4)),i=(r.n(n),r(5));r.n(i)},function(e,t,r){"use strict";var l=r(2),n=(r.n(l),"function"===typeof Symbol&&"symbol"===typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"===typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e}),__=wp.i18n.__,i=wp.blocks.registerBlockType,a=wp.blockEditor,o=(a.BlockControls,a.BlockIcon,a.InnerBlocks),u=a.InspectorControls,s=a.MediaPlaceholder,c=a.MediaUpload,d=a.MediaUploadCheck,p=a.withColors,m=a.PanelColorSettings,b=wp.components,g=b.Button,h=b.FormToggle,w=b.PanelBody,f=b.PanelRow,y=b.SelectControl,k=["image"],v=wp.element.createElement,E=wp.compose.createHigherOrderComponent(function(e){return function(t){var r=v(e,t);return"rlj/section"===t.name&&"undefined"===typeof t.insertBlocksAfter&&(r=v("div",{})),v(wp.element.Fragment,{},r)}},"allowSectionStyles");wp.hooks.addFilter("editor.BlockEdit","rlj/section",E),i("rlj/section",{title:__("Section"),icon:"layout",category:"common",keywords:[__("rlj-blocks"),__("Background"),__("Wrapper")],supports:{align:["full","wide"],anchor:!0},styles:[{name:"default",label:__("Default"),isDefault:!0},{name:"rounded",label:__("Rounded")},{name:"shadow",label:__("Shadow")},{name:"shadow-rounded",label:__("Shadow + Rounded")}],attributes:{url:{type:"string"},size:{type:"string",default:"large"},sizes:{type:"object"},id:{type:"number"},hasParallax:{type:"boolean",default:!1},overlay:{type:"boolean",default:!1},hideBackgroundWhileEditing:{type:"boolean",default:!1},backgroundColor:{type:"string"},customBackgroundColor:{type:"string"}},edit:p("backgroundColor")(function(e){var t={},r=e.className,l={maxHeight:"150px",margin:"0 auto"},i={maxWidth:"40px",maxHeight:"40px",margin:"0",position:"absolute",right:"0",opacity:".3"};return e.attributes.url&&(e.attributes.hideBackgroundWhileEditing||(t.backgroundImage="url("+e.attributes.url+")"),r+=" has-background",e.attributes.hasParallax&&(r+=" has-parallax")),e.backgroundColor.color&&e.backgroundColor.color&&(t.backgroundColor=e.backgroundColor.color),e.attributes.overlay&&(r+=" has-background-dim"),[wp.element.createElement(u,null,wp.element.createElement(w,null,wp.element.createElement(f,null,!e.attributes.url&&wp.element.createElement(s,{labels:{title:__("Background"),instructions:__("Upload an image or pick one from your media library.")},onSelect:function(t){e.setAttributes({id:t.id,url:t.url,sizes:t.sizes}),e.attributes.size&&t.sizes[e.attributes.size]&&e.setAttributes({url:t.sizes[e.attributes.size].url})},accept:"image/*",allowedTypes:k,notices:e.noticeUI,onError:e.onUploadError})),e.attributes.url&&wp.element.createElement(f,null,wp.element.createElement("img",{src:e.attributes.url,style:l})),e.attributes.url&&wp.element.createElement(f,null,wp.element.createElement(d,null,wp.element.createElement(c,{onSelect:function(t){e.setAttributes({id:t.id,url:t.url,sizes:t.sizes}),e.attributes.size&&t.sizes[e.attributes.size]&&e.setAttributes({url:t.sizes[e.attributes.size].url})},allowedTypes:k,value:e.attributes.id,render:function(e){var t=e.open;return wp.element.createElement(g,{onClick:t,className:"button button-large"},__("Change background"))}}))),e.attributes.url&&wp.element.createElement(f,null,wp.element.createElement(g,{onClick:function(){e.setAttributes({id:null,url:null})},className:"button button-large"},__("Remove background"))),e.attributes.url&&wp.element.createElement(f,null,wp.element.createElement(y,{label:__("Image Size"),value:e.attributes.size||"",options:function(){return"object"==n(e.attributes.sizes)?Object.keys(e.attributes.sizes).map(function(e){return{label:e,value:e}}):[]}(),onChange:function(t){e.setAttributes({size:t,url:e.attributes.sizes[t].url})}})),e.attributes.url&&wp.element.createElement(f,null,wp.element.createElement("label",{htmlFor:"rlj-section-has-parallax"},__("Fixed background","rlj")),wp.element.createElement(h,{id:"rlj-section-has-parallax",label:__("Fixed background","rlj"),checked:e.attributes.hasParallax,onChange:function(){e.setAttributes({hasParallax:!e.attributes.hasParallax})}})),e.attributes.url&&wp.element.createElement(f,null,wp.element.createElement("label",{htmlFor:"rlj-section-hide-background"},__("Hide background while editing","rlj")),wp.element.createElement(h,{id:"rlj-section-hide-background",label:__("Hide background while editing","rlj"),checked:e.attributes.hideBackgroundWhileEditing,onChange:function(){e.setAttributes({hideBackgroundWhileEditing:!e.attributes.hideBackgroundWhileEditing})}}))),wp.element.createElement(m,{title:__("Color Settings"),initialOpen:!1,colorSettings:[{value:e.backgroundColor.color,onChange:e.setBackgroundColor,label:__("Background Color")}]})),wp.element.createElement("div",{className:r,style:t},e.attributes.url&&e.attributes.hideBackgroundWhileEditing&&wp.element.createElement("img",{src:e.attributes.url,style:i}),wp.element.createElement(o,null))]}),save:function(e){var t={},r="";return e.attributes.url&&(t.backgroundImage="url("+e.attributes.url+")",r+=" has-background",e.attributes.hasParallax&&(r+=" has-parallax")),e.attributes.backgroundColor&&(r+=" has-"+e.attributes.backgroundColor+"-background-color"),e.attributes.customBackgroundColor&&(t.backgroundColor=e.attributes.customBackgroundColor),e.attributes.overlay&&(r+=" has-background-dim"),wp.element.createElement("div",{className:r,style:t},wp.element.createElement(o.Content,null))}})},function(e,t){},function(e,t){var __=wp.i18n.__,r=wp.blocks.registerBlockType,l=wp.blockEditor.InspectorControls,n=wp.components.TextControl;r("rlj/videolink",{title:__("Videolink"),icon:"format-video",category:"embed",keywords:[__("rlj-blocks"),__("Video"),__("YouTube"),__("Vimeo")],attributes:{url:{type:"string"},text:{type:"string"},title:{type:"string"},id:{type:"string"},provider:{type:"string"},asset_id:{type:"string"},width:{type:"integer"},height:{type:"integer"},asset_url:{type:"string"}},edit:function(e){function t(t){if(!i)return void console.log("module VideoLinkField is missing or not configured correctly");var l=t,n=r(l),o={video_id:n.id,video_provider:n.provider};n.id&&"none"!=n.id?(e.setAttributes({provider:n.provider,id:n.id,url:l}),jQuery.ajax({url:COCKPIT_VIDEOLINK_ROUTE+"/getVideoLinkData",type:"post",data:o,success:function(t){e.setAttributes(a?{width:t.width,height:t.height,asset_id:t._id,text:t.title,asset_url:COCKPIT_UPLOAD_FOLDER+t.path}:{width:t.meta.width,height:t.meta.height,asset_id:t.ID,text:t.post_title,asset_url:t.guid})},error:function(e){console.log(e)}})):e.setAttributes({provider:null,id:null,url:null,text:null,asset_id:null,asset_url:null,width:null,height:null})}function r(e){var t={},r=/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/,l=/https?:\/\/(?:www\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)(?:$|\/|\?)/,n=e.match(r);return n&&11==n[2].length?(t.id=n[2],t.provider="youtube"):(n=e.match(l),n?(t.id=n[3],t.provider="vimeo"):(t.id="none",t.provider="none")),t}var i="undefined"!=typeof COCKPIT_VIDEOLINK_ROUTE,a="undefined"!=typeof COCKPIT_UPLOAD_FOLDER,o={textAlign:"center"},u={display:"block",margin:"0 auto"};return[wp.element.createElement(l,null,wp.element.createElement(n,{label:__("Url"),value:e.attributes.url,help:__("Enter a YouTube or Vimeo link."),onChange:t}),wp.element.createElement("p",null,__("The thumbnail will be saved in your media library.")),wp.element.createElement(n,{label:__("Text"),value:e.attributes.text,onChange:function(t){e.setAttributes({text:t})}})),wp.element.createElement("div",{style:o},(!e.attributes.id||"none"==e.attributes.id)&&wp.element.createElement(n,{label:__("Url"),value:e.attributes.url,help:__("Enter a YouTube or Vimeo link."),onChange:t}),e.attributes.id&&e.attributes.asset_url&&wp.element.createElement("img",{src:e.attributes.asset_url,alt:"video thumbnail",style:u}),e.attributes.id&&e.attributes.asset_url&&wp.element.createElement("a",{href:e.attributes.url,title:e.attributes.title,"data-video-provider":e.attributes.provider,"data-video-id":e.attributes.id,"data-video-width":e.attributes.width,"data-video-height":e.attributes.height,"data-video-thumb":e.attributes.asset_url},e.attributes.text))]},save:function(e){return wp.element.createElement("a",{href:e.attributes.url,title:e.attributes.title,"data-video-provider":e.attributes.provider,"data-video-id":e.attributes.id,"data-video-width":e.attributes.width,"data-video-height":e.attributes.height,"data-video-thumb":e.attributes.asset_url},e.attributes.text)}})},function(e,t){function r(e){return"undefined"!==typeof e.attributes&&p.includes(e.name)&&(e.attributes=Object.assign(e.attributes,{title:{type:"string"}})),e}function l(e,t,r){var l=r.title;return"undefined"!==typeof l&&p.includes(t.name)&&e.children&&e.children.props&&e.children.props.children&&e.children.props.children[0]&&e.children.props.children[0].props&&(e.children.props.children[0].props.title=l),e}var __=wp.i18n.__,n=wp.hooks.addFilter,i=wp.element.Fragment,a=wp.blockEditor.InspectorControls,o=wp.compose.createHigherOrderComponent,u=wp.components,s=u.TextareaControl,c=u.PanelBody,d=u.PanelRow,p=["core/image"],m=o(function(e){return function(t){var r=t.name,l=t.attributes;t.setAttributes,l.title;return wp.element.createElement(i,null,wp.element.createElement(e,t),p.includes(r)&&wp.element.createElement(a,null,wp.element.createElement(c,null,wp.element.createElement(d,null,wp.element.createElement(s,{label:__("Title"),value:t.attributes.title,onChange:function(e){t.setAttributes({title:e})}})))))}},"withAdvancedControls");n("blocks.registerBlockType","rlj/image-title",r),n("editor.BlockEdit","rlj/image-title-control",m),n("blocks.getSaveContent.extraProps","rlj/applyImageTitle",l)},function(e,t){function r(e){return"undefined"!==typeof e.attributes&&p.includes(e.name)&&(e.attributes=Object.assign(e.attributes,{captionsToTitles:{type:"boolean",defautl:!1}})),e}function l(e,t,r){var l=r.captionsToTitles;return"undefined"!==typeof l&&p.includes(t.name)&&(e.captionsToTitles=l),e}var __=wp.i18n.__,n=wp.hooks.addFilter,i=wp.element.Fragment,a=wp.blockEditor.InspectorControls,o=wp.compose.createHigherOrderComponent,u=wp.components,s=u.ToggleControl,c=u.PanelBody,d=u.PanelRow,p=["core/gallery"],m=o(function(e){return function(t){var r=t.name,l=t.attributes,n=t.setAttributes,o=l.captionsToTitles;return wp.element.createElement(i,null,wp.element.createElement(e,t),p.includes(r)&&wp.element.createElement(a,null,wp.element.createElement(c,null,wp.element.createElement(d,null,wp.element.createElement(s,{label:__("Use captions as titles"),help:__("experimental"),checked:o,onChange:function(){n({captionsToTitles:!o})}})))))}},"withAdvancedControls");n("blocks.registerBlockType","rlj/gallery-titles",r),n("editor.BlockEdit","rlj/gallery-titles-control",m),n("blocks.getSaveContent.extraProps","rlj/applyGalleryTitles",l)}]);