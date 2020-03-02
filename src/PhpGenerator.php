<?php

class PhpGenerator
{
    /**
     * @var ClassBuilder
     */
    private $builder;

    /**
     * @var array
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
        $this->builder = new ClassBuilder($useTypeHinting, $useFluentSetters, $namespace);
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
        $this->classes = $this->builder->build($className, $obj);

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

        /** @var ClassPrototype $value */
        foreach ($this->classes as $value) {
            $str .= $value;
        }

        return $str;
    }
}

class ClassBuilder
{
    /**
     * @var bool
     */
    private $useTypeHinting;

    /**
     * @var bool
     */
    private $useFluentSetters;

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * ClassBuilder constructor.
     *
     * @param bool        $useTypeHinting
     * @param bool        $useFluentSetters
     * @param string|null $namespace
     */
    public function __construct(bool $useTypeHinting, bool $useFluentSetters = false, ?string $namespace = null)
    {
        $this->useTypeHinting = $useTypeHinting;
        $this->useFluentSetters = $useFluentSetters;
        $this->namespace = $namespace;
    }

    /**
     * @param string      $className
     * @param \stdClass   $obj
     * @param array       $objects
     *
     * @return array
     */
    public function build(string $className, \stdClass $obj, array $objects = []): array
    {
        $newClass = new ClassPrototype($className, $this->useTypeHinting, $this->useFluentSetters, $this->namespace);

        foreach ($obj as $key => $value) {
            if (is_object($value)) {
                $subClassName = StringUtil::snakeKebabToCamelCase($key);
                $newClass->addMethod($key, $subClassName);
                $objects = $this->build($subClassName, $value, $objects);
            } elseif (is_array($value)) {
                $newClass->addMethod($key, gettype($value));
                if (!empty($value) && is_object($value[0])) {
                    $subClassName = StringUtil::snakeKebabToCamelCase($key);
                    $objects = $this->build($subClassName, $value[0], $objects);
                }
            } else {
                $newClass->addMethod($key, gettype($value));
            }
        }

        $objects[$className] = $newClass;

        return $objects;
    }
}

class ClassPrototype
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var array
     */
    private $methods = [];

    /**
     * @var bool
     */
    private $useTypeHinting;

    /**
     * @var bool
     */
    private $useFluentSetters;

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * ClassPrototype constructor.
     *
     * @param string      $className
     * @param bool        $useTypeHinting
     * @param bool        $useFluentSetters
     * @param string|null $namespace
     */
    public function __construct(string $className, bool $useTypeHinting, bool $useFluentSetters = false, ?string $namespace = null)
    {
        $this->className = $className;
        $this->useTypeHinting = $useTypeHinting;
        $this->useFluentSetters = $useFluentSetters;
        $this->namespace = $namespace;
    }

    /**
     * @param string $methodName
     * @param string $dataType
     */
    public function addMethod(string $methodName, string $dataType): void
    {
        if (!$this->hasMethod($methodName)) {
            $this->methods[] = [
                'methodName' => $methodName,
                'dataType' => $dataType
            ];
        }
    }

    /**
     * @param string $methodName
     *
     * @return bool
     */
    public function hasMethod(string $methodName): bool
    {
        foreach ($this->methods as $method) {
            if ($method['methodName'] === $methodName) {
                return true;
            }
        }

        return false;
    }

    public function printProperty(array $method): string
    {
        $propertyName = lcfirst(StringUtil::snakeKebabToCamelCase($method['methodName']));
        $dataType = $this->getDataType($method['dataType']);
        return <<<EOL
    /**
     * @var $dataType
     */
    private \$$propertyName;


EOL;
    }

    /**
     * @param array $method
     *
     * @return string
     */
    public function printMethod(array $method): string
    {
        $methodName = StringUtil::snakeKebabToCamelCase($method['methodName']);
        $propertyName = lcfirst($methodName);
        $dataType = $this->getDataType($method['dataType']);
        $getterPrefix = $this->getGetterPrefix($method['dataType']);

        if ($this->useTypeHinting && $this->useFluentSetters) {
            return $this->printPhp7FluentSettersMethod($dataType, $getterPrefix, $methodName, $propertyName);
        } elseif ($this->useTypeHinting && !$this->useFluentSetters) {
            return $this->printPhp7Method($dataType, $getterPrefix, $methodName, $propertyName);
        } elseif (!$this->useTypeHinting && $this->useFluentSetters) {
            return $this->printPhp5FluentSettersMethod($dataType, $getterPrefix, $methodName, $propertyName);
        } else {
            return $this->printPhp5Method($dataType, $getterPrefix, $methodName, $propertyName);
        }
    }

    /**
     * @param string $dataType
     * @param string $getterPrefix
     * @param string $methodName
     * @param string $propertyName
     *
     * @return string
     */
    public function printPhp7Method(string $dataType, string $getterPrefix, string $methodName, string $propertyName): string
    {
        return <<<EOL

    /**
     * @return $dataType|null
     */
    public function $getterPrefix$methodName(): ?$dataType
    {
        return \$this->$propertyName;
    }

    /**
     * @param $dataType|null \$$propertyName
     */
    public function set$methodName(?$dataType \$$propertyName): void
    {
        \$this->$propertyName = \$$propertyName;
    }

EOL;
    }

    /**
     * @param string $dataType
     * @param string $getterPrefix
     * @param string $methodName
     * @param string $propertyName
     *
     * @return string
     */
    public function printPhp7FluentSettersMethod(string $dataType, string $getterPrefix, string $methodName, string $propertyName): string
    {
        return <<<EOL

    /**
     * @return $dataType|null
     */
    public function $getterPrefix$methodName(): ?$dataType
    {
        return \$this->$propertyName;
    }

    /**
     * @param $dataType|null \$$propertyName
     *
     * @return $this->className
     */
    public function set$methodName(?$dataType \$$propertyName): $this->className
    {
        \$this->$propertyName = \$$propertyName;
        
        return \$this;
    }

EOL;
    }

    /**
     * @param string $dataType
     * @param string $getterPrefix
     * @param string $methodName
     * @param string $propertyName
     *
     * @return string
     */
    public function printPhp5Method(string $dataType, string $getterPrefix, string $methodName, string $propertyName): string
    {
        return <<<EOL

    /**
     * @return $dataType|null
     */
    public function $getterPrefix$methodName()
    {
        return \$this->$propertyName;
    }

    /**
     * @param $dataType|null \$$propertyName
     */
    public function set$methodName(\$$propertyName)
    {
        \$this->$propertyName = \$$propertyName;
    }

EOL;
    }

    /**
     * @param string $dataType
     * @param string $getterPrefix
     * @param string $methodName
     * @param string $propertyName
     *
     * @return string
     */
    public function printPhp5FluentSettersMethod(string $dataType, string $getterPrefix, string $methodName, string $propertyName): string
    {
        return <<<EOL

    /**
     * @return $dataType|null
     */
    public function $getterPrefix$methodName()
    {
        return \$this->$propertyName;
    }

    /**
     * @param $dataType|null \$$propertyName
     *
     * @return $this->className
     */
    public function set$methodName(\$$propertyName)
    {
        \$this->$propertyName = \$$propertyName;
        
        return \$this;
    }

EOL;
    }

    /**
     * Convert PHP data types to type hints
     *
     * @param string $dataType
     *
     * @return string
     */
    private function getDataType(string $dataType): string
    {
        switch ($dataType) {
            case 'integer':
                return 'int';
            case 'boolean':
                return 'bool';
            case 'double':
                return 'float';
            case 'NULL':
                return 'string';
            case 'Array':
                return 'array';
        }

        return $dataType;
    }

    /**
     * Get getter prefix
     *
     * @param string $dataType
     *
     * @return string
     */
    private function getGetterPrefix(string $dataType): string
    {
        return 'boolean' === $dataType ? 'is' : 'get';
    }

    public function __toString()
    {
        $str = <<<EOL
<?php


EOL;

        if ($this->namespace) {
            $str .= <<<EOL
namespace $this->namespace;


EOL;

        }

        $str .= <<<EOL
class $this->className
{

EOL
;
        foreach ($this->methods as $method) {
            $str .= $this->printProperty($method);
        }

        foreach ($this->methods as $method) {
            $str .= $this->printMethod($method);
        }

        $str .= <<<EOL
}


EOL;


        return $str;
    }
}

class StringUtil
{
    /**
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
