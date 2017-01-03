<?php

namespace Masala;

use Nette\DI\CompilerExtension,
	Nette\PhpGenerator\ClassType;

class BuilderExtension extends CompilerExtension {

    public function loadConfiguration() {
    }

    public function beforeCompile() {
        parent::beforeCompile();
    }

    public function afterCompile(ClassType $class) {
        $initialize = $class->methods['initialize'];
        $initialize->addBody('Nette\Forms\Container::extensionMethod("\Nette\Forms\Container::addRange", function (\Nette\Forms\Container $_this, $name, $label = null, $defaults) { return $_this[$name] = new Masala\Range($label, $defaults, $this->getByType(?)); });', ['Nette\Localization\ITranslator']);
    }

}