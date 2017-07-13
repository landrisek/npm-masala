<?php

namespace Masala;

use Latte\Engine,
    Models\TranslatorModel,
    Nette\DI\Container,
    Nette\Http,
    Nette\Application,
    Nette\Application\IPresenter,
    Nette\Bridges\FormsLatte\FormMacros,
    Nette\Bridges\ApplicationLatte\TemplateFactory,
    Nette\Caching\Storages\SQLiteJournal,
    Nette\Caching\Storages\FileStorage,
    Nette\Database\Connection,
    Nette\Database\Context,
    Nette\Database\Structure,
    Nette\Security\User,
    Mockery,
    Tester\Assert;

final class MockService {

    /** @var Container */
    private $container;

    /** @var Context */
    private $cacheStorage;

    /** @var array */
    private $config;

    /** @var Context */
    private $context;

    /** @var BuilderExtension */
    private $extension;

    /** @var Http\Request */
    private $httpRequest;

    /** @var Http\Response */
    private $httpResponse;

    /** @var Http\Session */
    private $session;

    /** @var Application\Routers\RouteList */
    private $router;

    /** @var TemplateFactory */
    private $templateFactory;

    /** @var array */
    private $services;

    /** @var TranslatorModel */
    public $translatorModel;

    /** @var User */
    private $user;

    public function __construct(Container $container, TranslatorModel $translatorModel, BuilderExtension $extension) {
        $this->container = $container;
        $this->config = $container->getParameters();
        $this->extension = $extension;
        $this->translatorModel = $translatorModel;
    }

    /** @return IBuilder */
    public function getBuilder($source = 'Sale:Billing', $action = 'default') {
        $config = $this->extension->getConfiguration($this->config);
        $root = '/';
        $presenter = strtolower(preg_replace('/(.*)\:/', '', $source));
        $connection = new Connection($this->config['database']['dsn'], $this->config['database']['user'], $this->config['database']['password']);
        $journal = new SQLiteJournal($this->config['tempDir']);
        $cacheStorage = new FileStorage($this->config['tempDir'], $journal);
        $structure = new Structure($connection, $cacheStorage);
        $context = new Context($connection, $structure);
        $translatorModel = $this->container->getByType('Nette\Localization\ITranslator');
        $localization = array_keys($this->container->parameters['localization']);
        $translatorModel->setLocale(reset($localization));
        $urlScript = new Http\UrlScript();
        $urlScript->setScriptPath($root);
        $urlScript->setPath($root . $presenter . '/' . $action);
        $this->httpRequest = new Http\Request($urlScript);
        $exportService = $this->container->getByType('Masala\ExportService');
        $builder = new Builder($config['masala'], $exportService, $context, $cacheStorage, $this->httpRequest, $translatorModel);
        return $builder;
    }

    public function getCall($class, $method, $parameters, $parent) {
        $arguments = [];
        if (class_exists($class)) {
            $object = $this->container->getByType($class);
        } else {
            $object = $this->getParameter($class, $parent);
        }
        $overload = is_object($object) ? $object : $$object;
        if (is_array($parameters)) {
            $arguments[] = $parameters;
        } else {
            foreach (explode(',', $parameters) as $parameter) {
                $parameter = $this->getParameter($parameter, $parent);
                $arguments[] = $parameter;
            }
        }
        return call_user_func_array([$overload, $method], $arguments);
    }


    /** @return string */
    public function getConfig(array $keys) {
        $config = $this->config;
        foreach ($keys as $key) {
            $config = $config[$key];
        }
        return $config;
    }

    /** @return IPresenter */
    public function getPresenter($class, $latteFile, $parameters = [], $injected = false) {
        Assert::true(class_exists($class), 'Class ' . $class . ' not found.');
        if (false == $injected) {
            $this->setPresenter();
            $this->setDependencies($class);
        }
        echo 'testing ' . $class . "\n";
        Assert::false(empty($source = preg_replace('/(App|Module|Presenter|)/', '', $class)), 'Name of presenter is empty.');
        Assert::true(is_string($name = preg_replace('/\\\/', ':', $source)), 'Name of presenter is not set.');
        Assert::false(empty($name = ltrim($name, ':')), 'Name of presenter is empty');
        $mocks = empty($_POST) ? $class . '[getName,redirect]' : $class . '[getName,redirect,sendResponse]';
        $presenter = Mockery::mock($mocks, ['__get']);
        $action = preg_replace('/(.*)\/|\.latte/', '', $latteFile);
        if (property_exists($presenter, 'row')) {
            $presenter->row = new Row($this->container->parameters, $this->context, $this->cacheStorage);
        }
        if (property_exists($presenter, 'grid')) {
            $presenter->grid = $this->getBuilder($name, $action);
        }
        foreach ($this->services as $serviceId => $method) {
            if (isset($this->config['mockService'][$serviceId]) and property_exists($class, $serviceId)) {
                $service = $this->container->$method();
                foreach ($this->config['mockService'][$serviceId] as $setterId => $setter) {
                    if (is_array($setter)) {
                        foreach ($setter as $setId => $set) {
                            $setter[$setId] = (strlen($overload = preg_replace('/\$/', '', $set)) < strlen($set)) ? $$overload : $set;
                        }
                        $service->$setterId(call_user_func_array(array_shift($setter), $setter));
                    } elseif (is_string($setter)) {
                        $setter = (strlen($overload = preg_replace('/\$/', '', $setter)) < strlen($setter)) ? $$overload : $setter;
                        $service->$setterId($setter);
                    }
                }
                $presenter->$serviceId = $service;
            } elseif (class_exists($method) && property_exists($class, $serviceId)) {
                $presenter->$serviceId = $this->container->getByType($method);
            } elseif (property_exists($class, $serviceId)) {
                $presenter->$serviceId = $this->container->$method();
            }
        }
        if (isset($this->config['mockService'][$class])) {
            foreach ($this->config['mockService'][$class] as $mockId => $call) {
                $propertyValue = (isset($call['service'])) ? $this->getCall($call['service'], $call['method'], $call['parameters'], $presenter) : $call;
                $this->setProtectedProperty($presenter, $mockId, $propertyValue);
            }
        }
        $presenter->shouldReceive('redirect')->andReturn('redirect succeed');
        $presenter->shouldReceive('getName')->andReturn($name);
        $presenter->shouldReceive('sendResponse')->andReturn('response succeed');
        $presenter->shouldReceive('onShutdown');
        $urlScript = new Http\UrlScript();
        foreach ($parameters as $key => $value) {
            $urlScript->setQueryParameter($key, $value);
        }
        $this->httpRequest = new Http\Request($urlScript);
        $this->httpResponse =  $this->container->getByType('Nette\Http\IResponse');
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        Assert::true(is_file($latteFile) or isset($parameters['action']), 'Latte file ' . $latteFile . ' is not set.');
        $presenter->injectPrimary($this->container, $presenterFactory, $this->router, $this->httpRequest, $this->httpResponse, $this->session, $this->getUser(), $this->templateFactory);
        $presenter->template->setFile($latteFile);
        $presenter->autoCanonicalize = FALSE;
        Assert::same('App\\', substr($class, 0, 4), 'Presenter is not in App namespace.');
        Assert::true(is_string($presenterRequest = preg_replace('/Module|Presenter/', '', str_replace('\\', ':', substr($class, 4, strlen($class) - 4)))), 'Presenter ' . $class . ' request was not set');
        Assert::true(is_string($presenterAction = preg_replace('/(.*)\/|.latte/', '', $latteFile)), 'Presenter action for latte file ' . $latteFile . ' was not set.');
        if(empty($_POST)) {
            $request = new Application\Request($presenterRequest, 'GET', array_merge(['action' => $presenterAction], $parameters));
        } else {
            $request = new Application\Request($presenterRequest, 'POST', array_merge(['action' => $presenterAction], $parameters), $_POST);
            /** $request = Mockery::mock('Nette\Application\Request[getPost]', [$presenterRequest, 'GET', array_merge(['action' => $presenterAction], $parameters)]);
            $request->shouldReceive('getPost')->andReturn($_POST); */
        }
        $presenter->run($request);
        return $presenter;
    }

    /** @return array */
    public function getPresenters($component) {
        $this->setPresenter();
        $this->setDependencies();
        $files = $this->getFiles(['php'], 'Presenter');
        $factories = [];
        $contents = [];
        $injected = [];
        $templates = [];
        /** presenters */
        $presenters = [];
        foreach ($files as $fileId => $file) {
            $class = '';
            $handle = fopen($file, 'r+');
            $declarations = 0;
            $content = '';
            while (false != ($line = fgetcsv($handle, 100))) {
                $row = preg_replace('/ |\n/', '', $line[0]);
                Assert::same(0, preg_match('/exit;|dump\(|print\(/', $row), 'Forgotten exit, dump or print call on line ' . $line[0] . ' in file ' . $file . '. For testing use only exit() and die() for permanent usage.');
                /** namespace */
                if (1 == preg_match_all('/namespace(.*?);/', $row, $namespaces)) {
                    $content = preg_replace('/ |\n/', '', file_get_contents($file));
                    Assert::false(empty($content), 'Empty content in ' . $file);
                    Assert::false(0 == preg_match_all('/namespace(.*?);/', $content, $namespaces), 'Namespace was not recognised in ' . $file);
                    Assert::true(isset($namespaces[1]), 'Namespace was not recognised in ' . $file);
                    Assert::false(empty($class = $namespace = $namespaces[1][0] . '\\' . preg_replace('/\.(.*)/', '', basename($file))));
                }
                $content .= (is_string($line[0])) ? $row : '';
                /** initialization */
                (1 == preg_match_all('/class(.*)extends/', $line[0])) ? $declarations++ : null;
            }
            $contents[$class] = $content;
            fclose($handle);
            /** injection */
            if (1 == preg_match_all('/.*' . $component . '@inject(.*?)\;/', $content, $injections)) {
                $factories[$class] = preg_replace('/(.*)\$/', '', preg_replace('/(.*)\*\//', '', $injections[1][0]));
                $controls = explode(strrev('functioncreateComponent'), strrev($content));
                foreach ($controls as $control) {
                    if (0 < substr_count(strrev($control), $factories[$class])) {
                        $inject = explode('(', strrev($control));
                        $injected[$class] = array_shift($inject);
                        break;
                    }
                }
                isset($presenters[$class]) ? null : $presenters[$class] = [0 => $class];
            }
            Assert::true(2 > $declarations, 'You are using more classes in file ' . $file . '. Use one file for each class.');
            Assert::true(0 < $declarations, 'Class of ' . $file . ' is not set.' . $declarations);
            $i = 0;
            while ($namespace != 'Nette\Application\UI\Presenter') {
                $parents[$i++] = $namespace = get_parent_class($namespace);
            }
            Assert::false(empty($parents = array_reverse($parents)), 'Parent class is not set in ' . $class . '.');
            Assert::false(empty(array_shift($parents)), 'Unset nette presenter in ' . $class . ' failed.');
            foreach ($parents as $parent) {
                $presenters[$parent][] = $class;
            }
            $templates[$class] = $file;
        }
        $mocks = [];
        foreach ($factories as $factoryId => $factory) {
            foreach ($presenters[$factoryId] as $presenter) {
                preg_match_all('/(.*)\//', $templates[$presenter], $path);
                $template = preg_replace('/(.*)\\\|Presenter/', '', $presenter);
                $directory = $path[0][0] . '../templates/' . $template;
                if (is_dir($directory)) {
                    preg_match_all('/createComponent.*' . $factory . '/', $contents[$presenter], $components);
                    Assert::false(empty($lattes = scandir($directory)), 'Template directory for ' . $file . ' is empty.');
                    foreach ($lattes as $latte) {
                        if ('.' != $latte and '..' != $latte) {
                            Assert::true(is_file($file = $directory . '/' . $latte), $latte . ' in ' . $directory . ' is not a valid file.');
                            $content = file_get_contents($file);
                            Assert::false(0 < substr_count($content, '<?php') and '.latte' == substr($file, -6), 'Forgotten php mark in ' . $file);
                            Assert::true(2 > preg_match_all('/<form(.*)novalidate=""\>/', $content, $forms), 'Array for testing forms id is not set.');
                            Assert::true(is_array($forms), 'Array for testing forms id is not set.');
                            if (preg_match('/\{control ' . lcfirst($injected[$factoryId]) . '\}/', $content)) {
                                $mocks[$presenter] = $file;
                            }
                        }
                    }
                }
            }
        }
        $random = array_rand($mocks);
        Assert::true(is_array($this->config['mockService']['testParameters']), 'Mock service has no test parameters in config section.');
        return [$random => $this->getPresenter($random, $mocks[$random], $this->config['mockService']['testParameters'], true)];
    }

    public function getRequest() {
        return $this->httpRequest;
    }

    /** @return User */
    public function getUser() {
        if (null == $this->user) {
            $this->user = $this->container->getByType('Nette\Security\User');
            Assert::true(isset($this->config['mockService']['testUser']), 'Test user is not set.');
            Assert::true(isset($this->config['mockService']['testUser']['username']), 'Username for login of test user is not set.');
            Assert::true(isset($this->config['mockService']['testUser']['password']), 'Password for login of test user is not set.');
            Assert::false(empty($username = $this->config['mockService']['testUser']['username']), 'Assign credentials failed.');
            Assert::false(empty($password = $this->config['mockService']['testUser']['password']), 'Assign credentials failed.');
            Assert::same(null,  $this->user->login($username, $password), 'Mock login method succeed but it does return something. Do you wish to modify test?');
            Assert::true($this->user->isLoggedIn(), 'Test user is not loggged');
        }
        return $this->user;
    }


    /** @return mixed */
    public function getPrivateProperty($class, $order) {
        Assert::false(empty($serialization = (array) $class), 'Serialization failed.');
        Assert::false(empty($slice = array_slice($serialization, $order, 1)), 'There is no variable in given order.');
        return reset($slice);
    }
    
    public function getFiles($suffix, $regex = null) {
        $scan = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->config['appDir']));
        $files = [];
        foreach ($scan as $file) {
            if (!$file->isDir() and in_array(preg_replace('/(.*)\./', '', $file->getPathname()), $suffix)) {
                $name = basename($file->getPathname());
                if (is_string($regex) and preg_match('/' . $regex . '/', $name)) {
                    $files[] = $file->getPathname();
                } elseif (null == $regex) {
                    $files[] = $file->getPathname();
                }
            }
        }
        return $files;
    }

    public function getParameter($parameter, $parent) {
        if (0 < substr_count($parameter, '->')) {
            $variables = explode('->', $parameter);
            $call = array_shift($variables);
            $parameter = $$call;
            foreach ($variables as $variable) {
                if (isset($parameter->$variable)) {
                    $parameter = $parameter->$variable;
                } elseif (is_array($parameter) and isset($parameter[$variable])) {
                    $parameter = $parameter[$variable];
                } elseif (is_object($parameter) and
                        0 < substr_count($variable, '(') and
                        method_exists($parameter, preg_replace('/\((.*)\)/', '', $variable))) {
                    preg_match('/\((.*)\)/', $variable, $brackets);
                    $variable = preg_replace('/\((.*)\)/', '', $variable);
                    $parameter = $parameter->$variable($brackets[0]);
                }
            }
        }
        return $parameter;
    }

    public function setDependencies($class = false) {
        $services = isset($this->config['mockService']['services']) ? $this->config['mockService']['services'] : [];
        Assert::false(empty($services));
        foreach ($services as $serviceId => $service) {
            $this->services[$serviceId] = $service;
        }
        foreach (get_class_methods($this->container) as $method) {
            if (preg_match('/createService_(.*)/', $method) and preg_match('/Factory/', $method)) {
                $this->services[lcfirst(ltrim(preg_replace('/(.*)_/', '', $method), 'I'))] = $method;
            } elseif (preg_match('/createService_(.*)/', $method)) {
                $this->services[lcfirst(preg_replace('/(.*)_/', '', $method))] = $method;
            }
        }
    }

    private function setProtectedProperty($object, $property, $value) {
        $reflection = new \ReflectionClass($object);
        $protected = $reflection->getProperty($property);
        $protected->setAccessible(true);
        $protected->setValue($object, $value);
        return $object;
    }

    private function setPresenter() {
        Assert::true(is_object($this->router = new Application\Routers\RouteList), 'Router is not set.');
        $connection = new Connection($this->container->parameters['database']['dsn'], $this->container->parameters['database']['user'], $this->container->parameters['database']['password']);
        $temp = $this->config['tempDir'];
        $journal = new SQLiteJournal($temp);
        $this->cacheStorage = new FileStorage($temp, $journal);
        $structure = new Structure($connection, $this->cacheStorage);
        $this->context = new Context($connection, $structure);
        $latte = new Engine();
        $latte->onCompile[] = function($latte) {
            FormMacros::install($latte->getCompiler());
        };
        $latteFactory = Mockery::mock('Nette\Bridges\ApplicationLatte\ILatteFactory');
        $latteFactory->shouldReceive('create')->andReturn($latte);
        $this->templateFactory = new TemplateFactory($latteFactory, $this->httpRequest);
    }


}
