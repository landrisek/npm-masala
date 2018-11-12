<?php

namespace Tests\Masala;

use Masala\Grid,
    Masala\MockFacade,
    Nette\DI\Container,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class GridTest extends TestCase {

    /** @var Grid */
    private $class;

    /** @var Container */
    private $container;

    /** @var MockFacade */
    private $mockFacade;

    function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp(): void {
        $this->class = $this->container->getByType('Masala\Grid');
        $this->mockFacade = $this->container->getByType('Masala\MockFacade');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testAttached(): void {
        Assert::same('Masala\Grid', get_class($this->class), 'Namespace of ' . get_class($this->class) . ' must be exactly Masala as it is used as query parameter in /react/Grid.jsx:getSpice().');
    }

}

id(new GridTest($container))->run();