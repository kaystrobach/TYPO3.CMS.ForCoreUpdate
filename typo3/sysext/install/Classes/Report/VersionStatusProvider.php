<?php
namespace TYPO3\CMS\Install\Report;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Install tool controller, dispatcher class of the install tool.
 *
 * Handles install tool session, login and login form rendering,
 * calls actions that need authentication and handles form tokens.
 */

class VersionStatusProvider implements \TYPO3\CMS\Reports\StatusProviderInterface {
    /**
     * Compile environment status report
     *
     * @throws \TYPO3\CMS\Install\Exception
     * @return array<\TYPO3\CMS\Reports\Status>
     */
    public function getStatus() {
        return array(
            'Core Version' => $this->checkCoreVersion()
        );
    }

    function checkCoreVersion() {
        die('test');
        //$GLOBALS['LANG']->includeLLFile('EXT:upcoreup/locallang.xml');

        $titleMessage = "Ground Control to Major Tom";
        $message = "Test ok";
        $versionState = \TYPO3\CMS\Reports\Status::OK;


        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Reports\\Status',
            //$GLOBALS['LANG']->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_fileSystem'),
            'Latest TYPO3 Core Version of your branch',
            $titleMessage, $message, $versionState
        );
    }
}