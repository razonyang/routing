<?php
namespace DevLibs\Routing;

/**
 * Interface RouteInterface defined
 */
interface RouteInterface
{
    /**
     * @return mixed handler
     */
    public function handler();

    /**
     * @param $handler
     */
    public function setHandler($handler);

    /**
     * @return bool whether or not the path is end with slash
     */
    public function isEndWithSlash();

    /**
     * @param bool $isEndWithSlash
     */
    public function setIsEndWithSlash($isEndWithSlash);

    /**
     * @return array params
     */
    public function params();

    /**
     * @param array $params
     */
    public function setParams($params);

    /**
     * @return array settings
     */
    public function settings();

    /**
     * @param array $settings
     */
    public function setSettings($settings);
}