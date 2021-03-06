<?php

namespace Webflorist\RouteTree\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webflorist\RouteTree\RegisteredRoute;
use Webflorist\RouteTree\Exceptions\ActionNotFoundException;
use Webflorist\RouteTree\Exceptions\UrlParametersMissingException;

class Route extends JsonResource
{

    /**
     * The RegisteredRoute instance.
     *
     * @var RegisteredRoute
     */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     * @throws ActionNotFoundException
     */
    public function toArray($request)
    {
        return [
            'type' => 'routes',
            'id' => $this->generateRouteId(),
            'attributes' => [
                'node' => $this->resource->routeNode->getId(),
                'action' => $this->resource->routeAction->getName(),
                'uri' => $this->resource->path,
                'locale' => $this->resource->locale,
                'methods' => $this->resource->methods,
                'title' => $this->resource->routeAction->getTitle(
                    $this->resource->routeKeys,
                    $this->resource->locale
                ),
                'navTitle' => $this->resource->routeAction->getNavTitle(
                    $this->resource->routeKeys,
                    $this->resource->locale
                ),
                'payload' => $this->resource->routeAction->payload->toArray(
                    $this->resource->routeKeys,
                    $this->resource->locale
                )
            ]

        ];
    }

    /**
     * @return string
     */
    protected function generateRouteId(): string
    {
        $routeId = $this->resource->route->getName();
        if (!is_null($this->resource->routeKeys) && count($this->resource->routeKeys) > 0) {
            $routeId .= ':' . implode(',', $this->resource->routeKeys);
        }
        return $routeId;
    }
}