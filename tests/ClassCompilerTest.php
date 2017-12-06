<?php

namespace PETest\Component\ClassCompiler;

use PE\Component\ClassCompiler\ClassCompiler;
use PETest\Component\ClassCompiler\TestAsset\BarInterface;
use PETest\Component\ClassCompiler\TestAsset\FooInterface;

class ClassCompilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassCompiler
     */
    protected $compiler;

    protected function setUp()
    {
        $this->compiler = new ClassCompiler();
    }

    public function testClasses()
    {
        $this->compiler->addClass(BarInterface::class);
        static::assertTrue(in_array(BarInterface::class, $this->compiler->getClasses(), true));

        $this->compiler->addClasses([FooInterface::class]);
        static::assertTrue(in_array(FooInterface::class, $this->compiler->getClasses(), true));
    }
}
