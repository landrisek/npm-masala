<?php

namespace Tests\Masala;

use Masala\IBuilderFactory,
    Masala\SqlBuilder,
    Nette\DI\Container,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class SqlBuilderTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var IBuilderFactory */
    private $class;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp(): void {
        $this->class = new SqBuilder($this->container->getByType('Nette\Database\Context'), $this->container->getByType('Nette\Localization\ITranslator'));
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
        stream_wrapper_restore('php');
    }

    public function testFetch(): void {
        Assert::true(is_object($this->class), 'SqlBuilder is not set.');
    }
}

id(new SqlBuilderTest($container))->run();