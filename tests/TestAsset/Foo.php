<?php

namespace PETest\Component\ClassCompiler\TestAsset;

use PE\Component\ClassCompiler\Exception\RuntimeException;

class Foo implements FooInterface
{
    public function foo()
    {
        throw new RuntimeException();
    }
}