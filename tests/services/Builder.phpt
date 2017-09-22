<?php

namespace Test;

use Masala\IBuilder,
    Masala\IRow,
    Masala\Masala,
    Masala\MockService,
    Nette\Application\UI\Presenter,
    Nette\DI\Container,
    Nette\Reflection\Method,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class BuilderTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var IBuilder */
    private $class;

    /** @var IRow */
    private $row;

    /** @var Masala */
    private $masala;
    
    /** @var MockService */
    private $mockService;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $this->mockService = $this->container->getByType('Masala\MockService');
        $this->class = $this->mockService->getBuilder();
        $this->row = $this->container->getByType('Masala\IRow');
        $this->masala = $this->container->getByType('Masala\Masala');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
        stream_wrapper_restore('php');
    }

    public function testTable() {
        Assert::same($this->class, $this->class->table('test'), 'Table setter failed.');
    }

    public function testPrepare() {
        $presenters = $this->mockService->getPresenters('IMasalaFactory');
        $this->mockService->setPost(['offset'=>1]);
        foreach ($presenters as $class => $presenter) {
            if(isset($this->container->parameters['mockService']['presenters'][$class])) {
                $testParameters = $this->container->parameters['mockService']['presenters'][$class];
            } else if(isset($this->container->parameters['mockService']['testParameters'])) {
                $testParameters = $this->container->parameters['mockService']['testParameters'];
            } else {
                $testParameters = [];
            }
            Assert::true(is_array($parameters = $presenter->request->getParameters('action')), 'Parameters have not been set in ' . $class . '.');
            Assert::true(isset($parameters['action']), 'Action is not set in ' . $class . '.');
            Assert::notSame(6, strlen($method = 'action' . ucfirst(array_shift($parameters))), 'Action method of ' . $class . ' is not set.');
            Assert::true(is_object($reflection = new Method($class, $method)));
            $arguments = [];
            foreach ($reflection->getParameters() as $parameter) {
                Assert::true(isset($testParameters[$parameter->getName()]), 'There is no test parameters for ' . $parameter->getName() . ' in ' . $class . '.');
                $arguments[$parameter->getName()] = $testParameters[$parameter->getName()];
            }
            Assert::true(method_exists($class, $method), 'According to latte file should exist method ' . $method . ' in ' . $class . '.');
            Assert::same(null, call_user_func_array([$presenter, $method], $arguments), 'Method ' . $method . ' of ' . $class . ' does return something. Do you wish to modify test?');
            Assert::true(is_string($source = $presenter->grid->getTable()), 'Source set in method ' . $method . ' of ' . $class . ' is not set.');
            Assert::false(empty($presenter->grid->getTable()), 'Table is not set.');
            Assert::same($source, $presenter->grid->getTable(), 'Table ' . $source . ' was not set.');
            Assert::false(isset($this->select), 'Select in Builder should be private.');
            Assert::false(isset($this->join), 'Join in Builder should be private.');
            Assert::false(isset($this->leftJoin), 'Left join in Builder should be private.');
            Assert::false(isset($this->innerJoin), 'Inner join in Builder should be private.');
            Assert::same($this->class, $this->class->table($source), 'Builder:table does not return class itself.');
            Assert::true(is_array($columns = $this->row->table($source)->getDrivers($source)), 'Table columns are not defined.');
            Assert::true($presenter instanceof Presenter, 'Presenter is not set.');
            Assert::true(is_object($this->masala->setGrid($this->class)), 'Masala:setGrid failed.');
            Assert::true(is_object($presenter->addComponent($this->masala, 'IMasalaFactory')), 'Masala was not attached to presenter');
            Assert::same(null, $this->masala->attached($presenter), 'Masala:attached method succeed but it does return something. Do you wish modify test?');
            Assert::same(null, $this->class->attached($this->masala), 'Builder:attached method succed but it does return something. Do you wish modify test?');
            Assert::same($this->class->getId('test'), md5($this->masala->getName() . ':' . $presenter->getName() . ':' . $presenter->getAction()  . ':test:' . $presenter->getUser()->getId()), 'Consider using more simple key used for IBuilder:getOffset in corresponding Masala\IService.');
            Assert::false(empty($this->class->prepare()), 'Offset rows for grid were not set.');
            $this->setUp();
        }
        Assert::false(isset($this->class->table), 'Builder table variable should be private.');
        Assert::same($this->class, $this->class->table($presenter->grid->getTable()), 'Builder tables seter does not return class itself.');
        Assert::true(is_object($this->class->table($source)), 'Source setter does not return class itself in Masala');
        foreach ($presenter->grid->getFilters() as $key => $value) {
            Assert::true(is_object($this->class->where($key, $value)), 'Builder:where does not return class itself.');
        }
    }

    public function testGetQuery() {
        Assert::same($this->class, $this->class->table($this->container->parameters['tables']['help']), 'Builder:table does not return class itself.');
        Assert::same($this->class, $this->class->group(['id ASC']), 'Builder:group does not return class itself.');
        Assert::same($this->class, $this->class->limit(10), 'Builder:limit does not return class itself.');
        Assert::same($this->container->parameters['tables']['help'], $this->class->getTable(), 'Assign table for help failed.');
    }

    public function testConfig() {
        Assert::true(is_object($mockRepository = $this->container->getByType('Masala\MockRepository')), 'MockModel is not set.');
        Assert::true(is_object($extension = $this->container->getByType('Masala\MasalaExtension')), 'MasalaExtension is not set.');
        Assert::false(empty($configuration = $extension->getConfiguration($this->container->parameters)), 'Default configuration is not set.');
        Assert::true(isset($this->container->parameters['mockService']['testUser']), 'Test user is not set.');
        Assert::true(isset($this->container->parameters['masala']['users']), 'Table of users is not set.');
        Assert::true(isset($configuration['masala']['settings']), 'Column for setting of user is not set.');
        Assert::false(empty($table = $this->container->parameters['masala']['users']), 'Table of users is not set.');
        Assert::false(empty($column = $configuration['masala']['settings']), 'Column for settings of user is not set.');
        Assert::true(is_object($user = $mockRepository->getTestRow($table, [$column . ' IS NOT NULL'=>true])), 'There is no user with define setting');
        Assert::true(is_object(json_decode($user->$column)) || "[]" == $user->$column, 'Setting of user ' . $user->$column . ' is not valid json.');
    }

}

id(new BuilderTest($container))->run();