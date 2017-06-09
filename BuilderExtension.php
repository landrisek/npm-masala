<?php

namespace Masala;

use Exception,
    Nette\DI\CompilerExtension,
    Nette\PhpGenerator\ClassType;

class BuilderExtension extends CompilerExtension {

    private $defaults = ['assets' => 'assets/masala',
        'exportSpeed' => 20,
        'feeds' => 'feeds',
        'help' => 'help',
        'log' => 'log',
        'pagination' => 20,
        'spice' => 'spice',
        'user' => 'settings',
        'upload' => 10];

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
        $manifest = (array) json_decode(file_get_contents($parameters['wwwDir'] . '/' . $parameters['masala']['assets'] . '/manifest.json'));
        $builder->addDefinition($this->prefix('editForm'))
            ->setClass('Masala\EditForm', [$manifest['EditForm.js'], $parameters['masala']['upload']]);
        $builder->addDefinition($this->prefix('exportService'))
            ->setClass('Masala\ExportService', [$builder->parameters['tempDir']]);
        $builder->addDefinition($this->prefix('grid'))
                ->setClass('Masala\Grid', [$parameters['appDir'], $manifest['Grid.js']]);
        $builder->addDefinition($this->prefix('filterForm'))
                ->setClass('Masala\FilterForm', ['']);
        $builder->addDefinition($this->prefix('importForm'))
                ->setClass('Masala\ImportForm', [$manifest['ImportForm.js']]);
        $builder->addDefinition($this->prefix('helpModel'))
                ->setClass('Masala\HelpModel', [$parameters['masala']['help']]);
        $builder->addDefinition($this->prefix('masala'))
                ->setClass('Masala\Masala', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('mockModel'))
                ->setClass('Masala\MockModel');
        $builder->addDefinition($this->prefix('netteBuilder'))
                ->setClass('Masala\NetteBuilder', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('processForm'))
                ->setClass('Masala\ProcessForm', [$manifest['ProcessForm.js']]);
        $builder->addDefinition($this->prefix('rowBuilder'))
                ->setClass('Masala\RowBuilder', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('spiceModel'))
                ->setClass('Masala\SpiceModel', [$parameters['masala']['spice']]);
        $builder->addDefinition($this->prefix('mockService'))
                ->setClass('Masala\MockService', []);
        $builder->addDefinition($this->prefix('builderExtension'))
                ->setClass('Masala\BuilderExtension', []);
    }

    public function beforeCompile() {
        if(!class_exists('Nette\Application\Application')) {
            throw new MissingDependencyException('Please install and enable https://github.com/nette/nette.');
        }
        parent::beforeCompile();
    }

    public function afterCompile(ClassType $class) {
        $initialize = $class->methods['initialize'];
        $initialize->addBody('Nette\Forms\Container::extensionMethod("\Nette\Forms\Container::addRange", function (\Nette\Forms\Container $_this, $name, $label = null, $defaults) { return $_this[$name] = new Masala\Range($label, $defaults, $this->getByType(?)); });', ['Nette\Localization\ITranslator']);
    }

}

class MissingDependencyException extends Exception { }
