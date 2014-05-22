<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Robert Lemke (robert@typo3.org)
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
 *   47: class tx_rlmpofficedocuments_tvmod1
 *   58:     function templavoila_renderPreview (&$params, &$reference)
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * Class being used by templavoila's page module
 *
 * @author	Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage rlmp_officedocuments
 */
class tx_rlmpofficedocuments_tvmod1 {

	/**
	 * Renders the table of content as a preview in the templavoila page module. This is registered as a user-function
	 * and called from the page module!
	 *
	 * @param	array		$row: The current row of backend record to be rendered
	 * @param	string		$table: Name of the record's table
	 * @param	boolean		&$alreadyRendered: Used for returning true if we render this preview
	 * @param	object		&$reference: Reference to $this in the templavoila mod1
	 * @return	string		HTML output
	 * @access public
	 * @todo	Doesn't render the TOC really, only a stub for now
	 */
	function renderPreviewContent_preProcess ($row, $table, &$alreadyRendered, &$reference) {
		return '';
		if ($row['CType'] == 'list' && $row['list_type'] == 'rlmp_officedocuments_pi1') {

			$flexFormArr = t3lib_div::xml2array($row['pi_flexform']);

			$sKey = 's'.$reference->MOD_SETTINGS['currentSheetKey'];
			$lKey = is_array ($flexFormArr['data'][$sKey]['l'.$reference->MOD_SETTINGS['currentLanguageKey']]) ? 'l'.$reference->MOD_SETTINGS['currentLanguageKey'] : 'lDEF';
			$vKey = isset ($flexFormArr['data'][$sKey][$lKey]['field_officeFile']['v'.$reference->MOD_SETTINGS['currentLanguageKey']]) ? 'v'.$reference->MOD_SETTINGS['currentLanguageKey'] : 'vDEF';

			$filename = $flexFormArr['data'][$sKey][$lKey]['field_officeFile'][$vKey];

			$content = '<strong>'.htmlspecialchars ($filename).'</strong><br /><br />'.htmlspecialchars('...TOC...');
			$alreadyRendered = true;
			return $reference->linkEdit($content, $table, $row['uid']);
		}
	}
}
?>