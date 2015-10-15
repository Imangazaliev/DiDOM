<?php

namespace DiDom;

use InvalidArgumentException;

class Query
{
    /**
     * Types of expressions.
     * 
     * @string
     */
    const TYPE_XPATH = 'TYPE_XPATH';
    const TYPE_CSS   = 'TYPE_CSS';

    /**
     * @var array
     */
    protected static $compiled = array();

    /**
     * Transform CSS expression to XPath.
     *
     * @param  string $expression XPath expression or CSS selector
     * @param  string $type the type of the expression
     * @return string
     */
    public static function compile($expression, $type = self::TYPE_CSS)
    {
        if ($type == self::TYPE_XPATH) {
            return $expression;
        }

        $expressions = explode(',', $expression);
        $paths = [];

        foreach ($expressions as $expression) {
            $expression = trim($expression);

            if (array_key_exists($expression, static::$compiled)) {
                $paths[] = static::$compiled[$expression];
                continue;
            }

            static::$compiled[$expression] = static::cssToXpath($expression);

            $paths[] = static::$compiled[$expression];
        }

        return implode('|', $paths);
    }

    /**
     * @param  string $selector
     * @param  string $prefix
     * @return string
     */
    public static function cssToXpath($selector, $prefix = '//')
    {
        $tag     = '(?P<tag>[\*|\w|\-]+)?';
        $id      = '(?:#(?P<id>[\w|\-]+))?';
        $classes = '(?P<classes>\.[\w|\-|\.]+)*';
        $attrs   = '(?P<attrs>\[.+\])*';
        $child   = '(?:first|last|nth)-child)';
        $expr    = '(?:\((?P<expr>[^\)]+)\))';
        $pseudo  = '(?::(?P<pseudo>'.$child.$expr.'?)?';
        $rel     = '\s*(?P<rel>>)?';

        $regexp = '/'.$tag.$id.$classes.$attrs.$pseudo.$rel.'/is';

        if (preg_match($regexp, $selector, $tokens)) {
            $attributes = array();

            if ((!isset($tokens['tag'])) or ($tokens['tag'] == '')) {
                $tokens['tag'] = '*';
            }

            // if the id attribute specified
            if (isset($tokens['id']) and $tokens['id'] !== '') {
                $attributes[] = "@id='".$tokens['id']."'";
            }

            // if the attributes specified
            if (isset($tokens['attrs'])) {
                $tokens['attrs'] = trim($tokens['attrs'], '[]');
                $tokens['attrs'] = explode('][', $tokens['attrs']);

                foreach ($tokens['attrs'] as $attribute) {
                    if ($attribute !== '') {
                        list($name, $value) = array_pad(explode('=', $attribute), 2, null);

                        // if specified only the attribute name
                        $attributes[] = '@'.$name.($value == null ? '' : '='.$value);
                    }
                }
            }

            //if the class attribute specified
            if (isset($tokens['classes'])) {
                $tokens['classes'] = trim($tokens['classes'], '.');
                $tokens['classes'] = explode('.', $tokens['classes']);

                foreach ($tokens['classes'] as $class) {
                    if ($class !== '') {
                        $attributes[] = 'contains(concat(" ", normalize-space(@class), " "), " '.$class.' ")';
                    }
                }
            }

            // if the pseudo class specified
            if (isset($tokens['pseudo']) and $tokens['pseudo'] !== '') {
                if ('first-child' === $tokens['pseudo']) {
                    $attributes[] = '1';
                } elseif ('last-child' === $tokens['pseudo']) {
                    $attributes[] = 'last()';
                } elseif ('nth-child' === $tokens['pseudo']) {
                    if (isset($tokens['expr']) and '' !== $tokens['expr']) {
                        $expression = $tokens['expr'];

                        if ('odd' === $expression) {
                            $attributes[] = '(position() -1) mod 2 = 0 and position() >= 1';
                        } elseif ('even' === $expression) {
                            $attributes[] = 'position() mod 2 = 0 and position() >= 0';
                        } elseif (is_numeric($expression)) {
                            $attributes[] = 'position() = '.$expression;
                        } elseif (preg_match("/^((?P<mul>[0-9]+)n\+)(?P<pos>[0-9]+)$/is", $expression, $position)) {
                            if (isset($position['mul'])) {
                                $attributes[] = '(position() -'.$position['pos'].') mod '.$position['mul'].' = 0 and position() >= '.$position['pos'].'';
                            } else {
                                $attributes[] = $expression;
                            }
                        }
                    }
                }
            }

            $xpath = $prefix.$tokens['tag'];

            if ($count = count($attributes)) {
                $xpath .= ($count > 1) ? '[('.implode(') and (', $attributes).')]' : '['.implode(' and ', $attributes).']';
            }

            $subs   = trim(substr($selector, strlen($tokens[0])));
            $prefix = (isset($tokens['rel'])) ? '/' : '//';

            if ($subs !== '') {
                $xpath .= static::cssToXpath($subs, $prefix);
            }
        }

        return $xpath;
    }

    /**
     * @return array
     */
    public static function getCompiled()
    {
        return static::$compiled;
    }

    /**
     * @param  array $compiled
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function setCompiled($compiled)
    {
        if (!is_array($compiled)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be array, %s given', __METHOD__, gettype($compiled)));
        }

        static::$compiled = $compiled;
    }
}
