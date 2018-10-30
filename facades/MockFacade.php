<?php

namespace Masala;

use Mockery,
    Latte\Engine,
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
    Nette\Localization\ITranslator,
    Nette\Security\User,
    ReflectionClass,
    Tester\Assert;

/** @author Lubomir Andrisek */
final class MockFacade {

    /** @var Context */
    private $cacheStorage;

    /** @var array */
    private $config;

    /** @var Container */
    private $container;

    /** @var Context */
    private $context;

    /** @var MasalaExtension */
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

    /** @var ITranslator */
    public $translatorModel;

    /** @var User */
    private $user;

    /** @var IUser */
    private $usersModel;

    public function __construct(MasalaExtension $extension, Container $container, ITranslator $translatorModel, IUser $usersModel)  {
        $this->container = $container;
        $this->config = $container->getParameters();
        $this->extension = $extension;
        $this->translatorModel = $translatorModel;
        $this->usersModel = $usersModel;
    }

    public function getBuilder($source = 'Sale:Billing', $action = 'default'): IBuilder {
        $config = $this->extension->getConfiguration($this->config);
        $root = '/';
        $presenter = strtolower(preg_replace('/(.*)\:/', '', $source));
        $connection = new Connection($this->config['database']['dsn'], $this->config['database']['user'], $this->config['database']['password']);
        $journal = new SQLiteJournal($this->config['tempDir']);
        $cacheStorage = new FileStorage($this->config['tempDir'], $journal);
        $structure = new Structure($connection, $cacheStorage);
        $context = new Context($connection, $structure);
        $urlScript = new Http\UrlScript();
        $urlScript->setScriptPath($root);
        $urlScript->setPath($root . $presenter . '/' . $action);
        $this->httpRequest = new Http\Request($urlScript);
        $exportService = $this->container->getByType('Masala\ExportService');
        $builder = new Builder($config['masala'], $exportService, $context, $cacheStorage, $this->container->getByType('Masala\IRowFormFactory'), $this->translatorModel);
        return $builder;
    }

    public function getPresenter(string $class, string $latteFile, array $parameters): IPresenter {
        $this->init();
        Assert::true(class_exists($class), 'Class ' . $class . ' not found.');
        Assert::false(empty($source = preg_replace('/(App|Module|Presenter|)/', '', $class)), 'Name of presenter is empty.');
        Assert::true(is_string($name = preg_replace('/\\\/', ':', $source)), 'Name of presenter is not set.');
        Assert::false(empty($name = ltrim($name, ':')), 'Name of presenter is empty');
        $mocks = empty($_POST) ? $class . '[getName,redirect]' : $class . '[getName,redirect,sendResponse]';
        $presenter = Mockery::mock($mocks, ['__get']);
        $action = preg_replace('/(.*)\/|\.latte/', '', $latteFile);
        if (property_exists($presenter, 'grid')) {
            $presenter->grid = $this->getBuilder($name, $action);
        }
        Assert::false(empty($services = get_object_vars($this->container->getByType($class))), 'Tested presenter has no service to inject');
        foreach($services as $serviceId => $method) {
            $presenter->$serviceId = $method;
        }
        $presenter->shouldReceive('redirect')->andReturn('redirect succeed');
        $presenter->shouldReceive('getName')->andReturn($name);
        $presenter->shouldReceive('sendResponse')->andReturn('response succeed');
        $presenter->shouldReceive('onShutdown');
        $presenter->shouldReceive('onStartup');
        $urlScript = new Http\UrlScript();
        foreach ($parameters as $key => $value) {
            $urlScript->setQueryParameter($key, $value);
        }
        $this->httpRequest = new Http\Request($urlScript);
        $this->httpResponse =  $this->container->getByType('Nette\Http\IResponse');
        $presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
        Assert::true((is_file($latteFile) || isset($parameters['action'])), 'Latte file ' . $latteFile . ' is not set.');
        $presenter->injectPrimary($this->container, $presenterFactory, $this->router, $this->httpRequest, $this->httpResponse, $this->session, $this->getUser(true), $this->templateFactory);
        $presenter->template->setFile($latteFile);
        $presenter->autoCanonicalize = FALSE;
        Assert::same('App\\', substr($class, 0, 4), 'Presenter is not in App namespace.');
        Assert::true(is_string($presenterRequest = preg_replace('/Module|Presenter/', '', str_replace('\\', ':', substr($class, 4, strlen($class) - 4)))), 'Presenter ' . $class . ' request was not set');
        Assert::true(is_string($presenterAction = preg_replace('/(.*)\/|.latte/', '', $latteFile)), 'Presenter action for latte file ' . $latteFile . ' was not set.');
        if(empty($_POST)) {
            $request = new Application\Request($presenterRequest, 'GET', array_merge(['action' => $presenterAction], $parameters));
        } else {
            $request = new Application\Request($presenterRequest, 'POST', array_merge(['action' => $presenterAction], $parameters), $_POST);
        }
        $presenter->run($request);
        return $presenter;
    }

    public function getPresentersByComponent(string $component): array {
        $this->init();
        $files = $this->getFiles(['php'], 'Presenter');
        $factories = [];
        $contents = [];
        $injected = [];
        $templates = [];
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
        Assert::true(is_array($this->config['masala']['tests']['parameters']), 'Mock service has no test parameters in config section.');
        return [$random => $this->getPresenter($random, $mocks[$random], $this->config['masala']['tests']['parameters'], true)];
    }

    public function getPresentersByService(string $service): array {
        $this->init();
        $files = $this->getFiles(['php'], 'Presenter');
        $factories = [];
        $contents = [];
        $injected = [];
        $templates = [];
        $presenters = [];
        $this->presenters = [];
        foreach($this->container->findByType('Masala\IImport') as $name) {
            $method = 'createService__' . $name;
            Assert::false(empty($injection = preg_replace('/(.*)\\\/', '', get_class($this->container->$method()))), 'Service name is empty.');
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
                if (1 == preg_match_all('/.*' . $injection . '@inject(.*?)\;/', $content, $injections)) {
                    $factories[$class] = preg_replace('/(.*)\$/', '', preg_replace('/(.*)\*\//', '', $injections[1][0]));
                    $controls = explode('publicfunctionaction', $content);
                    unset($controls[0]);
                    foreach ($controls as $control) {
                        if(substr_count($control, $factories[$class]) > 0) {
                            $injected[$class] = strtolower(preg_replace('/\((.*)/', '', $control));
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
        }
        $random = array_rand($injected);
        Assert::false(empty($random), 'No injection found for ' . $service);
        Assert::true(is_array($parameters = $this->config['masala']['tests']['parameters']), 'Mock service has no test parameters in config section.');
        $parameters['action'] = $injected[$random];
        return [$random => $this->getPresenter($random, $parameters['action'], $parameters)];
    }

    public function getUser(): User {
        if (null == $this->user) {
            $this->user = $this->container->getByType('Nette\Security\User');
            Assert::true(isset($this->config['masala']['tests']['user']), 'Test user is not set.');
            Assert::true(isset($this->config['masala']['tests']['user']['username']), 'Username for login of test user is not set.');
            Assert::true(isset($this->config['masala']['tests']['user']['password']), 'Password for login of test user is not set.');
            Assert::false(empty($username = $this->config['masala']['tests']['user']['username']), 'Assign credentials failed.');
            Assert::false(empty($password = $this->config['masala']['tests']['user']['password']), 'Assign credentials failed.');
            Assert::false(empty($id = $this->config['masala']['tests']['user']['id']), 'Assign credentials failed.');
            Assert::true(is_object($user = $this->usersModel->getUser($id)), 'Test user was not found.');
            Assert::same(null,  $this->user->login($user->$username, $password), 'Mock login method succeed but it does return something. Do you wish to modify test?');
            Assert::true($this->user->isLoggedIn(), 'Test user is not loggged');
        }
        return $this->user;
    }

    /** @return mixed */
    public function getPrivateProperty(object $class, int $order) {
        Assert::false(empty($serialization = (array) $class), 'Serialization failed.');
        Assert::false(empty($slice = array_slice($serialization, $order, 1)), 'There is no variable in given order.');
        return reset($slice);
    }
    
    private function getFiles(array $suffix, string $regex): array {
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

    public function getParameter(string $parameter, object $parent): string {
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

    private function init(): void {
        if(null == $this->templateFactory) {
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
 
    public function setPost(array $post): void {
        file_put_contents('php://memory', 'PHP');
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "Masala\Stream");
        file_put_contents('php://input', json_encode($post));

    }

    private function setProtectedProperty($object, $property, $value) {
        $reflection = new ReflectionClass($object);
        $protected = $reflection->getProperty($property);
        $protected->setAccessible(true);
        $protected->setValue($object, $value);
        return $object;
    }

}

final class Stream {

    protected $index = 0;
    protected $length = null;
    protected $data = 'hello world';

    public $context;

    function __construct(){
        if(file_exists($this->buffer_filename())){
            $this->data = file_get_contents($this->buffer_filename());
        }else{
            $this->data = '';
        }
        $this->index = 0;
        $this->length = strlen($this->data);
    }

    protected function buffer_filename(){
        return sys_get_temp_dir().'\php_input.txt';
    }

    function stream_open($path, $mode, $options, &$opened_path){
        return true;
    }

    function stream_close(){
    }

    function stream_stat(){
        return array();
    }

    function stream_flush(){
        return true;
    }

    function stream_read($count){
        if(is_null($this->length) === TRUE){
            $this->length = strlen($this->data);
        }
        $length = min($count, $this->length - $this->index);
        $data = substr($this->data, $this->index);
        $this->index = $this->index + $length;
        return $data;
    }

    function stream_eof(){
        return ($this->index >= $this->length ? TRUE : FALSE);
    }

    function stream_write($data){
        return file_put_contents($this->buffer_filename(), $data);
    }

    function unlink(){
        if(file_exists($this->buffer_filename())){
            unlink($this->buffer_filename());
        }
        $this->data = '';
        $this->index = 0;
        $this->length = 0;
    }
}
