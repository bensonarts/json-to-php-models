<?php

namespace Strikebit\Util\Tests;

use PHPUnit\Framework\TestCase;
use Strikebit\Util\ClassPrototypeInterface;
use Strikebit\Util\PhpGenerator;

class PhpGeneratorTest extends TestCase
{
    public function testGenerator()
    {
        $jsonStr = file_get_contents(__DIR__ . '/../data/test.json');
        $namespace = 'Acme\Demo';
        $className = 'User';
        $generator = new PhpGenerator(true, true, $namespace);
        $this->assertInstanceOf(PhpGenerator::class, $generator);

        /** @var array $classes */
        $classes = $generator->fromJson($className, $jsonStr);
        $this->assertIsArray($classes);
        $count = count($classes);
        $this->assertEquals(6, $count);
        /** @var ClassPrototypeInterface $class */
        foreach ($classes as $class) {
            switch ($class->getClassName()) {
                case 'Country':
                    $this->assertTrue($class->hasMethod('code'));
                    $this->assertTrue($class->hasMethod('name'));
                    $this->assertTrue($class->hasMethod('planet'));
                    break;
                case 'Planet':
                    $this->assertTrue($class->hasMethod('name'));
                    $this->assertTrue($class->hasMethod('galaxy'));
                    break;
                case 'Income':
                    $this->assertTrue($class->hasMethod('netMonthly'));
                    break;
                case 'Pets':
                    $this->assertTrue($class->hasMethod('name'));
                    $this->assertTrue($class->hasMethod('type'));
                    break;
                case 'Address':
                    $this->assertTrue($class->hasMethod('street'));
                    $this->assertTrue($class->hasMethod('aptSuite'));
                    $this->assertTrue($class->hasMethod('city'));
                    $this->assertTrue($class->hasMethod('state'));
                    $this->assertTrue($class->hasMethod('postalCode'));
                    $this->assertTrue($class->hasMethod('country'));
                    break;
                case 'User':
                    $this->assertTrue($class->hasMethod('id'));
                    $this->assertTrue($class->hasMethod('employer'));
                    $this->assertTrue($class->hasMethod('balance'));
                    $this->assertTrue($class->hasMethod('first_name'));
                    $this->assertTrue($class->hasMethod('last_name'));
                    $this->assertTrue($class->hasMethod('enabled'));
                    $this->assertTrue($class->hasMethod('address'));
                    $this->assertTrue($class->hasMethod('income'));
                    $this->assertTrue($class->hasMethod('pets'));
                    break;
            }
        }
    }

    public function testPhp7TypeHintingFluentSettersOutput()
    {
        $jsonStr = file_get_contents(__DIR__ . '/../data/test.json');
        $namespace = 'Acme\Demo';
        $className = 'Test';
        $generator = new PhpGenerator(true, true, $namespace);
        $generator->fromJson($className, $jsonStr);
        $output = $generator->printClasses();
        $matchOutput = file_get_contents(__DIR__ . '/fixtures/sample-typehinting-fluent-setters.txt');

        $this->assertSame($output, $matchOutput);
    }

    public function testPhp7TypeHintingOutput()
    {
        $jsonStr = file_get_contents(__DIR__ . '/../data/test.json');
        $namespace = 'Acme\Demo';
        $className = 'Test';
        $generator = new PhpGenerator(true, false, $namespace);
        $generator->fromJson($className, $jsonStr);
        $output = $generator->printClasses();
        $matchOutput = file_get_contents(__DIR__ . '/fixtures/sample-typehinting.txt');

        $this->assertSame($output, $matchOutput);
    }

    public function testPhp5Output()
    {
        $jsonStr = file_get_contents(__DIR__ . '/../data/test.json');
        $namespace = 'Acme\Demo';
        $className = 'Test';
        $generator = new PhpGenerator(false, false, $namespace);
        $generator->fromJson($className, $jsonStr);
        $output = $generator->printClasses();
        $matchOutput = file_get_contents(__DIR__ . '/fixtures/sample.txt');

        $this->assertSame($output, $matchOutput);
    }

    public function testPhp5FluentSettersOutput()
    {
        $jsonStr = file_get_contents(__DIR__ . '/../data/test.json');
        $namespace = 'Acme\Demo';
        $className = 'Test';
        $generator = new PhpGenerator(false, true, $namespace);
        $generator->fromJson($className, $jsonStr);
        $output = $generator->printClasses();
        $matchOutput = file_get_contents(__DIR__ . '/fixtures/sample-fluent-setters.txt');

        $this->assertSame($output, $matchOutput);
    }
}
