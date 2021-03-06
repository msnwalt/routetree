<?php

use Webflorist\RouteTree\Events\NodeNotFound;
use Webflorist\RouteTree\RouteNode;
use Webflorist\RouteTree\Exceptions\NodeNotFoundException;
use Webflorist\RouteTree\RouteTree;
use Webflorist\RouteTree\Services\RouteUrlBuilder;

if (!function_exists('route_tree')) {
    /**
     * Gets the RouteTree singleton from Laravel's service-container
     *
     * @return RouteTree
     */
    function route_tree()
    {
        return app(RouteTree::class);
    }
}

if (!function_exists('route_node')) {
    /**
     * Retrieves (current or specific) RouteNode.
     *
     * @param string|null $id null to get current RoutNode
     * @return RouteNode
     * @throws NodeNotFoundException
     */
    function route_node(?string $id=null) : RouteNode
    {
        if (is_string($id)) {
            return route_tree()->getNode($id);
        }
        return route_tree()->getCurrentNode();
    }
}


if (!function_exists('route_node_url()')) {
    /**
     * Generate an URL to the action of a route-node.
     *
     * @param string $nodeId The node-id for which this url is generated (default=current node).
     * @param string $action The node-action for which this url is generated (default='index|get').
     * @param array $parameters An associative array of [parameterName => parameterValue] pairs to be used for any route-parameters in the url (default=current route-parameters).
     * @param string $locale The language this url should be generated for (default=current locale).
     * @param bool $absolute Create absolute paths instead of relative paths (default=true/configurable).
     * @return RouteUrlBuilder
     * @throws NodeNotFoundException
     */
    function route_node_url($nodeId = null, $action = null, $parameters = null, $locale = null, $absolute = null): RouteUrlBuilder
    {
        return new RouteUrlBuilder($nodeId, $action, $parameters, $locale, $absolute);
    }
}

if (!function_exists('trans_by_route')) {

    /**
     * Translate the given message and work with current route.
     *
     * @param string $id
     * @param bool $useParentNode
     * @param string $nodeId
     * @param array $parameters
     * @param string $locale
     * @return string
     * @throws NodeNotFoundException
     */
    function trans_by_route($id = null, $useParentNode = false, $nodeId = '', $parameters = [], $locale = null)
    {

        if (empty($nodeId)) {
            $routeNode = route_tree()->getCurrentNode();
        } else {
            $routeNode = route_tree()->getNode($nodeId);
        }

        if ($useParentNode) {
            $routeNode = $routeNode->getParentNode();
        }

        $id = $routeNode->getContentLangFile() . '.' . $id;

        return trans($id, $parameters, $locale);
    }
}

if (!function_exists('route_node_id()')) {
    /**
     * Get the node-id of the current route.
     *
     * @return string
     * @deprecated Use route_node()->getId() instead
     */
    function route_node_id()
    {
        return route_tree()->getCurrentNode()->getId();
    }
}