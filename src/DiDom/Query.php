<?php

namespace DiDom;

use InvalidArgumentException;
use RuntimeException;

class Query
{
    /**
     * Types of expressions.
     * 
     * @const string
     */
    const TYPE_XPATH = 'XPATH';
    const TYPE_CSS   = 'CSS';

    /**
     * @var array
     */
    protected static $compiled = array();

    /**
     * Converts a CSS selector into an XPath expression.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     *
     * @return string XPath expression
     */
    public static function compile($expression, $type = self::TYPE_CSS)
    {
        if (strcasecmp($type, self::TYPE_XPATH) == 0) {
            return $expression;
        }

        $selectors = explode(',', $expression);
        $paths = [];

        foreach ($selectors as $selector) {
            $selector = trim($selector);

            if (array_key_exists($selector, static::$compiled)) {
                $paths[] = static::$compiled[$selector];

                continue;
            }

            static::$compiled[$selector] = static::cssToXpath($selector);

            $paths[] = static::$compiled[$selector];
        }

        return implode('|', $paths);
    }

    /**
     * Converts a CSS selector into an XPath expression.
     * 
     * @param string $selector A CSS selector
     * @param string $prefix Specifies the nesting of nodes
     *
     * @return string XPath expression
     */
    public static function cssToXpath($selector, $prefix = '//')
    {
        $segments = self::getSegments($selector);
        $xpath = '';

        $pos = strrpos($selector, '::');

        if ($pos !== false) {
            $property = substr($selector, $pos+2);
            $property = self::parseProperty($property);
            $property = self::convertProperty($property['name'], $property['args']);

            $selector = substr($selector, 0, $pos);
        }

        while (count($segments) > 0) {
            $xpath .= self::buildXpath($segments, $prefix);

            $selector = trim(substr($selector, strlen($segments['selector'])));
            $prefix   = isset($segments['rel']) ? '/' : '//';

            if ($selector === '') {
                break;
            }

            $segments = self::getSegments($selector);
        }

        if (isset($property)) {
            $xpath = $xpath.'/'.$property;
        }

        return $xpath;
    }

    /**
     * @param string $property
     * 
     * @return array
     */
    protected static function parseProperty($property)
    {
        $name = '(?P<name>[\w\-]*)';
        $args = '(?:\((?P<args>[^\)]+)\))';
        $regexp = '/(?:'.$name.$args.'?)?/is';

        if (preg_match($regexp, $property, $segments)) {
            $result = [];

            $result['name'] = $segments['name'];
            $result['args'] = isset($segments['args']) ? explode('|', $segments['args']) : [];

            return $result;
        }

        throw new RuntimeException('Invalid selector');
    }

    /**
     * @param string $name
     * @param array  $args
     * 
     * @return string
     */
    protected static function convertProperty($name, $args = [])
    {
        if ($name === 'text') {
            return 'text()';
        }

        if ($name === 'attr') {
            $attributes = [];

            foreach ($args as $attribute) {
                $attributes[] = sprintf('name() = "%s"', $attribute);
            }

            return sprintf('@*[%s]', implode(' or ', $attributes));
        }

        throw new RuntimeException('Invalid selector: unknown property type');
    }

    /**
     * @param array  $segments
     * @param string $prefix Specifies the nesting of nodes
     *
     * @return string XPath expression
     *
     * @throws InvalidArgumentException if you neither specify tag name nor attributes
     */
    public static function buildXpath($segments, $prefix = '//')
    {
        $attributes = array();

        // if the id attribute specified
        if (isset($segments['id'])) {
            $attributes[] = sprintf('@id="%s"', $segments['id']);
        }

        // if the class attribute specified
        if (isset($segments['classes'])) {
            foreach ($segments['classes'] as $class) {
                $attributes[] = sprintf('contains(concat(" ", normalize-space(@class), " "), " %s ")', $class);
            }
        }

        // if the attributes specified
        if (isset($segments['attributes'])) {
            foreach ($segments['attributes'] as $name => $value) {
                $attributes[] = self::convertAttribute($name, $value);
            }
        }

        // if the pseudo class specified
        if (isset($segments['pseudo'])) {
            $expression   = isset($segments['expr']) ? trim($segments['expr']) : '';
            $attributes[] = self::convertPseudo($segments['pseudo'], explode(',', $expression));
        }

        if (count($attributes) === 0 and !isset($segments['tag'])) {
            throw new InvalidArgumentException('The array of segments should contain the name of the tag or at least one attribute');
        }

        $tagName = isset($segments['tag']) ? $segments['tag'] : '*';
        $xpath = $prefix.$tagName;

        if ($count = count($attributes)) {
            $xpath .= ($count > 1) ? sprintf('[(%s)]', implode(') and (', $attributes)) : sprintf('[%s]', $attributes[0]);
        }

        return $xpath;
    }

    /**
     * @param string $name  The attribute name
     * @param string $value The attribute value
     * 
     * @return string
     */
    protected static function convertAttribute($name, $value)
    {
        if (substr($name, 0, 1) === '^') {
            $xpath = sprintf('@*[starts-with(name(), "%s")]', substr($name, 1));

            return $value === null ? $xpath : sprintf('%s="%s"', $xpath, $value);
        }

        switch (substr($name, -1)) {
            case '^':
                $xpath = sprintf('starts-with(@%s, "%s")', substr($name, 0, -1), $value);
                break;
            case '$':
                $xpath = sprintf('ends-with(@%s, "%s")', substr($name, 0, -1), $value);
                break;
            case '*':
                $xpath = sprintf('contains(@%s, "%s")', substr($name, 0, -1), $value);
                break;
            default:
                // if specified only the attribute name
                $xpath = $value === null ? '@'.$name : sprintf('@%s="%s"', $name, $value);
                break;
        }

        return $xpath;
    }

    /**
     * Converts a CSS pseudo-class into an XPath expression.
     * 
     * @param string $pseudo Pseudo-class
     * @param string $parameters
     *
     * @return string
     *
     * @throws \RuntimeException if passed an unknown pseudo-class
     */
    protected static function convertPseudo($pseudo, $parameters = [])
    {
        switch ($pseudo) {
            case 'first-child':
                return 'position() = 1';
                break;
            case 'last-child':
                return 'position() = last()';
                break;
            case 'empty':
                return 'count(descendant::*) = 0';
                break;
            case 'not-empty':
                return 'count(descendant::*) > 0';
                break;
            case 'nth-child':
                return self::convertNthChildExpression($parameters[0]);
                break;
            case 'contains':
                $string = trim($parameters[0], ' \'"');
                $caseSensetive = isset($parameters[1]) and (trim($parameters[1]) === 'true');

                return self::convertContains($string, $caseSensetive);
                break;
        }

        throw new RuntimeException('Invalid selector: unknown pseudo-class');
    }

    /**
     * Converts nth-child expression into an XPath expression.
     * 
     * @param string $expression nth-expression
     * 
     * @return string
     * 
     * @throws \RuntimeException if passed nth-child is empty
     * @throws \RuntimeException if passed an unknown nth-child expression
     */
    protected static function convertNthChildExpression($expression)
    {
        if ($expression === '') {
            throw new RuntimeException('Invalid selector: nth-child expression must not be empty');
        }

        if ($expression === 'odd') {
            return '(position() -1) mod 2 = 0 and position() >= 1';
        } elseif ($expression === 'even') {
            return 'position() mod 2 = 0 and position() >= 0';
        } elseif (is_numeric($expression)) {
            return sprintf('position() = %d', $expression);
        } elseif (preg_match("/^(?P<mul>[0-9]?n)(?:(?P<sign>\+|\-)(?P<pos>[0-9]+))?$/is", $expression, $segments)) {
            if (isset($segments['mul'])) {
                $segments['mul'] = strtolower($segments['mul'] === 'n') ? 1 : trim(strtolower($segments['mul']), 'n');
                $segments['sign'] = (isset($segments['sign']) and $segments['sign'] === '+') ? '-' : '+';
                $segments['pos'] = isset($segments['pos']) ? $segments['pos'] : 0;

                return sprintf('(position() %s %d) mod %d = 0 and position() >= %d', $segments['sign'], $segments['pos'], $segments['mul'], $segments['pos']);
            }
        }

        throw new RuntimeException('Invalid selector: invalid nth-child expression');
    }

    /**
     * @param string $string
     * @param bool   $caseSensetive
     * 
     * @return string
     */
    protected static function convertContains($string, $caseSensetive = false)
    {
        if ($caseSensetive) {
            return sprintf('text() = "%s"', $string);
        }

        if (function_exists('mb_strtolower')) {
            return sprintf('php:functionString("mb_strtolower", .) = php:functionString("mb_strtolower", "%s")', $string);
        } else {
            return sprintf('php:functionString("strtolower", .) = php:functionString("strtolower", "%s")', $string);
        }
    }

    /**
     * Splits the CSS selector into parts (tag name, ID, classes, attributes, pseudo-class).
     * 
     * @param string $selector CSS selector
     *
     * @return array
     *
     * @throws \InvalidArgumentException if an empty string is passed
     * @throws \RuntimeException if the selector is not valid
     */
    public static function getSegments($selector)
    {
        $selector = trim($selector);

        if ($selector === '') {
            throw new InvalidArgumentException('The selector must not be empty');
        }

        $tag = '(?P<tag>[\*|\w|\-]+)?';
        $id = '(?:#(?P<id>[\w|\-]+))?';
        $classes = '(?P<classes>\.[\w|\-|\.]+)*';
        $attrs = '(?P<attrs>\[.+\])*';
        $name = '(?P<pseudo>[\w\-]*)';
        $expr = '(?:\((?P<expr>[^\)]+)\))';
        $pseudo = '(?::'.$name.$expr.'?)?';
        $rel = '\s*(?P<rel>>)?';

        $regexp = '/'.$tag.$id.$classes.$attrs.$pseudo.$rel.'/is';

        if (preg_match($regexp, $selector, $segments)) {
            if ($segments[0] === '') {
                throw new RuntimeException('Invalid selector');
            }

            $result['selector'] = $segments[0];
            $result['tag']      = (isset($segments['tag']) and $segments['tag'] !== '') ? $segments['tag'] : '*';

            // if the id attribute specified
            if (isset($segments['id']) and $segments['id'] !== '') {
                $result['id'] = $segments['id'];
            }

            // if the attributes specified
            if (isset($segments['attrs'])) {
                $attributes = trim($segments['attrs'], '[]');
                $attributes = explode('][', $attributes);

                foreach ($attributes as $attribute) {
                    if ($attribute !== '') {
                        list($name, $value) = array_pad(explode('=', $attribute, 2), 2, null);

                        // equal null if specified only the attribute name
                        $result['attributes'][$name] = is_string($value) ? trim($value, '\'"') : null;
                    }
                }
            }

            // if the class attribute specified
            if (isset($segments['classes'])) {
                $classes = trim($segments['classes'], '.');
                $classes = explode('.', $classes);

                foreach ($classes as $class) {
                    if ($class !== '') {
                        $result['classes'][] = $class;
                    }
                }
            }

            // if the pseudo class specified
            if (isset($segments['pseudo']) and $segments['pseudo'] !== '') {
                $result['pseudo'] = $segments['pseudo'];

                if (isset($segments['expr']) and $segments['expr'] !== '') {
                    $result['expr'] = $segments['expr'];
                }
            }

            // if it is a direct descendant
            if (isset($segments['rel'])) {
                $result['rel'] = $segments['rel'];
            }

            return $result;
        }

        throw new RuntimeException('Invalid selector');
    }

    /**
     * @return array
     */
    public static function getCompiled()
    {
        return static::$compiled;
    }

    /**
     * @param array $compiled
     *
     * @throws \InvalidArgumentException if the attributes is not an array
     */
    public static function setCompiled($compiled)
    {
        if (!is_array($compiled)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be array, %s given', __METHOD__, gettype($compiled)));
        }

        static::$compiled = $compiled;
    }
}
