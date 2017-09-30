<?php

use PHPUnit\Framework\TestCase;
use DevLibs\Routing\Route;

class RouteTest extends TestCase
{
    /**
     * @covers DevLibs\Routing\Route
     */
    public function testEmptyRoute()
    {
        $route = new Route();
        $this->assertEquals([], $route->params());
        $this->assertEquals([], $route->settings());
        $this->assertEquals(null, $route->handler());
        $this->assertEquals(false, $route->isEndWithSlash());
        return $route;
    }

    /**
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRoute
     *
     * @param Route $route
     */
    public function testSetIsEndWithSlash($route)
    {
        $route->setIsEndWithSlash(true);
        $this->assertEquals(true, $route->isEndWithSlash());
        $route->setIsEndWithSlash(false);
        $this->assertEquals(false, $route->isEndWithSlash());
    }

    /**
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRoute
     *
     * @param Route $route
     */
    public function testSetParams($route)
    {
        $params = ['name' => 'foo'];
        $route->setParams($params);
        $this->assertEquals($params, $route->params());
        $route->setParams([]);
        $this->assertEquals([], $route->params());
    }

    /**
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRoute
     *
     * @param Route $route
     */
    public function testSetHandler($route)
    {
        $handler = 'handler';
        $route->setHandler($handler);
        $this->assertEquals($handler, $route->handler());
        $route->setHandler(null);
        $this->assertEquals(null, $route->handler());
    }

    /**
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRoute
     *
     * @param Route $route
     */
    public function testSetSetting($route)
    {
        $settings = ['interval' => '5'];
        $route->setSettings($settings);
        $this->assertEquals($settings, $route->settings());
        $route->setSettings([]);
        $this->assertEquals([], $route->settings());
    }
}