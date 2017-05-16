<?php

namespace Masala;

use Nette\Application\IPresenter,
    Nette\Application\UI\Control,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
class ReactForm extends Control implements IReactFormFactory {

    /** @var IRequest */
    private $request;

    /** @var string */
    private $basePath;
    
    /** @var array */
    protected $data;

    /** @var ITranslator */
    private $translatorModel;
    
    public function __construct(IRequest $request, ITranslator $translatorModel) {
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
        if ($presenter instanceof IPresenter) {
            if(!empty($data = json_decode(file_get_contents('php://input'), true))) {
                $this->succeeded($data);
            }
            $this->basePath = $presenter->template->basePath;
        }
        return $this;
    }

    /** @return IReactFormFactory */
    private function add($key, $method, array $attributes = [], array $validators = []) {
        $validations = [];
        foreach($validators as $validatorId => $validator) {
            $validations[$validatorId] = ['value' => $validator, 'style' => ['display' => 'none']];
        }
        $this->data[$key] = ['Label' => $this->translatorModel->translate($key),
                            'Method' => $method, 
                            'Attributes' => $attributes, 
                            'Validators' => $validations];
        return $this;
    }

    /** @return IReactFormFactory */
    public function addDateTimePicker($key, array $attributes = [], array $validators = []) {
        return $this->add($key, __FUNCTION__, $attributes, $validators);
    }    
    
    public function addMessage($key, array $attributes = []) {
        return $this->add($key, __FUNCTION__, $attributes);
    }
    
    /** @return IReactFormFactory */
    public function addRadioList($key, array $attributes = [], array $validators = []) {
        return $this->add($key, __FUNCTION__, $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addRange($key, array $attributes = [], array $validators = []) {
        return $this->add($key, __FUNCTION__, $attributes, $validators);
    }    

    /** @return IReactFormFactory */
    public function addSubmit($key, array $attributes = []) {
        return $this->add($key, __FUNCTION__, $attributes);
    }

    /** @return IReactFormFactory */
    public function addSelect($key, array $attributes = [], array $validators = []) {
        return $this->add($key, __FUNCTION__, $attributes);
    }

    /** @return IReactFormFactory */
    public function addMultiSelect($key, array $attributes = [], array $validators = []) {
        return $this->add($key, __FUNCTION__, $attributes);
    }
    
    /** @return IReactFormFactory */
    public function addTitle($key, $attributes) {
        return $this->add($key, __FUNCTION__, $attributes);
    }
    
    /** @return IReactFormFactory */
    public function addText($key, array $attributes = [], array $validators = []) {
        return $this->add($key, __FUNCTION__, $attributes, $validators);
    }

    /** @return IRequest */
    public function getRequest() {
        return $this->request;
    }
 
    public function submit(Control $control) {
        
    }
    
    /** @return array */
    public function succeeded(array $data) {
        return $this->request->getUrl()->getQueryParameters();
    }
    
    public function render(...$args) {
        $this->template->setFile(__DIR__ . '/../templates/react.latte');
        $this->template->component = $this->getName();
        $this->template->control = $this->getParent();
        $this->template->data = json_encode($this->data);
        $this->template->basePath = $this->basePath;
        $this->template->render();
    }

}

interface IReactFormFactory {

    /** @return ReactForm */
    function create();
    
    /** @return ReactForm */
    function attached($presenter);
    
    function submit(Control $control);
    
    function succeeded(array $data);
}
