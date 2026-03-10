/**
 * Clean Researcher theme – Customizer live preview
 * Updates CSS custom properties instantly when theme mods change.
 */
( function () {
  'use strict';

  function applyFontVar( varName, value ) {
    const fallback = varName === '--font-title' ? 'serif' : 'sans-serif';
    const cssValue = value ? '"' + value + '", ' + fallback : '';

    if ( cssValue ) {
      document.documentElement.style.setProperty( varName, cssValue );
      return;
    }

    document.documentElement.style.removeProperty( varName );
  }

  function applyContentWidth( value ) {
    const width = Math.max( 640, Math.min( 840, Number( value ) || 760 ) );
    document.documentElement.style.setProperty( '--content-max', width + 'px' );
    document.documentElement.style.setProperty( '--layout-max', width + 560 + 'px' );
  }

  wp.customize( 'clean_researcher_font_title', function ( setting ) {
    setting.bind( function ( value ) {
      applyFontVar( '--font-title', value );
    } );
  } );

  wp.customize( 'clean_researcher_font_body', function ( setting ) {
    setting.bind( function ( value ) {
      applyFontVar( '--font-body', value );
    } );
  } );

  wp.customize( 'clean_researcher_content_width', function ( setting ) {
    setting.bind( function ( value ) {
      applyContentWidth( value );
    } );
  } );
} )();
