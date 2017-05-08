<?php

namespace Test;

use Masala\FilterForm,
    Masala\Masala,
    Masala\MockService,
    Nette\DI\Container,
    Nette\Forms,
    Nette\Reflection\Method,
    Nette\Utils\ArrayHash,
    Tester\Assert,
    Tester\TestCase;
    

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class FilterFormTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var MockService */
    private $mockService;

    /** @var FilterForm */
    private $class;

    /** @var array */
    private $presenters;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $this->mockService = $this->container->getByType('Masala\MockService');
        $this->class = $this->container->getByType('Masala\IFilterFormFactory');
        $this->presenters = ['App\DemoPresenter' => APP_DIR . '/Masala/demo/default.latte'];
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testAttached() {
        Assert::true(is_array($this->presenters), 'No presenter to test on import was set.');
        Assert::false(empty($this->presenters), 'There is no feed for testing.');
        Assert::true(100 > count($this->presenters), 'There is more than 100 available feeds for testing which could process long time. Consider modify test.');
        $builder = $this->container->getByType('Masala\IRowBuilder');
        foreach($this->container->parameters['tables'] as $table) {
            $builder->table($table);
            foreach($builder->getDrivers() as $column) {
                if('DATETIME' == $column['nativetype']) {
                    Assert::false(empty($date = $table . '.' . $column['name']), 'Datetime column is not set.');
                    break;
                }
            }
        }
        Assert::true(isset($date), 'No datetime column to test for range');
        foreach ($this->presenters as $class => $latte) {
            echo $class . "\n";
            $presenter = $this->mockService->getPresenter($class, $latte);
            Assert::true(is_object($presenter), 'Presenter was not set.');
            Assert::true(is_object($presenter->grid = $this->container->getByType('Masala\IBuilder')), 'Presenter grid was not set.');
            Assert::true(is_object($presenter->grid->table($table)
                                            ->where($date . ' >', date('Y-m-d', strtotime('-3 month')), date('Y-m-d', strtotime('-6 month')))
                                            ->where($date . ' <',date('Y-m-d', strtotime('now')), date('Y-m-d', strtotime('now')))
                                            ), 'Table of grid was not set.');
            Assert::true(is_object($masala = $this->container->getByType('Masala\Masala')), 'Masala is not set.');
            Assert::true(is_object($masala->setGrid($presenter->grid)), 'Masala:setGrid does not return class itself.');
            Assert::true(is_object($presenter->addComponent($masala, 'masala')), 'Attached Masala failed.');
            Assert::true(is_object($masala = $presenter->getComponent('masala')), 'Masala is not set');
            Assert::same($this->class, $this->class->setGrid($presenter->grid), 'Masala\FilterForm:setGrid does not return class itself.');
            Assert::true($masala instanceof Masala, 'Masala has wrong instation.');
            Assert::true(is_array($range = $presenter->grid->getRange($date)), 'Range is not set');
            Assert::false(empty($presenter->grid->getColumns()), 'Columns are not set in ' . $class . '.');
            Assert::true(is_object($masala['filterForm']), 'Grid filter is not set in ' . $class . '.');
            Assert::true($masala['filterForm']['filter'] instanceof Forms\Container, 'Form filter has wrong instation.');
            Assert::true(isset($range['>']), 'From in range parameter is not set.');
            Assert::true(isset($range['<']), 'To in range parameter is not set.');
            Assert::true(isset($range['min']), 'Min in range parameter is not set.');
            Assert::true(isset($range['max']), 'Max in range parameter is not set.');
            Assert::true(is_array($parameters = $presenter->request->getParameters('action')), 'Parameters have not been set in ' . $class . '.');
            Assert::notSame(6, strlen($method = 'action' . ucfirst(array_shift($parameters))), 'Action method of ' . $class . ' is not set.');
            Assert::true(is_object($reflection = new Method($class, $method)));
            $arguments = [];
            foreach ($reflection->getParameters() as $parameter) {
                Assert::true(isset($testParameters[$parameter->getName()]), 'There is no test parameters for ' . $parameter->getName() . ' in ' . $class . '.');
                $arguments[$parameter->getName()] = $testParameters[$parameter->getName()];
            }
            Assert::true(is_object($presenter), 'Presenter is not class.');
            Assert::true(in_array('addComponent', get_class_methods($presenter)), 'Presenter has no method addComponent.');
            Assert::true(is_object($this->class), 'Form was not set.');
            Assert::true($this->class instanceof FilterForm, 'Form has wrong instantion.');
            Assert::true(is_object($this->class->getValues()), 'Form values are not set.');
            Assert::true($this->class->getValues() instanceof ArrayHash, 'Form values are not set.');
            Assert::true(property_exists($this->class, 'translatorModel'), 'Translator model was not set');
            Assert::true(is_string($key = 'range'), 'Key is not set.');
            Assert::true(is_object($component = $masala['filterForm']['filter']));
            $masala->attached($presenter);
            Assert::false(empty($methods = get_class_methods($masala['filterForm'])), 'Masala\Form does not have any method.');
            Assert::false(isset($methods['succeeded']) or isset($methods['formSucceeded']), 'Masala\FiltwerForm:succeed is redundant as submit is provided by javascript.');
            $presenter->removeComponent($masala);
            $this->setUp();
        }
    }

}

id(new FilterFormTest($container))->run();
