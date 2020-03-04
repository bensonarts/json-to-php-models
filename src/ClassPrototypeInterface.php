<?php

namespace Strikebit\Util;

interface ClassPrototypeInterface
{
    /**
     * Get class name
     *
     * @return string
     */
    public function getClassName(): string;

    /**
     * Add method to class
     *
     * @param string $methodName
     * @param string $dataType
     */
    public function addMethod(string $methodName, string $dataType): void;

    /**
     * Check if method exists on class
     *
     * @param string $methodName
     *
     * @return bool
     */
    public function hasMethod(string $methodName): bool;

    /**
     * Print class property
     *
     * @param array $method
     *
     * @return string
     */
    public function printProperty(array $method): string;

    /**
     * Print class method
     *
     * @param array $method
     *
     * @return string
     */
    public function printMethod(array $method): string;
}
