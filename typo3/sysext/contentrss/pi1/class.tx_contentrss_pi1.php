<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Steffen Kamper <info@sk-typo3.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'RSS from Content' for the 'contentrss' extension.
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 * @package	TYPO3
 * @subpackage	tx_contentrss
 */
class tx_contentrss_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_contentrss_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_contentrss_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'contentrss';	// The extension key.
	var $pi_checkCHash = true;
	var $versioningEnabled = false;
	var $sys_language_mode;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		// init vars
		$pidList = $this->pi_getPidList($this->cObj->data['pages'],$this->conf["recursive"]);
		$type = $GLOBALS['TSFE']->type;
		$myType = intval($this->conf['typeNum']);
		
		// get language and version infos
		$this->sys_language_mode = $this->conf['sys_language_mode'] ? $this->conf['sys_language_mode'] : $GLOBALS['TSFE']->sys_language_mode;
		if (t3lib_extMgm::isLoaded('version')) {
			$this->versioningEnabled = true;
		}
		
		
		// no empty pid list
		if (!$pidList) {
			return '';
		}
		// type must be myType
		if ($type == 0 || $type != $myType) {
			return sprintf($this->pi_getLL('wrong_pagetype'), $this->cObj->typoLink('this', array('parameter' => $GLOBALS['TSFE']->id . ',' . $myType)));
		}
		
		// fetch Content elements
		$where = 'tx_contentrss_excluderss=0';
		$orderBy = $this->conf['orderBy'] ? $this->conf['orderBy'] : 'crdate';
		$limit = $this->conf['limit'] ? intval($this->conf['limit']) : '10';
		
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'*',
							'tt_content',
							$where . $this->cObj->enableFields('tt_content'),
							$groupBy='',
							$orderBy,
							$limit=''
						);
		if (!$res) {
			return $this->pi_getLL('no_content_found');
		}
		
		$contentRows = array();
		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$listType = $row['list_type'];
			if ($listType == '') {
				// normal content elements
				//get language overlay  
				if ($GLOBALS['TSFE']->sys_language_content) {
					$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tt_content', $row, $GLOBALS['TSFE']->sys_language_content, $this->sys_language_mode == 'strict' ? 'hideNonTranslated' : '');
				}
				if ($this->versioningEnabled) {
					// get workspaces Overlay
					$GLOBALS['TSFE']->sys_page->versionOL('tt_content', $row);
					// fix pid for record from workspace
					$GLOBALS['TSFE']->sys_page->fixVersioningPid('tt_content', $row);
				}
				$contentRows[] = $row;   
			} else {
				// it's a plugin, look for registered function
				if ($GLOBALS['extConf'][$this->extKey]['contentRSS'][$listType]['contentPreview']) {
					// call registered function
					$row['bodytext'] = t3lib_div::callUserFunction($GLOBALS['extConf'][$this->extKey]['contentRSS'][$listType]['contentPreview'], $row);
					$contentRows[] = $row;   
				}
			}
		}
		
		return $this->compileRows($contentRows);
	}
	
	function compileRows($rows) {
	
		$template = $this->conf['rssTemplate'];		
		$rowSubpart = $this->cObj->getSubpart($template, '###CONTENTROWS###');
		$rowHeader = $this->cObj->getSubpart($template, '###HEADER###');
		
		$rowContent = '';
		foreach($rows as $row) {
			$markerArray = array(
				'###TITLE###' => $row['header'] ? $row['header'] : '[no title]',
				'###LINK###' => $this->conf['siteLink'] . $this->cObj->typoLink_URL(array('parameter' => $row['pid'], 'section' => 'c' . $row['uid'])),
				'###CONTENT###' => t3lib_div::fixed_lgd(strip_tags($this->pi_RTEcssText($row['bodytext'])), $this->conf['contentLength']),
				'###AUTHOR###' => $row['author'],
				'###DATE###' => date('D, d M Y H:i:s O', $row['crdate']) 
			);
			$rowContent .= $this->cObj->substituteMarkerArrayCached($rowSubpart, $markerArray, array(), array());	
		}
		$subpartArray['###CONTENTROWS###'] = $rowContent;
		
		$markerArray = array(
			'###XML_DECLARATION###' => $this->conf['xmlDeclaration'],
			'###SITE_TITLE###' => $this->conf['siteTitle'],
			'###SITE_LINK###' => $this->conf['siteLink'],
			'###SITE_DESCRIPTION###' => $this->conf['siteDescription'],
			'###SITE_LANG###' => $this->conf['siteLang'],
			'###IMG###' => $this->conf['siteImage'],
			'###IMG_W###' => $this->conf['siteImageW'],
			'###IMG_H###' => $this->conf['siteImageH'],
			'###COPYRIGHT###' => $this->conf['rssCopyright'],
			'###WEBMASTER###' => $this->conf['rssWebmaster'],
			'###MANAGINGEDITOR###' => $this->conf['rssManagingEditor'],
			'###LASTBUILD###' => date('D, d M Y H:i:s O')
			
		);
		return $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, array());
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contentrss/pi1/class.tx_contentrss_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contentrss/pi1/class.tx_contentrss_pi1.php']);
}

?>