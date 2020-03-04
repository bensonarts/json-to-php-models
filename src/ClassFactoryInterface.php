<?php

namespace Strikebit\Util;

interface ClassFactoryInterface
{
    /**
     * @param string    $className
     * @param \stdClass $obj
     * @param array     $objects
     *
     * @return array
     */
    public function create(string $className, \stdClass $obj, array $objects = []): array;
}
