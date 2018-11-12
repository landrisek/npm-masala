<?php

namespace Tests\Masala;

use Masala\ImportForm,
    Masala\IImportFormFactory,
    Masala\IProcess,
    Masala\MockFacade,
    Nette\Database\Table\ActiveRow,
    Nette\DI\Container,
    Nette\Reflection\Method,
    Nette\Utils\Strings,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class ImportFormTest extends TestCase {

    /** @var IImportFormFactory */
    private $class;

    /** @var Container */
    private $container;

    /** @var IProcess */
    private $facade;

    /** @var MockFacade */
    private $mockFacade;

    /** @var array */
    private $presenters;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function __destruct() {
        echo 'Tests of Masala\ImportForm finished.' . "\n";
    }

    public function setUp(): void {
        Assert::true(is_object($this->mockFacade = $this->container->getByType('Masala\MockFacade')), 'MockFacade is not set.');
        Assert::false(empty($processes = $this->container->findByType('Masala\IProcess')), 'No Masala\IProcess found.');
        Assert::false(empty($facade = $processes[rand(0, sizeof($processes) -1)]));
        Assert::true(is_object($this->facade = $this->container->getService($facade)), 'Get IProcess failed.');
        Assert::false(empty($assets = $this->container->parameters['masala']['assets']), 'Masala assets should be injected through config.test.neon as extension is called after create container mehtod.');
        Assert::false(empty($manifest = (array) json_decode(file_get_contents($this->container->parameters['wwwDir'] . '/' . $assets . '/js/manifest.json'))));
        Assert::true(is_object($this->class = new ImportForm($this->container->parameters['wwwDir'] . '/' . $assets . '/masala/css', 
                                                            $manifest['ImportForm.js'], 
                                                            $this->container->getByType('Nette\Http\IRequest'), 
                                                            $this->container->getByType('Nette\Localization\ITranslator'))), 'IImportFormFactory is not set.');
        Assert::false(empty($this->presenters = $this->mockFacade->getPresentersByService('Masala\IImport')), 'No IImport service implementation.');
    }
    
    public function testAttached(): void {
        Assert::true(is_array($this->presenters), 'No presenter to test on import was set.');
        Assert::false(empty($this->presenters), 'There is no feed for testing.');
        Assert::true(100 > count($this->presenters), 'There is more than 100 available feeds for testing which could process long time. Consider modify test.');
        foreach ($this->presenters as $class => $presenter) {
            Assert::true(is_object($presenter), 'Presenter was not set.');
            $this->setUp();
        }
    }

    public function testSetFacade(): void {
        $mockRepository = $this->container->getByType('Masala\IMock');
        foreach ($this->presenters as $class => $presenter) {
            if($presenter->grid->isImport()) {
                echo $class . "/n";
                Assert::true(is_object($masala = $presenter->context->getByType('Masala\Masala')), 'Masala is not set.');
                Assert::true(is_object($masala->setGrid($presenter->grid)), 'Masala:setGrid does not return class itself.');
                Assert::true(is_object($masala->setRow($presenter->grid->copy())), 'Masala:setGrid does not return class itself.');
                Assert::true(is_object($presenter->addComponent($this->container->getByType('Masala\Masala'), 'masala')), 'Attached Masala failed.');
                Assert::true(is_object($masala = $presenter->getComponent('masala')), 'Masala is not set');
                Assert::same(null, $masala->attached($presenter), 'Masala:attached succeed but method return something. Do you wish to modify test?');
                Assert::true(is_object($presenter), 'Presenter was not set.');
                $facade = $presenter->grid->getImport();
                Assert::true(is_object($this->class->setFacade($facade)), 'ImportForm:setFacade does not return class itself.');
                Assert::same($this->class, $this->class->setFacade($facade), 'ImportForm:setFacade does not return class itself.');
                Assert::true(is_object($setting = $mockRepository->getTestRow($this->container->parameters['masala']['feeds'], 
                        ['type' => 'import', 'source' => $presenter->getName() . ':' . $presenter->getAction()])), 'Setting is not set.');
                Assert::true($setting instanceof ActiveRow, 'Import setting is not set in ' . $class . '.');
                Assert::notSame(null, $setting->mapper, 'Following tests require existing active row for source ' . $setting->feed . '.');
                Assert::false(empty($setting->mapper), 'Mapper for source ' . $setting->feed . ' is not set.');
                Assert::true(is_string($setting->feed), 'Name of feed was not set.');
                $presenter->removeComponent($masala);
            }
        }
    }

    public function testSucceed(): void {
        $testParameters = ['id' => 1, 'page' => 1];
        foreach ($this->presenters as $class => $presenter) {
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
            Assert::true($this->class instanceof IImportFormFactory, 'ImportForm has wrong instantion.');
            Assert::true(empty($this->class->getData()), 'Data has been attached too early.');
            Assert::true(property_exists($this->class, 'translatorRepository'), 'Translator repository was not set');
            Assert::true(is_object($this->class->setFacade($this->facade)), 'IProcess was not set.');
            $csv = __DIR__ . '/' . Strings::webalize(preg_replace('/App|Module|Presenter|action/', '', $class . '-' . $method)) . '.csv';
            Assert::true(is_file($csv), 'Test upload file ' . $csv . ' is not set.');
            $presenter->addComponent($this->class, 'importForm');
            Assert::false(empty($data = $this->class->getData()), 'Data are empty.');
            Assert::true(isset($data['_prepare-progress']), 'Prepare button is missing.');
            $presenter->removeComponent($this->class);
            $this->setUp();
        }
    }
}

id(new ImportFormTest($container))->run();
