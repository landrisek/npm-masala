<?php

namespace Test;

use Masala\ImportForm,
    Masala\Masala,    
    Masala\MockService,
    Masala\RowBuilder,
    Models\HelpModel,
    Models\TranslatorModel,
    Nette\Database\Connection,
    Nette\Database\Context,
    Nette\Database\Structure,
    Nette\DI\Container,
    Nette\Caching\Storages\FileStorage,
    Nette\Http\Request,
    Nette\Http\UrlScript,
    Nette\Reflection\Method,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

class MasalaTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var Masala */
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
        $translatorModel = new TranslatorModel($tables['translator'], $context, $cacheStorage);
        $this->mockService = new MockService($this->container, $translatorModel);
        $grid = $this->mockService->getBuilder();
        $setting = new RowBuilder($parameters['masala'], $context, $cacheStorage);
        $this->helpModel = new HelpModel($this->container->parameters['tables']['help'], $grid, $setting, $context, $cacheStorage);
        $importForm = new ImportForm($translatorModel);
        $urlScript = new UrlScript;
        $httpRequest = new Request($urlScript);
        $form = $this->container->getByType('Masala\IFilterFormFactory');
        $this->class = new Masala($parameters['masala'], $translatorModel, $importForm, $form, $httpRequest);
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testOrder() {
        Assert::true(is_object($presenter = $this->mockService->getPresenter('App\SaleModule\ProductsPresenter', WWW_DIR . 'app/SaleModule/templates/Products/default.latte')));
    }

    public function testAttached() {
        Assert::true(file_exists($path = APP_DIR . '/grids/Masala/'), 'Masala folder does not exist in default folder. Please modify test.');
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
        $presenters = $this->mockService->getPresenters('IMasalaFactory');
        $testParameters = ['feed' => 'laurasport', 
                            'id' => 1, 
                            'date' => date('Y-m-d', strtotime('now')), 
                            'limit' => 10, 
                            'type' => 'inventure', 'status' => 'translated', 
                            'producerId'=>126];
        foreach ($presenters as $class => $presenter) {
            echo 'testing ' . $presenter . "\n";
            /** @todo: when using this->import, Module:Presenter:action MUST be in fc_feeds.source */
            Assert::true(is_array($parameters = $presenter->request->getParameters('action')), 'Parameters have not been set in ' . $class . '.');
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
            Assert::true(is_object($presenter->grid->where('id IS NOT NULL')), 'Grid setter method does not return class itself.');
            $this->class->setGrid($presenter->grid);
            $presenter->addComponent($this->class, 'IMasalaFactory');
            Assert::true(is_object($grid = $presenter->grid), 'Grid IBuilder is not set.');
            Assert::same($source, $presenter->grid->getTable(), 'Source ' . $source . ' for Masala IBuilder was not set.');
            Assert::true(!empty($serializations = (array) $this->class), 'Serialize of Masala failed.');
            /* Assert::same(null, $presenter->grid->build([], $this->class->getName()), 'Source ' . $source . ' in ' . $class . ':' . $method . ' for Masala failed.'); */
            Assert::true(isset($serializations["\x00Masala\Masala\x00port"]), 'Port in serialized class is not set.');
            Assert::false(isset($presenter->grid->select), 'Select in IBuilder should be private.');
            Assert::false(isset($presenter->grid->join), 'Join in IBuilder should be private.');
            Assert::false(isset($presenter->grid->leftJoin), 'Left join in IBuilder should be private.');
            Assert::false(isset($presenter->grid->innerJoin), 'Inner join in IBuilder should be private.');
            Assert::true(is_array($filters = (null != $presenter->grid->getFilters()) ? $presenter->grid->getFilters() : []), 'Filters in Masala IBuilder are not set.');
            /* Assert::same(null, $this->class->getGrid()->build([], $this->class->getName()), 'VO:build does retunn something. Do you wish to modify test?'); */
            Assert::same($presenter->grid, $presenter->grid->table($source), 'Set table of VO does not return class itself.');
            Assert::true(is_array($columns = $this->helpModel->getColumns($source)), 'Table columns are not defined.');
            Assert::true(isset($serializations["\x00Masala\Masala\x00columns"]), 'Columns in serialized class is not set.');
            Assert::true(is_array($renderColumns = $serializations["\x00Masala\Masala\x00columns"]), 'No columns was rendered.');
            $notShow = [];
            foreach ($columns as $column) {
                if (0 < substr_count($column['vendor']['Comment'], '@hidden')) {
                    $notShow[$column['name']] = $column['name'];
                }
            }
            $select = $this->class->getGrid()->getColumns();
            foreach ($renderColumns as $renderColumn) {
                if(isset($notShow[$renderColumn->name])) {
                    Assert::true(is_object($reflector = new \ReflectionClass($class)), 'Reflection is not set.');
                    Assert::false(empty($file = $reflector->getFileName()), 'File of ' . $class . ' is not set.');
                    Assert::same('@todo: check if rendered column is @hidden in select.' . $notShow, $select);
                }
                Assert::false(isset($notShow[$renderColumn->name]), 'Column ' . $renderColumn->name . ' has been rendered even if it does contain @hidden annotation in table ' . $source . '.');
            }
            $this->setUp();
        }
    }

    public function testHandlers() {
        $latte = APP_DIR . '/grids/Masala/templates/handlers.latte';
        Assert::true(is_file($latte), 'Latte file is not set.');
        Assert::false(empty($process = file_get_contents($latte)), 'Process latte is empty.');
        Assert::true(0 < substr_count($process, '/* disable this redirect during debugging in console */'), 'It seems that done methods {link message!} in javascript is disabled, commented or not set. Did you manipulated just with space?');
        Assert::false(empty($trimmed = preg_replace('/\s/', '', $process)));
        Assert::same(0, substr_count($trimmed, '/*location.href={link message!,status=>$status};'), '{link message!} is commented.');
        Assert::same(0, substr_count($trimmed, 'location.href={link message!,status=>$status};*/'), '{link message!} is commented.');
    }

    public function testGetComment() {
        Assert::false(empty($columns = $this->helpModel->getColumns()), 'Columns of HelpModel was not set.');
        Assert::true(isset($columns[1]), 'Json column was not set');
        Assert::same('json', $columns[1]['name'], 'Json column was not set');
        Assert::false(empty($comment = $columns[1]['vendor']['Comment']), 'Json column comment shoudl be not empty');
        Assert::same(1, substr_count($comment, '@hidden'), $this->helpModel->getSource() . '.json should have disabled comment via annotation @hidden.');
    }

}

id(new MasalaTest($container))->run();
