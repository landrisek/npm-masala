<?php

namespace Test;

use Masala\IGridFactory;
use Masala\Masala,
    Masala\MockService,
    Nette\DI\Container,
    Nette\Reflection\Method,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../bootstrap.php';

/** @author Lubomir Andrisek */
final class MasalaTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var Masala */
    private $class;

    /** @var MockService */
    private $mockService;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $this->mockService = $this->container->getByType('Masala\MockService');
        $this->class = $this->container->getByType('Masala\Masala');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testAttached() {
        Assert::true(file_exists($path = $this->container->parameters['appDir'] . '/Masala/'), 'Masala folder does not exist in default folder. Please modify test.');
        $columns = scandir($path);
        foreach ($columns as $column) {
            if (0 < substr_count($column, 'column') and 'column.latte' != $column) {
                Assert::same(0, preg_match('/{\*|\*}/', file_get_contents($path . $column)), 'There is comment mark {* or *} in latte ' . $column . ' Masala.');
                Assert::true(1 < count($presenter = explode('.', $column)), 'In column name is not enough arguments to assign presenter.');
                Assert::true(class_exists($class = 'App\\' . ucfirst($presenter[0]) . 'Module\\' . ucfirst($presenter[1]) . 'Presenter'), $class . ' does not exist.');
                if (isset($presenter[2]) and 'column' != $presenter[2]) {
                    Assert::false(empty($method = 'action' . ucfirst($presenter[2])), 'Assigned method is empty string.');
                    Assert::true(is_object($object = new $class()), 'Instatiation of ' . $class . ' failed.');
                    Assert::true(method_exists($object, $method), $class . ' must have method ' . $method . '.');
                    Assert::true(is_object($reflection = new Method(get_class($object), $method)), 'Reflection failed.');
                    Assert::notSame(0, count($parameters = $reflection->getParameters()), 'Method ' . $method . ' of class ' . $class . ' should have one parameter at least. Do you wish to modify test?');
                }
            }
        }
        Assert::true(is_object($extension = $this->container->getByType('Masala\BuilderExtension')));
        Assert::false(empty($config = $extension->getConfiguration($this->container->parameters)));
        Assert::false(empty($settings = (array) json_decode($this->mockService->getUser()->getIdentity()->getData()[$config['masala']['settings']])), 'Test user does not have settings.');
        Assert::false(empty($setting = (array) reset($settings)), 'User setting is not set');
        $_POST = [$column => 'true'];
        $presenters = $this->mockService->getPresenters('IMasalaFactory');
        foreach ($presenters as $class => $presenter) {
            Assert::false(empty($_POST), 'Post cannot be empty for testing xhr handlers.');
            if(isset($this->container->parameters['mockService']['presenters'][$class])) {
                $testParameters = $this->container->parameters['mockService']['presenters'][$class];
            } else if(isset($this->container->parameters['mockService']['testParameters'])) {
                $testParameters = $this->container->parameters['mockService']['testParameters'];
            } else {
                $testParameters = [];
            }
            echo 'testing ' . $presenter . "\n";
            /** @todo: when using this->import, Module:Presenter:action MUST be in fc_feeds.source */
            Assert::true(is_array($parameters = $presenter->request->getParameters('action')), 'Parameters have not been set in ' . $class . '.');
            Assert::notSame(6, strlen($method = 'action' . ucfirst(array_shift($parameters))), 'Action method of ' . $class . ' is not set.');
            Assert::true(is_object($reflection = new Method($class, $method)));
            $arguments = [];
            dump($testParameters);
            foreach ($reflection->getParameters() as $parameter) {
                Assert::true(isset($testParameters[$parameter->getName()]), 'There is no test parameters for ' . $parameter->getName() . ' in ' . $class . '.');
                $arguments[$parameter->getName()] = $testParameters[$parameter->getName()];
            }
            Assert::true(method_exists($class, $method), 'According to latte file should exist method ' . $method . ' in ' . $class . '.');
            /* Assert::same(null, call_user_func_array([$presenter, $method], $arguments), 'Method ' . $method . ' of ' . $class . ' does return something. Do you wish to modify test?'); */
            Assert::true(is_string($source = $presenter->grid->getTable()), 'Source set in method ' . $method . ' of ' . $class . ' is not set.');
            Assert::true(is_object($presenter->grid->where('id IS NOT NULL')), 'Grid setter method does not return class itself.');
            $this->class->setGrid($presenter->grid);
            $presenter->addComponent($this->class, 'IMasalaFactory');
            Assert::true(is_object($grid = $presenter->grid), 'Grid IBuilder is not set.');
            Assert::same($source, $presenter->grid->getTable(), 'Source ' . $source . ' for Masala IBuilder was not set.');
            /* Assert::same(null, $presenter->grid->build([], $this->class->getName()), 'Source ' . $source . ' in ' . $class . ':' . $method . ' for Masala failed.'); */
            Assert::false(isset($presenter->grid->select), 'Select in IBuilder should be private.');
            Assert::false(isset($presenter->grid->join), 'Join in IBuilder should be private.');
            Assert::false(isset($presenter->grid->leftJoin), 'Left join in IBuilder should be private.');
            Assert::false(isset($presenter->grid->innerJoin), 'Inner join in IBuilder should be private.');
            Assert::true(is_array($filters = (null != $presenter->grid->getFilters()) ? $presenter->grid->getFilters() : []), 'Filters in Masala IBuilder are not set.');
            /* Assert::same(null, $this->class->getGrid()->build([], $this->class->getName()), 'VO:build does retunn something. Do you wish to modify test?'); */
            Assert::same($presenter->grid, $presenter->grid->table($source), 'Set table of VO does not return class itself.');
            Assert::true(is_array($columns = $presenter->grid->getDrivers($source)), 'Table columns are not defined.');
            Assert::true(is_object($grid = $this->mockService->getPrivateProperty($this->class, 2)), 'Masala builder is not set.');
            Assert::true(is_array($renderColumns = $grid->getColumns()), 'No columns was rendered.');
            foreach($renderColumns as $column => $annotation) {
                $_POST[$column] = 'true';
                break;
            }
            Assert::false(empty($_POST), 'No column to annotate is set.');
            Assert::true(is_object($gridFactory = $this->mockService->getPrivateProperty($this->class, 3)), 'IGridFactory is not set.');
            Assert::true($gridFactory instanceof IGridFactory, 'GridFactory has wrong instation.');
            Assert::same($gridFactory, $gridFactory->setGrid($grid), 'GridFactory::setGrid does not return class itself.');
            Assert::same($presenter, $presenter->addComponent($gridFactory, 'gridFactory'), 'IPresenter::addComponent does not return class itself.');
            Assert::true(isset($_POST[$column]), 'Test $_POST data were unexpected overwrited.');
            Assert::same('response succeed', $gridFactory->handleSetting(), 'Grid::handleSetting failed.');
            Assert::false(empty($_POST[$column] = 'false'));
            Assert::same('response succeed', $gridFactory->handleSetting(), 'Grid::handleSetting failed.');
            $notShow = [];
            foreach ($columns as $column) {
                if (0 < substr_count($column['vendor']['Comment'], '@hidden')) {
                    $notShow[$column['name']] = $column['name'];
                }
            }
            Assert::false(empty($this->class->getGrid()->getColumns()),'Columns are not set.');
            foreach ($renderColumns as $key => $renderColumn) {
                if (isset($notShow[$key])) {
                    Assert::true(is_object($reflector = new \ReflectionClass($class)), 'Reflection is not set.');
                    Assert::false(empty($file = $reflector->getFileName()), 'File of ' . $class . ' is not set.');
                    Assert::false(is_object($handle = fopen($file, 'r+')), 'Open tested controller failed.');
                    echo $file . "\n";
                    $read = false;
                    while (!feof($handle)) {
                        $line = fgets($handle);
                        if (preg_match('/' . $method . '/', $line)) {
                            $read = true;
                        } elseif (true == $read and preg_match('/\}/', $line)) {
                            break;
                        } elseif (true == $read and preg_match('/' . $key . '/', $line)) {
                            echo $line;
                            Assert::same(0, preg_match('/@hidden/', $line), 'Discovered @hidden annotation in rendered ' . $source . '.' . $key . ' in ' . $class . ':' . $method);
                        }
                    }
                }
            }
            $this->setUp();
        }
    }

    public function testLatte() {
        $latte = $this->container->parameters['appDir'] . '/Masala/templates/grid.latte';
        Assert::true(is_file($latte), 'Latte file for grid is not set.');
        Assert::false(empty($grid = file_get_contents($latte)), 'Latte file is empty.');
        Assert::true(0 < substr_count($grid, '$(this).datetimepicker({ format: $(this).attr(\'data\'), locale: {$locale} })'), 'It seems that datatimepicker in javascript has unintended format. Did you manipulated just with space?');
        Assert::true(0 < substr_count($grid, '<script src="{$js}"></script>'), 'It seems that react component is not included.');
    }

    public function testGetComment() {
        $row = $this->container->getByType('Masala\IRow');
        Assert::false(empty($key = array_rand($this->container->parameters['tables'])), 'Test source was not set.');
        Assert::false(empty($source = $this->container->parameters['tables'][$key]), 'Test source was not set.');
        Assert::false(empty($columns = $row->table($source)->getDrivers($source)), 'Columns of tables ' . $source . ' was not set.');
        Assert::false(empty($source = $this->container->parameters['tables']['help']), 'Test source for table help was not set.');
        Assert::false(empty($columns = $row->table($source)->getDrivers($source)), 'Columns of tables ' . $source . ' was not set.');
        Assert::true(isset($columns[1]), 'Json column was not set');
        Assert::same('json', $columns[1]['name'], 'Json column was not set');
        Assert::false(empty($comment = $columns[1]['vendor']['Comment']), 'Json column comment should be not empty');
        Assert::same(1, substr_count($comment, '@hidden'), $source . '.json should have disabled comment via annotation @hidden.');
    }

}

id(new MasalaTest($container))->run();
