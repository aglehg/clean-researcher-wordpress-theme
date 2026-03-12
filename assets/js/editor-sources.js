( function ( wp ) {
  'use strict';

  const blocks = wp.blocks;
  const blockEditor = wp.blockEditor;
  const components = wp.components;
  const data = wp.data;
  const domReady = wp.domReady;
  const element = wp.element;
  const i18n = wp.i18n;
  const richText = wp.richText;

  if (
    ! blocks ||
    ! blockEditor ||
    ! components ||
    ! data ||
    ! domReady ||
    ! element ||
    ! i18n ||
    ! richText
  ) {
    return;
  }

  const registerBlockType = blocks.registerBlockType;
  const useBlockProps = blockEditor.useBlockProps;
  const RichTextToolbarButton = blockEditor.RichTextToolbarButton;
  const Button = components.Button;
  const Modal = components.Modal;
  const Notice = components.Notice;
  const Placeholder = components.Placeholder;
  const TextControl = components.TextControl;
  const CheckboxControl = components.CheckboxControl;
  const useSelect = data.useSelect;
  const createElement = element.createElement;
  const Fragment = element.Fragment;
  const useEffect = element.useEffect;
  const useState = element.useState;
  const __ = i18n.__;
  const registerFormatType = richText.registerFormatType;
  const insert = richText.insert;
  const create = richText.create;

  const SOURCE_BLOCK = 'clean-researcher/sources';
  const CITATION_FORMAT = 'clean-researcher/citation';

  function flattenBlocks( list ) {
    return ( list || [] ).reduce( function ( acc, block ) {
      acc.push( block );

      if ( block.innerBlocks && block.innerBlocks.length ) {
        return acc.concat( flattenBlocks( block.innerBlocks ) );
      }

      return acc;
    }, [] );
  }

  function getSourcesState() {
    const editorSelect = data.select( 'core/block-editor' );
    const allBlocks = editorSelect ? flattenBlocks( editorSelect.getBlocks() ) : [];
    const sourceBlocks = allBlocks.filter( function ( block ) {
      return block.name === SOURCE_BLOCK;
    } );

    const firstBlock = sourceBlocks[ 0 ] || null;
    const sources = firstBlock && Array.isArray( firstBlock.attributes.sources )
      ? firstBlock.attributes.sources
      : [];
    const sourceMap = {};

    sources.forEach( function ( source, index ) {
      if ( source && source.id ) {
        sourceMap[ source.id ] = {
          number: index + 1,
          title: source.title || '',
        };
      }
    } );

    return {
      count: sourceBlocks.length,
      sources: sources,
      sourceMap: sourceMap,
    };
  }

  function getCitationLabel( sourceIds, sourceMap ) {
    const labels = ( sourceIds || [] )
      .map( function ( sourceId ) {
        return sourceMap[ sourceId ] ? String( sourceMap[ sourceId ].number ) : null;
      } )
      .filter( Boolean );

    return labels.length ? '[' + labels.join( ', ' ) + ']' : '[?]';
  }

  function createSourceId() {
    return 'crsrc_' + Math.random().toString( 36 ).slice( 2, 10 );
  }

  function renderSourceRow( source, index, sources, setAttributes ) {
    function updateField( field, value ) {
      const nextSources = sources.map( function ( item, itemIndex ) {
        if ( itemIndex !== index ) {
          return item;
        }

        return Object.assign( {}, item, {
          [ field ]: value,
        } );
      } );

      setAttributes( { sources: nextSources } );
    }

    function moveSource( offset ) {
      const targetIndex = index + offset;

      if ( targetIndex < 0 || targetIndex >= sources.length ) {
        return;
      }

      const nextSources = sources.slice();
      const current = nextSources[ index ];
      nextSources[ index ] = nextSources[ targetIndex ];
      nextSources[ targetIndex ] = current;

      setAttributes( { sources: nextSources } );
    }

    function removeSource() {
      setAttributes( {
        sources: sources.filter( function ( item, itemIndex ) {
          return itemIndex !== index;
        } ),
      } );
    }

    return createElement(
      'div',
      {
        key: source.id,
        style: {
          border: '1px solid #d1d5db',
          borderRadius: '6px',
          padding: '16px',
          marginBottom: '12px',
          background: '#fff',
        },
      },
      createElement(
        'div',
        {
          style: {
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            gap: '8px',
            marginBottom: '12px',
          },
        },
        createElement(
          'strong',
          null,
          '#' + String( index + 1 )
        ),
        createElement(
          'div',
          {
            style: {
              display: 'flex',
              gap: '8px',
            },
          },
          createElement(
            Button,
            {
              variant: 'secondary',
              onClick: function () {
                moveSource( -1 );
              },
              disabled: index === 0,
            },
            __( 'Up', 'clean-researcher' )
          ),
          createElement(
            Button,
            {
              variant: 'secondary',
              onClick: function () {
                moveSource( 1 );
              },
              disabled: index === sources.length - 1,
            },
            __( 'Down', 'clean-researcher' )
          ),
          createElement(
            Button,
            {
              variant: 'tertiary',
              isDestructive: true,
              onClick: removeSource,
            },
            __( 'Remove', 'clean-researcher' )
          )
        )
      ),
      createElement( TextControl, {
        label: __( 'Title', 'clean-researcher' ),
        value: source.title || '',
        onChange: function ( value ) {
          updateField( 'title', value );
        },
      } ),
      createElement( TextControl, {
        label: __( 'URL', 'clean-researcher' ),
        type: 'url',
        value: source.url || '',
        onChange: function ( value ) {
          updateField( 'url', value );
        },
        placeholder: 'https://',
      } )
    );
  }

  domReady( function () {
    registerBlockType( SOURCE_BLOCK, {
      apiVersion: 3,
      title: __( 'Sources', 'clean-researcher' ),
      icon: 'book-alt',
      category: 'text',
      attributes: {
        sources: {
          type: 'array',
          default: [],
        },
      },
      edit: function EditSourcesBlock( props ) {
        const blockProps = useBlockProps();
        const sources = Array.isArray( props.attributes.sources ) ? props.attributes.sources : [];
        const sourceBlockCount = useSelect( function () {
          return getSourcesState().count;
        }, [] );

        function addSource() {
          props.setAttributes( {
            sources: sources.concat( [ { id: createSourceId(), title: '', url: '' } ] ),
          } );
        }

        return createElement(
          'section',
          blockProps,
          createElement(
            'div',
            {
              style: {
                border: '1px solid #d1d5db',
                borderRadius: '8px',
                padding: '24px',
                background: '#f9fafb',
              },
            },
            sourceBlockCount > 1
              ? createElement(
                  Notice,
                  {
                    status: 'warning',
                    isDismissible: false,
                  },
                  __( 'Use a single Sources block per post so citations resolve consistently.', 'clean-researcher' )
                )
              : null,
            createElement(
              'div',
              { style: { marginBottom: '16px' } },
              createElement(
                'h2',
                { style: { margin: '0 0 8px', fontSize: '20px' } },
                __( 'Sources', 'clean-researcher' )
              ),
              createElement(
                'p',
                { style: { margin: 0, color: '#4b5563' } },
                __( 'Add article sources here, then insert citations inline from the text toolbar.', 'clean-researcher' )
              )
            ),
            sources.length
              ? sources.map( function ( source, index ) {
                  return renderSourceRow( source, index, sources, props.setAttributes );
                } )
              : createElement(
                  Placeholder,
                  {
                    label: __( 'No sources yet', 'clean-researcher' ),
                    instructions: __( 'Add at least one source before inserting citations in the article body.', 'clean-researcher' ),
                  }
                ),
            createElement(
              Button,
              {
                variant: 'primary',
                onClick: addSource,
              },
              __( 'Add source', 'clean-researcher' )
            )
          )
        );
      },
      save: function SaveSourcesBlock( props ) {
        const blockProps = blockEditor.useBlockProps.save( {
          className: 'cr-sources not-prose mt-12 rounded-lg border border-gray-200 bg-gray-50 px-6 py-6',
        } );
        const sources = Array.isArray( props.attributes.sources ) ? props.attributes.sources : [];

        return createElement(
          'section',
          blockProps,
          createElement(
            'h2',
            {
              className: 'mt-0 mb-3 text-lg font-bold text-gray-900',
            },
            __( 'Sources', 'clean-researcher' )
          ),
          createElement(
            'ol',
            {
              className: 'm-0 list-decimal pl-5 text-sm text-gray-700',
            },
            sources.map( function ( source ) {
              const title = source.title || __( 'Untitled source', 'clean-researcher' );

              return createElement(
                'li',
                {
                  key: source.id,
                  id: 'cr-source-' + source.id,
                  'data-source-id': source.id,
                  className: 'mb-2 last:mb-0',
                },
                source.url
                  ? createElement(
                      'a',
                      {
                        href: source.url,
                        className: 'hover:underline',
                      },
                      title
                    )
                  : title
              );
            } )
          )
        );
      },
    } );

    registerFormatType( CITATION_FORMAT, {
      title: __( 'Citation', 'clean-researcher' ),
      tagName: 'span',
      className: 'cr-citation',
      attributes: {
        sourceIds: 'data-source-ids',
      },
      edit: function EditCitationFormat( props ) {
        const state = useSelect( function () {
          return getSourcesState();
        }, [] );
        const availableSources = state.sources;
        const sourceMap = state.sourceMap;
        const activeSourceIds = ( props.activeAttributes && props.activeAttributes.sourceIds
          ? String( props.activeAttributes.sourceIds ).split( ',' )
          : [] )
          .map( function ( sourceId ) {
            return sourceId.trim();
          } )
          .filter( Boolean );

        const [ isOpen, setIsOpen ] = useState( false );
        const [ selectedSourceIds, setSelectedSourceIds ] = useState( activeSourceIds );

        useEffect( function () {
          if ( ! isOpen ) {
            setSelectedSourceIds( activeSourceIds );
          }
        }, [ props.isActive, props.activeAttributes ? props.activeAttributes.sourceIds : '', isOpen ] );

        function toggleSource( sourceId, checked ) {
          if ( checked ) {
            setSelectedSourceIds(
              selectedSourceIds.concat( [ sourceId ] ).filter( function ( value, index, all ) {
                return all.indexOf( value ) === index;
              } )
            );
            return;
          }

          setSelectedSourceIds(
            selectedSourceIds.filter( function ( value ) {
              return value !== sourceId;
            } )
          );
        }

        function insertCitation() {
          if ( ! selectedSourceIds.length ) {
            setIsOpen( false );
            return;
          }

          const sourceIds = selectedSourceIds.filter( function ( sourceId ) {
            return !! sourceMap[ sourceId ];
          } );

          if ( ! sourceIds.length ) {
            setIsOpen( false );
            return;
          }

          const label = getCitationLabel( sourceIds, sourceMap );
          const html = '<span class="cr-citation" data-source-ids="' + sourceIds.join( ',' ) + '">' + label + '</span>';

          props.onChange( insert( props.value, create( { html: html } ) ) );
          setIsOpen( false );
        }

        return createElement(
          Fragment,
          null,
          createElement( RichTextToolbarButton, {
            icon: 'editor-ol',
            title: __( 'Insert citation', 'clean-researcher' ),
            onClick: function () {
              setIsOpen( true );
            },
            isActive: props.isActive,
            disabled: ! availableSources.length,
          } ),
          isOpen
            ? createElement(
                Modal,
                {
                  title: __( 'Insert citation', 'clean-researcher' ),
                  onRequestClose: function () {
                    setIsOpen( false );
                  },
                },
                availableSources.length
                  ? createElement(
                      Fragment,
                      null,
                      createElement(
                        'div',
                        { style: { marginBottom: '16px' } },
                        availableSources.map( function ( source, index ) {
                          return createElement( CheckboxControl, {
                            key: source.id,
                            label: '#' + String( index + 1 ) + ' ' + ( source.title || __( 'Untitled source', 'clean-researcher' ) ),
                            checked: selectedSourceIds.indexOf( source.id ) !== -1,
                            onChange: function ( checked ) {
                              toggleSource( source.id, checked );
                            },
                          } );
                        } )
                      ),
                      createElement(
                        'div',
                        {
                          style: {
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: '12px',
                          },
                        },
                        createElement(
                          'p',
                          {
                            style: {
                              margin: 0,
                              color: '#4b5563',
                            },
                          },
                          getCitationLabel( selectedSourceIds, sourceMap )
                        ),
                        createElement(
                          'div',
                          {
                            style: {
                              display: 'flex',
                              gap: '8px',
                            },
                          },
                          createElement(
                            Button,
                            {
                              variant: 'tertiary',
                              onClick: function () {
                                setIsOpen( false );
                              },
                            },
                            __( 'Cancel', 'clean-researcher' )
                          ),
                          createElement(
                            Button,
                            {
                              variant: 'primary',
                              onClick: insertCitation,
                              disabled: ! selectedSourceIds.length,
                            },
                            __( 'Insert', 'clean-researcher' )
                          )
                        )
                      )
                    )
                  : createElement(
                      Notice,
                      {
                        status: 'warning',
                        isDismissible: false,
                      },
                      __( 'Add sources in the Sources block before inserting citations.', 'clean-researcher' )
                    )
              )
            : null
        );
      },
    } );
  } );

  function syncCitationLabels() {
    const state = getSourcesState();
    const selector = '.cr-citation[data-source-ids]';
    const containers = [ document ];
    const editorFrame = document.querySelector( 'iframe[name="editor-canvas"], iframe.editor-canvas__iframe' );

    if ( editorFrame && editorFrame.contentDocument ) {
      containers.push( editorFrame.contentDocument );
    }

    containers.forEach( function ( container ) {
      container.querySelectorAll( selector ).forEach( function ( node ) {
        const sourceIds = String( node.getAttribute( 'data-source-ids' ) || '' )
          .split( ',' )
          .map( function ( sourceId ) {
            return sourceId.trim();
          } )
          .filter( Boolean );

        node.textContent = getCitationLabel( sourceIds, state.sourceMap );

        const missingSource = sourceIds.some( function ( sourceId ) {
          return ! state.sourceMap[ sourceId ];
        } );

        if ( missingSource ) {
          node.style.opacity = '0.6';
          node.title = __( 'This citation points to a missing source.', 'clean-researcher' );
        } else {
          node.style.opacity = '';
          node.title = '';
        }
      } );
    } );
  }

  data.subscribe( syncCitationLabels );
  window.setTimeout( syncCitationLabels, 300 );
} )( window.wp );
