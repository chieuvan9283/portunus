<?php

namespace Portunus\Console;

use Composer\Script\Event;
use Portunus\Application;

class Composer
{
    const DEFAULT_DATA_DIR = 'data';
    const DEFAULT_DB_NAME = 'portunus.sqlite';

    /**
     * @param Event $event
     */
    protected static function initialize(Event $event)
    {
        if (!defined('PORTUNUS_COMPOSER_VENDOR_DIR')) {
            define('PORTUNUS_COMPOSER_VENDOR_DIR', $event->getComposer()->getConfig()->get('vendor-dir'));
        }
    }

    /**
     * Post update event handler
     *
     * @param Event $event
     * @throws \Exception
     */
    public static function postUpdate(Event $event)
    {
        self::initialize($event);
        self::writeConfigXml($event);
        Application::createDb();
    }

    /**
     * Post install event handler
     *
     * @param Event $event
     * @throws \Exception
     */
    public static function postInstall(Event $event)
    {
        self::initialize($event);
        self::writeConfigXml($event);
        Application::createDb();
    }

    /**
     * Writes portunus config xml
     *
     * @param Event $event
     */
    protected static function writeConfigXml(Event $event)
    {
        $portunusDataDir = self::DEFAULT_DATA_DIR;
        $portunusDbName = self::DEFAULT_DB_NAME;
        extract(self::getConfigParams($event));

        $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<!--
NOTE: THIS FILE IS AUTO-GENERATED BY THE PORTUNUS LIBRARY
Created: %%CREATED%%
-->
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <parameters>
        <parameter key="portunus.composer_vendor_dir" type="constant">PORTUNUS_COMPOSER_VENDOR_DIR</parameter>
        <parameter key="portunus.dev">false</parameter>
        <parameter key="doctrine.db.filename">%%PORTUNUS_FILENAME%%</parameter>
        <parameter key="doctrine.db.data_dir">%%PORTUNUS_DATADIR%%</parameter>
    </parameters>
</container>
EOF;

        // write config xml
        $xml = str_replace('%%CREATED%%', time(), $xml);
        $xml = str_replace('%%PORTUNUS_FILENAME%%', $portunusDbName, $xml);
        $xml = str_replace('%%PORTUNUS_DATADIR%%', $portunusDataDir, $xml);
        file_put_contents(__DIR__.'/../../../config/config.xml', $xml);
    }

    /**
     * Gets the db configuration params
     *
     * @param Event $event
     * @return array
     */
    protected static function getConfigParams(Event $event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        $portunusDataDir = self::DEFAULT_DATA_DIR;
        if (array_key_exists('portunus-data-dir', $extra)) {
            $portunusDataDir = $extra['portunus-data-dir'];
        }

        $portunusDbName = self::DEFAULT_DB_NAME;
        if (array_key_exists('portunus-db-name', $extra)) {
            $portunusDbName = $extra['portunus-db-name'];
        }

        return compact('portunusDbName', 'portunusDataDir');
    }
}