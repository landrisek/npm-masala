<?php

namespace Test;

use Masala\ImportForm,
    Masala\Masala,
    Masala\MockService,
    Masala\RowBuilder,
    Models\HelpModel,
    Models\TranslatorModel,
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

    /** @var HelpModel */
    private $helpModel;

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
        $translatorModel = new TranslatorModel($this->container->parameters['tables']['translator'], $context, $cacheStorage);
        $this->mockService = new MockService($this->container, $translatorModel);
        $this->class = $this->mockService->getBuilder();
        $setting = new RowBuilder($parameters['masala'], $context, $cacheStorage);
        $this->helpModel = new HelpModel($tables['help'], $this->class, $setting, $context, $cacheStorage);
        $importForm = new ImportForm($translatorModel);
        $urlScript = new UrlScript;
        $httpRequest = new Request($urlScript);
        $form = $this->container->getByType('Masala\IFilterFormFactory');
        $this->masala = new Masala($parameters['masala'], $translatorModel, $importForm, $form, $httpRequest);
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
            'id' => 1,
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
            Assert::true(is_array($columns = $this->helpModel->getColumns($source)), 'Table columns are not defined.');
            Assert::true($presenter instanceof Presenter, 'Presenter is not set.');
            Assert::true(is_object($this->masala->setGrid($this->class)), 'Masala:setGrid failed.');
            Assert::true(is_object($presenter->addComponent($this->masala, 'IMasalaFactory')), 'Masala was not attached to presenter');
            Assert::same(null, $this->masala->attached($presenter), 'Masala:attached method succeed but it does return something. Do you wish modify test?');
            Assert::same(null, $this->class->attached($this->masala), 'NetteBuilder:attached method succed but it does return something. Do you wish modify test?');
            Assert::same($this->class->getId('test'), $presenter->getName() . ':' . $presenter->getAction() . ':test:', 'Consider using more simple key used for IBuilder:getOffset in corresponding Masala\IService.');
            $this->setUp();
        }
        Assert::false(isset($this->class->table), 'NetteBuilder table variable should be private.');
        Assert::same($this->class, $this->class->table($presenter->grid->getTable()), 'NetteBuilder tables seter does not return class itself.');
        Assert::true(is_object($this->class->table($source)), 'Source setter does not return class itself in Masala');
        foreach ($presenter->grid->getFilters() as $key => $value) {
            Assert::true(is_object($this->class->where($key, $value)), 'NetteBuilder:where does not return class itself.');
        }
    }

    public function testGetQuery() {
        Assert::same($this->class, $this->class->table($this->helpModel->getSource()), 'NetteBuilder:table does not return class itself.');
        Assert::same($this->class, $this->class->group('id ASC'), 'NetteBuilder:group does not return class itself.');
        Assert::same($this->class, $this->class->limit(10), 'NetteBuilder:limit does not return class itself.');
        Assert::same($this->helpModel->getSource(), $this->class->getTable(), 'Assign table for help failed.');
    }

}

id(new NetteBuilderTest($container))->run();
