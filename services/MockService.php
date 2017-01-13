<?php

namespace Masala;

use Latte,
    Models\TranslatorModel,
    Nette\DI\Container,
    Nette\Http,
    Nette\Application,
    Nette\Bridges,
    Nette\Caching\Storages\SQLiteJournal,
    Nette\Caching\Storages\FileStorage,
    Nette\Database\Connection,
    Nette\Database\Context,
    Nette\Database\Structure,
    Mockery,
    Security\User,
    Sunra\PhpSimple\HtmlDomParser,
    Tester\Assert;

final class MockService {

    /** @var Container */
    private $container;

    /** @var TranslatorModel */
    public $translatorModel;

    /** @var User */
    private $user;

    /** @var Context */
    private $cacheStorage;

    /** @var Context */
    private $context;

    /** @var Http\Request */
    private $httpRequest;

    /** @var Http\Response */
    private $httpResponse;

    /** @var Http\Session */
    private $session;

    /** @var Application\Routers\RouteList */
    private $router;

    /** @var Bridges\ApplicationLatte\TemplateFactory */
    private $templateFactory;

    /** @var Array */
    private $config;

    /** @var Array */
    private $services;

    public function __construct(Container $container, TranslatorModel $translatorModel) {
        $this->container = $container;
        $this->config = $container->getParameters();
        $this->translatorModel = $translatorModel;
    }

    /** setters */
    private function setProtectedProperty($object, $property, $value) {
        $reflection = new \ReflectionClass($object);
        $protected = $reflection->getProperty($property);
        $protected->setAccessible(true);
        $protected->setValue($object, $value);
        return $object;
    }

    private function setPresenter() {
        /** user */
        Assert::true(is_object($this->router = new Application\Routers\RouteList), 'Router is not set.');
        $this->getUser();
        /** database */
        $connection = new Connection('mysql:host=localhost;dbname=cz_4camping', 'worker', 'dokempu');
        $temp = WWW_DIR . '/tests/temp';
        $journal = new SQLiteJournal($temp);
        $this->cacheStorage = new FileStorage($temp, $journal);
        $structure = new Structure($connection, $this->cacheStorage);
        $this->context = new Context($connection, $structure);
        /** template */
        $latte = new Latte\Engine();
        $latte->onCompile[] = function($latte) {
            Bridges\FormsLatte\FormMacros::install($latte->getCompiler());
        };
        $latteFactory = Mockery::mock('Nette\Bridges\ApplicationLatte\ILatteFactory');
        $latteFactory->shouldReceive('create')->andReturn($latte);
        $this->templateFactory = new Bridges\ApplicationLatte\TemplateFactory($latteFactory, $this->httpRequest);
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

    /** getters */
    public function getUser() {
        if (null == $this->user) {
            $urlScript = new Http\UrlScript;
            $this->httpRequest = new Http\Request($urlScript);
            $this->httpResponse = new Http\Response;
            $this->session = new Http\Session($this->httpRequest, $this->httpResponse);
            $userStorage = new Http\UserStorage($this->session);
            $this->user = new User($userStorage);
        }
        return $this->user;
    }

    public function getConfig(Array $keys) {
        $config = $this->config;
        foreach ($keys as $key) {
            $config = $config[$key];
        }
        return $config;
    }

    public function getRequest() {
        return $this->httpRequest;
    }

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
            Assert::true(0 === substr_count($content, 'this->template->userSetting') or 'App\BasePresenter' == $class, 'Latte variable userSetting is reserved for call in App/BasePresenter:[beforeRender] but it has been called in ' . $file);
            $contents[$class] = $content;
            fclose($handle);
            /** injection */
            if (1 == preg_match_all('/.*' . $component . '(.*?)\;/', $content, $injections)) {
                $factories[$class] = preg_replace('/(.*)\$/', '', preg_replace('/(.*)\*\//', '', $injections[1][0]));
                $controls = explode(strrev('functioncreateComponent'), strrev($content));
                foreach ($controls as $control) {
                    if (0 < substr_count(strrev($control), $factories[$class])) {
                        $inject = explode('(', strrev($control));
                        $injected[$class] = array_shift($inject);
                        break;
                    }
                }
                /** $injected[$class] = (empty($components[0])) ? false : preg_replace('/\(\)(.*)/', '', preg_replace('/(.*)createComponent/', '', $components[0][0])); */
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
            /* Assert::true(is_object($presenter = new $namespace));
              Assert::true(is_object($presenter = Mockery::mock($namespace . '[redirect]'))); */
        }
        /** mocking */
        $mocks = [];
        foreach ($factories as $factoryId => $factory) {
            foreach ($presenters[$factoryId] as $presenter) {
                preg_match_all('/(.*)\//', $templates[$presenter], $path);
                $template = preg_replace('/(.*)\\\|Presenter/', '', $presenter);
                $directory = $path[0][0] . '../templates/' . $template;
                if (is_dir($directory)) {
                    preg_match_all('/createComponent.*' . $factory . '/', $contents[$presenter], $components);
                    $localInjection = (empty($components[0])) ? false : preg_replace('/\(\)(.*)/', '', preg_replace('/(.*)createComponent/', '', $components[0][0]));
                    $template = preg_replace('/(.*)\\\|Presenter/', '', $presenter);
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
        return [$random => $this->getPresenter($random, $mocks[$random], [], true)];
    }

    public function getPresenter($class, $latteFile, $parameters = [], $injected = false) {
        if (false == $injected) {
            $this->setPresenter();
            $this->setDependencies($class);
        }
        echo 'testing ' . $class . "\n";
        Assert::false(empty($source = preg_replace('/(App|Module|Presenter|)/', '', $class)), 'Name of presenter is empty.');
        Assert::true(is_string($name = preg_replace('/\\\/', ':', $source)), 'Name of presenter is not set.');
        Assert::false(empty($name = ltrim($name, ':')), 'Name of presenter is empty');
        $presenter = Mockery::mock($class . '[redirect,getName]', ['__get']);
        $presenter->setting = new RowBuilder($this->container->parameters, $this->context, $this->cacheStorage);
        $action = preg_replace('/(.*)\/|\.latte/', '', $latteFile);
        $presenter->grid = $this->getBuilder($name, $action);
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
            } elseif (class_exists($method)) {
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
        /** mock redirect */
        $presenter->shouldReceive('redirect')->andReturn('redirect succeed');
        $presenter->shouldReceive('getName')->andReturn($name);
        $presenter->shouldReceive('sendResponse')->andReturn(true);
        $presenter->shouldReceive('onShutdown');
        $urlScript = new Http\UrlScript();
        foreach ($parameters as $key => $value) {
            $urlScript->setQueryParameter($key, $value);
        }
        $this->httpRequest = new Http\Request($urlScript);
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        Assert::true(is_file($latteFile) or isset($parameters['action']), 'Latte file ' . $latteFile . ' is not set.');
        $presenter->injectPrimary($this->container, $presenterFactory, $this->router, $this->httpRequest, $this->httpResponse, $this->session, $this->user, $this->templateFactory);
        $presenter->template->setFile($latteFile);
        $presenter->autoCanonicalize = FALSE;
        Assert::same('App\\', substr($class, 0, 4), 'Presenter is not in App namespace.');
        Assert::true(is_string($presenterRequest = preg_replace('/Module|Presenter/', '', str_replace('\\', ':', substr($class, 4, strlen($class) - 4)))), 'Presenter ' . $class . ' request was not set');
        Assert::true(is_string($presenterAction = preg_replace('/(.*)\/|.latte/', '', $latteFile)), 'Presenter action for latte file ' . $latteFile . ' was not set.');
        $request = new Application\Request($presenterRequest, 'GET', array_merge(['action' => $presenterAction], $parameters));
        $presenter->run($request);
        return $presenter;
    }

    public function getService($class) {
        $this->setDependencies();
        Assert::true(class_exists($class), 'Try to instantiate non exist class ' . $class);
        Assert::true(is_object($reflection = new \ReflectionClass($class)), 'Reflection of ' . $class . ' failed.');
        Assert::true(is_object($constructor = $reflection->getConstructor()), 'Reflection of Constructor of ' . $class . ' failed.');
        $dependencies = [];
        $i = 0;
        foreach ($constructor->getParameters() as $parameter) {
            Assert::true(is_object($parameter), 'Reflection of parameter in constructor ' . $class . ' is not object.');
            if (isset($this->services[$parameter->getName()])) {
                Assert::false(empty($method = $this->services[$parameter->getName()]), 'Dependency ' . $parameter->getName() . ' was not set as service.');
                Assert::false(empty($dependencies[] = $this->container->$method()), 'Dependency ' . $parameter->getName() . ' creation failed.');
            } elseif ('grid' == $parameter->getName()) {
                Assert::false(empty($dependencies[] = $this->getBuilder()), 'Source for model ' . $class . ' was not set as parameter.');
            } elseif ('setting' == $parameter->getName()) {
                $connection = new Connection($this->config['database']['dsn'], $this->config['database']['user'], $this->config['database']['password']);
                $cacheStorage = new FileStorage(__DIR__ . $this->config['mockService']['temp']);
                $structure = new Structure($connection, $cacheStorage);
                $context = new Context($connection, $structure, null, $cacheStorage);
                Assert::false(empty($dependencies[] = new RowBuilder($this->config['masala'], $context, $cacheStorage)), 'Source for model ' . $class . ' was not set as parameter.');
            } elseif ('storage' == $parameter->getName()) {
                Assert::false(empty($dependencies[] = new FileStorage(__DIR__ . '/../../../../tests/temp')), 'Storage was not set.');
            } elseif ('database' == $parameter->getName()) {
                $connection = new Connection($this->config['database']['dsn'], $this->config['database']['user'], $this->config['database']['password']);
                $cacheStorage = new FileStorage(__DIR__ . $this->config['mockService']['temp']);
                $structure = new Structure($connection, $cacheStorage);
                Assert::false(empty($dependencies[] = new Context($connection, $structure, null, $cacheStorage)), 'Context was not set.');
            } elseif ('source' == $parameter->getName()) {
                Assert::false(empty($dependencies[] = $this->config['tables'][lcfirst(preg_replace('/(.*)\\\|Model/', '', $class))]), 'Source for model ' . $class . ' was not set as parameter.');
            } elseif (isset($this->config['tables'][$parameter->getName()])) {
                Assert::false(empty($dependencies[] = $this->config['tables'][$parameter->getName()]), 'Source for model ' . $class . ' was not set as parameter.');
            } elseif (isset($this->config[lcfirst(preg_replace('/(.*)\\\/', '', $class))])) {
                Assert::false(empty($dependencies[] = $this->config[lcfirst(preg_replace('/(.*)\\\/', '', $class))]), 'Source for model ' . $class . ' was not set as parameter.');
            } else {
                Assert::same(1, lcfirst(preg_replace('/(.*)\\\/', '', $class)));
                Assert::false(empty($dependencies[] = $this->config[$parameter->getName()]), 'Parameter ' . $parameter->getName() . ' was not set as parameter.');
            }
            Assert::notSame($i, count($dependencies), 'Increment of dependencies for constructor failed on ' . $parameter->getName());
            $i++;
        }
        $callback = function () use ($class) {
            return [$class, '__construct'];
        };
        Assert::true(is_object($service = new $class(...$dependencies)));
        return $service;
    }

    public function getFiles($suffix, $regex = null) {
        $scan = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(WWW_DIR . '/app'));
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

    public function getBuilder($source = 'Sale:Billing', $action = 'default') {
        $root = '/';
        $module = preg_replace('/\:(.*)/', '', $source);
        $presenter = strtolower(preg_replace('/(.*)\:/', '', $source));
        $connection = new Connection($this->config['database']['dsn'], $this->config['database']['user'], $this->config['database']['password']);
        $journal = new SQLiteJournal(__DIR__ . $this->config['mockService']['temp']);
        $cacheStorage = new FileStorage(__DIR__ . $this->config['mockService']['temp'], $journal);
        $structure = new Structure($connection, $cacheStorage);
        $context = new Context($connection, $structure);
        $translatorModel = new TranslatorModel($this->config['tables']['translator'], $context, $cacheStorage);
        $urlScript = new Http\UrlScript();
        $urlScript->setScriptPath($root);
        $urlScript->setPath($root . $presenter . '/');
        $this->httpRequest = new Http\Request($urlScript);
        $exportService = $this->container->getByType('Masala\ExportService');
        $migrationService = $this->container->getByType('Masala\MigrationService');
        $router = new Application\Routers\RouteList($module);
        $router[] = new Application\Routers\Route('<presenter>/<action>', $presenter . ':' . $action);
        $linkGenerator = new Application\LinkGenerator($router, $this->httpRequest->getUrl());
        $dom = new HtmlDomParser();
        $builder = new NetteBuilder($this->config['masala'], $translatorModel, $exportService, $dom, $migrationService, $this, $context, $cacheStorage, $this->httpRequest, $linkGenerator);
        return $builder;
    }

}
