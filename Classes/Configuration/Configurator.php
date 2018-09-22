<?php

/***************************************************************
 *  Copyright notice
 *
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

namespace SourceBroker\Hugo\Configuration;

use SourceBroker\Hugo\Domain\Repository\Typo3PageRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Configurator Class
 */
class Configurator
{
    /**
     * @var Configurator[]
     */
    protected static $instances = [];

    /**
     * Configuration of module set as array
     *
     * @var null|array
     */
    protected $config = null;

    /**
     * @var int
     */
    protected $pageUid;

    /**
     * @param int $pid
     *
     * @return Configurator
     */
    public static function getByPid(int $pid)
    {
        if (!isset(self::$instances[$pid])) {
            self::$instances[$pid] = GeneralUtility::makeInstance(self::class, null, $pid);
        }

        return self::$instances[$pid];
    }

    /**
     *
     * @return Configurator
     * @throws Exception
     */
    public static function getFirstRootsiteConfig()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $rootPages = $objectManager->get(Typo3PageRepository::class)->getSiteRootPages();
        if ($rootPages !== null) {
            foreach ($rootPages as $rootPage) {
                return self::getByPid((int)$rootPage['uid']);
            }
        } else {
            throw new Exception('Can not find any root page in your TYPO3.', 1537646094);
        }
    }

    /**
     * Configurator constructor.
     * @param null $config
     * @param int $pageIdToGetTsConfig
     * @throws \Exception
     */
    public function __construct($config = null, $pageIdToGetTsConfig = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        } else {
            $this->getPagesTSconfigForHugo((int)$pageIdToGetTsConfig);
        }

        $this->pageUid = (int)$pageIdToGetTsConfig;
    }

    /**
     * @return array|null
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array|null $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return int
     */
    public function getPageUid(): int
    {
        return $this->pageUid;
    }

    /**
     * Return option from configuration array with support for nested comma separated notation as "option1.suboption"
     *
     * @param string $name Configuration
     * @param null $overwriteConfig
     * @return array|null|string
     */
    public function getOption($name = null, $overwriteConfig = null)
    {
        $config = null;
        if (is_string($name)) {
            $pieces = explode('.', $name);
            if ($pieces !== false) {
                if ($overwriteConfig === null) {
                    $config = $this->config;
                } else {
                    $config = $overwriteConfig;
                }
                foreach ($pieces as $piece) {
                    if (!is_array($config) || !array_key_exists($piece, $config)) {
                        return null;
                    }
                    $config = $config[$piece];
                }
            }
        }
        return $config;
    }

    /**
     * Load configurator with TSconfig from give page id
     *
     * @param int $pageIdToGetTsConfig
     * @throws \Exception
     */
    protected function getPagesTSconfigForHugo(int $pageIdToGetTsConfig)
    {
        $config = GeneralUtility::makeInstance(TypoScriptService::class)
            ->convertTypoScriptArrayToPlainArray(BackendUtility::getPagesTSconfig($pageIdToGetTsConfig));

        if (!isset($config['tx_hugo'])) {
            throw new \Exception(
                'There is no TSconfig for tx_hugo in the page id=' . $pageIdToGetTsConfig,
                1501692752398
            );
        }

        $this->setConfig($config['tx_hugo']);

        self::$instances[$pageIdToGetTsConfig] = $this;
    }
}
