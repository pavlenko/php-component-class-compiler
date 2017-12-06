<?php

namespace PE\Component\ClassCompiler;

use PE\Component\ClassCompiler\Exception\RuntimeException;
use PhpParser\BuilderFactory;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

class ClassCompiler
{
    /**
     * @var PrettyPrinter
     */
    protected $printer;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var NodeTraverser
     */
    protected $traverser;

    /**
     * @var BuilderFactory
     */
    protected $builder;

    /**
     * @var string[]
     */
    protected $classes = [];

    /**
     * @var string[]
     */
    private $declared = [];

    /**
     * @var string[]
     */
    private $files = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->printer   = new PrettyPrinter();
        $this->traverser = new NodeTraverser();
        $this->parser    = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->builder   = new BuilderFactory();

        $this->declared = array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits());
    }

    /**
     * Add list of classes to compile list
     *
     * @param string[] $classes
     */
    public function addClasses(array $classes)
    {
        foreach ($classes as $class) {
            $this->addClass($class);
        }
    }

    /**
     * Add class name to compile list
     *
     * @param string $class
     */
    public function addClass($class)
    {
        $this->classes[] = (string) $class;
    }

    /**
     * @return string[]
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @return \string[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Compile class list with dependencies
     *
     * Do not remove comments from class and methods because it can has type hinting
     *
     * @throws RuntimeException
     *
     * @codeCoverageIgnore Very complex to automatic test
     */
    public function compile()
    {
        $this->files = [];

        $passed = [];
        $names  = array_unique($this->classes);

        // Create namespaces with root namespace for force use brackets {} wrap
        $namespaces = ['' => $this->builder->{'namespace'}(null)->getNode()];

        while ($name = array_shift($names)) {
            if (
                array_key_exists($name, $passed)
                || class_exists($name, false)
                || interface_exists($name, false)
                || trait_exists($name, false)
            ) {
                continue;
            }

            try {
                $file = (new \ReflectionClass($name))->getFileName();
            } catch (\ReflectionException $ex) {
                // Just skip class -> it can be loaded via composer
                continue;
            }

            if (!file_exists($file) || !is_readable($file)) {
                throw new RuntimeException(sprintf('File %s not exists or not readable', $file));
            }

            $this->files[$file] = true;

            /* @var $statements Stmt[] */
            $statements = $this->parser->parse(file_get_contents($file));

            $namespaceName = '';

            $classes = $interfaces = $uses = $functions = [];

            // Parse namespace
            foreach ($statements as $statement) {
                if ($statement instanceof Stmt\Namespace_) {
                    $namespaceName = (string) $statement->name;

                    if (!array_key_exists($namespaceName, $namespaces)) {
                        $namespaces[$namespaceName] = $statement;

                        $statements       = $statement->stmts;
                        $statement->stmts = [];
                    } else {
                        $statements = $statement->stmts;
                    }

                    break;
                }
            }

            /* @var $namespace Stmt\Namespace_ */
            $namespace = $namespaces[$namespaceName];

            // Parse classes
            foreach ($statements as $statement) {
                if ($statement instanceof Stmt\Class_) {
                    $classes[($namespace->name ? $namespace->name . '\\' : '') . $statement->name] = $statement;

                    if ($statement->extends && !array_key_exists((string) $statement->extends, $passed)) {
                        $extends = (string) $statement->extends;

                        if (!($statement->extends instanceof FullyQualified)) {
                            $extends = $namespace->name . '\\' . $extends;
                        }

                        if (!array_key_exists($extends, $passed) && !in_array($extends, $this->declared, true)) {
                            $names[] = $extends;
                        }
                    }

                    if (count($statement->implements)) {
                        foreach ((array) $statement->implements as $implementStmt) {
                            $implement = (string) $implementStmt;

                            if (!($implementStmt instanceof FullyQualified)) {
                                $implement = $namespace->name . '\\' . $implement;
                            }

                            if (
                                !array_key_exists($implement, $passed) &&
                                !in_array($implement, $this->declared, true)
                            ) {
                                $names[] = $implement;
                            }
                        }
                    }
                } else if ($statement instanceof Stmt\Interface_) {
                    $interfaces[$statement->name] = $statement;

                    if (count($statement->extends)) {
                        foreach ((array) $statement->extends as $extendStmt) {
                            $extend = (string) $extendStmt;

                            if (!($extendStmt instanceof FullyQualified)) {
                                $extend = $namespace->name . '\\' . $extend;
                            }

                            if (!array_key_exists($extend, $passed) && !in_array($extend, $this->declared, true)) {
                                $names[] = $extend;
                            }
                        }
                    }
                } else if ($statement instanceof Stmt\Use_ && count($statement->uses) === 1) {
                    $uses[$useName = (string) $statement->uses[0]->name] = $statement;

                    if (!array_key_exists($useName, $passed) && !in_array($useName, $this->declared, true)) {
                        $names[] = $useName;
                    }
                } else if ($statement instanceof Stmt\Function_ || $statement instanceof Stmt\If_) {
                    $functions[] = $statement;
                }
            }

            $namespace->stmts = array_merge($namespace->stmts, $uses, $interfaces, $classes, $functions);
            $passed[$name]    = true;
        }

        $this->files = array_keys($this->files);

        return "<?php\n" . $this->printer->prettyPrint($namespaces);
    }
}