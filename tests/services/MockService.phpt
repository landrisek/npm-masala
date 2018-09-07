<?php

namespace Test;

use Masala\IMock,
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

    /** @var IMock */
    private $mockRepository;

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
        $this->mockRepository = $this->container->getByType('Masala\IMock');
        $this->translatorModel = $this->container->getByType('Nette\Localization\ITranslator');
        $this->class = $this->container->getByType('Masala\MockService');
        $this->tables = $this->mockRepository->getTestTables();
        $this->request = $this->container->getByType('Nette\Http\IRequest');
    }

    function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testGetCall() {
        Assert::true(method_exists($this->class, 'getCall'), 'MockService:getCall method is not set.');
        $calls = [];
        foreach ($this->container->parameters['masala'] as $key => $call) {
            if(preg_match('/[A-Za-z]+\.[A-Za-z]+/', $key)) {
                $calls[preg_replace('/\.(.*)/', '', $key)] = $call;
            }
        }
        $parameters = $this->container->parameters['masala'];
        foreach ($parameters as $key => $annotations) {
            if (is_array($annotations)) {
                foreach($annotations as $table => $annotation) {
                    $parameters = (isset($annotation['parameters'])) ? $annotation['parameters'] : null; 
                    $arguments = $this->class->getCall($annotation['service'], $annotation['method'], $parameters, $this->container->getByType('Masala\IRowFormFactory'));
                    Assert::true(is_array($arguments), 'Call ' . $key . ' from masala config failed.');
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
