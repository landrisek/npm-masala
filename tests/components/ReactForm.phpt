<?php

namespace Tests\Masala;

use Masala\IReactFormFactory,
    Masala\MockFacade,
    Masala\ReactForm,
    Nette\DI\Container,
    Nette\Reflection\Method,
    Tester\Assert,
    Tester\TestCase;
    
$container = require __DIR__ . '/../../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class ReactFormTest extends TestCase {

    /** @var IReactFormFactory */
    private $class;

    /** @var Container */
    private $container;

    /** @var MockFacade */
    private $mockFacade;

    /** @var array */
    private $presenters;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp(): void {
        $this->mockFacade = $this->container->getByType('Masala\MockFacade');
        $extension = $this->container->getByType('Masala\MasalaExtension');
        Assert::false(empty($parameters = $extension->getConfiguration([])), 'ExtensionBuilder default configuration is empty.');
        $assets = $this->container->parameters['wwwDir'] . '/' . $parameters['masala']['assets'] . '/';
        $request = $this->container->getByType('Nette\Http\IRequest');
        $translatorRepository = $this->container->getByType('Nette\Localization\ITranslator');
        $this->class = new ReactForm($assets . 'css', $assets . '/js/GeneralForm.js', $request, $translatorRepository);
        $this->presenters = ['Masala\Demo\DemoPresenter' => $this->container->parameters['appDir'] . '/components/masala/demo/default.latte'];
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }
    
    public function testAttached(): void {
        Assert::true(is_array($this->presenters), 'No presenter to test on import was set.');
        Assert::false(empty($this->presenters), 'There is no feed for testing.');
        Assert::true(100 > count($this->presenters), 'There is more than 100 available feeds for testing which could process long time. Consider modify test.');
        $builder = $this->container->getByType('Masala\IBuilder');
        foreach($this->container->parameters['tables'] as $table) {
            $builder->table($table);
            foreach($builder->getDrivers($table) as $column) {
                if('DATETIME' == $column['nativetype']) {
                    Assert::false(empty($date = $table . '.' . $column['name']), 'Datetime column is not set.');
                    break;
                }
            }
        }
        Assert::true(isset($date), 'No datetime column to test.');
        foreach ($this->presenters as $class => $latte) {
            $presenter = $this->mockFacade->getPresenter($class, $latte, []);
            Assert::true(is_object($presenter), 'Presenter was not set.');
            Assert::true(is_object($presenter->grid = $this->container->getByType('Masala\IBuilder')), 'Presenter grid was not set.');
            Assert::true(in_array('addComponent', get_class_methods($presenter)), 'Presenter has no method addComponent.');
            Assert::true(is_object($presenter->addComponent($this->class, 'reactForm')), 'Attached ReactForm failed.');
            Assert::true(is_array($parameters = $presenter->request->getParameters('action')), 'Parameters have not been set in ' . $class . '.');
            Assert::notSame(6, strlen($method = 'action' . ucfirst(array_shift($parameters))), 'Action method of ' . $class . ' is not set.');
            Assert::true(is_object($reflection = new Method($class, $method)));
            $arguments = [];
            foreach ($reflection->getParameters() as $parameter) {
                Assert::true(isset($testParameters[$parameter->getName()]), 'There is no test parameters for ' . $parameter->getName() . ' in ' . $class . '.');
                $arguments[$parameter->getName()] = $testParameters[$parameter->getName()];
            }
            Assert::true(is_object($presenter), 'Presenter is not class.');
            Assert::true(is_object($this->class), 'FilterForm was not set.');
            Assert::true($this->class instanceof ReactForm, 'ReactForm has wrong instantion.');
            Assert::true(is_object($this->class->addSelect('test', 'test', ['data' => [3 => '3', 1 => '1', 2 => '2']])), 'ReactForm:addSelect does not return class itself.');
            Assert::false(empty($data = $this->class->getData()), 'Form values are not set in class ' . $class . ' for source ' . $table);
            Assert::same(['_3' => '3', '_1' => '1', '_2' => '2'], $data['test']['Attributes']['data'], 'ReactForm:addSelect did not concat keys to keep order in javascript arrays.');
            Assert::true(is_array($data), 'ReactForm values are not set.');
            Assert::true(property_exists($this->class, 'css'), 'Private property for styles does not exist.');
            Assert::true(property_exists($this->class, 'js'), 'Private property for javascript does not exist.');
            Assert::false(empty($methods = get_class_methods($this->class)), 'Masala\IReactFormFactory does not have any method.');
            Assert::false(isset($methods['succeeded']) or isset($methods['formSucceeded']), 'IReactForm should have only one succeed method called submit.');
            Assert::false(isset($methods['submit']), 'Masala\IRectForm:submit method is missing');
            $presenter->removeComponent($this->class);
            $this->setUp();
        }
    }

}

id(new ReactFormTest($container))->run();