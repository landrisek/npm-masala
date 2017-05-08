<?php

namespace Test;

use Masala\EditForm,
    Masala\MockModel,
    Masala\MockService,
    Models\TranslatorModel,
    Nette\DI\Container,
    Nette\Http\Request,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class MockServiceTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var MockModel */
    private $mockModel;

    /** @var TranslatorModel */
    private $translatorModel;

    /** @var Request */
    private $request;

    /** @var MockService */
    private $class;

    function __construct(Container $container) {
        $this->container = $container;
    }

    function setUp() {
        $this->mockModel = $this->container->getByType('Masala\MockModel');
        $this->translatorModel = $this->container->getByType('Nette\Localization\ITranslator');
        $this->class = $this->container->getByType('Masala\MockService');
        $this->tables = $this->mockModel->getTestTables();
        $this->request = $this->container->getByType('Nette\Http\IRequest');
    }

    function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testGetCall() {
        Assert::true(method_exists($this->class, 'getCall'), 'MockService:getCall method is not set.');
        $compulsories = (isset($this->container->parameters['mockService']['calls'])) ? $this->container->parameters['mockService']['calls'] : [];
        $presenters = [];
        $parameters = $this->container->parameters['masala'];
        foreach ($compulsories as $table => $annotations) {
            Assert::true(!is_array($annotations) or isset($parameters[$table]), 'Compulsory for Masala config annotation ' . $table . ' is not used. Do you wish to remove it?');
        }
        foreach ($parameters as $table => $annotations) {
            Assert::true(!is_array($annotations) or isset($compulsories[$table]['class']), 'Compulsory for Masala config annotation ' . $table . ' was not set. You must add test.');
            if (is_array($annotations) and ! isset($presenters[$compulsories[$table]['class']])) {
                $tableName = preg_replace('/\.(.*)/', '', $table);
                $row = $this->mockModel->getTestRow($tableName);
                $presenters[$compulsories[$table]['class']] = $this->class->getPresenter($compulsories[$table]['class'], WWW_DIR . '/' . $compulsories[$table]['latte'], ['id' => $row->id]);
                Assert::true(is_object($presenter = $presenters[$compulsories[$table]['class']]), 'Presenter ' . $compulsories[$table]['class'] . ' was not instantiated.');
                Assert::false(empty($presenter->getAction()), 'Action of presenter ' . $compulsories[$table]['class'] . ' is not set for annotation ' . $table . '.');
                $setting = $this->container->getByType('Masala\IRowBuilder');
                $grid = $this->class->getBuilder($presenter->getName(), 'default');
                $form = new EditForm(10, $this->translatorModel, $this->class, $this->request);
                $form->setRow($setting->table($tableName));
                $presenters[$compulsories[$table]['class']]->addComponent($form, 'EditForm');
                Assert::true(is_object($presenters[$compulsories[$table]['class']]), 'Presenter was not set.');
            }
            if (is_array($annotations)) {
                foreach ($annotations as $method => $annotation) {
                    $arguments = $this->class->getCall($annotation['service'], $annotation['method'], $annotation['parameters'], $form);
                    Assert::true(is_string($arguments) or is_null($arguments) or is_array($arguments), 'Arguments return by getCall are not set for table.column ' . $table);
                    Assert::true(is_string($annotation['service']), 'Service is not set for table.column ' . $table);
                    Assert::false(is_array($annotation['service']), 'Assigned service should be string for table.column ' . $table);
                    Assert::true(is_string($annotation['method']), 'Method is not set for table.column ' . $table);
                }
            }
        }
    }

}

id(new MockServiceTest($container))->run();
