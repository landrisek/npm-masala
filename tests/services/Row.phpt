<?php

namespace Test;

use Masala\MockModel,
    Masala\IRow,
    Nette\Database\Table\ActiveRow,
    Nette\DI\Container,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class RowTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var IRow */
    private $class;

    /** @var MockModel */
    private $mockModel;

    /** @var array */
    private $primary;
    
    /** @var ActiveRow */
    private $row;
    
    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        Assert::true(is_object($this->class = $this->container->getByType('Masala\IRow')), 'IRow is not set.');
        Assert::true(is_object($this->mockModel = $this->container->getByType('Masala\MockModel')), 'MockModel is not set.');
        Assert::true(is_object($grid = $this->container->getByType('Masala\IBuilder')), 'IBuilder is not set.');
        Assert::true(is_object($extension = $this->container->getByType('Masala\BuilderExtension')), 'BuilderExtension is not set');
        Assert::false(empty($table = $this->container->parameters['masala']['users']), 'Table of users in config is not set.');
        Assert::false(empty($credentials = $this->container->parameters['mockService']['testUser']), 'Table of users in config is not set.');
        unset($credentials['password']);
        unset($credentials['username']);
        Assert::same($this->class, $this->class->table($table), 'Table setter failed.');
        foreach($credentials as $column => $value) {
            Assert::true(is_object($this->class->where($column, $value)), 'IRow:where does not return class itself.');
        }
        Assert::false(empty($this->primary = $grid->table($table)->getPrimary()), 'Primary is not set.');
        Assert::true(is_object($this->row = $this->class->check()), 'Test row is not set for source ' . $table . '.');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testSubmit() {
        $after = [];
        $before = [];
        Assert::false(empty($row = $this->class->getData()), 'Test row is empty.');
        foreach($row as $column => $value) {
            if(!isset($this->primary[$this->class->getTable() . '.' .$column])) {
                Assert::false(empty($before[$column] = 'test'), 'Assign value failed.');
                Assert::false(empty($after[$column] = $value), 'Assign value failed.');
                break;
            }
        }
        foreach($this->primary as $column) {
            $clauses[$column] = $this->class->$column;
            if(is_numeric($this->class->$column)) {
                echo $this->class->$column;
                $after['primary'][$column] = '_' . $this->class->$column;
                $before['primary'][$column] = '_' . $this->class->$column;
            } else {
                $after['primary'][$column] = $this->class->$column;
                $before['primary'][$column] = $this->class->$column;
            }
        }
        Assert::same(false, $this->mockModel->getTestRow($this->class->getTable(), $before['primary']), 'Concated data keys should not exist in test table.');
        Assert::true(is_object($this->mockModel->getTestRow($this->class->getTable(), $clauses)), 'Concated data keys should not exist in test table.');
        Assert::true(isset($after['primary']), 'Primary keys are not set.');
        Assert::true(isset($before['primary']), 'Primary keys are not set.');
        Assert::same(1, $this->class->update($before), 'Test update failed. Possible concating of keys for javascript in ReactForm is corrupted');
        Assert::same(1, $this->class->update($after), 'Test update back failed.');
    }

}

id(new RowTest($container))->run();
