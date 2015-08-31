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
        if ($type == Query::TYPE_XPATH) {
            return $expression;
        }

        $expressions = explode(',', $expression);
        $paths = [];

        foreach ($expressions as $expression) {
            if (array_key_exists($expression, static::$compiled)) {
                $paths[] = static::$compiled[$expression];
            }

            static::$compiled[$expression] = static::cssToXpath($expression);

            $paths[] = static::$compiled[$expression];
        }

        return implode('|', $paths);
    }

    /**
     * @param  string $selector
     * @param  bool   $prefix
     * @return string
     */
    protected static function cssToXpath($selector, $prefix = '//')
    {
        $tag = "(?P<tag>[a-z0-9]+)?";
        $attr = "(\[(?P<attr>\S+)=(?P<value>[^\]]+)\])?";
        $id = "(#(?P<id>[^\s:>#\.]+))?";
        $class = "(\.(?P<class>[^\s:>#\.]+))?";
        $child = "(first|last|nth)-child";
        $expr = "(\((?P<expr>[^\)]+)\))";
        $pseudo = "(:(?P<pseudo>".$child.")".$expr."?)?";
        $rel = "\s*(?P<rel>>)?";

        $regexp = "/".$tag.$attr.$id.$class.$pseudo.$rel."/isS";
        $xpath  = '';

        if (preg_match($regexp, $selector, $tokens)) {
            $attributes = array();

            // if the id attribute specified
            if (isset($tokens['id']) and $tokens['id'] !== '') {
                $attributes[] = "@id='".$tokens['id']."'";
            }

            // if the attributes specified
            if (isset($tokens['attr']) and $tokens['attr'] !== '') {
                // if specified only the attribute name
                if (!(isset($tokens['value']))) {
                    $attributes[] = "@".$tokens['attr'];
                } else {
                    $attrValue = !empty($tokens['value']) ? $tokens['value'] : '';
                    $attributes[] = "@".$tokens['attr']."='".$attrValue."'";
                }
            }

            //if the class attribute specified
            if (isset($tokens['class']) and $tokens['class'] !== '') {
                $attributes[] = 'contains(concat(" ", normalize-space(@class), " "), " '.$tokens['class'].' ")';
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
                        } elseif (preg_match("/^[0-9]+$/", $expression)) {
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

            $xpath  = $prefix;
            $xpath .= ((isset($tokens['tag'])) and ($tokens['tag'] !== '')) ? $tokens['tag'] : '*';

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
