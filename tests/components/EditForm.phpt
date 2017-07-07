<?php

namespace Test;

use Masala\EditForm,
    Masala\MockModel,
    Masala\MockService,
    Masala\IRow,
    Nette\Database\Row,
    Nette\Database\Table\ActiveRow,
    Nette\DI\Container,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class EditFormTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var array */
    private $tables;

    /** @var EditForm */
    private $class;

    /** @var MockModel */
    private $mockModel;

    /** @var MockService */
    private $mockService;

    /** @var IRow */
    private $row;

    function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $this->mockModel = $this->container->getByType('Masala\MockModel');
        $this->mockService = $this->container->getByType('Masala\MockService');
        $this->class = $this->container->getByType('Masala\EditForm');
        $this->row = $this->container->getByType('Masala\IRow');
        $this->tables = $this->mockModel->getTestTables();
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    private function getRandomTable() {
        $this->setUp();
        Assert::false(empty($tables = $this->container->parameters['tables']), 'Test tables are not set.');
        Assert::false(empty($excluded = $this->container->parameters['mockService']['excluded']), 'No tables to excluded. Do you wish to modify test?');
        foreach($excluded as $table => $exclude) {
            unset($tables[$table]);
        }
        Assert::false(empty($key = array_rand($tables)), 'Test source is not set.');
        Assert::false(empty($source = $this->container->parameters['tables'][$key]), 'Test table is not set.');
        echo $source;
        return $source;
    }
    
    public function testSetRow() {
        Assert::false(empty($source = $this->getRandomTable()), 'Test table is not set.');
        if(is_object($this->mockModel->getTestRow($source))) {
            Assert::true(is_object($this->row->table($source)->check()), 'IRow:check failed on source ' . $source);            
        }
        Assert::notSame(false, $this->row, 'There is no VO for testing EditForm.');
        Assert::true($this->row instanceof IRow, 'There is no VO for testing EditForm.');
        Assert::same($this->class, $this->class->setRow($this->row), 'Setter does not return class.');
    }

    public function testIsSignalled() {
        Assert::false(empty($source = $this->getRandomTable()), 'Test table is not set.');
        Assert::false(empty($setting = $this->mockModel->getTestRow($source)), 'There is no test row for source ' . $source);
        $presenter = $this->mockService->getPresenter('App\DemoPresenter', $this->container->parameters['appDir'] . '/Masala/demo/default.latte', ['id' => $setting->id]);
        Assert::true(is_object($presenter), 'Presenter was not set.');
        Assert::true(is_bool($this->class->isSignalled()), 'Signalled method should return boolean value.');
    }

    public function testAttached() {
        $this->testSetRow();
        Assert::true(is_object($grid = $this->container->getByType('Masala\IBuilder')));
        Assert::false(empty($primary = $this->mockModel->getPrimary($this->row->getTable())), 'Primary is not set');
        if(is_array($primary)) {
            Assert::false(empty($keys = array_keys($primary)), 'Primary keys are not set');
            Assert::false(empty($key = reset($keys)), 'Primary key is not set');            
        } else {
            Assert::false(empty($key = $primary), 'Primary key for ' . $this->row->getTable() . ' is not set.');
        }
        Assert::true(isset($this->row->$key), 'Primary key missing for source '. $this->row->getTable());            
        $presenter = $this->mockService->getPresenter('App\DemoPresenter', $this->container->parameters['appDir'] . '/Masala/demo/default.latte', [$key => $this->row->$key]);
        Assert::true(is_object($presenter), 'Presenter was not set.');
        $presenter->addComponent($this->class, 'EditForm');
        Assert::true(is_array($serialize = (array) $this->class), 'Serialization of ' . $this->class->getName() . ' failed.');
        Assert::true(is_array($variables = array_slice($serialize, 3, 1)), 'Extract IRow for testing failed');
        Assert::true(is_object($row = reset($variables)), 'IRow is not set as third position variable.');
        Assert::true($row instanceof IRow, 'IRow is not set.');
        Assert::false(empty($attached = $row->getColumns()), 'Columns for EditForm are not set.');
        Assert::false(empty($columns = $this->row->getColumns()), 'Injected IRow has no data.');
        Assert::same($columns, $attached, 'DI attached different data.');
        $required = false;
        $primary = '';
        foreach ($columns as $column) {
            if(false == $column['nullable'] and 
               'PRI' != $column['vendor']['Key'] and 
               0 === substr_count($column['vendor']['Comment'], '@unedit') and
               null === $column['default']) {
                $required = $column['name'];
            } else if('PRI' == $column['vendor']['Key']) {
                $primary .= $column['name'] . ', ';
            }
            $notEdit = (0 < substr_count($column['vendor']['Comment'], '@unedit')) ? $column['name'] : 'THISNAMECOMPONENTSHOULDNEVERBEUSED';
        }
        Assert::false(empty($data = $this->class->getData()), 'No data was attached.');
        Assert::false(isset($this->class[$notEdit]), 'Component ' . $notEdit . 'has been render even if it has annotation @unedit');
        Assert::false(is_bool($required) && empty($primary), 'Table ' . $this->row->getTable() . ' has all columns with default null. Are you sure it is not dangerous?');
        Assert::notSame(true, isset($data[$notEdit]));
        if (is_string($required)) {
            if(!isset($data[$required]['Attributes']['required'])) {
                dump($data[$required]['Attributes']);
            }
            Assert::true(isset($data[$required]['Attributes']['required']), 'Component ' . $required . ' from table ' . $this->row->getTable() . ' should be required as it is not nullable column.');
        }
    }

    public function testColumnComments() {
        $this->setUp();
        $tables = [];
        $excluded = (isset($this->container->parameters['mockService']['excluded'])) ? $this->container->parameters['mockService']['excluded'] : [];
        shuffle($this->tables);
        if(isset($this->container->parameters['mockService']['prefix'])) {
            Assert::false(empty($structure = 'Tables_in_' . preg_replace('/.*dbname\=/', '', $this->container->parameters['database']['dsn'])), 'Structure of DB is not set.');
            foreach ($this->tables as $table) {
                if ($this->container->parameters['mockService']['prefix'] != substr($table->$structure, 0, 7) and ! in_array($table->$structure, $excluded)) {
                    $tables[] = $table->$structure;
                }
            }
        }
        foreach ($tables as $name) {
            Assert::false(empty($name));
            Assert::true(is_integer($index = rand(0, count($this->tables) - 1)), 'Index for random table is not set.');
            Assert::true(($table = $this->tables[$index]) instanceof Row, 'Table to test column comments is not set.');
            Assert::true(is_string($name), 'Table name is not defined.');
            Assert::true(is_array($columns = $this->row->getColumns($name)), 'Table columns are not defined.');
        }
        foreach ($this->container->parameters['masala'] as $key => $compulsory) {
            if(preg_match('/[A-Za-z]+\.[A-Za-z]+/', $key)) {
                foreach($this->container->parameters['masala'][$key] as $call) {
                    $parameters = (isset($call['parameters'])) ? $call['parameters'] : null; 
                    $defaults = $this->mockService->getCall($call['service'], $call['method'], $parameters, $this->class);
                    Assert::true(is_array($defaults), 'Call ' . $key . ' from masala config failed.');
                    $name = preg_replace('/\.(.*)/', '', $key);
                    $setting = $this->mockModel->getTestRow($name);
                    $this->row->table($name);
                    Assert::true($setting instanceof ActiveRow or in_array($name, $excluded), 'There is no row in ' . $name . '. Are you sure it is not useless?');
                    if ($setting instanceof ActiveRow) {
                        Assert::true(is_array($columns = $this->row->getColumns($name)), 'Table columns are not defined.');
                        $this->class->setRow($this->row);
                        $data = $this->class->getData();
                        foreach ($columns as $column) {
                            if (0 === substr_count($column['vendor']['Comment'], '@unedit') and 'PRI' !== $column['vendor']['Key']) {
                                Assert::true(isset($data[$column['name']]), 'Column ' . $column['name'] . ' in table ' . $name . ' was not draw as component in Masala\EditForm.');
                            } else {
                                Assert::false(isset($data[$column['name']]), 'Column ' . $column['name'] . ' in table ' . $name . ' was draw as component in Masala\EditForm even it should not.');
                            }
                        }
                        $this->setUp();
                    }
                }
            }
        }
    }


}

id(new EditFormTest($container))->run();
