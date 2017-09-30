<?php
namespace DevLibs\Routing;

/**
 * Class Router is a fast, flexible and powerful HTTP router.
 */
class Router
{
    const SLASH = '/';

    /**
     * @var string request method CONNECT.
     */
    const METHOD_CONNECT = 'CONNECT';

    /**
     * @var string request method DELETE.
     */
    const METHOD_DELETE = 'DELETE';

    /**
     * @var string request method GET.
     */
    const METHOD_GET = 'GET';

    /**
     * @var string request method HEAD.
     */
    const METHOD_HEAD = 'HEAD';

    /**
     * @var string request method OPTIONS.
     */
    const METHOD_OPTIONS = 'OPTIONS';

    /**
     * @var string request method PATCH.
     */
    const METHOD_PATCH = 'PATCH';

    /**
     * @var string request method POST.
     */
    const METHOD_POST = 'POST';

    /**
     * @var string request method PUT.
     */
    const METHOD_PUT = 'PUT';

    /**
     * @var string request method TRACE.
     */
    const METHOD_TRACE = 'TRACE';

    /**
     * @var array a set of request methods.
     * @see getAllowMethods()
     */
    public static $methods = [
        self::METHOD_CONNECT,
        self::METHOD_DELETE,
        self::METHOD_GET,
        self::METHOD_HEAD,
        self::METHOD_OPTIONS,
        self::METHOD_PATCH,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_TRACE,
    ];

    /**
     * @var array mapping from group prefix to group router.
     */
    private $groups = [];

    /**
     * @var array a set of route.
     */
    private $routes = [];

    /**
     * @var array extra data for extending.
     */
    private $settings = [];

    /**
     * @var int a trick for dispatch and handle a request.
     * @see handle()
     * @see dispatch()
     */
    private $routesNextIndex = 1;

    /**
     * @var array a set of route's patterns.
     */
    private $patterns = [];

    /**
     * @var null|string a combined pattern of all patterns.
     */
    private $combinedPattern;

    /**
     * @var mixed
     * @see handle()
     */
    public static $replacePatterns = [
        '~<([^:]+)>~',
        '~<([^:]+):([^>]+)>?~',
        '~/$~'
    ];

    /**
     * @var mixed
     * @see handle()
     */
    public static $replacements = [
        '([^/]+)',
        '($2)',
        ''
    ];

    /**
     * @var string the name of route class which implements RouteInterface
     */
    public static $routeClassName = Route::class;

    /**
     * Router constructor.
     *
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $this->settings = $settings;
    }

    /**
     * Create a group router.
     *
     * @param string $prefix the group router's prefix MUST NOT contains '/'.
     * @param array $settings
     * @return static a instance of group router.
     */
    public function group($prefix, array $settings = [])
    {
        // inherits parent's settings.
        $router = new static(array_merge_recursive($this->settings, $settings));
        $this->groups[$prefix] = $router;
        return $this->groups[$prefix];
    }

    /**
     * Registers a handler for handling the specific request which
     * relevant to the given method and path.
     *
     * @param string|array $method request method, is case sensitive,
     * it is RECOMMENDED to use uppercase.
     *     method              validity
     *     "GET"               valid(RECOMMENDED)
     *     "GET|POST"          valid(RECOMMENDED)
     *     "GET,POST"          invalid
     *     ['GET', 'POST']     valid
     *
     * @param string $path the regular expression, the path MUST start with '/', if not,
     * the slash '/' will be appended to the head of path.
     * Param pattern MUST be one of "<name>" and "<name:regex>", in default,
     * it will be converted to "([^/]+)" and "(regex)" respectively.
     * The path will be converted to a pattern by preg_replace(@see $replacePatterns, @see $replacements),
     * you can change it in need.
     *
     * @param mixed $handler request handler.
     *
     * @param null|array $settings extra data for extending.
     *
     * Examples:
     *     path                              matched
     *     "/users"                           "/users"
     *     "/users/<id:\d+>"                  "/users/123"
     *     "/users/<id:\d+>/posts"            "/users/123/posts"
     *     "/users/<id:\d+>/posts/<post>"     "/users/123/posts/456", "/users/123/posts/post-title"
     */
    public function handle($method, $path, $handler, $settings = null)
    {
        if (is_array($method)) {
            $method = implode('|', $method);
        }

        // format path to regular expression.
        $pattern = preg_replace(static::$replacePatterns, static::$replacements, $path);
        // store pattern
        $this->patterns[$this->routesNextIndex] = "({$method})\ {$pattern}(/?)";

        // collect param's name.
        preg_match_all('/<([^:]+)(:[^>]+)?>/', $path, $matches);
        $params = empty($matches[1]) ? [] : $matches[1];
        $this->routes[$this->routesNextIndex] = [$handler, $params, $settings];

        // calculate the next index of routes.
        $this->routesNextIndex += count($params) + 2;

        // set combinedPattern as null when routes has been changed.
        $this->combinedPattern = null;
    }

    /**
     * A shortcut for registering a handler to handle GET request.
     *
     * @see handle()
     *
     * @param $path
     * @param $handler
     * @param null|array $setting
     */
    public function get($path, $handler, $setting = null)
    {
        $this->handle(self::METHOD_GET, $path, $handler, $setting);
    }

    /**
     * A shortcut for registering a handler to handle DELETE request.
     *
     * @see handle()
     *
     * @param $path
     * @param $handler
     * @param null|array $setting
     */
    public function delete($path, $handler, $setting = null)
    {
        $this->handle(self::METHOD_DELETE, $path, $handler, $setting);
    }

    /**
     * A shortcut for registering a handler to handle POST request.
     *
     * @see handle()
     *
     * @param $path
     * @param $handler
     * @param null|array $setting
     */
    public function post($path, $handler, $setting = null)
    {
        $this->handle(self::METHOD_POST, $path, $handler, $setting);
    }

    /**
     * A shortcut for registering a handler to handle PUT request.
     *
     * @see handle()
     *
     * @param $path
     * @param $handler
     * @param null|array $setting
     */
    public function put($path, $handler, $setting = null)
    {
        $this->handle(self::METHOD_PUT, $path, $handler, $setting);
    }

    /**
     * Retrieves handler with the given method and path.
     *
     * @param string $method request method
     * @param string $path request URL without the query string.
     * @return null|RouteInterface if matched, returns a route instance which implements RouteInterface,
     * otherwise null will be returned.
     */
    public function dispatch($method, $path)
    {
        // look for group router via the prefix.
        if ($path != '' && $path != self::SLASH && count($this->groups) > 0) {
            $start = ($path[0] == self::SLASH) ? 1 : 0;
            if (false !== $pos = strpos($path, self::SLASH, $start)) {
                $len = $pos + 1 - $start - (($path[$pos] == self::SLASH) ? 1 : 0);
                $prefix = substr($path, $start, $len);
            } else {
                $prefix = substr($path, $start);
            }
            if (isset($this->groups[$prefix])) {
                // dispatch recursive.
                $group = $this->groups[$prefix];
                $path = substr($path, strlen($prefix) + $start);
                return $group->dispatch($method, $path);
            }
        }

        return $this->dispatchInternal($method, $path, $this->settings);
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $settings router's setting.
     * @return null|RouteInterface
     */
    private function dispatchInternal($method, $path, $settings)
    {
        if (null === $pattern = $this->getCombinedPattern()) {
            return null;
        }
        if (preg_match($pattern, $method . ' ' . $this->formatPath($path), $matches)) {
            // retrieves route
            for ($i = 1; $i < count($matches) && ($matches[$i] === ''); ++$i) ;
            $route = $this->routes[$i];

            // create a route instance which implements RouterInterface
            /**
             * @var RouteInterface $instance
             */
            $instance = new static::$routeClassName;
            $instance->setHandler($route[0]);

            // fills up param's value
            $params = [];
            foreach ($route[1] as $param) {
                $params[$param] = $matches[++$i];
            }
            $instance->setParams($params);

            // merges group's settings
            $instance->setSettings(is_array($route[2]) ? array_merge_recursive($settings, $route[2]) : $settings);

            // determines whether the path is end with slash
            $instance->setIsEndWithSlash($matches[++$i] == self::SLASH);

            return $instance;
        }

        return null;
    }

    /**
     * Detects all allowed methods of the given path.
     *
     * @param $path
     * @param null|array $methods
     *
     * @return array
     */
    public function getAllowMethods($path, $methods = null)
    {
        if (null === $pattern = $this->getCombinedPattern()) {
            return [];
        }

        if ($methods === null) {
            $methods = self::$methods;
        }

        $allowMethods = [];
        foreach ($methods as $method) {
            if (preg_match($pattern, $method . ' ' . $this->formatPath($path))) {
                $allowMethods[] = $method;
            }
        }
        return $allowMethods;
    }

    /**
     * Get the combined pattern.
     *
     * @return null|string returns null if no patterns, otherwise,
     * combines all patterns into one, and returns the combined pattern.
     */
    private function getCombinedPattern()
    {
        if ($this->combinedPattern === null) {
            if (empty($this->patterns)) {
                return null;
            }
            $this->combinedPattern = "~^(?:" . implode("|", $this->patterns) . ")$~x";
        }

        return $this->combinedPattern;
    }

    private function formatPath($path)
    {
        if ($path != '' && $path[0] != self::SLASH) {
            $path = self::SLASH . $path;
        }
        return $path;
    }
}