<?php

namespace Test;

use Masala\Grid,
    Masala\IRow,
    Masala\MockService,
    Nette\DI\Container,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class GridTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var Grid */
    private $class;

    /** EditForm */
    private $editForm;

    /** @var IRow */
    private $row;

    /** @var MockService */
    private $mockService;

    function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $this->editForm = $this->container->getByType('Masala\EditForm');
        $this->class = $this->container->getByType('Masala\Grid');
        $this->row = $this->container->getByType('Masala\IRow');
        $this->mockService = $this->container->getByType('Masala\MockService');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testSetRow() {
        $this->setUp();
        $mockModel = $this->container->getByType('Masala\MockModel');
        Assert::false(empty($key = array_rand($this->container->parameters['tables'])), 'Test source is not set.');
        if(is_object($mockModel->getTestRow($this->container->parameters['tables'][$key]))) {
            Assert::true(is_object($this->row->table($this->container->parameters['tables'][$key])->check()), 
                    'IRow:check failed on source ' . $this->container->parameters['tables'][$key]);            
        }
        Assert::notSame(false, $this->row, 'There is no VO for testing EditForm.');
        Assert::true($this->row instanceof IRow, 'There is no VO for testing EditForm.');
        Assert::same($this->editForm, $this->editForm->setRow($this->row), 'Setter does not return ' . get_class($this->editForm));
        Assert::true(is_object($this->class->addComponent($this->editForm, 'editForm')), 'Add editForm to grid failed.');
    }


    public function testAttached() {
        Assert::same('Masala\Grid', get_class($this->class), 'Namespace of ' . get_class($this->class) . ' must be exactly Masala as it is used as query parameter in /react/Grid.jsx:getSpice().');
    }

}

id(new GridTest($container))->run();
