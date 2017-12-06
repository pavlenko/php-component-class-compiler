# CMF Class compiler component
### Installation
```sh
php composer.phar require cmf/component-class-compiler:1.0.0
```
### Usage
```php
use CMF\Component\ClassCompiler\ClassCache;
use CMF\Component\ClassCompiler\ClassCompiler;

// Instantiate class compiler
$classCompiler = new ClassCompiler();

// Add classes to compile (include dependencies)
$classCompiler->addClasses([
    'Foo',
    'Bar',
]);

// Compiles classes to string which can be saved as php file
$classCompiler->compile();
```