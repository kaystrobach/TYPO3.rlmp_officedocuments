<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003, 2004 Robert Lemke (robert@typo3.org)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   63: class tx_rlmpofficedocuments_pi1 extends tslib_pibase
 *   78:     function main($content,$conf)
 *  168:     function renderPageClassic (&$officeDoc)
 *  212:     function renderPageTemplaVoila (&$officeDoc)
 *  226:     function getBrowseLinks (&$officeDoc, $compositeName='body')
 *  270:     function renderTOC ($tocArr)
 *  322:     function configurePageBreaksAndToc (&$docObj)
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('rlmp_officelib').'class.tx_rlmpofficelib_div.php');
require_once(t3lib_extMgm::extPath('rlmp_officelib').'class.tx_rlmpofficelib_officefactory.php');
require_once(t3lib_extMgm::extPath('rlmp_officelib').'oowriter/class.tx_rlmpofficelib_oowriterdocument.php');
require_once(t3lib_extMgm::extPath('rlmp_officelib').'class.tx_rlmpofficelib_renderhtml.php');

if (t3lib_extMgm::isLoaded('templavoila'))	{
	require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_htmlmarkup.php');
}

/**
 * Plugin 'Office Documents' for the 'rlmp_officedocuments' extension.
 *
 * @author	Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage tx_rlmpofficedocuments
 */
class tx_rlmpofficedocuments_pi1 extends tslib_pibase {
	var $prefixId = 'tx_rlmpofficedocuments_pi1';						// Same as class name
	var $scriptRelPath = 'pi1/class.tx_rlmpofficedocuments_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'rlmp_officedocuments';								// The extension key.
	var $objOfficeLibDiv = null;										// Contains an instance of the multi-purpose thingy library
	var $performPageBreaks = true;										// Can be set via piVars
	var $renderEngine;
	var $pi_checkCHash = TRUE;											// Make sure that empty CHashes are handled correctly

	/**
	 * The mandatory main() function
	 *
	 * @param	string		$content: Some content (if any)
	 * @param	array		$conf: The TS configuration array
	 * @return	string		HTML output
	 * @access public
	 */
	function main($content,$conf)	{
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->objOfficeLibDiv =& rlmp_officelib_div::getInstance();

	 		// Evaluate the page number to be shown
		if ($this->piVars['showPage']) $this->conf['showPage'] = $this->piVars['showPage'];
		if (!$this->conf['showPage']) {
			$this->conf['showPage'] = $this->conf['pageBreaks.']['TOC.']['startWithTOC'] ? 'TOC' : 1;
		}

		if ($this->piVars['view'] == 'single') { $this->performPageBreaks = false; }
		if ($this->piVars['debug'] == 'yeah') { $this->conf['debug'] = true; }
		if ($this->piVars['debug'] == 'nope') { $this->conf['debug'] = false; }

			// Fetch a unique instance of the office factory
		$officeFactory = rlmp_officelib_officefactory::getInstance();

			// Create a new open office writer document and load document
		$officeDoc = $officeFactory->createDocument('rlmp_officelib_oowriterdocument');
		$officeDoc->autoGenerateNumbering = $this->conf['renderSettings.']['autogenerateNumbering'];

		$filenameFromConf = $this->cObj->stdWrap ($this->conf['file'], $this->conf['file.']);
		$pathAndFilename = strlen ($filenameFromConf) ? $filenameFromConf : t3lib_div::getFileAbsFilename ('uploads/tx_rlmpofficedocuments/'.$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'field_officeFile'));
		if (!@file_exists($pathAndFilename)) {
			return $this->pi_wrapInBaseClass(htmlspecialchars(sprintf($this->pi_getLL ('error_filenotfound'), $pathAndFilename)));
		}
		if (!strlen($filenameFromConf)) {
			$officeDoc->relPathAndFilename = 'uploads/tx_rlmpofficedocuments/'.$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'field_officeFile');
		}

		if (!$officeDoc->load ($pathAndFilename)) {
			$content = htmlspecialchars(sprintf($this->pi_getLL ('error_whileloading'), $pathAndFilename));
			if (is_array ($officeDoc->errorMessages)) {
				$content .= ' '.htmlspecialchars($this->pi_getLL ('error_messagesmighthelp')). '<br /><ul>';
				foreach ($officeDoc->errorMessages as $msg) {
					$content .= '<li>'.htmlspecialchars($msg).'</li>';
				}
				$content .= '</ul>';
			}
			return $this->pi_wrapInBaseClass($content);
		}

			// Select class for rendering the document, in our case one for creating HTML output
		$this->renderEngine = new rlmp_officelib_renderhtml();
		$this->renderEngine->conf = $this->conf['renderSettings.']['renderEngine.'];
		$this->renderEngine->conf['internalLinkTemplate'] = urldecode($this->pi_linkTP_keepPIvars_url (array ('showPage'=>'###PAGE###'),1));

		$officeDoc->setRenderEngine ($this->renderEngine);
		$officeDoc->debug = $this->conf['debug'];

		if (intval($this->conf['pageBreaks.']['breakByHeadings.']['enable']) && $this->performPageBreaks) {
			$this->configurePageBreaksAndToc($officeDoc);
			$officeDoc->performPageBreaks('body');
		}

		if ($this->conf['showPage'] == 'TOC' && $this->performPageBreaks) {
			$toc = $this->renderTOC($officeDoc->getTOC());
			if ($toc) {
				$content .= $toc;
			} else {
				$content .= $this->pi_getLL ('error_notocdata');
				if ($this->conf['debug']) {
					if (is_array ($officeDoc->errorMessages)) {
						$content .= '<br />'.htmlspecialchars($this->pi_getLL ('error_messagesmighthelp')). '<br /><ul>';
						foreach ($officeDoc->errorMessages as $msg) {
							$content .= '<li>'.htmlspecialchars($msg).'</li>';
						}
						$content .= '</ul>';
					}
				}
			}
		}

		if (intval ($this->conf['showPage']) || !$toc) {
			$this->conf['showPage'] = t3lib_div::intInRange ($this->conf['showPage'], 1, $officeDoc->numberOfPages);
			$useTemplavoila = ($this->conf['template.']['mode'] == 'templavoila' || $this->conf['template.']['mode'] == 'auto');
			if (!t3lib_extMgm::isLoaded('templavoila')) { $useTemplavoila = false; }

				// Set some special (dynamic) meta properties which can be displayed by using certain fields
			$officeDoc->metaObj->properties['_page-number'] = intval ($this->conf['showPage']);
			$officeDoc->metaObj->properties['_number-of-pages'] = $officeDoc->numberOfPages;

			if ($useTemplavoila) {
				$content .= $this->renderPageTemplaVoila($officeDoc);
			} elseif ($this->conf['template.']['mode'] == 'classic' || $this->conf['template.']['mode'] == 'auto') {
				$content .= $this->renderPageClassic($officeDoc);
			} else {
				$content .= sprintf($this->pi_getLL('error_unknownvalue','error'), $this->conf['template.']['mode'], 'template.mode');
			}
		}
#debug ($officeDoc->hyperlinkObjects,'hyperlinkobj',__LINE__,__FILE__,1);
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Rendering using the classic template mode
	 *
	 * @param	$officeDoc:		Reference to loaded office document object
	 * @return	HTML		output
	 * @access private
	 */
	function renderPageClassic (&$officeDoc) {
		global $TSFE;

		$templateFile = t3lib_div::getURL (t3lib_div::getFileAbsFileName($this->conf['template.']['filePageBreaks']));
		$browseLinksArr = $this->getBrowseLinks($officeDoc);
		$markerArr = array (
			'###PREVIOUS_LINK###' => $browseLinksArr['previous'],
			'###NEXT_LINK###' => $browseLinksArr['next'],
			'###TOC_LINK###' => $browseLinksArr['toc'],
			'###PAGEBREAK_SWITCH###' => $browseLinksArr['pagebreak'],
			'###DOWNLOAD-LINK###' => $browseLinksArr['download'],
			'###PAGE_TITLE###' => $browseLinksArr['title'],
			'###PAGE_NUMBER###' => intval ($this->conf['showPage']),
			'###TOTAL_PAGES###' => intval ($officeDoc->numberOfPages),
			'###FOOTER###' => $officeDoc->render ('footer'),
			'###HEADER###' => $officeDoc->render ('header'),
			'###CONTENT###' => $officeDoc->render('body', array ('page' => $this->conf['showPage'])),
		);

		if ($TSFE->charSet!='utf-8') {
			$markerArr['###CONTENT###'] = $TSFE->csConvObj->utf8_to_entities ($markerArr['###CONTENT###'], $TSFE->charSet);
			$markerArr['###FOOTER###'] = $TSFE->csConvObj->utf8_to_entities ($markerArr['###FOOTER###'], $TSFE->charSet);
			$markerArr['###HEADER###'] = $TSFE->csConvObj->utf8_to_entities ($markerArr['###HEADER###'], $TSFE->charSet);
		}

		if (is_array ($officeDoc->metaObj->properties)) {
			foreach ($officeDoc->metaObj->properties as $key => $property) {
				$markerArr['###DOCVARS_'.strtoupper($key).'###'] = $this->renderEngine->renderValue ($property);
			}
		}

		$templateCode = $this->cObj->getSubpart ($templateFile, '###RLMP_OFFICEDOCUMENTS_TEMPLATE###');
		$templateCode = $this->cObj->substituteMarkerArray ($templateCode, $markerArr);

		return $templateCode;
	}

	/**
	 * Rendering using the templavoila template mode
	 *
	 * @param	$officeDoc:		Reference to loaded office document object
	 * @return	HTML		output
	 * @access private
	 */
	function renderPageTemplaVoila (&$officeDoc) {
		$out = '<!-- Templa Voila rendering not implemented yet, falling back to classic mode. -->'.chr(10);
		$out .= $this->renderPageClassic($officeDoc);
		return $out;
	}

	/**
	 * Renders links for browsing the document, ie. "previous", "next" and "table of content" links
	 *
	 * @param	object		&$officeDoc: The current document object
	 * @param	string		$compositeName: The name of the composite text object, usually 'body'
	 * @return	array		contains links / labels
	 * @access private
	 */
	function getBrowseLinks (&$officeDoc, $compositeName='body') {
		global $TSFE;

		$outArr = array ();
		$toc = $officeDoc->toc[$compositeName];

		$this->cObj->setCurrentVal ($toc['pageTitles'][$this->conf['showPage']+1] ? $TSFE->csConv ($toc['pageTitles'][$this->conf['showPage']+1], 'utf-8') : htmlspecialchars($this->pi_getLL ('pagebrowser_next','next')));
		$nextLabel = $this->cObj->cObjGetSingle ($this->conf['template.']['browseBar.']['nextLabelCObj'], $this->conf['template.']['browseBar.']['nextLabelCObj.']);

		$this->cObj->setCurrentVal ($toc['pageTitles'][$this->conf['showPage']-1] ? $TSFE->csConv ($toc['pageTitles'][$this->conf['showPage']-1], 'utf-8') : htmlspecialchars($this->pi_getLL ('pagebrowser_previous','previous')));
		$prevLabel = $this->cObj->cObjGetSingle ($this->conf['template.']['browseBar.']['prevLabelCObj'], $this->conf['template.']['browseBar.']['prevLabelCObj.']);

		$this->cObj->setCurrentVal ($toc['pageTitles'][$this->conf['showPage']] ? $TSFE->csConv ($toc['pageTitles'][$this->conf['showPage']], 'utf-8') : '');
		$currentLabel = $this->cObj->cObjGetSingle ($this->conf['template.']['browseBar.']['currentLabelCObj'], $this->conf['template.']['browseBar.']['currentLabelCObj.']);

		$prevNr = ($this->conf['showPage'] > 1) ? $this->conf['showPage']-1 : 0;
		$nextNr = ($this->conf['showPage'] +1 <= $officeDoc->numberOfPages) ? $this->conf['showPage']+1 : 0;

		$this->cObj->setCurrentVal (basename($officeDoc->relPathAndFilename));
		$downloadLabel = $this->cObj->cObjGetSingle ($this->conf['template.']['downloadLabelCObj'], $this->conf['template.']['downloadLabelCObj.']);
		$downloadParameter = (strlen ($this->conf['downloadLink'])) ? $this->conf['downloadLink'] : $officeDoc->relPathAndFilename;

		$tocLabel = $this->cObj->cObjGetSingle ($this->conf['template.']['tocLabelCObj'], $this->conf['template.']['tocLabelCObj.']);

		$pbsCObjName = $this->performPageBreaks ? 'singleCObj' : 'multipleCObj';
		$pageBreakSwitchLabel = $this->cObj->cObjGetSingle ($this->conf['template.']['pagebreakSwitchLabel.'][$pbsCObjName], $this->conf['template.']['pagebreakSwitchLabel.'][$pbsCObjName.'.']);

		$outArr['title'] =  $currentLabel;
		$outArr['previous'] =  ($prevNr ? ($this->pi_linkTP_keepPIvars($prevLabel, array ('showPage'=>intval($prevNr)),1)) : '');
		$outArr['next'] =  ($nextNr ? ($this->pi_linkTP_keepPIvars($nextLabel, array ('showPage'=>intval($nextNr)),1)) : '');
		$outArr['toc'] =  $this->pi_linkTP_keepPIvars($tocLabel, array ('showPage'=>'TOC', 'view'=>'multiple'),1);
		$outArr['download'] = $this->cObj->typoLink ($downloadLabel,array ('parameter' => $downloadParameter));
		$outArr['pagebreak'] = $this->pi_linkTP_keepPIvars($pageBreakSwitchLabel, array ('view'=> $this->performPageBreaks ? 'single' : 'multiple'),1);

		return $outArr;
	}

	/**
	 * Renders a table of content for the current document
	 *
	 * @param	array		$tocArr: The table of content as 'level' => level, 'sectionheader' => toc entry
	 * @return	string		HTML output
	 * @access private
	 */
	 function renderTOC ($tocArr) {
	 	global $TSFE;

	 	$offset = 12;
		if (is_array ($tocArr['items'])) {
		 	$out = chr(10).str_repeat (' ',$offset).'<div class="table-of-content">'.chr(10);
			$out .= str_repeat (' ',$offset+3).'<h2>'.$this->pi_getLL('toc','Table Of Content').'</h2>'.chr(10);
			$level = 1;
			$lines = array ();
			foreach ($tocArr['items'] as $item) {
				if ($item['sectionHeader']) {
					if (!intval ($item['level'])) {
						$item['level'] = $level;
					}
					$sectionHeader = $TSFE->csConvObj->utf8_to_entities ($item['sectionHeader'], $TSFE->charSet);
					while ($item['level'] > $level) {
						$lines[] = str_repeat(' ',($level*3)+$offset+3).'<ul>';
						$level++;
					}
					while ($item['level'] < $level) {
						$level--;
						$lines[] = str_repeat(' ',($level*3)+$offset+3).'</ul>';
					}
					$lines[] = str_repeat(' ',(($level*3)+$offset+3)).'<li><strong>'.' '. $item['numbering'].'</strong> '.$this->pi_linkTP_keepPIvars($sectionHeader,array('showPage'=>$item['page']),1).'</li>';
					while ($item['level'] < $level) {
						$level--;
						$lines[] = str_repeat(' ',($level*3)+$offset+3).'</ul>';
					}
				}
			}
			while (1 < $level) {
				$level--;
				$lines[] = str_repeat(' ',($level*3)+$offset+3).'   </ul>';
			}
			$out .= str_repeat (' ',$offset+3).'<ul>'.chr(10);
			$out .= implode (chr(10),$lines).chr(10);
			$out .= str_repeat (' ',$offset+3).'</ul>'.chr(10);
			$out .= str_repeat (' ',$offset).'</div>'.chr(10);
		} else {
			$out = false;
		}

	 	return $out;
	 }

	/**
	 * Configures the page breaks and table of content levels for the given document object.
	 *
	 * @param	object		$obj: The document object to be configured
	 * @return	void
	 * @access private
	 */
	 function configurePageBreaksAndToc (&$docObj) {
	 	$evalArr = array ();
	 	if (isset ($this->conf['pageBreaks.']['minCharsPerPage'])) {
			$evalArr[] = '$this->charactersSinceLastPageBreak >= '.intval ($this->conf['pageBreaks.']['minCharsPerPage']);
	 	}

			// Configure page breaks
		if (intval ($this->conf['pageBreaks.']['breakByHeadings.']['enable'])) {
			$breakLevel = t3lib_div::intInRange ($this->conf['pageBreaks.']['breakByHeadings.']['level'],1,10);
			$evalArr[] = 'is_a ($obj, "rlmp_officelib_tcheader")';
			$evalArr[] = '$obj->level <= '.$breakLevel;
			$evalArr[] = 'isset ($obj->content)';

			if (intval ($this->conf['pageBreaks.']['breakByHeadings.']['omitConsecutives'])) {
				$evalArr[] = '!is_a ($this->previousObj, "rlmp_officelib_tcheader")';
			}
		}
	    $docObj->pageBreakConf['body'] = array ('eval' => $evalArr);

			// Configure table of content
		$tocLevel = t3lib_div::intInRange ($this->conf['pageBreaks.']['TOC.']['level'],1,10);
		$docObj->tocConf['body'] = array (
			'eval' => array (
				'is_a ($obj, "rlmp_officelib_tcheader")',
				'$obj->level <= '.$tocLevel,
				'isset ($obj->content)'
			)
		);
	 }

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rlmp_officedocuments/pi1/class.tx_rlmpofficedocuments_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rlmp_officedocuments/pi1/class.tx_rlmpofficedocuments_pi1.php']);
}

?>