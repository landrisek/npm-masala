<?php

namespace Test;

use Masala\ImportForm,
    Masala\MockService,
    Models\TranslatorModel,
    Nette\Database\Connection,
    Nette\Database\Context,
    Nette\Database\Structure,
    Nette\Database\Table\ActiveRow,
    Nette\Caching\Storages\FileStorage,
    Nette\DI\Container,
    Nette\Reflection\Method,
    Nette\Utils\Strings,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class ImportFormTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var Array */
    private $presenters;

    /** @var MockService @inject */
    private $mockService;

    /** @var ImportForm */
    private $class;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        /** database */
        $connection = new Connection($this->container->parameters['database']['dsn'], $this->container->parameters['database']['user'], $this->container->parameters['database']['password']);
        $cacheStorage = new FileStorage(__DIR__ . '/../../../temp');
        $structure = new Structure($connection, $cacheStorage);
        $context = new Context($connection, $structure, null, $cacheStorage);
        /** models */
        $translatorModel = new TranslatorModel($this->container->parameters['localization'], $this->container->parameters['tables']['translator'], $context, $cacheStorage);
        $translatorModel->setLocale('cs');
        $this->mockService = new MockService($this->container, $translatorModel);
        $this->class = new ImportForm($translatorModel);
        $this->presenters = (isset($this->container->parameters['mockService']['import'])) ? $this->container->parameters['mockService']['import'] : [];
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testSetService() {
        foreach ($this->presenters as $class => $latte) {
            $presenter = $this->mockService->getPresenter($class, $this->presenters[$class]);
            Assert::false(empty($localization = array_keys($this->container->parameters['localization'])), 'Localization section in config file is not set.');
            Assert::true(is_object($presenter->translatorModel->setLocale(reset($localization))), 'ITranslator:setLocale does not return class itself.');
            Assert::true(is_object($masala = $presenter->context->getByType('Masala\Masala')), 'Masala is not set.');
            Assert::true(is_object($masala->setGrid($presenter->grid)), 'Masala:setGrid does not return class itself.');
            Assert::true(is_object($presenter->addComponent($this->container->getByType('Masala\Masala'), 'masala')), 'Attached Masala failed.');
            Assert::true(is_object($masala = $presenter->getComponent('masala')), 'Masala is not set');
            Assert::same(null, $masala->attached($presenter), 'Masala:attached succeed but method return something. Do you wish to modify test?');
            Assert::true(is_object($presenter), 'Presenter was not set.');
            $service = $presenter->grid->getImport();
            Assert::true(is_object($this->class->setService($service)), 'ImportForm:setService does not return class itself.');
            Assert::same($this->class, $this->class->setService($service), 'ImportForm:setService does not return class itself.');
            $setting = $presenter->grid->getSetting('import');
            Assert::true($setting instanceof ActiveRow, 'Import setting is not set in ' . $class . '.');            
            Assert::notSame(null, $setting->mapper, 'Following tests require existing active row for source ' . $setting->feed . '.');
            Assert::false(empty($setting->mapper), 'Mapper for source ' . $setting->feed . ' is not set.');
            Assert::true(is_string($setting->feed), 'Name of feed was not set.');
            $presenter->removeComponent($masala);
        }
    }

    public function testAttached() {
        Assert::true(is_array($this->presenters), 'No presenter to test on import was set.');
        Assert::false(empty($this->presenters), 'There is no feed for testing.');
        Assert::true(100 > count($this->presenters), 'There is more than 100 available feeds for testing which could process long time. Consider modify test.');
        foreach ($this->presenters as $class => $latte) {
            $presenter = $this->mockService->getPresenter($class, $this->presenters[$class]);
            Assert::true(is_object($presenter), 'Presenter was not set.');
            $presenter->addComponent($this->class, 'ImportForm');
            $this->setUp();
        }
    }

    public function testSucceed() {

        $testParameters = ['id' => 1, 'feed' => 'laurasport', 'page' => 1, 'grid' => 'products'];
        /* http://stackoverflow.com/questions/21807656/use-mockery-to-unit-test-a-class-with-dependencies */
        foreach ($this->presenters as $class => $latte) {
            $presenter = $this->mockService->getPresenter($class, $latte);
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
            Assert::true(is_object($this->class), 'ImportForm was not set.');
            Assert::true($this->class instanceof ImportForm, 'ImportForm has wrong instantion.');
            /* Assert::same(true, is_object($this->class->getValues()));
              Assert::true($this->class->getValues() instanceof Nette\Utils\ArrayHash); */
            Assert::true(property_exists($this->class, 'translatorModel'), 'Translator model was not set');
            /* $presenter->addComponent($this->class, 'ImportForm'); */
            $csv = __DIR__ . '/' . Strings::webalize(preg_replace('/App|Module|Presenter|action/', '', $class . '-' . $method)) . '.csv';
            Assert::true(is_file($csv), 'Test upload file ' . $csv . ' is not set.');
            /* Assert::same(1, $this->class->succeed($this->class));
              $submittedBy = Mockery::mock(Nette\Forms\Controls\SubmitButton::class);
              $submittedBy->shouldReceive('getName')
              ->andReturn('save'); */
            $this->setUp();
        }
    }

}

id(new ImportFormTest($container))->run();
