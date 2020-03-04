<?php

namespace Strikebit\Util;

class PhpGenerator
{
    /**
     * @var ClassFactoryInterface
     */
    private $factory;

    /**
     * @var array<ClassPrototypeInterface>
     */
    private $classes = [];

    /**
     * PhpGenerator constructor.
     *
     * @param bool        $useTypeHinting
     * @param bool        $useFluentSetters
     * @param string|null $namespace
     */
    public function __construct(
        bool $useTypeHinting = true,
        bool $useFluentSetters = false,
        ?string $namespace = null
    ) {
        $this->factory = new ClassFactory($useTypeHinting, $useFluentSetters, $namespace);
    }

    /**
     * @param string $className
     * @param string $jsonStr
     *
     * @return array
     */
    public function fromJson(string $className, string $jsonStr): array
    {
        $obj = json_decode($jsonStr, false);
        $this->classes = $this->factory->create($className, $obj);

        return $this->classes;
    }

    /**
     * Print output of classes into string
     *
     * @return string
     */
    public function printClasses(): string
    {
        $str = '';

        /** @var ClassPrototypeInterface $value */
        foreach ($this->classes as $value) {
            $str .= $value;
        }

        return $str;
    }
}
