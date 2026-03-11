( function ( wp ) {
  'use strict';

  const registerPlugin = wp.plugins && wp.plugins.registerPlugin;
  const PluginPostStatusInfo = wp.editPost && wp.editPost.PluginPostStatusInfo;
  const createElement = wp.element && wp.element.createElement;
  const __ = wp.i18n && wp.i18n.__;
  const select = wp.data && wp.data.select;

  if ( ! registerPlugin || ! PluginPostStatusInfo || ! createElement || ! __ || ! select ) {
    return;
  }

  function ExcerptSeoHint() {
    const postType = select( 'core/editor' ).getCurrentPostType();

    if ( postType !== 'post' ) {
      return null;
    }

    return createElement(
      PluginPostStatusInfo,
      null,
      createElement(
        'div',
        { className: 'clean-researcher-editor-hint-wrap' },
        createElement(
          'p',
          { className: 'clean-researcher-editor-hint' },
          __( 'Excerpt is used for SEO meta description.', 'clean-researcher' )
        ),
        createElement(
          'p',
          { className: 'clean-researcher-editor-hint' },
          __( 'Add meaningful alt text to images for accessibility and image SEO.', 'clean-researcher' )
        )
      )
    );
  }

  registerPlugin( 'clean-researcher-editor-hints', {
    render: ExcerptSeoHint,
  } );
} )( window.wp );
