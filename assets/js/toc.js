/**
 * Clean Researcher theme – TOC builder + mobile drawer
 *
 * 1. Scans .clean-researcher-content headings up to configured depth and injects anchor IDs.
 * 2. Builds the ordered list in both the sidebar TOC and the mobile drawer.
 * 3. Highlights the active heading while scrolling.
 * 4. Handles the mobile toggle button / overlay.
 */
( function () {
  'use strict';

  // ── Helpers ────────────────────────────────────────────────────────────────

  /** Convert a heading's text to a URL-friendly slug (unique). */
  const usedSlugs = {};

  function toSlug( text ) {
    let slug = text
      .toLowerCase()
      .replace( /[^\w\s-]/g, '' )
      .trim()
      .replace( /[\s_]+/g, '-' )
      .replace( /-+/g, '-' );

    if ( ! slug ) { slug = 'section'; }

    let unique = slug;
    let count  = 1;
    while ( usedSlugs[ unique ] ) {
      unique = slug + '-' + ( ++count );
    }
    usedSlugs[ unique ] = true;
    return unique;
  }

  function getMaxDepth() {
    const rawDepth = Number( window.cleanResearcherToc && window.cleanResearcherToc.maxDepth );

    if ( Number.isNaN( rawDepth ) ) {
      return 2;
    }

    return Math.max( 2, Math.min( 4, rawDepth ) );
  }

  // ── Build TOC ───────────────────────────────────────────────────────────────

  function buildToc() {
    const content = document.querySelector( '.clean-researcher-content' );
    if ( ! content ) { return; }

    const desktopNav = document.getElementById( 'toc-nav' );
    const mobileOl   = document.getElementById( 'toc-drawer-list' );

    // Bail early on templates that include article content but no TOC containers.
    if ( ! desktopNav && ! mobileOl ) { return; }

    const maxDepth = getMaxDepth();
    const selector = maxDepth === 2 ? 'h2' : ( maxDepth === 3 ? 'h2, h3' : 'h2, h3, h4' );
    const headings = Array.from( content.querySelectorAll( selector ) );

    if ( headings.length < 2 ) {
      // Not enough headings – hide every TOC shell completely.
      const sidebar = document.querySelector( '[data-toc-sidebar]' );
      const btn     = document.querySelector( '.toc-mobile-btn' );
      const drawer  = document.getElementById( 'toc-drawer' );
      const overlay = document.getElementById( 'toc-overlay' );

      [ sidebar, btn, drawer, overlay ].forEach( function ( el ) {
        if ( ! el ) { return; }
        el.hidden = true;
        el.setAttribute( 'aria-hidden', 'true' );
        // Force hide because utility classes like xl:block can override [hidden].
        el.style.display = 'none';
      } );

      return;
    }

    // Assign IDs where missing
    headings.forEach( function ( h ) {
      if ( ! h.id ) {
        h.id = toSlug( h.textContent );
      }
    } );

    const fragment = buildList( headings );

    // Inject into desktop sidebar
    const desktopOl = desktopNav ? desktopNav.querySelector( '.toc-list' ) : null;
    if ( desktopOl ) {
      desktopOl.replaceWith( fragment.cloneNode( true ) );
    }

    // Inject into mobile drawer
    if ( mobileOl ) {
      const mobileClone = fragment.cloneNode( true );
      mobileClone.id = 'toc-drawer-list';
      mobileOl.replaceWith( mobileClone );
    }

    // Re-query both lists for the scroll spy
    const allLinks = document.querySelectorAll(
      '#toc-nav .toc-list a, #toc-drawer-list a, .toc-list a'
    );

    setupScrollSpy( headings, allLinks );
  }

  /** Build a nested <ol> from a flat array of heading elements. */
  function buildList( headings ) {
    const root     = document.createElement( 'ol' );
    root.className = 'toc-list';

    // Level map: h2 → depth 0, h3 → depth 1, h4 → depth 2
    const tagDepth = { H2: 0, H3: 1, H4: 2 };

    const stack = [ { el: root, depth: -1 } ]; // stack of { el, depth }

    headings.forEach( function ( h ) {
      const depth   = tagDepth[ h.tagName ] ?? 0;
      const li      = document.createElement( 'li' );
      li.className  = 'toc-' + h.tagName.toLowerCase();

      const a       = document.createElement( 'a' );
      a.href        = '#' + h.id;
      a.className   = 'toc-link';
      a.textContent = h.textContent;
      li.appendChild( a );

      // Pop stack until we find a parent with smaller depth
      while ( stack.length > 1 && stack[ stack.length - 1 ].depth >= depth ) {
        stack.pop();
      }

      const parent = stack[ stack.length - 1 ].el;

      // If parent is <li>, we need an <ol> child
      if ( parent.tagName === 'LI' ) {
        let ol = parent.querySelector( ':scope > ol' );
        if ( ! ol ) {
          ol = document.createElement( 'ol' );
          parent.appendChild( ol );
        }
        ol.appendChild( li );
        stack.push( { el: li, depth } );
      } else {
        parent.appendChild( li );
        stack.push( { el: li, depth } );
      }
    } );

    return root;
  }

  // ── Scroll spy ─────────────────────────────────────────────────────────────

  function setupScrollSpy( headings, links ) {
    if ( ! ( 'IntersectionObserver' in window ) ) { return; }

    const linkMap = {};
    links.forEach( function ( a ) {
      const id = a.getAttribute( 'href' ).slice( 1 );
      if ( ! linkMap[ id ] ) { linkMap[ id ] = []; }
      linkMap[ id ].push( a );
    } );

    let activeId = null;

    const observer = new IntersectionObserver(
      function ( entries ) {
        entries.forEach( function ( entry ) {
          if ( entry.isIntersecting ) {
            const id = entry.target.id;
            if ( id !== activeId ) {
              // Remove old active
              if ( activeId && linkMap[ activeId ] ) {
                linkMap[ activeId ].forEach( function ( a ) {
                  a.classList.remove( 'is-active' );
                } );
              }
              activeId = id;
              if ( linkMap[ id ] ) {
                linkMap[ id ].forEach( function ( a ) {
                  a.classList.add( 'is-active' );
                } );
              }
            }
          }
        } );
      },
      {
        rootMargin: '0px 0px -70% 0px',
        threshold:  0,
      }
    );

    headings.forEach( function ( h ) { observer.observe( h ); } );
  }

  // ── Mobile drawer ───────────────────────────────────────────────────────────

  function setupMobileDrawer() {
    const btn     = document.querySelector( '.toc-mobile-btn' );
    const drawer  = document.getElementById( 'toc-drawer' );
    const overlay = document.getElementById( 'toc-overlay' );

    if ( ! btn || ! drawer ) { return; }

    function open() {
      drawer.classList.add( 'is-open' );
      drawer.setAttribute( 'aria-hidden', 'false' );
      btn.setAttribute( 'aria-expanded', 'true' );
      if ( overlay ) {
        overlay.hidden = false;
        overlay.classList.add( 'is-open' );
        overlay.setAttribute( 'aria-hidden', 'false' );
      }
      document.body.style.overflow = 'hidden';
    }

    function close() {
      drawer.classList.remove( 'is-open' );
      drawer.setAttribute( 'aria-hidden', 'true' );
      btn.setAttribute( 'aria-expanded', 'false' );
      if ( overlay ) {
        overlay.classList.remove( 'is-open' );
        overlay.setAttribute( 'aria-hidden', 'true' );
        overlay.hidden = true;
      }
      document.body.style.overflow = '';
    }

    btn.addEventListener( 'click', function () {
      const isOpen = drawer.classList.contains( 'is-open' );
      isOpen ? close() : open();
    } );

    if ( overlay ) {
      overlay.hidden = true;
      overlay.addEventListener( 'click', close );
    }

    // Close on TOC link click (navigate away)
    drawer.addEventListener( 'click', function ( e ) {
      if ( e.target.closest( 'a' ) ) { close(); }
    } );

    // Close on Escape
    document.addEventListener( 'keydown', function ( e ) {
      if ( e.key === 'Escape' && drawer.classList.contains( 'is-open' ) ) {
        close();
        btn.focus();
      }
    } );
  }

  // ── Desktop TOC collapse ───────────────────────────────────────────────────

  function setupDesktopTocToggle() {
    const sidebar = document.querySelector( '[data-toc-sidebar]' );
    if ( ! sidebar ) { return; }

    const btn  = sidebar.querySelector( '[data-toc-collapse-btn]' );
    const icon = sidebar.querySelector( '[data-toc-collapse-icon]' );
    if ( ! btn || ! icon ) { return; }

    const collapsedClass = 'is-collapsed';
    const storageKey = 'cleanResearcherTocCollapsed';

    function setStoredState( isCollapsed ) {
      try {
        window.localStorage.setItem( storageKey, isCollapsed ? '1' : '0' );
      } catch ( e ) {
        // Ignore storage failures (private mode, blocked storage, etc.).
      }
    }

    function getStoredState() {
      try {
        return window.localStorage.getItem( storageKey ) === '1';
      } catch ( e ) {
        return false;
      }
    }

    function syncToggleState() {
      const isCollapsed = sidebar.classList.contains( collapsedClass );
      btn.setAttribute( 'aria-expanded', String( ! isCollapsed ) );
      btn.setAttribute(
        'aria-label',
        isCollapsed ? 'Expand table of contents' : 'Minimize table of contents'
      );

      icon.classList.toggle( 'fa-arrow-left', ! isCollapsed );
      icon.classList.toggle( 'fa-arrow-right', isCollapsed );
    }

    btn.addEventListener( 'click', function () {
      sidebar.classList.toggle( collapsedClass );
      setStoredState( sidebar.classList.contains( collapsedClass ) );
      syncToggleState();
    } );

    if ( getStoredState() ) {
      sidebar.classList.add( collapsedClass );
    }

    syncToggleState();
  }

  // ── Init ────────────────────────────────────────────────────────────────────

  document.addEventListener( 'DOMContentLoaded', function () {
    buildToc();
    setupMobileDrawer();
    setupDesktopTocToggle();
  } );

} )();
