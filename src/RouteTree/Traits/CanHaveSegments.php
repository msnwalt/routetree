<?php

namespace Webflorist\RouteTree\Traits;

use Webflorist\RouteTree\LanguageMapping;

/**
 * Trait CanHaveParameterRegex
 *
 * This trait provides RouteNodes and RouteActions
 * with functionality to manage path segments
 * for different locales.
 *
 * @package Webflorist\RouteTree
 */
trait CanHaveSegments
{

    /**
     * An associative array with the languages as keys and the path-segments to be used for this node as values.
     *
     * @var array
     */
    protected $segments = [];

    /**
     * Sets the path-segment(s) to be used for this node.
     * Can either be a LanguageMapping object,
     * or a string (to be used for all locales).
     *
     * @param LanguageMapping|string $segment
     * @return CanHaveSegments
     */
    public function segment($segment)
    {

        // Iterate through configured languages.
        foreach ($this->getLocales() as $locale) {

            // If $segments is an array and contains an entry for this locale, we use that.
            if ($segment instanceof LanguageMapping && $segment->has($locale)) {
                $this->setSegmentForLanguage($segment->get($locale), $locale);
            } // If $segments is a string, we use that.
            else if (is_string($segment)) {
                $this->setSegmentForLanguage($segment, $locale);
            }

        }

        return $this;
    }

    /**
     * Is a segment set for $locale?
     *
     * @param $locale
     * @return bool
     */
    protected function hasSegment(string $locale) : bool
    {
        return $this->getSegment($locale) !== null;
    }

    /**
     * Returns the segment set for $locale.
     *
     * @param string $locale
     * @return string|null
     */
    protected function getSegment(string $locale) : ?string
    {
        if (is_string($this->segments)) {
            return $this->segments;
        }

        if (is_array($this->segments) && isset($this->segments[$locale])) {
            return $this->segments[$locale];
        }

        return null;
    }

    /**
     * Sets the path-segment to be used for this node in the specified languages.
     *
     * @param $segment
     * @param $language
     */
    protected function setSegmentForLanguage($segment, $language)
    {

        $this->segments[$language] = $segment;

        // If the path segment is a parameter, we also store it in $this->parameter (if not already done).
        if ((substr($segment, 0, 1) === '{') && (substr($segment, -1) === '}')) {
            if (method_exists($this, 'parameter') && is_null($this->parameter)) {
                $this->parameter(str_replace('{', '', str_replace('}', '', $segment)));
            }
        }

    }

}