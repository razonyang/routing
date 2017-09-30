<?php

use PHPUnit\Framework\TestCase;
use DevLibs\Routing\Router;
use DevLibs\Routing\Route;

class RouterTest extends TestCase
{
    private $class;

    /**
     * @covers DevLibs\Routing\Router::__construct
     */
    public function testEmptyRouter()
    {
        $router = new Router();

        $this->assertEquals([], $this->getPropertyValue($router, 'groups'));
        $this->assertEquals([], $this->getPropertyValue($router, 'routes'));
        $this->assertEquals([], $this->getPropertyValue($router, 'settings'));
        $this->assertEquals([], $this->getPropertyValue($router, 'patterns'));
        $this->assertEquals(null, $this->getPropertyValue($router, 'combinedPattern'));
        $this->assertEquals(1, $this->getPropertyValue($router, 'routesNextIndex'));
        $this->assertEquals(
            ['~<([^:]+)>~', '~<([^:]+):([^>]+)>?~', '~/$~'],
            $this->getPropertyValue($router, 'replacePatterns')
        );
        $this->assertEquals(
            ['([^/]+)', '($2)', ''],
            $this->getPropertyValue($router, 'replacements')
        );

        // another router with specify settings
        $settings = ['name' => 'foo'];
        $anotherRouter = new Router($settings);
        $this->assertEquals($settings, $this->getPropertyValue($anotherRouter, 'settings'));

        return $router;
    }

    /**
     * @covers  DevLibs\Routing\Router
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRouter
     *
     * @param Router $router
     */
    public function testHandle($router)
    {
        $route = new Route();
        $route->setHandler('handle');

        $path = '/handle';

        $router->handle(Router::METHOD_GET, $path, $route->handler());
        $this->assertCount(1, $this->getPropertyValue($router, 'routes'));

        $router->handle(Router::METHOD_POST, $path, $route->handler());
        $this->assertCount(2, $this->getPropertyValue($router, 'routes'));

        // test dispatch
        foreach (['handle', '/handle'] as $path) {
            foreach (Router::$methods as $method) {
                if (in_array($method, [Router::METHOD_GET, Router::METHOD_POST])) {
                    $this->assertEquals($route, $router->dispatch($method, $path));
                } else {
                    $this->assertEquals(null, $router->dispatch($method, $path));
                }
            }
        }
    }

    /**
     * @covers  DevLibs\Routing\Router
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRouter
     *
     * @param Router $router
     */
    public function testHandleShortcut($router)
    {
        $methods = Router::$methods;
        $methods[] = 'TRACE'; // additional method
        // treats method as path and handler
        foreach ($methods as $method) {
            $path = '/' . $method;
            switch ($method) {
                case Router::METHOD_DELETE:
                    $router->delete($path, $method);
                    break;
                case Router::METHOD_GET:
                    $router->get($path, $method);
                    break;
                case Router::METHOD_POST:
                    $router->post($path, $method);
                    break;
                case Router::METHOD_PUT:
                    $router->put($path, $method);
                    break;
                default:
                    $router->handle($method, $path, $method);
                    break;
            }
        }

        // dispatch
        foreach ($methods as $method) {
            foreach ($methods as $path) {
                $res = $router->dispatch($method, $path);
                if ($method == $path) {
                    $this->assertEquals($method, $res->handler());
                } else {
                    $this->assertEquals(null, $res);
                }
            }
        }
    }

    /**
     * @covers  DevLibs\Routing\Router
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRouter
     *
     * @param Router $router
     */
    public function testParamPlaceholder($router)
    {
        // round one, without any param placeholder
        $route1 = new Route();
        $route1->setHandler('users');
        $router->get('/users', $route1->handler());
        $this->assertEquals($route1, $router->dispatch(Router::METHOD_GET, '/users'));

        // round two, one param placeholder which can matches any type value
        $route2 = new Route();
        $route2->setHandler('user profile');
        $router->get('/users/<username>', $route2->handler());
        // string
        $round2Res1 = $router->dispatch(Router::METHOD_GET, '/users/foo');
        $this->assertEquals($route2->handler(), $round2Res1->handler());
        $this->assertEquals(['username' => 'foo'], $round2Res1->params());
        // int
        $round2Res2 = $router->dispatch(Router::METHOD_GET, '/users/123456');
        $this->assertEquals($route2->handler(), $round2Res2->handler());
        $this->assertEquals(['username' => '123456'], $round2Res2->params());

        // round three, one param placeholder which can matches specify type value
        $route3 = new Route();
        $route3->setHandler('order detail');
        // matches integer only
        $router->get('/orders/<order_id:\d+>', $route3->handler());
        // string
        $round3Res1 = $router->dispatch(Router::METHOD_GET, '/orders/bar');
        $this->assertEquals(null, $round3Res1);
        // int
        $round3Res2 = $router->dispatch(Router::METHOD_GET, '/orders/654321');
        $this->assertEquals($route3->handler(), $round3Res2->handler());
        $this->assertEquals(['order_id' => '654321'], $round3Res2->params());

        // round four, multiple param placeholders
        $route4 = new Route();
        $route4->setHandler('post detail');
        $router->get('/posts/<year:\d{4}>/<month:\d{2}>/<title>', $route4->handler());
        // invalid year
        $round4Res1 = $router->dispatch(Router::METHOD_GET, '/posts/201/09/hello-world');
        $this->assertEquals(null, $round4Res1);
        // invalid month
        $round4Res2 = $router->dispatch(Router::METHOD_GET, '/posts/2017/9/hello-world');
        $this->assertEquals(null, $round4Res2);
        // invalid year and month
        $round4Res3 = $router->dispatch(Router::METHOD_GET, '/posts/201/9/hello-world');
        $this->assertEquals(null, $round4Res3);
        // valid year and month
        $round4Res4 = $router->dispatch(Router::METHOD_GET, '/posts/2017/09/hello-world');
        $this->assertEquals($route4->handler(), $round4Res4->handler());
        $this->assertEquals(['year' => '2017', 'month' => '09', 'title' => 'hello-world'], $round4Res4->params());

        // round five, multiple param placeholders which not split by slash
        $route5 = new Route();
        $route5->setHandler('post detail');
        $router->get('/posts/<year:\d{4}><month:\d{2}>/<title>', $route5->handler());
        // invalid year
        $round5Res1 = $router->dispatch(Router::METHOD_GET, '/posts/20109/hello-world');
        $this->assertEquals(null, $round5Res1);
        // invalid month
        $round5Res2 = $router->dispatch(Router::METHOD_GET, '/posts/20179/hello-world');
        $this->assertEquals(null, $round5Res2);
        // valid year and month
        $round5Res3 = $router->dispatch(Router::METHOD_GET, '/posts/201709/hello-world');
        $this->assertEquals($route5->handler(), $round5Res3->handler());
        $this->assertEquals(['year' => '2017', 'month' => '09', 'title' => 'hello-world'], $round5Res3->params());
    }

    /**
     * @covers  DevLibs\Routing\Router
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRouter
     *
     * @param Router $router
     */
    public function testDispatch($router)
    {
        // no patterns
        $this->assertEquals(null, $router->dispatch(Router::METHOD_GET, '/'));
    }

    /**
     * @covers  DevLibs\Routing\Router
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRouter
     *
     * @param Router $router
     */
    public function testGetAllowMethods($router)
    {
        $path = '/';
        // round one
        $this->assertEquals([], $router->getAllowMethods($path));

        // round two
        $this->assertEquals([], $router->getAllowMethods('/404'));

        // round three
        $routeGet = new Route();
        $routeGet->setHandler('get');
        $router->get($path, $routeGet->handler());
        $this->assertEquals([Router::METHOD_GET], $router->getAllowMethods($path));

        // round four
        $routePost = new Route();
        $routePost->setHandler('post');
        $router->post($path, $routePost->handler());
        $this->assertEquals([Router::METHOD_GET, Router::METHOD_POST], $router->getAllowMethods($path));

        // round five
        $router->handle(Router::$methods, '/any', 'any');
        $this->assertEquals(Router::$methods, $router->getAllowMethods('/any'));

        // round six
        $specifyMethods = [Router::METHOD_GET, Router::METHOD_DELETE, Router::METHOD_POST];
        $this->assertEquals($specifyMethods, $router->getAllowMethods('/any', $specifyMethods));
    }

    /**
     * @covers  DevLibs\Routing\Router
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRouter
     *
     * @param Router $router
     */
    public function testSlashes($router)
    {
        $route = new Route();
        $route->setHandler('slashes1');
        $router->get('/slashes1', $route->handler());

        $res1 = $router->dispatch(Router::METHOD_GET, 'slashes1');
        $this->assertEquals($route->handler(), $res1->handler());
        $this->assertEquals(false, $res1->isEndWithSlash());

        $res2 = $router->dispatch(Router::METHOD_GET, 'slashes1/');
        $this->assertEquals($route->handler(), $res2->handler());
        $this->assertEquals(true, $res2->isEndWithSlash());

        $res3 = $router->dispatch(Router::METHOD_GET, '/slashes1');
        $this->assertEquals($route->handler(), $res3->handler());
        $this->assertEquals(false, $res3->isEndWithSlash());

        $res4 = $router->dispatch(Router::METHOD_GET, '/slashes1/');
        $this->assertEquals($route->handler(), $res4->handler());
        $this->assertEquals(true, $res4->isEndWithSlash());
    }

    /**
     * @covers  DevLibs\Routing\Router
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRouter
     *
     * @param Router $router
     */
    public function testGroup($router)
    {
        // group v1 without settings
        $group1 = $router->group('v1');
        $route1 = new Route();
        $route1->setHandler('v1 homepage');
        $group1->handle(Router::METHOD_GET, '', $route1->handler());
        $this->assertArrayHasKey('v1', $this->getPropertyValue($router, 'groups'));

        // group v1 round one
        $v1Round1 = $router->dispatch(Router::METHOD_GET, 'v1');
        $this->assertEquals($route1, $v1Round1);

        // group v1 round two
        $v1Round2 = $router->dispatch(Router::METHOD_GET, '/v1');
        $this->assertEquals($route1, $v1Round2);

        $route1WithSlash = clone $route1;
        $route1WithSlash->setIsEndWithSlash(true);

        // group v1 round three
        $v1Round3 = $router->dispatch(Router::METHOD_GET, 'v1/');
        $this->assertEquals($route1WithSlash, $v1Round3);

        // group v1 round four
        $v1Round4 = $router->dispatch(Router::METHOD_GET, '/v1/');
        $this->assertEquals($route1WithSlash, $v1Round4);


        // group v2 with specify settings
        $v2Settings = ['name' => 'bar'];
        $group2 = $router->group('v2', $v2Settings);

        $v2Route1 = new Route();
        $v2Route1->setHandler('v2 homepage');

        $v2Route2 = new Route();
        $v2Route2->setHandler('analyze');
        $v2Route2->setSettings(['user' => 'bar']);

        $group2->handle(Router::METHOD_GET, '/', $v2Route1->handler());
        $group2->handle(Router::METHOD_GET, '/analyze', $v2Route2->handler(), $v2Route2->settings());

        // group v2 round one
        $v2Round1 = $router->dispatch(Router::METHOD_GET, '/v2/');
        $this->assertEquals($v2Route1->handler(), $v2Round1->handler());
        $this->assertEquals($v2Settings, $v2Round1->settings());

        // group v2 round two
        $v2Round2 = $router->dispatch(Router::METHOD_GET, '/v2/analyze');
        $this->assertEquals($v2Route2->handler(), $v2Round2->handler());
        $this->assertEquals(array_merge_recursive($v2Settings, $v2Route2->settings()), $v2Round2->settings());
    }

    /**
     * @covers  DevLibs\Routing\Router
     * @covers  DevLibs\Routing\Route
     *
     * @depends clone testEmptyRouter
     *
     * @param Router $router
     */
    public function testNestedRouter($router)
    {
        // group backend
        $groupBackend = $router->group('admin', ['interval' => 5]);
        $backendRoute = new Route();
        $backendRoute->setHandler('backend panel');
        $groupBackend->get('/', $backendRoute->handler());
        $res1 = $router->dispatch(Router::METHOD_GET, '/admin');
        $this->assertEquals($backendRoute->handler(), $res1->handler());
        $this->assertEquals(['interval' => 5], $res1->settings());

        $groupUser = $groupBackend->group('users', ['timeout' => 60]);
        $userListRoute = new Route();
        $userListRoute->setHandler('user list');
        $groupUser->get('/', $userListRoute->handler());
        $res2 = $router->dispatch(Router::METHOD_GET, '/admin/users');
        $this->assertEquals($userListRoute->handler(), $res2->handler());
        $this->assertEquals(['interval' => 5, 'timeout' => 60], $res2->settings());
    }

    /**
     * @param Object $obj
     * @param string $name
     * @return mixed
     */
    private function getPropertyValue(&$obj, $name)
    {
        if (!$this->class) {
            $this->class = new ReflectionClass(Router::class);
        }
        $property = $this->class->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }
}