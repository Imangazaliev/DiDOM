<?php
/**
 * Created by PhpStorm.
 * User: Ben
 * Date: 2016/11/7
 * Time: 下午7:46
 */

namespace DiDom;


trait common
{
    /**
     * @param $selector
     * @param string $type
     * @return null|string
     */
    public function firstElementText($selector, $type = Query::TYPE_CSS)
    {
        if ($Element = $this->first($selector, $type)) {
            return $Element->text();
        }
        return null;
    }


    /**
     * @param $selector
     * @param string $type
     * @return null|string
     */
    public function firstElementHtml($selector, $type = Query::TYPE_CSS)
    {
        if ($Element = $this->first($selector, $type)) {
            return $Element->html();
        }
        return null;
    }

    /**
     * @param $selector
     * @param string $type
     * @return null|string
     */
    public function firstElementInnerHtml($selector, $type = Query::TYPE_CSS)
    {
        if ($Element = $this->first($selector, $type)) {
            return $Element->innerHtml();
        }
        return null;
    }
}