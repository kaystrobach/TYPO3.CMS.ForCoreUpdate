<?php
namespace TYPO3\CMS\Install\ExtDirect;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Helper {
    /**
     * @var null
     */
    protected static $instance = NULL;

    /**
     * @return null|tx_CoreUpdate_Lib_Helper
     */

    public function __construct() {

    }

    static function getInstance() {
        if(self::$instance === NULL) {
            self::$instance = new Helper();
        }
        return self::$instance;
    }
    function getAllVersionInformation() {
        $versionInformation = json_decode(GeneralUtility::getURL('http://get.typo3.org/json'), TRUE);
        return $versionInformation;
    }

    function getAllVersionInformationByBranch($branch) {
        $versionInformation = $this->getAllVersionInformation();
        if(array_key_exists($branch, $versionInformation)) {
            #throw new Exception(json_encode($versionInformation[$branch]['releases']));
            return $versionInformation[$branch]['releases'];
        } else {
            return array();
        }
    }

    /**
     * @throws Exception
     */
    function isUpToDate() {
        $versionInformation = $this->getAllVersionInformation();

        if($versionInformation === null) {
            $return = array(
                'systemState'             => 'error',
                'message'                 => 'Could not retrieve version information.',
                'installed'               => TYPO3_version,
                'latest_installed_branch' => '?',
            );
        } else {
            if(($versionInformation['latest_lts'] === TYPO3_version)
            || ($versionInformation['latest_stable'] === TYPO3_version)
            || ($versionInformation['latest_old_stable'] === TYPO3_version)){
                    // system is uptodate - perfect
                $systemstate = 'noUpdate';
            } elseif(
                ($this->getMinor($versionInformation['latest_lts']) === $this->getMinor(TYPO3_version))
              ||($this->getMinor($versionInformation['latest_stable']) === $this->getMinor(TYPO3_version))
              ||($this->getMinor($versionInformation['latest_old_stable']) === $this->getMinor(TYPO3_version))
              ||($this->getMinor($versionInformation['latest_deprecated']) === $this->getMinor(TYPO3_version))) {
                    // system is not uptodate, but the version is maintained, search for minor updates
                $minorVersion = $this->getMinor(TYPO3_version);
                $patchVersion = $this->getPatch(TYPO3_version);
                /*if(!array_key_exists($minorVersion . '.' . $patchVersion, $versionInformation[$this->getMinor(TYPO3_version)]['releases'])) {
                    $systemstate = 'error';
                    $message     = 'installed TYPO3 version not found in version list';
                } else {*/
                    $systemstate  = 'noUpdate';
                    $patchVersion++;

                    while(array_key_exists($minorVersion . '.' . $patchVersion, $versionInformation[$this->getMinor(TYPO3_version)]['releases'])) {
                        switch($versionInformation[$this->getMinor(TYPO3_version)]['releases'][$minorVersion . '.' . $patchVersion]['type']) {
                            case 'regular':
                                if($systemstate !== 'newSecurityUpdate') {
                                    $systemstate = 'newMinorUpdate';
                                }
                            break;
                            case 'security':
                                $systemstate = 'newSecurityUpdate';
                            break;
                            default:
                                throw new Exception('unknown update type');
                            break;
                        }
                        $patchVersion++;
                    }
                /*}*/
            } else {
                // system is not uptodate, version is not maintained anymore
                $systemstate = 'newMajorUpdate';
            }
            $return = array(
                'systemState'             => $systemstate,
                'latest_lts'              => $versionInformation['latest_lts'],
                'latest_stable'           => $versionInformation['latest_stable'],
                'latest_old_stable'       => $versionInformation['latest_old_stable'],
                'latest_deprecated'       => $versionInformation['latest_deprecated'],
                'latest_installed_branch' => $versionInformation[$this->getMinor(TYPO3_version)]['latest'],
                'installed'               => TYPO3_version,
            );
            if(!empty($message)) {
                $return['message'] = $message;
            }
        }
        return $return;
    }

  protected function initCache() {
        t3lib_cache::initializeCachingFramework();
        try {
            $this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('coreupdate_versionStore');
        } catch (t3lib_cache_exception_NoSuchCache $e) {
            $this->cacheInstance = $GLOBALS['typo3CacheFactory']->create(
                'coreupdate_versionStore',
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['coreupdate_versionStore']['frontend'],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['coreupdate_versionStore']['backend'],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['coreupdate_versionStore']['options']
            );
        }
    }

    protected function storeInCache($value) {
        $this->initCache();
        return $GLOBALS['typo3CacheManager']->getCache('coreupdate_versionStore')->set('coreupdate_versionStore', $value, array(), 3600);
    }

  protected function getFromCache() {
        $this->initCache();
        return $GLOBALS['typo3CacheManager']->getCache('coreupdate_versionStore')->get('coreupdate_versionStore');
    }

    public function getMinor($version) {
        list($major, $minor, $patch) = explode('.', TYPO3_version);
        $branch_version = intval($major) . '.' . intval($minor);
        return $branch_version;
    }
    public function getPatch($version) {
        list($major, $minor, $patch) = explode('.', TYPO3_version);
        return intval($patch);
    }

    public function copyDirectory($source, $destination) {
        if (is_dir($source)) {
            @mkdir($destination);
            $directory = dir($source);
            while ( FALSE !== ( $readdirectory = $directory->read() ) ) {
                if ( $readdirectory == '.' || $readdirectory == '..' ) {
                    continue;
                }
                $PathDir = $source . DIRECTORY_SEPARATOR . $readdirectory; 
                if ( is_dir( $PathDir ) ) {
                    $this->copyDirectory( $PathDir, $destination . DIRECTORY_SEPARATOR . $readdirectory );
                    continue;
                }
                copy( $PathDir, $destination . DIRECTORY_SEPARATOR . $readdirectory );
            }
     
            $directory->close();
        }else {
            copy( $source, $destination );
        }
    }

}
