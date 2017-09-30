# PHP Router [![Build Status](https://travis-ci.org/devlibs/routing.svg?branch=master)](https://travis-ci.org/devlibs/routing) [![Coverage Status](https://coveralls.io/repos/github/devlibs/routing/badge.svg?branch=master)](https://coveralls.io/github/devlibs/routing?branch=master)

A fast, flexible and scalable HTTP router for PHP.

## Features

- **Grouping and nested group**
- **Easy to design RESTful API**
- **Full Tests**
- **Flexible and scalable**
- **No third-party library dependencies**
- **Named Param Placeholder**
- **Detect all request methods of the specify path**
- **Straightforward documentation**

## Requirements

- PHP - `7.0`, `7.1` and `master` are tested, theoretically, it supports `5.4` or newer.

## Install

```
composer require devlibs/routing:dev-master
```

## Documentation

```php
include '/path-to-vendor/autoload.php';

use DevLibs\Routing\Router;

// create an router instance
$settings = [
    'middlewares' => [
        'DebugMiddleware',
    ],
];
$router = new Router($settings);
```

### Register handler

```php
Router::handle($method, $path, $handler, $settings = null);
```

- `method` - `string` or `array`, **case-sensitive**, such as `GET`, `GET|POST`, `['GET', 'POST']`, but "GET, POST" and "GET| POST" are invalid.
- `path` - the path **MUST** start with slash `/`, such as `/`, `/users`, `/users/<username>`.
- `handler` - `mixed`, whatever you want.
- `settings` - user-defined settings.

Examples

| Method            | Path                         | Handler | Matched                            | Unmatched                              |
|:------------------|:-----------------------------|:--------|:-----------------------------------|----------------------------------------|
| GET               | /                            | handler | `GET /`                            | `POST /` `get /`                       |
| GET&#124;POST     | /users                       | handler | `GET /users` `POST /users`         |                                        |
| ['GET', 'POST']   | /merchants                   | handler | `GET /merchants` `POST /merchants` |                                        |
| GET               | /users/<username>            | handler | `GET /users/foo` `GET /users/bar`  |                                        |
| GET               | /orders/<order_id:\d+>       | handler | `GET /orders/123456`               | `GET /orders/letters`                  |

It also provides a few shortcuts for registering handler:

- `Router::delete`
- `Router::get`
- `Router::post`
- `Router::put`

```php
$router->get('/', 'handler');

$router->handle('GET|POST', '/users', 'handler');

$router->handle(['GET', 'POST'], '/merchants', 'handler');

$router->get('/users/<username>', 'handler');

$router->get('/orders/<order_id:\d+>', 'handler');
```

### Dispatch request

```php
Router::dispatch($method, $path);
```

- `method` - request method, **case-sensitive**.
- `path` - URI path

If matched, an [`Route`](#route) instance which implements [`RouteInterface`](#routeinterface) will be returns, `null` otherwise.

```php
$path = '/users/baz';
$route = $router->dispatch(Router::METHOD_GET, $path);
if (is_null($route)) {
    throw new \Exception('404 Not Found');
}

// handle requset
$handler = $route->handler(); // 'handler'
$params = $route->params(); // ['username' => 'baz']
$settings = $route->settings(); // $settings
```

### Route

Class `Route` implements [`RouteInterface`](#routeinterface), provides some basic methods.

You can also define your own `Route` class via the following code snippet:

```php
Router::$routeClassName = 'namespace\MyRoute';
```

### RouteInterface

`Route` class **MUST** implements this interface, see [`RouteInterface`](src/RouteInterface.php) for more detail.

### Named Params Placeholder

As the examples shown above, Router has ability to detect the param's value of the path.

In general, an placeholder pattern MUST be one of "<name>" and "<name:regex>", it will be 
converted to `([^/]+)` and `(regex)` respectively.
You can also change it via replace the `Router::$replacePatterns` and `Router::$replacements`.

| Pattern                                     | Path                                       | Matched | Params |
|:--------------------------------------------|:-------------------------------------------|:--------|:--------------------------------------------------------|
| `/guests/<name>`                            | `/guests/小明`                              | YES     | `['name' => '小明']`                                     |
| `/guests/<name:\w+>`                        | `/guests/foo`                              | YES     | `['name' => 'foo']`                                     |
| `/guests/<name:\w+>`                        | `/guests/小明`                              | NO      |                                                         |
| `/orders/<order_id:\d+>`                    | `/orders/123`                              | YES     | `['order_id' => '123']`                                 |
| `/orders/<order_id:\d+>`                    | `/orders/letters`                          | NO      |                                                         |
| `/posts/<year:\d{4}>/<month:\d{2}>/<title>` | `/posts/2017/10/hello-world`               | YES     | `['year' => '2017', 'month' => '10', name' => 'foo']`   |
| `/posts/<year:\d{4}>/<month:\d{2}>/<title>` | `/posts/201/10/hello-world`                | NO      |                                                         |
| `/posts/<year:\d{4}>/<month:\d{2}>/<title>` | `/posts/2017/9/hello-world`                | NO      |                                                         |
| `/posts/<year:\d{4}><month:\d{2}>/<title>`  | `/posts/201710/hello-world`                | YES     | `['year' => '2017', 'month' => '10', name' => 'foo']`   |

### Settings

You can extend Router via `settings`, such as `param's default value` and `middleware` etc, but this topic are out of
scope of this document.

### Grouping

Grouping is an powerful feature of Router for separating modules or API's versions.
So this library also implements this feature, it allows nested grouping.

```php
Router::group($prefix, array $settings = []);
```

- `prefix` - group prefix, it **MUST NOT** contains slash `/`. 
- `settings` - settings for extending, it will inherits parent's settings.

```php
// grouping
$v1Settings = [
    'version' => '1',
    'middlewares' => [
        'AuthMiddleware',
    ],
];
$v1 = $router->group('v1', $v1Settings);
$v1->get('/hello', 'hello');
$route = $router->dispatch(Router::METHOD_GET, '/v1/hello'); // matched
/**
 * [
 *     'version' => '1',
 *     'middlewares' => [
 *         'DebugMiddleware',
 *         'AuthMiddleware',
 *     ],
 * ];
 */
 var_dump($route->settings());
```

```php
// nested group
$v1Users = $v1->group('users');
$v1Users->get('/', 'users');
$v1Users->get('/<name>', 'user profile');
$route = $router->dispatch(Router::METHOD_GET, '/v1/users'); // matched
$route = $router->dispatch(Router::METHOD_GET, '/v1/users/bar'); // matched
```

### RESTful API

As the examples shown above, it is obviously easy to design a RESTful API application.

```php
$router->get('/products', 'products');
$router->post('/products', 'create product');
$router->get('/products/<product_id:\d+>', 'product detail');
$router->delete('/products/<product_id:\d+>', 'delete product');
```

### Detect methods

In consideration of `OPTIONS` request, it provides an API for detecting all valid methods of the specify URI path.

```php
Route::getAllowMethods($path, $methods = null);
```

- `path` - request URL path
- `methods` - `Router::$methods` defined some common request methods, but it does not include all request methods,
 you can specify the methods if the key method is not one of the `Router::$methods`. 

```
$allowMethods = $router->getAllowMethods('/merchants'); // ['GET', 'POST']
```

## FAQ

### Package Not Found

Please add the following repository into `repositories` when `composer` complains about
that `Could not find package devlibs/routing ...`.

```json
{
    "type": "git",
    "url": "https://github.com/devlibs/routing.git"
}
```