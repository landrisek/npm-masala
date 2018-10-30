<?php

namespace Masala;

use Exception,
    Nette\DI\CompilerExtension,
    Nette\PhpGenerator\ClassType;

/** @author Lubomir Andrisek */
final class MasalaExtension extends CompilerExtension {

    private $defaults = ['assets' => 'assets/masala',
        'css' => 'assets/masala/css',
        'feeds' => 'feeds',
        'format' => ['date' => ['build' => 'd.m.Y', 'query'=> 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")'],
                    'time' => ['build' => 'Y-m-d H:i:s', 'query' => 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")']],
        'help' => 'help',
        'npm' => 'bower',
        'keywords' => 'keywords',
        'log' => 'log',
        'pagination' => 20,
        'settings' => 'settings',
        'speed' => 50,
        'spice' => 'spice',
        'tests' => ['user' => ['id' => 1, 'password' => 'password', 'username' => 'username'],
                    'parameters' => ['date' => '2017-4-12', 'id' => 4574, 'limit' => 10]],
        'upload' => 10,
        'write' => 'write'];

    public function getConfiguration(array $parameters) {
        foreach($this->defaults as $key => $parameter) {
            if(!isset($parameters['masala'][$key])) {
                $parameters['masala'][$key] = $parameter;
            }
        }
        return $parameters;
    }
    
    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $parameters = $this->getConfiguration($builder->parameters);
        $manifest = (array) json_decode(file_get_contents($parameters['wwwDir'] . '/' . $parameters['masala']['assets'] . '/js/manifest.json'));
        $builder->addDefinition($this->prefix('Builder'))
                ->setFactory('Masala\Builder', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('masalaExtension'))
                ->setFactory('Masala\MasalaExtension', []);
        $builder->addDefinition($this->prefix('contentForm'))
                ->setFactory('Masala\ContentForm', [$manifest['ContentForm.js']]);
        $builder->addDefinition($this->prefix('exportService'))
                ->setFactory('Masala\ExportService', [$builder->parameters['tempDir']]);
        $builder->addDefinition($this->prefix('emptyRow'))
                ->setFactory('Masala\EmptyRow');
        $builder->addDefinition($this->prefix('grid'))
                ->setFactory('Masala\Grid', [$parameters['appDir'], $manifest['Grid.js'], $parameters['masala']]);
        $builder->addDefinition($this->prefix('filterForm'))
                ->setFactory('Masala\FilterForm', [$parameters['masala']['css'], '']);
        $builder->addDefinition($this->prefix('importForm'))
                ->setFactory('Masala\ImportForm', [$parameters['masala']['css'], $manifest['ImportForm.js']]);
        $builder->addDefinition($this->prefix('helpRepository'))
                ->setFactory('Masala\HelpRepository', [$parameters['masala']['help']]);
        $builder->addDefinition($this->prefix('keywordsRepository'))
                ->setFactory('Masala\KeywordsRepository', [$parameters['masala']['keywords']]);
        $builder->addDefinition($this->prefix('masala'))
                ->setFactory('Masala\Masala', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('mockRepository'))
                ->setFactory('Masala\MockRepository');
        $builder->addDefinition($this->prefix('mockFacade'))
                ->setFactory('Masala\MockFacade');
        $builder->addDefinition($this->prefix('rowForm'))
                ->setFactory('Masala\RowForm', [$parameters['masala']['css'], $manifest['RowForm.js']]);
        $builder->addDefinition($this->prefix('writeRepository'))
                ->setFactory('Masala\WriteRepository', [$parameters['masala']['write']]);
    }

    public function beforeCompile() {
        if(!class_exists('Nette\Application\Application')) {
            throw new MissingDependencyException('Please install and enable https://github.com/nette/nette.');
        }
        parent::beforeCompile();
    }

    public function afterCompile(ClassType $class) {
    }

}

class MissingDependencyException extends Exception { }
