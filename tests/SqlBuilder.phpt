<?php

namespace Tests\Masala;

use Masala\SqlBuilder;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class SqlBuilderTest extends TestCase {

    /** @var SqlBuilder */
    private $class;

    /** @var Container */
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function __destruct() {
        echo 'Tests of Masala\SqlBuilder finished.' . "\n";
        stream_wrapper_restore('php');
    }

    protected function setUp(): void {
        $this->class = $this->container->getByType('Masala\SqlBuilder');
    }

   public function testFetch(): void {
       Assert::false(is_object($this->class), 'SqlBuilder is not set.');
   }
}

id(new SqlBuilderTest($container))->run();
