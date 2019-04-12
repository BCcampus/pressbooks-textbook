<?php

/**
 * Filter provides different views of the data. It takes arrays and turns them into html
 * October 2012
 *
 * @package   Pressbooks_Textbook
 * @author    Brad Payne
 * @license   GPL-2.0+
 */

namespace PBT\Modules\Catalogue;

class Filter {

	private $resultsData = [];
	private $size        = 0;
	private $uuid;
	private $subject = '';

	/**
	 * Filter constructor.
	 *
	 * @param EquellaFetch $response
	 */
	public function __construct( EquellaFetch $response ) {
		/* get results from an equella array */
		$this->resultsData = $response->getResults();
		$this->size        = count( $this->resultsData );
		$this->uuid        = $response->getUuid();

		$this->subject = $response->getWhereClause();

	}

	/**
	 * @param $number
	 *
	 * @return float|int|string
	 */
	private function determineFileSize( $number ) {
		$result = '';
		$num    = '';

		//bail if nothing is passed.
		if ( empty( $number ) ) {
			return $result;
		}

		//if it's a number
		if ( is_int( $number ) ) {
			$num = intval( $number );
		}
		//only process if it's bigger than zero
		if ( $num > 0 ) {
			//return in Megabytes
			$result = ( $num / 1000000 );
			//account for the fact that it might be less than 1MB
			( $result <= 1 ) ? $result = round( $result, 2 ) : $result = intval( $result );
			$result                    = '(' . $result . ' MB)';
		}
		return $result;
	}

	/**
	 * Helper function to evaluate the type of document and add the appropriate logo
	 *
	 * @param $string
	 *
	 * @return array
	 */
	private function addLogo( $string ) {

		if ( ! stristr( $string, 'print copy' ) === false ) {
			$result = [
				'string' => "PRINT <i class='fa fa-print'></i>",
				'type'   => 'print',
			];
		} else {
			$result = [
				'string' => "<i class='fa fa-globe'></i> WEBSITE <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document-code.png' alt='External website. This icon is licensed under a Creative Commons
		Attribution 3.0 License. Copyright Yusuke Kamiyamane. '/>",
				'type'   => 'url',
			];
		}

		//if it's a zip
		if ( ! stristr( $string, '.zip' ) === false || ! stristr( $string, '.tbz' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document-zipper.png' alt='ZIP file '/>",
				'type'   => 'zip',
			];
		}
		//if it's a word file
		if ( ! stristr( $string, 'Word' ) === false || ! stristr( $string, '.rtf' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document-word.png' alt='WORD file'/>",
				'type'   => 'doc',
			];
		}
		//if it's a pdf
		if ( ! stristr( $string, 'PDF' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document-pdf.png' alt='PDF file'/>",
				'type'   => 'pdf',
			];
		}
		//if it's an epub
		if ( ! stristr( $string, 'eReader' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document-epub.png' alt='EPUB file'/>",
				'type'   => 'epub',
			];
		}
		//if it's a mobi
		if ( ! stristr( $string, 'Kindle' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document-mobi.png' alt='MOBI file'/>",
				'type'   => 'mobi',
			];
		}
		// if it's a wxr
		if ( ! stristr( $string, 'XML' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document-xml.png' alt='XML file' />",
				'type'   => 'xml',
			];
		}
		// if it's an odt
		if ( ! stristr( $string, 'OpenDocument Text' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document.png' alt='ODT file' />",
				'type'   => 'odt',
			];
		}
		if ( ! stristr( $string, '.hpub' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document.png' alt='HPUB file' />",
				'type'   => 'hpub',
			];
		}
		if ( ! stristr( $string, 'HTML' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document-code.png' alt='XHTML file' />",
				'type'   => 'html',
			];
		}
		// if it's a tex
		if ( ! stristr( $string, '.tex' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . PBT_PLUGIN_URL . "admin/assets/img/document-tex.png' alt='TEX file' />",
				'type'   => 'tex',
			];
		}

		return $result;
	}

		/**
	 * Checks for part of a string, removes it and returns to make it something
	 * the API can deal with
	 *
	 * @param type $license
	 * @return type
	 * @throws \Exception
	 */
	protected function v3license( $license ) {

		// check for string match CC-BY-3.0
		if ( false === strpos( $license, '-3.0' ) ) {
			return $license;
		}

		$license = strstr( $license, '-3.0', true );

		return $license;
	}

	/**
	 * Helper function to display whichever license applies
	 *
	 * @param $string
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function licensePicker( $string ) {
		$result = 'license error';

		$xml = simplexml_load_string( $string );

		if ( $xml ) {
			$license = $xml->lom->rights->description[0];
			$license = $this->v3license( $license );

			switch ( $license ) {
				case 'CC-BY-NC-SA':
					$result = "<figure><a rel='license' href='http://creativecommons.org/licenses/by-nc-sa/4.0/deed.en_US'>
        <img alt='Creative Commons License' style='border-width:0' src='http://i.creativecommons.org/l/by-nc-sa/4.0/88x31.png' />
        </a><figcaption><small class='muted'>Except where otherwise noted, this work is licensed under a <a rel='license' href='http://creativecommons.org/licenses/by-nc-sa/4.0/deed.en_US'>
        Creative Commons Attribution-NonCommercial-ShareAlike 4.0 Unported License</a>.
        This license lets others remix, tweak, and build upon your work non-commercially, as long as they credit you and license their new creations under the identical terms.</small></figcaption></figure>";
					break;

				case 'CC-BY-NC-ND':
					$result = "<figure><a rel='license' href='http://creativecommons.org/licenses/by-nc-nd/4.0/'>
        <img alt='Creative Commons License' style='border-width:0' src='http://i.creativecommons.org/l/by-nc-nd/4.0/88x31.png' />
        </a><figcaption><small class='muted'>Except where otherwise noted, this work is licensed under a <a rel='license' href='http://creativecommons.org/licenses/by-nc-nd/4.0/'>
        Creative Commons Attribution-NonCommercial-NoDerivs 4.0 Unported License</a>.
        This license only allows others to download your works 
        and share them with others as long as they credit you, but they can’t change them in any way or use them commercially.</small></figcaption></figure>";
					break;

				case 'CC-BY-NC':
					$result = "<figure><a rel='license' href='http://creativecommons.org/licenses/by-nc/4.0/'>
        <img alt='Creative Commons License' style='border-width:0' src='http://i.creativecommons.org/l/by-nc/4.0/88x31.png' />
        </a><figcaption><small class='muted'>Except where otherwise noted, this work is licensed under a <a rel='license' href='http://creativecommons.org/licenses/by-nc/4.0/'>
        Creative Commons Attribution-NonCommercial 4.0 Unported License</a>.
        This license lets others remix, tweak, and build upon your work non-commercially, 
        and although their new works must also acknowledge you and be non-commercial, 
        they don’t have to license their derivative works on the same terms.</small></figcaption></figure>";
					break;

				case 'CC-BY-ND':
					$result = "<figure><a rel='license' href='http://creativecommons.org/licenses/by-nd/4.0/deed.en_US'>
        <img alt='Creative Commons License' style='border-width:0' src='http://i.creativecommons.org/l/by-nd/4.0/88x31.png' />
        </a><figcaption><small class='muted'>Except where otherwise noted, this work is licensed under a <a rel='license' href='http://creativecommons.org/licenses/by-nd/4.0/deed.en_US'>
        Creative Commons Attribution-NoDerivs 4.0 Unported License</a>.
        This license allows for redistribution, commercial and non-commercial, as long as it is passed along unchanged and in whole, with credit to you.</small></figcaption></figure>";
					break;

				case 'CC-BY-SA':
					$result = "<figure><a rel='license' href='http://creativecommons.org/licenses/by-sa/4.0/deed.en_US'>
        <img alt='Creative Commons License' style='border-width:0' src='http://i.creativecommons.org/l/by-sa/4.0/88x31.png' />
        </a><figcaption><small class='muted'>Except where otherwise noted, this work is licensed under a <a rel='license' href='http://creativecommons.org/licenses/by-sa/4.0/deed.en_US'>
        Creative Commons Attribution-ShareAlike 4.0 Unported License</a>.
        This license lets others remix, tweak, and build upon your work even for commercial purposes, 
        as long as they credit you and license their new creations under the identical terms. 
        This license is often compared to “copyleft” free and open source software licenses. 
        All new works based on yours will carry the same license, so any derivatives will also allow commercial use. 
        This is the license used by Wikipedia, and is recommended for materials that would benefit from incorporating content 
        from Wikipedia and similarly licensed projects.</small></figcaption></figure>";
					break;

				case 'CC-BY':
					$result = "<figure><a rel='license' href='http://creativecommons.org/licenses/by/4.0/deed.en_US'>
        <img alt='Creative Commons License' style='border-width:0' src='http://i.creativecommons.org/l/by/4.0/88x31.png' />
        </a><figcaption><small class='muted'>Except where otherwise noted, this work is licensed under a <a rel='license' href='http://creativecommons.org/licenses/by/4.0/deed.en_US'>
        Creative Commons Attribution 4.0 Unported License</a>.
        This license lets others distribute, remix, tweak, and build upon your work, even commercially, as long as they credit you for the original creation. 
        This is the most accommodating of licenses offered. 
        Recommended for maximum dissemination and use of licensed materials.</small></figcaption></figure>";
					break;

				case 'CC0':
					$result = "<figure><a rel='license' href='http://creativecommons.org/publicdomain/mark/1.0/'>
        <img alt='Public Domain Mark' style='border-width:0' src='http://i.creativecommons.org/p/mark/1.0/88x31.png' />
        </a><figcaption><small class='muted'>Except where otherwise noted, <a rel='license' href='http://creativecommons.org/publicdomain/mark/1.0/'>
	this work is free of known copyright restrictions
        </a></small></figcaption></figure>";
					break;

				default:
					$result = "<figure><a rel='license' href='http://creativecommons.org/licenses/by-sa/2.5/ca/deed.en_US'>
        <img alt='Creative Commons License' style='border-width:0' src='http://i.creativecommons.org/l/by-sa/2.5/ca/88x31.png' /></a>
        <figcaption><small class='muted'>Except where otherwise noted, this work is licensed under a <a rel='license' href='http://creativecommons.org/licenses/by-sa/2.5/ca/deed.en_US'>
        Creative Commons Attribution-ShareAlike 2.5 Canada License</a>.</small></figcaption></figure>";
					break;
			}
		}

		return $result;
	}

	/**
	 * Filters through an array by the keys you pass it, with a default limit of 10
	 * and unless specified otherwise, starting at the beginning of the array
	 *
	 * @param int $start
	 * @param int $limit
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function displayBySubject( $start = 0, $limit = 0 ) {
		$html = '';
		$i    = 0;

		//just in case a start value is passed that is greater than what is available
		if ( $start > $this->size ) {
			$html = "<p>That's it, no more records</p>";
			return $html;
		}

		// necessary to see the last record
		$start = ( $start === $this->size ) ? $start = $start - 1 : $start;

		// if we're displaying all of the results (from a search form request)
		if ( $limit === 0 ) {
			$limit = $this->size;
			$html .= '<ol>';
		} else {
			$html .= '<ul>';
		}

		// check if it's been reviewed
		while ( $i < $limit ) {
			$desc = ( strlen( $this->resultsData[ $start ]['description'] ) > 500 ) ? substr( $this->resultsData[ $start ]['description'], 0, 499 ) . '...' : $this->resultsData[ $start ]['description'];
			( false === strpos( $this->resultsData[ $start ]['metadata'], 'REVIEWED149df27a3ba8b2ddeff0d7ed1e6e54e4' ) ) ? $reviews = '' : $reviews = ' <sup><small> Faculty reviewed</small></sup>';
			$authors = EquellaFetch::arrayToCSV( $this->resultsData[ $start ]['drm']['options']['contentOwners'], 'name' );

			$html .= '<li>';
			$html .= '<h4>' . $this->resultsData[ $start ]['name'] . $reviews . '</h4>';
			$html .= '<strong>Author(s):</strong> ' . $authors . '<br>';
			$html .= '<strong>Last modified:</strong> ' . date( 'M j, Y', strtotime( $this->resultsData[ $start ]['modifiedDate'] ) );
			$html .= '<p><strong>Description:</strong> ' . $desc . '</p>';
			$html .= '</li><ul>';

			$attachments = $this->reOrderAttachments( $this->resultsData[ $start ]['attachments'] );

			foreach ( $attachments  as $attachment ) {
				( array_key_exists( 'size', $attachment ) ) ? $file_size = $this->determineFileSize( $attachment['size'] ) : $file_size = '';
				$logo_type = $this->addLogo( $attachment['description'] );
				$html     .= "<li><a class='btn btn-small' href='" . $attachment['links']['view'] . "' title='" . $attachment['description'] . "'>
				" . $logo_type['string'] . '</a> '
					. $attachment['description'] . ' ' . $file_size . '</li>';
			}
			$html .= '</ul>';

			//send it to the picker for evaluation
			$substring = $this->licensePicker( $this->resultsData[ $start ]['metadata'] );
			//include it, depending on what license it is
			$html .= $substring;

			$start ++;
			$i ++;
		}
		if ( $limit === $this->size ) {
			$html .= '</ol>';
		} else {
			$html .= '</ul>';
		}

		return $html;
	}

	/**
	 * Reorders an array of attachments based on an arbitrary hierarchy
	 *
	 * @param array $attachments
	 * @return array $new_order of attachments
	 */
	private function reOrderAttachments( array $attachments ) {
		$new_order = [];

		// string hunting
		foreach ( $attachments as $key => $attachment ) {

			if ( isset( $attachment['filename'] ) ) {
				$filetype = strstr( $attachment['filename'], '.' );
			} else {
				$filetype = '';
			}

			if ( isset( $attachment['url'] ) ) {
				$sfu = wp_parse_url( $attachment['url'] );
				if ( isset( $sfu['host'] ) && 0 === strcmp( 'opentextbook.docsol.sfu.ca', $sfu['host'] ) ) {
					$filetype = '.print';
				}
			}

			switch ( $filetype ) {

				case '.pdf':
					$val = 'b';
					break;
				case '.epub':
					$val = 'c';
					break;
				case '.mobi':
					$val = 'd';
					break;
				case '.print':
					$val = 'e';
					break;
				case '.xml':
					$val = 'f';
					break;
				case '._vanilla.xml':
					$val = 'g';
					break;
				case '.html':
					$val = 'h';
					break;
				case '.tex':
					$val = 'i';
					break;
				case '.odt':
					$val = 'j';
					break;
				case '.docx':
					$val = 'k';
					break;
				case '._3.epub':
					$val = 'l';
					break;
				case '.hpub':
					$val = 'm';
					break;
				default:
					$val = 'a';
					break;
			}

			$sort[ $key ] = $val;
		}
		// sort it alphabetically
		asort( $sort );

		// rebuild the array
		foreach ( $sort as $k => $v ) {
			$new_order[] = $attachments[ $k ];
		}

		return $new_order;
	}

}


