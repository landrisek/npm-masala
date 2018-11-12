<?php

namespace Tests\Masala;

use Masala\IBuilder,
    Masala\Masala,
    Masala\MockFacade,
    Nette\Application\UI\Presenter,
    Nette\DI\Container,
    Nette\Reflection\Method,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class BuilderTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var IBuilder */
    private $class;

    /** @var Masala */
    private $masala;
    
    /** @var MockFacade */
    private $mockFacade;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    /** @return void */
    protected function setUp() {
        $this->mockFacade = $this->container->getByType('Masala\MockFacade');
        $this->class = $this->mockFacade->getBuilder();
        $this->masala = $this->container->getByType('Masala\Masala');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
        stream_wrapper_restore('php');
    }

    public function testConfig(): void {
        Assert::true(is_object($mockRepository = $this->container->getByType('Masala\MockRepository')), 'MockRepository is not set.');
        Assert::true(is_object($extension = $this->container->getByType('Masala\MasalaExtension')), 'MasalaExtension is not set.');
        Assert::false(empty($configuration = $extension->getConfiguration($this->container->parameters)), 'Default configuration is not set.');
        Assert::true(isset($this->container->parameters['masala']['tests']['user']), 'Test user is not set.');
        Assert::true(isset($this->container->parameters['masala']['users']), 'Table of users is not set.');
        Assert::true(isset($configuration['masala']['settings']), 'Column for setting of user is not set.');
        Assert::false(empty($table = $this->container->parameters['masala']['users']), 'Table of users is not set.');
        Assert::false(empty($column = $configuration['masala']['settings']), 'Column for settings of user is not set.');
        Assert::true(is_object($user = $mockRepository->getTestRow($table, [$column . ' IS NOT NULL'=>true])), 'There is no user with define setting');
        Assert::true(is_object(json_decode($user->$column)) || "[]" == $user->$column, 'Setting of user ' . $user->$column . ' is not valid json.');
    }

    public function testGetQuery(): void {
        Assert::same($this->class, $this->class->table($this->container->parameters['tables']['help']), 'Builder:table does not return class itself.');
        Assert::same($this->class, $this->class->group(['id ASC']), 'Builder:group does not return class itself.');
        Assert::same($this->class, $this->class->limit(10), 'Builder:limit does not return class itself.');
        Assert::same($this->container->parameters['tables']['help'], $this->class->getTable(), 'Assign table for help failed.');
    }

    public function testPrepare(): void {
        $presenters = $this->mockFacade->getPresentersByComponent('IMasalaFactory');
        $this->mockFacade->setPost(['Offset'=>1]);
        foreach ($presenters as $class => $presenter) {
            if(isset($this->container->parameters['mockFacade']['presenters'][$class])) {
                $testParameters = $this->container->parameters['mockFacade']['presenters'][$class];
            } else if(isset($this->container->parameters['mockFacade']['testParameters'])) {
                $testParameters = $this->container->parameters['mockFacade']['testParameters'];
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
            Assert::true(is_object($presenter->grid = $this->class->table($source)), 'IBuilder in presenter is not set.');
            Assert::true(is_object($presenter->row = $this->class->copy()), 'IBuilder in presenter is not set.');
            Assert::true(is_array($columns = $this->class->table($source)->getDrivers($source)), 'Table columns are not defined.');
            Assert::true($presenter instanceof Presenter, 'Presenter is not set.');
            Assert::true(is_object($this->masala->setGrid($this->class)), 'Masala:setGrid failed.');
            Assert::true(is_object($this->masala->setRow($this->class->copy())), 'Masala:setRow failed.');
            Assert::true(is_object($presenter->addComponent($this->masala, 'IMasalaFactory')), 'Masala was not attached to presenter');
            Assert::same(null, $this->masala->attached($presenter), 'Masala:attached method succeed but it does return something. Do you wish modify test?');
            Assert::same(null, $this->class->attached($this->masala), 'Builder:attached method succed but it does return something. Do you wish modify test?');
            Assert::same($this->class->getId('test'), md5($this->masala->getName() . ':' . $presenter->getName() . ':' . $presenter->getAction()  . ':test:' . $presenter->getUser()->getId()), 'Consider using more simple key used for IBuilder:getOffset in corresponding Masala\IProcess.');
            Assert::false(empty($this->class->prepare()), 'Offset rows for grid were not set.');
            Assert::false(empty($rows = $this->class->getOffsets()), 'Test row is empty in ' . $class . '.');
            Assert::false(empty($row = reset($rows)), 'Test row is not set.');
            $testRow = [];
            foreach($columns as $column) {
                $testRow[$column['name']] = 'test';
            }
            Assert::same(null, $this->mockFacade->setPost(['Row'=>$testRow]), 'MockFacade::setPost does return something.');
            Assert::false(empty($row = $this->class->getRow()), 'IRowFormFactory::getRow return empty array.');
            Assert::same(reset($row), 'test');
            $testRow = [];
            foreach($columns as $column) {
                $testRow[$column['name']] = '_test';
            }
            Assert::same(null, $this->mockFacade->setPost(['Row'=>$testRow]), 'MockFacade::setPost does return something.');
            Assert::false(empty($row = $this->class->getRow()), 'IRowFormFactory::getRow return empty array.');
            Assert::notSame(reset($row), '_test', 'Data was not deconcated.');
            Assert::false(empty($row = $this->class->row(1, ['test'=>'_test'])->getData()), 'IRowFormFactory::getData return empty array.');
            $this->setUp();
        }
        Assert::false(isset($this->class->table), 'Builder table variable should be private.');
        Assert::same($this->class, $this->class->table($presenter->grid->getTable()), 'Builder tables seter does not return class itself.');
        Assert::true(is_object($this->class->table($source)), 'Source setter does not return class itself in Masala');
        foreach ($presenter->grid->getFilters() as $key => $value) {
            Assert::true(is_object($this->class->where($key, $value)), 'Builder:where does not return class itself.');
        }
    }

    public function testRow(): void {
        Assert::false(empty($tables = $this->container->parameters['tables']), 'Test tables are not set.');
        Assert::true(shuffle($tables), 'Test table is not set.');
        Assert::false(empty($table = reset($tables)), 'Test table is not set.');
        Assert::false(empty($drivers = $this->class->getDrivers($table)), 'Drivers are not set for table ' . $table);
        foreach($drivers as $driver) {
            if(true == $driver['primary']) {
                Assert::same(0, preg_match('/\@unedit/', $driver['vendor']['Comment']), 'Primary keys should be not unedit.');
            }
        }
    }

    public function testTable(): void {
        Assert::same($this->class, $this->class->table('test'), 'Table setter failed.');
    }

}

id(new BuilderTest($container))->run();