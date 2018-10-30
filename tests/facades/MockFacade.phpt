<?php

namespace Tests\Masala;

use Masala\MockFacade,
    Nette\DI\Container,
    Nette\Security\User,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class MockFacadeTest extends TestCase {

    /** @var MockFacade */
    private $class;

    /** @var Container */
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $this->class = $this->container->getByType('Masala\MockFacade');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testGetUser(): void {
        Assert::true($this->class->getUser() instanceof User, 'User is not set.');
    }
}

id(new MockFacadeTest($container))->run();