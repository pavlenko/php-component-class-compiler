<?php

namespace PETest\Component\ClassCompiler\TestAsset;

use PE\Component\ClassCompiler\Exception\RuntimeException;

class Bar extends Baz
{
    /**
     * @throws RuntimeException
     */
    public function foo()
    {
        throw new RuntimeException();
    }
}