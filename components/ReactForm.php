<?php

namespace Masala;

use Nette\Application\UI\ISignalReceiver,
    Nette\Application\IPresenter,
    Nette\Application\UI\Control,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
class ReactForm extends Control implements IReactFormFactory {

    /** @var array */
    private $data;

    /** @var string */
    private $jsDir;

    /** @var IRequest */
    private $request;

    public function __construct($jsDir, IRequest $request) {
        $this->jsDir = $jsDir;
        $this->request = $request;
    }

    /** @return IReactFormFactory */
    public function create() {
        return $this;
    }

    /** @return IReactFormFactory */
    public function attached($presenter) {
        parent::attached($presenter);
    }

    /** @return IReactFormFactory */
    private function add($key, $label, $method, $tag, array $attributes = [], array $validators = []) {
        $validations = [];
        foreach($validators as $validatorId => $validator) {
            $validations[$validatorId] = ['value' => ucfirst($validator), 'style' => ['display' => 'none']];
        }
        $attributes['id'] = $key;
        foreach($attributes as $attributeId => $attribute) {
            if(null === $attribute) {
                unset($attributes[$attributeId]);
            /** keep given order in javascript */    
            } elseif ('data' == $attributeId && is_array($attribute)) {
                foreach($attribute as $overwriteId => $overwrite) {
                    $attributes[$attributeId]['_' . $overwriteId] = $overwrite;
                    unset($attributes[$attributeId][$overwriteId]);
                }
            }
        }
        $this->data[$key] = ['Attributes' => $attributes,
                            'Label' => $label,
                            'Method' => $method, 
                            'Validators' => $validations,
                            'Tag' => $tag];
        return $this;
    }

    /** @return IReactFormFactory */
    public function addAction($key, $label, array $attributes = [], array $validators = []) {
        $attributes['style']['marginLeft'] = '10px';
        return $this->add($key, $label, __FUNCTION__, 'a', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addCheckbox($key, $label, array $attributes = [], array $validators = []) {
        $attributes['type'] = 'checkbox';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addDateTime($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label, 'addDateTime', 'input', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addEmpty($key, $label, array $attributes = []) {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    private function addHandlers(array $links) {
        $handlers = [];
        if($this->getParent() instanceof ISignalReceiver) {
            $methods = array_flip(get_class_methods($this->getParent()));
            $calls = array_flip(get_class_methods($this));
            foreach($links as $link) {
                if($this instanceof ISignalReceiver && isset($calls['handle' . ucfirst($link)])) {
                    $handlers[$link] = $this->link($link);
                } else if(isset($methods['handle' . ucfirst($link)]) and $this->getParent() instanceof IPresenter) {
                    $handlers[$link] = $this->getParent()->link('this', ['do' => $link]);
                } elseif(isset($methods['handle' . ucfirst($link)])) {
                    $handlers[$link] = $this->getParent()->link($link);
                }
            }
        }
        return $handlers;
    }

    /** @return IReactFormFactory */
    public function addHidden($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addMessage($key, $label, array $attributes = []) {
        $attributes['style'] = ['display' => 'none'];
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    /** @return IReactFormFactory */
    public function addMultiSelect($key, $label, array $attributes = [], array $validators = []) {
        if(null == $attributes['value']) {
            $attributes['value'] = [];
        }
        return $this->add($key, $label, __FUNCTION__, 'select', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addMultiUpload($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addRadioList($key, $label, array $attributes = [], array $validators = []) {
        $attributes['type'] = 'radio';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addSubmit($key, $label, array $attributes = []) {
        $attributes['type'] = 'submit';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    /** @return IReactFormFactory */
    public function addSelect($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label, __FUNCTION__, 'select', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addProgressBar($key, $label = '',  array $attributes = [], array $validators = []) {
        $attributes['width'] = 0;
        return $this->add($key . '-progress', $label, __FUNCTION__, 'div', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addTitle($key, $label,  $attributes) {
        return $this->add($key, $label,  __FUNCTION__, 'div', $attributes);
    }
    
    /** @return IReactFormFactory */
    public function addText($key, $label, array $attributes = [], array $validators = []) {
        $attributes['type'] = isset($attributes['type']) ? $attributes['type'] : 'text';
        return $this->add($key, $label,__FUNCTION__, 'input', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addTextArea($key, $label, array $attributes = [], array $validators = []) {
        $attributes['type'] = 'textarea';
        return $this->add($key, $label,__FUNCTION__, 'input', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addUpload($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label,__FUNCTION__, 'input', $attributes, $validators);
    }

    /** @return IRequest */
    public function getRequest() {
        return $this->request;
    }

    /** @return array */
    public function getData() {
        return $this->data;
    }

    /** @return bool */
    public function isSignalled() {
        return !empty($this->request->getUrl()->getQueryParameter('do'));
    }

    public function render(...$args) {
        $this->template->component = $this->getName();
        $this->template->data = json_encode(['row' => $this->data, 'validators' => []]);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->links = json_encode($this->addHandlers(['delete', 'done', 'export', 'import', 'prepare', 'run', 'save', 'submit', 'validate']));
        $this->template->setFile(__DIR__ . '/../templates/react.latte');
        $this->template->render();
    }

}

interface IReactFormFactory {

    /** @return ReactForm */
    function create();
    
    /** @return ReactForm */
    function attached($parent);

}
