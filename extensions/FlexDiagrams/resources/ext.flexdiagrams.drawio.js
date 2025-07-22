/**
 * Class for handling Drawio namespace (diagrams.net)
 *
 * @author Sanjay Thiyagarajan
 */
( function ( $, mw, fd ) {

	'use strict';

	var gLinkedPages = {};

	/**
	 * Inheritance class for the fd.base constructor
	 *
	 *
	 * @class
	 */
	fd.base = fd.base || {};

	/**
	 * @class
	 * @constructor
	 */
	fd.drawio = function () {};

	var drawio_proto = new fd.base();

	var editor = 'https://embed.diagrams.net/?embed=1&spin=1&proto=json';

	function edit( path, content ) {
		var $iframe = $( '<iframe>' );
		$iframe.attr( 'frameborder', '0' );

		var close = function () {
			// Disable unload warning before redirect
			window.onbeforeunload = null;
			var diagramURL = mw.config.get( 'wgServer' ) + mw.config.get( 'wgScript' ) +
				'?title=' + encodeURIComponent( pageName );
			window.location.href = diagramURL;
		};

		var receive = function ( evt ) {
			if ( evt.data.length > 0 ) {
				var msg = JSON.parse( evt.data );

				if ( msg.event === 'init' ) {
					// Always request SVG export on save
					if ( content === null ) {
						$iframe[ 0 ].contentWindow.postMessage( JSON.stringify({
							action: 'load',
							autosave: 0,
							xml: ''
						}), '*' );
					} else {
						$iframe[ 0 ].contentWindow.postMessage( JSON.stringify({
							action: 'load',
							autosave: 0,
							xml: content
						}), '*' );
					}
				} else if ( msg.event === 'save' ) {
					// Request SVG export from Drawio
					// Save XML, then request SVG export
					window._drawioXml = msg.xml;
					$iframe[ 0 ].contentWindow.postMessage( JSON.stringify({
						action: 'export',
						format: 'svg',
						xml: msg.xml
					}), '*' );
				} else if ( msg.event === 'export' ) {
					// If SVG is a data URI, decode and insert as markup
					var svgData;
					if (msg.data.startsWith('data:image/svg+xml;base64,')) {
						svgData = atob(msg.data.split(',')[1]);
					} else {
						svgData = msg.data;
					}
					// Save both XML and SVG as JSON
					var diagramObj = {
						xml: window._drawioXml || '',
						svg: svgData
					};
					drawio_proto.updatePageAndRedirectUser(pageName, JSON.stringify(diagramObj));
				} else if ( msg.event === 'exit' ) {
					close();
				}
			}
		};

		// Suppress 'beforeunload' warning only during save/export
		var suppressUnload = false;
		var originalOnBeforeUnload = window.onbeforeunload;
		window.addEventListener( 'message', function(evt) {
			try {
				var msg = JSON.parse(evt.data);
				if (msg.event === 'save' || msg.event === 'export') {
					suppressUnload = true;
					window.onbeforeunload = null;
				} else if (msg.event === 'exit') {
					suppressUnload = false;
					window.onbeforeunload = originalOnBeforeUnload;
				}
			} catch (e) {}
			receive(evt);
		});

		window.onbeforeunload = function(e) {
			if (!suppressUnload) {
				return 'Changes you made may not be saved.';
			}
		};

		$iframe.attr( 'src', editor );
		$( '#canvas' ).append( $iframe );
	}

	fd.drawio.prototype = drawio_proto;

	drawio_proto.initialize = function () {
		if ( mw.config.get( 'wgArticleId' ) === 0 ) {
			edit( pageName + '.png' );
		} else {
			var diagramURL = mw.config.get( 'wgServer' ) + mw.config.get( 'wgScript' ) +
				'?title=' + encodeURIComponent( pageName ) + '&action=raw';
		$.get( diagramURL, function ( data ) {
			var diagramObj;
			try {
				diagramObj = JSON.parse(data);
			} catch (e) {
				diagramObj = null;
			}

			if ( mw.config.get( 'wgAction' ) == 'editdiagram' ) {
				var $saveInfo = new OO.ui.MessageWidget( {
					type: 'notice',
					label: new OO.ui.HtmlSnippet( mw.message( 'flexdiagrams-drawio-saveinfo',
						$( '#wpSave' ).attr( 'value' ) ).text() )
				} );
				$saveInfo.$element.insertBefore( '#bodyContent' );
				if (diagramObj && diagramObj.xml) {
					edit( null, diagramObj.xml );
				} else {
					edit( null, data );
				}
			} else if ((diagramObj && diagramObj.svg) || data.trim().startsWith('<svg') || (typeof data === 'string' && data.startsWith('data:image/svg+xml;base64,'))) {
				// Prefer SVG rendering
				var svgMarkup;
				if (diagramObj && diagramObj.svg) {
					svgMarkup = diagramObj.svg;
				} else if (typeof data === 'string' && data.startsWith('data:image/svg+xml;base64,')) {
					// Decode base64 SVG
					svgMarkup = atob(data.split(',')[1]);
				} else {
					svgMarkup = data;
				}
				$('#canvas').empty().html(svgMarkup);
				// Make SVG <a> links clickable and open in new tab
				var svgEl = $('#canvas').find('svg')[0];
				if (svgEl) {
					var links = svgEl.querySelectorAll('a');
					links.forEach(function(link) {
						link.setAttribute('target', '_blank');
						link.style.cursor = 'pointer';
					});
				}
			} else {
				// Fallback to image only if no SVG is present
				var $img = $('<img>');
				$img.attr('id', 'diagramContainer');
				$img.attr('src', data);
				$('#canvas').empty().append($img);
			}
		} );
		}

		this.enableSave( this );
	};

	var imgData = null;

	window.addEventListener( 'message', onmessage, false );
	window.onmessage = function ( e ) {
		var obj = JSON.parse( e.data );
		if ( obj.event == 'export' ) {
			imgData = obj.data;
		}
	};

	drawio_proto.exportDiagram = function ( data ) {
		if ( imgData != null ) {
			drawio_proto.updatePageAndRedirectUser( pageName, imgData );
		} else {
			var diagramURL = mw.config.get( 'wgServer' ) + mw.config.get( 'wgScript' ) +
				'?title=' + encodeURIComponent( pageName );
			window.location.href = diagramURL;
		}

	};

	var pageName = $( '#canvas' ).attr( 'data-wiki-page' );
	if ( pageName == null ) {
		pageName = mw.config.get( 'wgPageName' );
	}

	var drawioHandler = new fd.drawio();
	drawioHandler.initialize();

}( jQuery, mediaWiki, flexdiagrams ) );
