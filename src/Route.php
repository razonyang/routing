<?php
namespace DevLibs\Routing;

/**
 * Class Route implements RouterInterface
 */
class Route implements RouteInterface
{
    /*
     * @var bool whether or not the path is end with slash.
     */
    protected $isEndWithSlash = false;

    /**
     * @var mixed $handler
     */
    protected $handler;

    /**
     * @var array $params
     */
    protected $params = [];

    /**
     * @var array $settings
     */
    protected $settings = [];

    /**
     * @return mixed handler
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * @param mixed $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }

    /**
     * @return bool whether or not the path is end with slash.
     */
    public function isEndWithSlash()
    {
        return $this->isEndWithSlash;
    }

    /**
     * @param bool $isEndWithSlash
     */
    public function setIsEndWithSlash($isEndWithSlash)
    {
        $this->isEndWithSlash = $isEndWithSlash;
    }

    /**
     * @return array params
     */
    public function params()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return array settings
     */
    public function settings()
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }
}