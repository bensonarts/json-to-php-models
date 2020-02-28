<?php

class JsonParser
{
    /**
     * @var ClassBuilder
     */
    private $builder;

    /**
     * JsonParser constructor.
     *
     * @param bool $useTypeHinting
     * @param bool $useFluentSetters
     */
    public function __construct(bool $useTypeHinting = true, bool $useFluentSetters = false)
    {
        $this->builder = new ClassBuilder($useTypeHinting, $useFluentSetters);
    }

    /**
     * @param string $className
     * @param string $jsonStr
     * @param string $namespace|null
     */
    public function fromJson(string $className, string $jsonStr, ?string $namespace = null): void
    {
        $obj = json_decode($jsonStr, false);
        $this->builder->build($className, $obj, $namespace);
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
     * ClassBuilder constructor.
     *
     * @param bool $useTypeHinting
     * @param bool $useFluentSetters
     */
    public function __construct(bool $useTypeHinting, bool $useFluentSetters = false)
    {
        $this->useTypeHinting = $useTypeHinting;
        $this->useFluentSetters = $useFluentSetters;
    }

    /**
     * @param string      $className
     * @param \stdClass   $obj
     * @param string|null $namespace
     *
     * @return ClassPrototype
     */
    public function build(string $className, \stdClass $obj, ?string $namespace): ClassPrototype
    {
        $newClass = new ClassPrototype($className, $this->useTypeHinting, $this->useFluentSetters, $namespace);

        foreach ($obj as $key => $value) {
            if (is_object($value)) {
                $className = StringUtil::snakeKebabToCamelCase($key);
                $newClass->addMethod($key, $className);
                $this->build($className, $value, $namespace);
            } elseif (is_array($value)) {
                $newClass->addMethod($key, gettype($value));
                if (!empty($value) && is_object($value[0])) {
                    $className = StringUtil::snakeKebabToCamelCase($key);
                    $this->build($className, $value[0], $namespace);
                }
            } else {
                $newClass->addMethod($key, gettype($value));
            }
        }

        echo $newClass;

        return $newClass;
    }
}

class ClassPrototype
{
    /**
     * @var string
     */
    private $name;

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
     * @param string      $name
     * @param bool        $useTypeHinting
     * @param bool        $useFluentSetters
     * @param string|null $namespace
     */
    public function __construct(string $name, bool $useTypeHinting, bool $useFluentSetters = false, ?string $namespace = null)
    {
        $this->name = $name;
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
     * @return $this->name
     */
    public function set$methodName(?$dataType \$$propertyName): $this->name
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
     * @return $this->name
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
namespace $this->namespace


EOL;

        }

        $str .= <<<EOL
class $this->name
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

$json = file_get_contents(__DIR__ . '/../data/test.json');
$parser = new JsonParser(true, false);
$parser->fromJson('Person', $json, 'Acme\Entity');
