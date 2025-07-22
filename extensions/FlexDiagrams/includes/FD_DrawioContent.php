<?php
/**
 * Content of DRAWIO Diagrams
 */

use MediaWiki\Html\Html;

class FDDrawioContent extends TextContent {

	public function __construct( $text, $modelId = CONTENT_MODEL_FD_DRAWIO ) {
		parent::__construct( $text, $modelId );
	}

	public function getHtml() {
		global $wgOut;

		$wgOut->addModules( 'ext.flexdiagrams.drawio' );
		// Output SVG directly if present
		if ( strpos( $this->mText, '<svg' ) !== false ) {
			return $this->mText;
		}
		// Fallback: show as preformatted text
		return Html::element( 'pre', [], $this->mText );
	}

	/**
	 * @return string The wikitext to include when another page includes this
	 * content, or false if the content is not includable in a wikitext page.
	 */
	public function getWikitextForTransclusion() {
		return '<span class="error">' . wfMessage( 'flexdiagrams-embedding-unsupported' )->plain() . '</span>';
	}
}
