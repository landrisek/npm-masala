<?php

namespace Masala;

use Nette\DI\CompilerExtension,
    Nette\PhpGenerator\ClassType;

class BuilderExtension extends CompilerExtension {

    private $defaults = ['bower' => 'bower_modules',
        'exportSpeed' => 20,
        'feeds' => 'feeds',
        'help' => 'help',
        'log' => 'log',
        'pagination' => 20,
        'root' => '/app',
        'spice' => 'spice',
        'user' => 'settings',
        'upload' => 10,
        'views' => 'view'];

    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $parameters = $builder->parameters;
        foreach($this->defaults as $key => $parameter) {
            if(!isset($parameters['masala'][$key])) {
                $parameters['masala'][$key] = $parameter;
            }
        }
        $builder->addDefinition($this->prefix('filterForm'))
                ->setClass('Masala\FilterForm');
        $builder->addDefinition($this->prefix('importForm'))
                ->setClass('Masala\ImportForm');
        $builder->addDefinition($this->prefix('editForm'))
                ->setClass('Masala\EditForm', [$parameters['masala']['upload']]);
        $builder->addDefinition($this->prefix('exportService'))
                ->setClass('Masala\ExportService', [$builder->parameters['tempDir'], $parameters['masala']['exportSpeed']]);
        $builder->addDefinition($this->prefix('helpModel'))
                ->setClass('Masala\HelpModel', [$parameters['masala']['help']]);
        $builder->addDefinition($this->prefix('masala'))
                ->setClass('Masala\Masala', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('mockModel'))
                ->setClass('Masala\MockModel');
        $builder->addDefinition($this->prefix('netteBuilder'))
                ->setClass('Masala\NetteBuilder', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('rowBuilder'))
                ->setClass('Masala\RowBuilder', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('spiceModel'))
                ->setClass('Masala\SpiceModel', [$parameters['masala']['spice']]);
        $builder->addDefinition($this->prefix('migrationService'))
                ->setClass('Masala\MigrationService', [$parameters['masala']['views']]);
        $builder->addDefinition($this->prefix('mockService'))
                ->setClass('Masala\MockService', []);
    }

    public function beforeCompile() {
        parent::beforeCompile();
    }

    public function afterCompile(ClassType $class) {
        $initialize = $class->methods['initialize'];
        $initialize->addBody('Nette\Forms\Container::extensionMethod("\Nette\Forms\Container::addRange", function (\Nette\Forms\Container $_this, $name, $label = null, $defaults) { return $_this[$name] = new Masala\Range($label, $defaults, $this->getByType(?)); });', ['Nette\Localization\ITranslator']);
    }

}
