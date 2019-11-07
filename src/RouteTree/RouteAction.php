<?php

namespace Webflorist\RouteTree;

use Illuminate\Routing\Route;
use Webflorist\RouteTree\Exceptions\UrlParametersMissingException;
use Webflorist\RouteTree\Traits\CanHaveParameterRegex;

class RouteAction
{

    use CanHaveParameterRegex;

    /**
     * The route-node this action belongs to.
     *
     * @var RouteNode
     */
    protected $routeNode = null;

    /**
     * Name of the action (e.g. index|create|show|get etc.)
     *
     * @var string
     */
    protected $name = null;

    /**
     * The closure to be used for this action.
     *
     * @var \Closure
     */
    protected $closure = null;

    /**
     * The controller-method to be used for this action.
     *
     * @var string
     */
    protected $uses = null;

    /**
     * The path-suffix, this action will have on top of it's node's path.
     *
     * @var string
     */
    protected $pathSuffix = null;

    /**
     * The full paths, this action was generated with.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * HTTP verb for this action.
     *
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $view;

    /**
     * @var array
     */
    private $viewData;

    /**
     * @var string
     */
    private $redirect;

    /**
     * @var int
     */
    private $redirectStatus;

    /**
     * Array of middleware, this action should be registered with.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Array of inherited middleware that should be skipped by this action.
     *
     * @var array
     */
    protected $skipMiddleware = [];

    /**
     * RouteAction constructor.
     *
     * @param string $method
     * @param string $action
     * @param RouteNode $routeNode
     */
    public function __construct(string $method, $action, RouteNode $routeNode)
    {
        $this->method = $method;
        $this->routeNode = $routeNode;
        $this->setAction($action);
        return $this;
    }

    /**
     * Set the name of this action.
     *
     * @param string $name
     */
    public function name(string $name) {
        $this->name = $name;
    }

    /**
     * Get the name of this action.
     *
     * @return string
     */
    public function getName()
    {
        if (!is_null($this->name)) {
            return $this->name;
        }
        return $this->method;
    }

    /**
     * Returns list of all possible actions, their method, route-name-suffix, parent-action and title-closure.
     *
     * @return array
     */
    public function getActionConfigs()
    {
        return [
            'index' => [
                'method' => 'get',
                'suffix' => 'index',
                'defaultTitle' => function () {
                    return $this->routeNode->getTitle();
                },
                'defaultNavTitle' => function () {
                    return $this->routeNode->getNavTitle();
                }
            ],
            'create' => [
                'method' => 'get',
                'suffix' => 'create',
                'parentAction' => 'index',
                'defaultTitle' => function () {
                    return trans('Webflorist-RouteTree::routetree.createTitle', ['resource' => $this->routeNode->getTitle()]);
                },
                'defaultNavTitle' => function () {
                    return trans('Webflorist-RouteTree::routetree.createNavTitle');
                }
            ],
            'store' => [
                'method' => 'post',
                'suffix' => 'store'
            ],
            'show' => [
                'method' => 'get',
                'suffix' => 'show',
                'parentAction' => 'index',
                'defaultTitle' => function () {
                    return $this->routeNode->getTitle() . ': ' . $this->routeNode->getActiveValue();
                },
                'defaultNavTitle' => function () {
                    return $this->routeNode->getActiveValue();
                }
            ],
            'edit' => [
                'method' => 'get',
                'suffix' => 'edit',
                'parentAction' => 'show',
                'defaultTitle' => function () {
                    return trans('Webflorist-RouteTree::routetree.editTitle', ['item' => $this->routeNode->getActiveValue()]);
                },
                'defaultNavTitle' => function () {
                    return trans('Webflorist-RouteTree::routetree.editNavTitle');
                }
            ],
            'update' => [
                'method' => 'put',
                'suffix' => 'update',
                'parentAction' => 'index'
            ],
            'destroy' => [
                'method' => 'delete',
                'suffix' => 'destroy',
                'parentAction' => 'index'
            ],
            'get' => [
                'method' => 'get'
            ],
            'post' => [
                'method' => 'post'
            ],
        ];
    }

    /**
     * Adds a single middleware to this action.
     *
     * @param string $name Name of the middleware.
     * @param array $parameters Parameters the middleware should be called with.
     * @return RouteAction
     */
    public function middleware(string $name, array$parameters=[]) {
        $this->middleware[$name] = $parameters;
        return $this;
    }

    /**
     * Skip an inherited middleware.
     *
     * @param string $name Name of the middleware.
     * @return RouteAction
     */
    public function skipMiddleware(string $name) {
        if (array_search($name,$this->skipMiddleware) === false) {
            $this->skipMiddleware[] = $name;
        }
        return $this;
    }

    /**
     * Set the path-suffix, this action will have on top of it's node's path.
     *
     * @param string $pathSuffix
     * @return RouteAction
     */
    public function setPathSuffix($pathSuffix)
    {
        $this->pathSuffix = $pathSuffix;
        return $this;
    }

    /**
     * Get the route-node this action belongs to.
     *
     * @return RouteNode
     */
    public function getRouteNode()
    {
        return $this->routeNode;
    }

    /**
     * Get the title of this action.
     *
     * @param array $parameters An associative array of [parameterName => parameterValue] pairs to be used for any route-parameters in the title-generation (default=current route-parameters).
     * @param string $locale The language the title should be fetched for (default=current locale).
     * @return string
     */
    public function getTitle($parameters = null, $locale = null)
    {

        // Try to get a title specifically set for this action.
        $title = $this->routeNode->getData('title', $parameters, $locale, $this->name);
        if ($title !== false) {
            return $this->routeNode->processTitle($parameters, $locale, $title);
        }

        // Next try calling the closure for a default-title configured within $this->getActionConfigs().
        $actionConfigs = $this->getActionConfigs();
        if (isset($actionConfigs[$this->name]['defaultTitle'])) {
            return call_user_func_array($actionConfigs[$this->name]['defaultTitle'], []);
        }

        // The default-fallback is the RouteNode's title.
        return $this->routeNode->getTitle();
    }

    /**
     * Get the action-navigation-title.
     *
     * @param array $parameters An associative array of [parameterName => parameterValue] pairs to be used for any route-parameters in the title-generation (default=current route-parameters).
     * @param string $locale The language the title should be fetched for (default=current locale).
     * @return string
     */
    public function getNavTitle($parameters = null, $locale = null)
    {

        // Try to get a navTitle specifically set for this action.
        $title = $this->routeNode->getData('navTitle', $parameters, $locale, $this->name);
        if ($title !== false) {
            return $this->routeNode->processTitle($parameters, $locale, $title);
        }

        // Next try calling the closure for a default-navTitle configured within $this->getActionConfigs().
        $actionConfigs = $this->getActionConfigs();
        if (isset($actionConfigs[$this->name]['defaultNavTitle'])) {
            return call_user_func_array($actionConfigs[$this->name]['defaultNavTitle'], []);
        }

        // Try to get a title specifically set for this action.
        $title = $this->routeNode->getData('title', $parameters, $locale, $this->name);
        if ($title !== false) {
            return $this->routeNode->processTitle($parameters, $locale, $title);
        }

        // Next try calling the closure for a default-title configured within $this->getActionConfigs().
        $actionConfigs = $this->getActionConfigs();
        if (isset($actionConfigs[$this->name]['defaultTitle'])) {
            return call_user_func_array($actionConfigs[$this->name]['defaultTitle'], []);
        }

        // The default-fallback is the RouteNode's navTitle.
        return $this->routeNode->getNavTitle();
    }

    /**
     * Set the action (controller-method, view, redirect, closure, etc.)
     * this RouteAction should use.
     *
     * @param \Closure|array|string|callable|null $name
     * @return RouteAction
     */
    public function setAction($name)
    {
        // TODO: add support for various types of $action;
        if (is_string($name) && strpos($name,'@') > 0) {
            $this->setUses($name);
        }
        else if (is_array($name) && (count($name) === 2) && isset($name['view']) && isset($name['data'])) {
            $this->setView($name['view'], $name['data']);
        }
        else if (is_array($name) && (count($name) === 2) && isset($name['redirect']) && isset($name['status'])) {
            $this->setRedirect($name['redirect'], $name['status']);
        }
        else if ($name instanceof \Closure) {
            $this->setClosure($name);
        }
        return $this;
    }

    /**
     * Set the action-string of this action (e.g. index|update|destroy|get|put etc.).
     *
     * @param string $action
     * @return RouteAction
     */
    public function setAction_OLD($action)
    {
        $actionConfigs = $this->getActionConfigs();
        if (!isset($actionConfigs[$action])) {
            // TODO: throw exception
        }
        $this->name = $action;
        return $this;
    }

    /**
     * Gets an array of all hierarchical actions of this node and all parent nodes
     * (with the root-node-action as the first element).
     *
     * This is very useful for breadcrumbs.
     *
     * E.g. The edit action of the node 'user.comment'
     * with path '/user/{user}/comments/{comment}/edit' consists of the following parent actions:
     *
     * - the default-action of the root-node with path '/'
     * - the index-action of the node 'user' with path '/user'
     * - the show-action of the node 'user' with path '/user/{user}'
     * - the index action of the node 'user.comment' with path '/user/{user}/comments'
     * - the show action of the node 'user.comment' with path '/user/{user}/comments/{comment}'
     *
     * @return RouteAction[]
     */
    public function getRootLineActions()
    {

        $rootLineActions = [];

        $this->accumulateRootLineActions($rootLineActions);

        $rootLineActions = array_reverse($rootLineActions);

        return $rootLineActions;
    }

    /**
     * Accumulate all parent actions of this and any parent nodes represented in the path for this action.
     *
     * @param $rootLineActions
     */
    protected function accumulateRootLineActions(&$rootLineActions)
    {

        $this->accumulateParentActions($rootLineActions);
        if ($this->routeNode->hasParentNode()) {
            $mostActiveRootLineAction = $this->routeNode->getParentNode()->getLowestRootLineAction();
            if ($mostActiveRootLineAction !== false) {
                array_push($rootLineActions, $mostActiveRootLineAction);
                $mostActiveRootLineAction->accumulateRootLineActions($rootLineActions);
            }
        }

    }

    /**
     * Accumulate all parent-actions within the same routeNode for this action.
     * E.g.
     * The action 'edit' with it's path 'user/{user}/edit' is a child of
     * the action 'show' with it's path 'user/{user]', which is itself a child of
     * the action 'index' with it's path 'user'.
     *
     * @param $parentActions
     */
    protected function accumulateParentActions(&$parentActions)
    {

        $actionConfigs = $this->getActionConfigs();
        if (isset($actionConfigs[$this->name]['parentAction'])) {
            $parentActionName = $actionConfigs[$this->name]['parentAction'];
            $parentAction = $this->routeNode->getAction($parentActionName);
            array_push($parentActions, $parentAction);
            $parentAction->accumulateParentActions($parentActions);
        }

    }

    /**
     * Set the closure this action should call.
     *
     * @param \Closure $closure
     * @return RouteAction
     */
    public function setClosure($closure)
    {
        $this->closure = $closure;
        return $this;
    }

    /**
     * Set 'controller@method', this action will use.
     *
     * @param string $uses
     * @return RouteAction
     */
    private function setUses($uses)
    {
        $this->uses = $uses;
        return $this;
    }

    /**
     * Set view and view-data, this action will use.
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    private function setView(string $view, array $data=[])
    {
        $this->view = $view;
        $this->viewData = $data;
    }


    /**
     * Set redirect and status-code, this action will use.
     *
     * @param string $redirect
     * @param int $status
     * @return void
     */
    private function setRedirect(string $redirect, int $status=302)
    {
        $this->redirect = $redirect;
        $this->redirectStatus = $status;
    }

    /**
     * Get the URL to this action.
     *
     * @param array $parameters An associative array of [parameterName => parameterValue] pairs to be used for any route-parameters in the url (default=current route-parameters).
     * @param string $locale The language this url should be generated for (default=current locale).
     * @param bool $absolute Create absolute paths instead of relative paths (default=true/configurable).
     * @return mixed
     * @throws UrlParametersMissingException
     */
    public function getUrl($parameters = null, $locale = null, $absolute = null)
    {

        // If no language is specifically stated, we use the current locale.
        RouteTree::establishLocale($locale);

        if (is_null($absolute)) {
            $absolute = config('routetree.absolute_urls');
        }

        return route($this->generateRouteName($locale), $this->autoFillPathParameters($parameters, $locale, true), $absolute);

    }

    /**
     * Tries to accumulate all path-parameters needed for an URL to this RouteAction.
     * The parameters can be stated as an associative array with $parameters.
     * If not all required parameters are stated, the missing ones are tried to be auto-fetched,
     * which is only possible, if the parent-nodes they belong to are currently active.
     *
     * @param array $parameters An associative array of [parameterName => parameterValue] pairs to use.
     * @param string $language The language to be used for auto-fetching the parameter-values.
     * @param bool $translateValues : If true, the auto-fetched parameter-values are tried to be auto-translated.
     * @return array
     * @throws UrlParametersMissingException
     */
    public function autoFillPathParameters($parameters, $language, $translateValues = false)
    {

        // Init the return-array.
        $return = [];

        // Get all parameters needed for the path to this action.
        $requiredParameters = $this->getPathParameters($language);

        if (count($requiredParameters) > 0) {

            // We try filling $return with the $requiredParameters from $parameters.
            $this->fillParameterArray($parameters, $requiredParameters, $return);

            // If not all required parameters were stated in the handed over $parameters-array,
            // we try to auto-fetch them from the parents of this node, if they are currently active.
            if (count($requiredParameters) > 0) {

                // Get all current path-parameters for the requested language, but only for active nodes.
                $currentPathParameters = $this->routeNode->getParametersOfNodeAndParents(true, $language, $translateValues);

                // We try filling $return with the still $requiredParameters from $currentPathParameters.
                $this->fillParameterArray($currentPathParameters, $requiredParameters, $return);

                // If there are still undetermined parameters missing, we throw an error
                if (count($requiredParameters) > 0) {
                    throw new UrlParametersMissingException('URL could not be generated due to the following undetermined parameter(s): ' . implode(',', $requiredParameters));
                }
            }
        }

        return $return;

    }

    /**
     * Returns an array of all path-parameters needed for this RouteAction
     * These are basically all path-segments enclosed in curly braces.
     *
     * @param null $locale
     * @return array
     */
    public function getPathParameters($locale = null)
    {

        // If no language is specifically stated, we use the current locale.
        RouteTree::establishLocale($locale);

        $parameters = [];
        $pathSegments = explode('/', $this->paths[$locale]);
        foreach ($pathSegments as $segment) {
            if ((substr($segment, 0, 1) === '{') && (substr($segment, -1) === '}')) {
                array_push($parameters, str_replace('{', '', str_replace('}', '', $segment)));
            }
        }

        return $parameters;

    }

    /**
     * Generate routes in each language for this action.
     *
     * @throws Exceptions\RouteNameAlreadyRegisteredException
     * @throws Exceptions\NodeNotFoundException
     */
    public function generateRoutes()
    {

        // Compile the middleware.
        $middleware = $this->compileMiddleware();

        // Compile parameter regexes.
        $parameterRegex = $this->compileParameterRegex();

        // Iterate through configured languages
        // and build routes.
        foreach (RouteTree::getLocales() as $locale) {

            $route = $this->createRoute($locale);

            $route->name(
                $this->generateRouteName($locale)
            );

            $route->middleware(
                $middleware
            );

            $route->where(
                $parameterRegex
            );

            // And register the generated route with the RouteTree service about this registered route,
            // so it can manage a static list.
            route_tree()->registerRoute($route, $this, $locale);

        }

    }

    /**
     * Generates the compiled middleware-array to be handed over to the laravel-route-generator.
     *
     * @return array
     */
    private function compileMiddleware()
    {

        // Get the middleware from the node (except this action is configured to skip it).
        $middleware = [];
        foreach ($this->routeNode->getMiddleware() as $middlewareKey => $middlewareParams) {
            if (array_search($middlewareKey, $this->skipMiddleware) === false) {
                $middleware[$middlewareKey] = $middlewareParams;
            }
        }

        // Merge it with middleware set within this action.
        $middleware = array_merge($middleware, $this->middleware);

        // Compile it into laravel-syntax.
        $compiledMiddleware = [];
        if (count($middleware) > 0) {
            foreach ($middleware as $middlewareName => $middlewareParams) {
                $compiledMiddleware[$middlewareName] = $middlewareName;
                if (count($middlewareParams) > 0) {
                    $compiledMiddleware[$middlewareName] .= ':' . implode(',', $middlewareParams);
                }
            }
        }

        return $compiledMiddleware;
    }



    /**
     * Generates the compiled array of parameter-regexes (wheres)
     * to be handed over to the laravel-route-generator.
     *
     * @return array
     */
    private function compileParameterRegex()
    {
        return array_merge($this->routeNode->wheres, $this->wheres);
    }

    /**
     * Generates a full route-name for this action for a specific language.
     *
     * @param $locale
     * @return string
     */
    private function generateRouteName($locale)
    {

        // A route name always starts with the locale.
        $routeName = $locale;

        // Then we append the id of the route-node.
        if (strlen($this->routeNode->getId()) > 0) {
            $routeName .= '.' . $this->routeNode->getId();
        }

        $routeName .= '.'.$this->getName();

        return $routeName;
    }

    /**
     * Tries to fill $targetParameters
     * with the keys stated in $requiredParameters
     * taken from $sourceParameters.
     *
     * @param $sourceParameters
     * @param $requiredParameters
     * @param $targetParameters
     * @return array
     */
    protected function fillParameterArray(&$sourceParameters, &$requiredParameters, &$targetParameters)
    {
        foreach ($requiredParameters as $key => $parameter) {
            if (is_array($sourceParameters) && isset($sourceParameters[$parameter])) {
                $targetParameters[$parameter] = $sourceParameters[$parameter];
                unset($requiredParameters[$key]);
            }
        }
    }

    /**
     * Checks, if the current action is active (optionally with the desired parameters).
     *
     * @param null $parameters
     * @return string
     */
    public function isActive($parameters = null)
    {

        // Check, if the current action is identical to this node.
        if (route_tree()->getCurrentAction() === $this) {

            // If no parameters are specifically requested, we immediately return true.
            if (is_null($parameters)) {
                return true;
            }

            // If a set of parameters should also be checked, we get the current route-parameters,
            // check if each one is indeed set, and return the boolean result.
            $currentParameters = \Route::current()->parameters();
            $allParametersSet = true;
            foreach ($parameters as $desiredParameterName => $desiredParameterValue) {
                if (!isset($currentParameters[$desiredParameterName]) || ($currentParameters[$desiredParameterName] !== $desiredParameterValue)) {
                    $allParametersSet = false;
                }
            }
            return $allParametersSet;
        }

        return false;
    }

    /**
     * @param string $locale
     * @return Route
     * @throws Exceptions\NodeNotFoundException
     */
    private function createRoute(string $locale) : Route
    {
        $uri = $this->generateUri($locale);
        
        // In case of a View Route...
        if (!is_null($this->view)) {
            return \Illuminate\Support\Facades\Route::view(
                $uri,
                $this->view,
                $this->viewData
            );
        }

        // In case of a Redirect Route...
        if (!is_null($this->redirect)) {
            return \Illuminate\Support\Facades\Route::redirect(
                $uri,
                route_tree()->getNode($this->redirect)->getPath($locale),
                $this->redirectStatus
            );
        }

        // In case of a regular action route...
        $action = [];
        if (!is_null($this->uses)) {
            $action['uses'] = $this->uses;
            if (substr($this->uses,0,1) !== '\\') {
                $action['uses'] = $this->routeNode->getNamespace() . '\\' . $this->uses;
            }
        }
        else if (is_callable($this->closure)) {
            array_push($action, $this->closure);
        }
        return \Illuminate\Support\Facades\Route::{$this->method}($uri, $action);
    }

    /**
     * @param string $locale
     * @return string
     */
    private function generateUri(string $locale): string
    {
        // Get the uri for this route-node and locale to register this route with.
        $uri = $this->routeNode->getPath($locale);

        // Save the generated uri to $this->paths
        $this->paths[$locale] = $uri;

        return $uri;
    }

}