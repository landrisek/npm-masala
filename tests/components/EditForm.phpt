<?php

namespace Test;

use Masala\EditForm,
    Masala\MockModel,
    Masala\MockService,
    Masala\RowBuilder,
    Models\TranslatorModel,
    Nette\Utils\ArrayHash,
    Nette\Caching\Storages\FileStorage,
    Nette\Caching\Storages\SQLiteJournal,
    Nette\Database\Connection,
    Nette\Database\Context,
    Nette\Database\Structure,
    Nette\Database\Row,
    Nette\Database\Table\ActiveRow,
    Nette\DI\Container,
    Nette\Http\Request,
    Nette\Http\UrlScript,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class EditFormTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var Array */
    private $tables;

    /** @var EditForm */
    private $class;

    /** @var MockModel */
    private $mockModel;

    /** @var MockService */
    private $mockService;

    /** @var RowBuilder */
    private $row;

    function __construct(Container $container) {
        $this->container = $container;
    }

    /** @todo: mock beforeRender in presenter */
    protected function setUp() {
        /** database */
        $connection = new Connection($this->container->parameters['database']['dsn'], $this->container->parameters['database']['user'], $this->container->parameters['database']['password']);
        $journal = new SQLiteJournal(__DIR__ . '/../../../../temp');
        $cacheStorage = new FileStorage(__DIR__ . '/../../../../temp', $journal);
        $structure = new Structure($connection, $cacheStorage);
        $context = new Context($connection, $structure);
        $parameters = $this->container->getParameters();
        $tables = $parameters['tables'];
        /** models */
        $translatorModel = new TranslatorModel($tables['translator'], $context, $cacheStorage);
        $this->mockModel = new MockModel($context, $cacheStorage);
        $this->mockService = new MockService($this->container, $translatorModel);
        $grid = $this->mockService->getBuilder('Sale:Google', 'default');
        $this->row = new RowBuilder($parameters['masala'], $context, $cacheStorage);
        $this->helpModel = $this->container->getByType('Masala\HelpModel');
        $urlScript = new UrlScript();
        $httpRequest = new Request($urlScript);
        $builder = $this->container->getByType('Masala\NetteBuilder');
        $this->class = new EditForm(10, $translatorModel, $this->mockService, $httpRequest);
        $this->tables = $this->mockModel->getTestTables();
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    /** @todo: check if sql columns used in queries in table triggers exist */
    public function testSetRow() {
        $this->setUp();
        $key = 'categories';
        Assert::false(empty($source = reset($this->container->parameters['tables'])), 'Test source is not set.');
        Assert::true(is_object($this->row->table($source)->check()), 'RowBuilder:check failed.');
        Assert::notSame(false, $this->row, 'There is no VO for testing EditForm.');
        Assert::true($this->row instanceof RowBuilder, 'There is no VO for testing EditForm.');
        Assert::same($this->class, $this->class->setRow($this->row), 'Setter does not return class.');
    }

    /** https://forum.nette.org/cs/21274-test-formulare-podstrceni-tlacitka */
    public function testSucceeded() {
        $this->testSetRow();
        Assert::false(empty($source = reset($this->container->parameters['tables'])), 'There is no table for test source.');
        Assert::false(empty($setting = $this->mockModel->getTestRow($source)), 'There is no test row for source ' . $source);
        $presenter = $this->mockService->getPresenter('App\ApiModule\DemoPresenter', WWW_DIR . '/app/grids/Masala/demo/default.latte', ['id' => $setting->id]);
        Assert::true(is_object($presenter), 'Presenter was not set.');
        //$presenter->addComponent($this->class, 'EditForm');
        /* $submittedBy = Mockery::mock(Nette\Forms\Controls\SubmitButton::class);
          $submittedBy->shouldReceive('getName')->andReturn('save');
          $this->class->shouldReceive('isSubmitted')->andReturn($submittedBy); */
        Assert::same($this->class, $this->class->signalled('attached'), 'Signalled method should return class EditForm.');
        Assert::same(true, is_object($this->class->getValues()));
        Assert::true($this->class->getValues() instanceof ArrayHash);
    }

    public function testAttached() {
        $this->testSetRow();
        /** todo: $presenters = $this->mockService->getPresenters('IEditFormFactory'); */
        $presenter = $this->mockService->getPresenter('App\ApiModule\DemoPresenter', WWW_DIR . '/app/grids/Masala/demo/default.latte', ['id' => $this->row->id]);
        Assert::true(is_object($presenter), 'Presenter was not set.');
        $presenter->addComponent($this->class, 'EditForm');
        Assert::false(empty($columns = $this->class->getRow()->getColumns()), 'Columns for EditForm are not set.');
        Assert::false(empty($source = array_rand($this->container->parameters['tables'])), 'Test Source is not set.');
        $columns = $this->row->getColumns($source);
        $required = false;
        foreach ($columns as $column) {
            if(false == $column['nullable'] and 
               'PRI' != $column['vendor']['Key'] and 
               0 === substr_count($column['vendor']['Comment'], '@unedit') and
               null === $column['default']) {
                $required = $column['name'];
            }
            $notEdit = (0 < substr_count($column['vendor']['Comment'], '@unedit')) ? $column['name'] : 'THISNAMECOMPONENTSHOULDNEVERBEUSED';
        }
        Assert::false(isset($this->class[$notEdit]), 'Component ' . $notEdit . 'has been render even if it has annotation @unedit');
        Assert::false(is_bool($required), 'Table ' . $source . ' has all columns with default null. Are you sure it is not dangerous?');
        Assert::notSame(true, isset($this->class[$notEdit]));
        if (is_string($required)) {
            Assert::same(true, $this->class[$required]->isRequired(), 'Component ' . $required . ' from table ' . $source . ' should be required as it is not nullable column.');
        }
        Assert::same('Masala\EditForm', get_class($this->class), 'Namespace of EditForm must be exactly Masala as it is used as query parameter for hidden field spice.');
    }

    /** @todo: IEditFormService:attached must return service */
    public function testSetPresenter() {
        
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
        $compulsories = (isset($this->container->parameters['mockService']['compulsories'])) ? $this->container->parameters['mockService']['compulsories'] : [];
        foreach ($compulsories as $name => $compulsory) {
            $setting = $this->mockModel->getTestRow($name);
            $this->row->table($name);
            Assert::true($setting instanceof ActiveRow or in_array($name, $excluded), 'There is no row in ' . $name . '. Are you sure it is not useless?');
            if ($setting instanceof ActiveRow) {
                Assert::true(is_array($columns = $this->row->getColumns($name)), 'Table columns are not defined.');
                $this->class->setRow($this->row);
                $presenter = $this->mockService->getPresenter($compulsory['class'], WWW_DIR . 'app/' . $compulsory['latte'], ['id' => $setting->id]);
                Assert::true(is_object($presenter), 'Presenter was not set.');
                $presenter->addComponent($this->class, 'EditForm');
                Assert::true(is_array($formComponents = array_keys((array) $this->class->getComponents())), 'Form components are not set.');
                foreach ($columns as $column) {
                    if (0 === substr_count($column['vendor']['Comment'], '@unedit') and 'PRI' !== $column['vendor']['Key']) {
                        Assert::true(isset($this->class[$column['name']]), 'Column ' . $column['name'] . ' in table ' . $name . ' was not draw as component in Masala\EditForm.');
                    } else {
                        Assert::false(isset($this->class[$column['name']]), 'Column ' . $column['name'] . ' in table ' . $name . ' was draw as component in Masala\EditForm even it should not.');
                    }
                }
                $this->setUp();
            }
        }
    }

    public function testAddDateTimePicker() {
        Assert::same(0, substr_count(preg_replace('/\s/', '', file_get_contents(WWW_DIR . '/app/grids/Masala/templates/edit.latte')), '{input$componentclass'), 'Components of Masala\EditForm must not have class in latte as it will overide datetimepicker class.');
    }

}

id(new EditFormTest($container))->run();
