<?php

namespace Masala;

use Exception,
    Nette\DI\CompilerExtension,
    Nette\PhpGenerator\ClassType;

final class MasalaExtension extends CompilerExtension {

    private $defaults = ['assets' => 'assets/masala',
        'exportSpeed' => 20,
        'importSpeed' => 100,
        'feeds' => 'feeds',
        'format' => ['date' => ['edit' => 'Y-m-d', 'query'=> 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")', 'text' => 'd.m.Y'],
                    'time' => ['edit' => 'Y-m-d H:i:s', 'query' => 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")', 'text' => 'd.m.Y H:i:s']],
        'help' => 'help',
        'npm' => 'bower',
        'keywords' => 'keywords',
        'log' => 'log',
        'pagination' => 20,
        'spice' => 'spice',
        'settings' => 'settings',
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
                ->setClass('Masala\Builder', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('masalaExtension'))
                ->setClass('Masala\MasalaExtension', []);
        $builder->addDefinition($this->prefix('contentForm'))
                ->setClass('Masala\ContentForm', [$manifest['ContentForm.js']]);
        $builder->addDefinition($this->prefix('editForm'))
                ->setClass('Masala\EditForm', [$manifest['EditForm.js'], $parameters['masala']['upload']]);
        $builder->addDefinition($this->prefix('exportService'))
                ->setClass('Masala\ExportService', [$builder->parameters['tempDir']]);
        $builder->addDefinition($this->prefix('emptyRow'))
                ->setClass('Masala\EmptyRow');
        $builder->addDefinition($this->prefix('grid'))
                ->setClass('Masala\Grid', [$parameters['appDir'], $manifest['Grid.js'], $parameters['masala']]);
        $builder->addDefinition($this->prefix('filterForm'))
                ->setClass('Masala\FilterForm', ['']);
        $builder->addDefinition($this->prefix('importForm'))
                ->setClass('Masala\ImportForm', [$manifest['ImportForm.js']]);
        $builder->addDefinition($this->prefix('helpRepository'))
                ->setClass('Masala\HelpRepository', [$parameters['masala']['help']]);
        $builder->addDefinition($this->prefix('keywordsRepository'))
                ->setClass('Masala\KeywordsRepository', [$parameters['masala']['keywords']]);
        $builder->addDefinition($this->prefix('masala'))
                ->setClass('Masala\Masala', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('mockRepository'))
                ->setClass('Masala\MockRepository');
        $builder->addDefinition($this->prefix('mockService'))
                ->setClass('Masala\MockService');
        $builder->addDefinition($this->prefix('processForm'))
                ->setClass('Masala\ProcessForm', [$manifest['ProcessForm.js']]);
        $builder->addDefinition($this->prefix('row'))
                ->setClass('Masala\Row', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('writeRepository'))
                ->setClass('Masala\WriteRepository', [$parameters['masala']['write']]);
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
