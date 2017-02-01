<?php

namespace Test;

use Masala\EditForm,
    Masala\MockModel,
    Masala\MockService,
    Masala\RowBuilder,
    Models\MonitorModel,
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

class EditFormTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var Array */
    private $tables;

    /** @var MonitorModel */
    private $monitorModel;

    /** @var EditForm */
    private $class;

    /** @var MockModel */
    private $mockModel;

    /** @var MockService */
    private $mockService;

    /** @var RowBuilder */
    private $setting;

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
        $this->setting = new RowBuilder($parameters['masala'], $context, $cacheStorage);
        $this->monitorModel = $this->container->getByType('Models\MonitorModel');
        $urlScript = new UrlScript();
        $httpRequest = new Request($urlScript);
        $this->class = new EditForm([], $grid, $translatorModel, $this->mockService, $httpRequest);
        $this->tables = $this->mockModel->getTestTables();
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    /** @todo: check if sql columns used in queries in table triggers exist */
    public function testSetSetting() {
        $this->setUp();
        $key = 'categories';
        $this->setting->table($this->monitorModel->upload);
        Assert::notSame(false, $this->setting, 'There is no VO for testing EditForm.');
        Assert::true($this->setting instanceof RowBuilder, 'There is no VO for testing EditForm.');
        Assert::same($this->class, $this->class->setSetting($this->setting), 'Setter does not return class.');
    }

    /** https://forum.nette.org/cs/21274-test-formulare-podstrceni-tlacitka */
    public function testSucceeded() {
        $this->testSetSetting();
        Assert::false(empty($source = $this->container->parameters['tables']['write']), 'There is table for source write');
        Assert::false(empty($setting = $this->mockModel->getTestRow($source)), 'There is no test row for source ' . $source);
        $presenter = $this->mockService->getPresenter('App\ContentModule\WritePresenter', WWW_DIR . 'app/ContentModule/templates/Write/edit.latte', ['id' => $setting->id]);
        Assert::true(is_object($presenter), 'Presenter was not set.');
        $presenter->addComponent($this->class, 'EditForm');
        /* $submittedBy = Mockery::mock(Nette\Forms\Controls\SubmitButton::class);
          $submittedBy->shouldReceive('getName')->andReturn('save');
          $this->class->shouldReceive('isSubmitted')->andReturn($submittedBy); */
        Assert::same($this->class->getSetting(), $this->class->getSetting()->setSubmit('save'), 'Set submit method should return class RowBuilder.');
        Assert::same('save', $this->class->getSetting()->getSubmit(), 'In this test should be form submitted by save component.');
        Assert::same(true, is_object($this->class->getValues()));
        Assert::true($this->class->getValues() instanceof ArrayHash);
    }

    public function testAttached() {
        $this->testSetSetting();
        /** todo: $presenters = $this->mockService->getPresenters('IEditFormFactory'); */
        $presenter = $this->mockService->getPresenter('App\SaleModule\HeurekaPresenter', WWW_DIR . 'app/SaleModule/templates/Heureka/edit.latte');
        Assert::true(is_object($presenter), 'Presenter was not set.');
        $presenter->addComponent($this->class, 'EditForm');
        Assert::false(empty($columns = $this->class->getSetting()->getColumns()), 'Columns for EditForm are not set.');
        Assert::false(empty($presenter->monitorModel->upload), 'Source for monitor upload is not set.');
        $columns = $presenter->monitorModel->getColumns($presenter->monitorModel->upload);
        $required = false;
        foreach ($columns as $column) {
            false == $column['nullable'] and 'PRI' != $column['vendor']['Key'] and 0 === substr_count($column['vendor']['Comment'], '@unedit') ? $required = $column['name'] : null;
            $notEdit = (0 < substr_count($column['vendor']['Comment'], '@unedit')) ? $column['name'] : 'THISNAMECOMPONENTSHOULDNEVERBEUSED';
        }
        Assert::false(isset($this->class[$notEdit]), 'Component ' . $notEdit . 'has been render even if it has annotation @unedit');
        Assert::true(is_string($required), 'Table ' . $presenter->monitorModel->upload . ' has all columns with default null. Are you sure it is not dangerous?');
        //Assert::same(null, $this->class->removeComponent($this->class['do']));
        Assert::notSame(true, isset($this->class[$notEdit]));
        if (false != $required) {
            Assert::same(true, $this->class[$required]->isRequired(), 'Component ' . $required . ' should be required as it is not nullable column.');
        }
        Assert::same('Masala\EditForm', get_class($this->class), 'Namespace of EditForm must be exactly Masala as it is used as query parameter for hidden field spice.');
    }

    /** @todo: IEditFormService:attached must return service */
    public function testSetPresenter() {
        
    }

    public function testColumnComments() {
        $this->setUp();
        $tables = [];
        $excluded = ['fc_acl_ownership', 'fc_filters_sentences', 'fc_predictions', 'fc_sentences'];
        shuffle($this->tables);
        foreach ($this->tables as $table) {
            if ('shopio_' != substr($table->Tables_in_cz_4camping, 0, 7) and ! in_array($table->Tables_in_cz_4camping, $excluded)) {
                $tables[] = $table->Tables_in_cz_4camping;
            }
        }
        foreach ($tables as $name) {
            Assert::false(empty($name));
            Assert::same(true, preg_match('/fc_(.*)/', $name) or preg_match('/sortiment-(.*)/', $name) or $name == 'files', 'Table ' . $name . ' has no fc prefix.');
            Assert::true(is_integer($index = rand(0, count($this->tables) - 1)), 'Index for random table is not set.');
            Assert::true(($table = $this->tables[$index]) instanceof Row, 'Table to test column comments is not set.');
            Assert::true(is_string($name), 'Table name is not defined.');
            Assert::true(is_array($columns = $this->monitorModel->getColumns($name)), 'Table columns are not defined.');
        }
        $compulsories = ['fc_words' => ['class' => 'App\ContentModule\WritePresenter', 'latte' => 'ContentModule/templates/Write/word.latte'],
            'fc_write' => ['class' => 'App\ContentModule\WritePresenter', 'latte' => 'ContentModule/templates/Write/edit.latte']];
        foreach ($compulsories as $name => $compulsory) {
            $setting = $this->mockModel->getTestRow($name);
            $this->setting->table($name);
            Assert::true($setting instanceof ActiveRow or in_array($name, $excluded), 'There is no row in ' . $name . '. Are you sure it is not useless?');
            if ($setting instanceof ActiveRow) {
                Assert::true(is_array($columns = $this->monitorModel->getColumns($name)), 'Table columns are not defined.');
                $this->class->setSetting($this->setting);
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
