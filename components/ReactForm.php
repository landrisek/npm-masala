<?php

namespace Masala;

use Mockery\Exception;
use Nette\Application\UI\ISignalReceiver,
    Nette\Application\IPresenter,
    Nette\Application\UI\Control,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
class ReactForm extends Control implements IReactFormFactory {

    /** @var array */
    private $data;

    /** @var string */
    private $jsDir;

    /** @var IRequest */
    private $request;

    /** @var ITranslator */
    private $translatorModel;

    public function __construct($jsDir, IRequest $request, ITranslator $translatorModel) {
        $this->jsDir = $jsDir;
        $this->request = $request;
        $this->translatorModel = $translatorModel;
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
    private function add($key, $label, $method, array $attributes = [], array $validators = []) {
        $validations = [];
        foreach($validators as $validatorId => $validator) {
            $validations[$validatorId] = ['value' => $validator, 'style' => ['display' => 'none']];
        }
        $attributes['id'] = strtolower('frm-' .
            preg_replace('/\\\(.*)/', '', get_class($this)) . '-' .
            preg_replace('/(.*)\\\/', '', get_class($this)) .
            '-' . $key);
        foreach($attributes as $attributeId => $attribute) {
            if(null === $attribute) {
                unset($attributes[$attributeId]);
            }
        }
        $this->data[$key] = ['Label' => $label,
                            'Method' => $method, 
                            'Attributes' => $attributes, 
                            'Validators' => $validations];
        return $this;
    }

    /** @return IReactFormFactory */
    public function addAction($key, $label, array $attributes = [], array $validators = []) {
        $attributes['style']['marginLeft'] = '10px';
        return $this->add($key, $label, __FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addCheckbox($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label, __FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addDateTimePicker($key, $label, array $attributes = [], array $validators = []) {
        $attributes['class'] = 'form-control datetimepicker';
        return $this->add($key, $label, 'addText', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addEmpty($key, $label, array $attributes = []) {
        return $this->add($key, $label, __FUNCTION__, $attributes);
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
        return $this->add($key, $label, __FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addMessage($key, $label, array $attributes = []) {
        $attributes['style'] = ['display' => 'none'];
        return $this->add($key, $label, __FUNCTION__, $attributes);
    }

    /** @return IReactFormFactory */
    public function addMultiSelect($key, $label, array $attributes = [], array $validators = []) {
        if(null == $attributes['value']) {
            $attributes['value'] = [];
        }
        return $this->add($key, $label, __FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addMultiUpload($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label, __FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addRadioList($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label, __FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addRange($key, $label,  array $attributes = [], array $validators = []) {
        return $this->add($key, $label, __FUNCTION__, $attributes, $validators);
    }    

    /** @return IReactFormFactory */
    public function addSubmit($key, $label,  array $attributes = []) {
        return $this->add($key, $label, __FUNCTION__, $attributes);
    }

    /** @return IReactFormFactory */
    public function addSelect($key, $label,  array $attributes = [], array $validators = []) {
        return $this->add($key, $label, __FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addProgressBar($key, $label = '',  array $attributes = [], array $validators = []) {
        $attributes['width'] = 0;
        return $this->add($key . '-progress', $label, __FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addTitle($key, $label,  $attributes) {
        return $this->add($key, $label,  __FUNCTION__, $attributes);
    }
    
    /** @return IReactFormFactory */
    public function addText($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label,__FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addTextArea($key, $label, array $attributes = [], array $validators = []) {
        return $this->add($key, $label,__FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addUpload($key, $label, array $attributes = [], array $validators = []) {
        if(!isset($validators['type'])) {
            throw new Exception('Uploaded file has no validator for type');
        }
        return $this->add($key, $label,__FUNCTION__, $attributes, $validators);
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
        $this->template->data = json_encode($this->data);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->links = json_encode($this->addHandlers(['delete', 'done', 'export', 'import', 'prepare', 'run', 'save', 'submit']));
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
