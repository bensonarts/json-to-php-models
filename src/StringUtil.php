<?php

namespace Strikebit\Util;

class StringUtil
{
    /**
     * Convert snake case or kebab case to camelCase
     *
     * @param $value
     *
     * @return string
     */
    public static function snakeKebabToCamelCase($value): string
    {
        $value = str_replace('-', '', ucwords($value, '-'));
        $value = str_replace('_', '', ucwords($value, '_'));

        return $value;
    }
}
