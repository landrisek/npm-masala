<?php

namespace Test;

use Masala\ImportForm,
    Masala\Masala,
    Masala\MockService,
    Masala\RowBuilder,
    Masala\HelpModel,
    Masala\MockModel,
    Nette\Application\UI\Presenter,
    Nette\Caching\Storages\FileStorage,
    Nette\Database\Connection,
    Nette\Database\Context,
    Nette\Database\Structure,
    Nette\DI\Container,
    Nette\Http\Request,
    Nette\Http\UrlScript,
    Nette\Reflection\Method,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../../bootstrap.php';

class NetteBuilderTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var NetteBuilder */
    private $class;

    /** @var MockModel */
    private $mockModel;

    /** @var MockService */
    private $mockService;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        /** database */
        $connection = new Connection('mysql:host=localhost;dbname=cz_4camping', 'worker', 'dokempu');
        $cacheStorage = new FileStorage(__DIR__ . '/../../../../temp');
        $structure = new Structure($connection, $cacheStorage);
        $context = new Context($connection, $structure, null, $cacheStorage);
        $parameters = $this->container->getParameters();
        $tables = $parameters['tables'];
        /** models */
        $translatorModel = $this->container->getByType('Nette\Localization\ITranslator');
        $this->mockModel = $this->container->getByType('Masala\MockModel');
        $this->mockService = new MockService($this->container, $translatorModel);
        $this->class = $this->mockService->getBuilder();
        $setting = new RowBuilder($parameters['masala'], $context, $cacheStorage);
        $helpModel = new HelpModel($parameters['masala']['help'], $context, $cacheStorage);
        $import = new ImportForm($translatorModel);
        $urlScript = new UrlScript;
        $httpRequest = new Request($urlScript);
        $filter = $this->container->getByType('Masala\IFilterFormFactory');
        $edit = $this->container->getByType('Masala\IEditFormFactory');
        $row = $this->container->getByType('Masala\IRowBuilder');
        $this->masala = new Masala($parameters['masala'], $helpModel, $translatorModel, $edit, $filter, $import, $httpRequest, $row);
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testTable() {
        Assert::same($this->class, $this->class->table('test'), 'Table setter failed.');
    }

    public function testFilter() {
        $presenters = $this->mockService->getPresenters('IMasalaFactory');
        $testParameters = ['feed' => 'laurasport',
            'id' => 126,
            'date' => date('Y-m-d', strtotime('now')),
            'limit' => 10,
            'type' => 'inventure',
            'status' => 'translated',
            'producerId' => 126];
        foreach ($presenters as $class => $presenter) {
            Assert::true(is_array($parameters = $presenter->request->getParameters('action')), 'Parameters have not been set in ' . $class . '.');
            Assert::true(isset($parameters['action']), 'Action is not set in ' . $class . '.');
            echo 'testing ' . $class . ':' . $parameters['action'];
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
            /** @todo: mock post parameters
             * Assert::same(1, $presenter->grid->filter()->getOffsets(), 'Offset rows for grid were not set.');
             * Assert::same(null, $presenter->grid->build([], null, $this->masala->getName()), 'Source ' . $source . ' in ' . $class . ':' . $method . ' for Masala failed.'); */
            Assert::false(isset($this->select), 'Select in NetteBuilder should be private.');
            Assert::false(isset($this->join), 'Join in NetteBuilder should be private.');
            Assert::false(isset($this->leftJoin), 'Left join in NetteBuilder should be private.');
            Assert::false(isset($this->innerJoin), 'Inner join in NetteBuilder should be private.');
            Assert::same($this->class, $this->class->table($source), 'NetteBuilder:table does not return class itself.');
            Assert::true(is_array($columns = $this->mockModel->getColumns($source)), 'Table columns are not defined.');
            Assert::true($presenter instanceof Presenter, 'Presenter is not set.');
            Assert::true(is_object($this->masala->setGrid($this->class)), 'Masala:setGrid failed.');
            Assert::true(is_object($presenter->addComponent($this->masala, 'IMasalaFactory')), 'Masala was not attached to presenter');
            Assert::same(null, $this->masala->attached($presenter), 'Masala:attached method succeed but it does return something. Do you wish modify test?');
            Assert::same(null, $this->class->attached($this->masala), 'NetteBuilder:attached method succed but it does return something. Do you wish modify test?');
            Assert::same($this->class->getId('test'), $this->masala->getName() . ':' .$presenter->getName() . ':' . $presenter->getAction() . ':test:', 'Consider using more simple key used for IBuilder:getOffset in corresponding Masala\IService.');
            $this->setUp();
        }
        Assert::false(isset($this->class->table), 'NetteBuilder table variable should be private.');
        Assert::same($this->class, $this->class->table($presenter->grid->getTable()), 'NetteBuilder tables seter does not return class itself.');
        Assert::true(is_object($this->class->table($source)), 'Source setter does not return class itself in Masala');
        foreach ($presenter->grid->getFilters() as $key => $value) {
            Assert::true(is_object($this->class->where($key, $value)), 'NetteBuilder:where does not return class itself.');
        }
        Assert::same($this->class, $this->class->table($this->class->getTable()), 'Assign table for ' . get_class($this->class) . 'failed.');
        Assert::same($this->class, $this->class->group('id ASC'), 'NetteBuilder:group does not return class itself.');
        Assert::same($this->class, $this->class->limit(10), 'NetteBuilder:limit does not return class itself.');
        Assert::true(isset($this->container->parameters['masala']['help']), 'Source table for help is not set.');
    }

    public function testConfig() {
        Assert::true(is_object($mockModel = $this->container->getByType('Masala\MockModel')), 'MockModel is not set.');
        Assert::true(isset($this->container->parameters['tables']['users']), 'Table of users is not set.');
        Assert::true(isset($this->container->parameters['masala']['user']), 'Column for setting of user is not set.');
        Assert::false(empty($table = $this->container->parameters['tables']['users']), 'Column for setting of user is not set.');
        Assert::false(empty($column = $this->container->parameters['masala']['user']), 'Column for setting of user is not set.');
        Assert::true(is_object($user = $mockModel->getTestRow($table, [$column . ' IS NOT NULL'=>true])), 'There is no user with define setting');
        Assert::true(is_object(json_decode($user->$column)), 'Setting of user ' . $user->$column . ' is not valid json.');
    }

}

id(new NetteBuilderTest($container))->run();
