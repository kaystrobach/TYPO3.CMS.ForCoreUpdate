<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ExtDirect\Helper;

/**
 * Handle update core
 */
class CoreUpdater extends Action\AbstractAction implements Action\ActionInterface {

    /**
     * @var array
     */
    protected $linksToCheck = array(
        'typo3', 'index.php', 'typo3_src'
    );

    /**
     * @var array
     */
    protected $filesToCheck = array(
        'typo3', 'index.php'
    );

    /**
     * @var null|array
     */
    protected $calculatedPaths = null;

    /**
     * @var array
     */
    protected $versionInformation = null;

    /**
    * From v6.2.0alpha2 there is no t3lib directory in the source
    */
    protected function hasInstallationt3lib () {
        list($major, $minor, $patch) = explode('.', TYPO3_version);
        if ($major >= 6) {
            if ($major > 6) {
                return false;
            } else {
                if ($minor > 2) {
                    if ($patch > 1) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    protected function isSymlinkInstallation() {
        return is_link(PATH_site . 'index.php');
    }


    protected function initializeEnvironment() {
        // get paths
        $this->calculatedPaths  = array(
            'PATH_site' => PATH_site,
            'realSourceLocation' => NULL,
        );

        if ( ! is_link(PATH_site.'index.php')) {
            $this->calculatedPaths['realSourceLocation'] = PATH_site;
        } else {
            $this->calculatedPaths['realSourceLocation'] = realpath(dirname(PATH_site . readlink(PATH_site.'index.php')) . '/..') . '/';
        }

        // check write permissions
        if(!is_writable(PATH_site)) {
            throw new \TYPO3\CMS\Install\Exception('Can´t write TYPO3 root directory! Needed to change the symlink ...');
        }
        if(!is_writable($this->calculatedPaths['realSourceLocation'])) {
            throw new \TYPO3\CMS\Install\Exception('Can´t write source location, this is needed to save the new source ...');
        }
    }

    protected function getBranchDownloads() {
        $this->getVersionInformation();
        $branchInstalled = substr(
            $this->versionInformation['installed'],
            0,
            strrpos($this->versionInformation['installed'], '.')
        );
        return Helper::getInstance()->getAllVersionInformationByBranch($branchInstalled);
    }

    protected function getLocallyAvailableVersions() {
        $files = scandir($this->calculatedPaths['realSourceLocation']);
        $this->availableVersionSources =  array();

        $this->getVersionInformation();
        $branchInstalled = substr(
            $this->versionInformation['installed'],
            0,
            strrpos($this->versionInformation['installed'], '.')
        );

        foreach($files as $file) {
            if(substr($file, 0, 10) === 'typo3_src-') {
                $version = substr($file, 10);
                $versionBranch = substr($version, 0, strrpos($version, '.'));
                if($branchInstalled === $versionBranch) {
                    $this->availableVersionSources[] = $version;
                }
            }
        }

        return $this->availableVersionSources;
    }

    protected function getAllLocallyAvailableVersions() {
        $files = scandir($this->calculatedPaths['realSourceLocation']);
        $this->availableVersionSources = array();

        foreach($files as $file) {
            if(substr($file, 0, 10) === 'typo3_src-') {
                $version = substr($file, 10);
                $this->availableVersionSources[] = $version;
            }
        }

        return $this->availableVersionSources;
    }


    protected function getVersionInformation() {
        if($this->versionInformation === null) {
            return $this->versionInformation = Helper::getInstance()->isUpToDate();
        } else {
            return $this->versionInformation;
        }
    }

    protected function getAvailableDownloads() {
        $this->getVersionInformation();
        $branchInstalled = substr(
            $this->versionInformation['installed'],
            0,
            strrpos($this->versionInformation['installed'], '.')
        );

        $branchVersions = Helper::getInstance()->getAllVersionInformationByBranch($branchInstalled);
        
        list($major, $minor) = explode('.', $branchInstalled);
        $nextVersions = array();
        if ($minor > 0) {
            $nextBranch = implode('.', array($major, $minor+1));
            foreach (Helper::getInstance()->getAllVersionInformationByBranch($nextBranch) as $ver) {
                if ($ver !== TYPO3_version) {
                    array_push($nextVersions, $ver);
                }
            }
        }

        return array_merge($nextVersions, $branchVersions);
    }

    /**
     * Action to switch the version
     *
     * @param string $selectedVersionSwitch
     */
    function switchAction($selectedVersionSwitch) {
        $this->initializeEnvironment();

        $newSource = $this->calculatedPaths['realSourceLocation'] . 'typo3_src-' . $selectedVersionSwitch;
        if ($this->isSymlinkInstallation()) {
            /*
                Update with symlinks
            */
            if(file_exists($newSource) && is_dir($newSource)) {
                unlink(PATH_site . 'typo3_src');
                symlink($newSource, PATH_site . 'typo3_src');
            } else {
                $this->flashMessageContainer->add(
                    htmlspecialchars($newSource) . ' does not exist',
                    'Problem switching source',
                    \t3lib_Flashmessage::ERROR
                );
            }
        } else {
            /*
                Update without symlinks
            */
            foreach ($this->filesToCheck as $fileToCheck) {
                if ( ! is_writable(PATH_site . $fileToCheck)) {
                    $message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
                    $message->setTitle('Problem switching source');
                    $message->setMessage(htmlspecialchars(PATH_site . $fileToCheck) . ' isn\'t writable');
                    return $message;
                }
            }
            
            rename(PATH_site . 'index.php', PATH_site . 'index.php.old');
            copy($newSource . DIRECTORY_SEPARATOR . 'index.php', PATH_site . 'index.php');

            rename(PATH_site . 'typo3', PATH_site . 'typo3.old');
            Helper::getInstance()->copyDirectory($newSource . DIRECTORY_SEPARATOR. 'typo3' . DIRECTORY_SEPARATOR, PATH_site . 'typo3' . DIRECTORY_SEPARATOR);

            rename(PATH_site . 't3lib', PATH_site . 't3lib.old');
            if ($this->hasInstallationt3lib()) {
                Helper::getInstance()->copyDirectory($newSource . DIRECTORY_SEPARATOR . 't3lib' . DIRECTORY_SEPARATOR, PATH_site . 't3lib' . DIRECTORY_SEPARATOR);
            }
        }

        $message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\OkStatus');
        $message->setMessage('Switched to ' . $selectedVersionSwitch);
        return $message;
    }

    /**
     * Action to get a version from the provider
     *
     * @param string $selectedVersion
     */
    function importAction($selectedVersion) {
        $this->initializeEnvironment();

        if(!is_dir($this->calculatedPaths['realSourceLocation'] . 'typo3_src-' . $selectedVersion)) {
            if(!class_exists('ZipArchive')) {
                $this->flashMessageContainer->add(
                    'Missing class ZipArchive, please install the corresponding PHP Extension.'
                );
                return;
            }
            $zipFile = $this->calculatedPaths['realSourceLocation']  . 'typo3_src-' . $selectedVersion . '.zip';
            $buffer  = GeneralUtility::getUrl('http://get.typo3.org/' . $selectedVersion . '/zip/');
            GeneralUtility::writeFile($zipFile, $buffer);
            unset($buffer);
            $zipArchive = new \ZipArchive();
            $zipArchive->open($zipFile);
            $extracted = $zipArchive->extractTo($this->calculatedPaths['realSourceLocation']);
            $zipArchive->close();
            unset($zipArchive);
            unlink($zipFile);

            if ($extracted) {
                $message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\OkStatus');
                $message->setTitle('Extracted');
                $message->setMessage('Extracted to  ' . htmlspecialchars($this->calculatedPaths['realSourceLocation']));
                return $message;
            }
        } else {
            $message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\WarningStatus');
            $message->setMessage('Already exists ' . htmlspecialchars($selectedVersion));
            return $message;
        }
        $this->redirect('index');
    }


    /**
     * Handle this action
     *
     * @return string content
     */
    public function handle() {
        $this->initialize();

        $actionMessages = array();
        
        try {
            $this->initializeEnvironment();
        } catch(\TYPO3\CMS\Install\Exception $e) {
            $message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
            $message->setMessage($e);
            $actionMessages[] = $message;
        }

        $this->view->assign('versionInformation',      $this->getVersionInformation());
        $this->view->assign('calculatedPaths',         $this->calculatedPaths);
        $this->view->assign('availableVersionSources', $this->getLocallyAvailableVersions());
        $this->view->assign('allAvailableVersionSources', $this->getAllLocallyAvailableVersions());
        $this->view->assign('branchDownloads',         $this->getBranchDownloads());
        $this->view->assign('availableDownloads',      $this->getAvailableDownloads());


        if (isset($this->postValues['set']['import'])) {
            //$this->view->assign('updateAction', 'performUpdate');
            $selectedVersion = $this->postValues['values']['selectedVersion'];
            $actionMessages[] = $this->importAction($selectedVersion);
        } else if (isset($this->postValues['set']['switch'])) {
            $selectedVersion = $this->postValues['values']['selectedVersionSwitch'];
            $actionMessages[] = $this->switchAction($selectedVersion);
        }

        $this->view->assign('actionMessages', $actionMessages);
        return $this->view->render();
    }
}

?>
