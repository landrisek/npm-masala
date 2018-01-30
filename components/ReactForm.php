<?php

namespace Masala;

use Nette\Application\UI\ISignalReceiver,
    Nette\Application\IPresenter,
    Nette\Application\UI\Control,
    Nette\ComponentModel\IComponent,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
class ReactForm extends Control implements IReactFormFactory {

    /** @var array */
    private $data = [];

    /** @var string */
    private $jsDir;

    /** @var IRequest */
    private $request;

    public function __construct(string $jsDir, IRequest $request) {
        $this->jsDir = $jsDir;
        $this->request = $request;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
    }

    private function add($key, $label, $method, $tag, array $attributes = [], array $validators = []): IReactFormFactory {
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

    public function addAction($key, $label, array $attributes = [], array $validators = []): IReactFormFactory {
        $attributes['style']['marginLeft'] = '10px';
        return $this->add($key, $label, __FUNCTION__, 'a', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addCheckbox($key, $label, array $attributes = [], array $validators = []): IReactFormFactory  {
        $attributes['type'] = 'checkbox';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addDateTime($key, $label, array $attributes = [], array $validators = []): IReactFormFactory  {
        return $this->add($key, $label, 'addDateTime', 'input', $attributes, $validators);
    }

    /** @return IReactFormFactory */
    public function addEmpty($key, $label, array $attributes = []): IReactFormFactory  {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    private function addHandlers(array $links): array {
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

    public function addHidden($key, $label, array $attributes = [], array $validators = []): IReactFormFactory  {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes, $validators);
    }

    public function addMessage($key, $label, array $attributes = []): IReactFormFactory  {
        $attributes['style'] = ['display' => 'none'];
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addMultiSelect($key, $label, array $attributes = [], array $validators = []): IReactFormFactory {
        if(null == $attributes['value']) {
            $attributes['value'] = [];
        }
        return $this->add($key, $label, __FUNCTION__, 'select', $attributes, $validators);
    }

    public function addMultiUpload($key, $label, array $attributes = [], array $validators = []): IReactFormFactory  {
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes, $validators);
    }

    public function addRadioList($key, $label, array $attributes = [], array $validators = []): IReactFormFactory {
        $attributes['type'] = 'radio';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes, $validators);
    }

    public function addSubmit($key, $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = 'submit';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addSelect($key, $label, array $attributes = [], array $validators = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'select', $attributes, $validators);
    }

    public function addProgressBar($key, $label = '',  array $attributes = [], array $validators = []): IReactFormFactory {
        $attributes['width'] = 0;
        return $this->add($key . '-progress', $label, __FUNCTION__, 'div', $attributes, $validators);
    }

    public function addTitle($key, $label,  array $attributes): IReactFormFactory  {
        return $this->add($key, $label,  __FUNCTION__, 'div', $attributes);
    }
    
    public function addText($key, $label, array $attributes = [], array $validators = []): IReactFormFactory {
        $attributes['type'] = isset($attributes['type']) ? $attributes['type'] : 'text';
        return $this->add($key, $label,__FUNCTION__, 'input', $attributes, $validators);
    }

    public function addTextArea($key, $label, array $attributes = [], array $validators = []): IReactFormFactory {
        $attributes['type'] = 'textarea';
        return $this->add($key, $label,__FUNCTION__, 'input', $attributes, $validators);
    }

    public function addUpload($key, $label, array $attributes = [], array $validators = []): IReactFormFactory {
        return $this->add($key, $label,__FUNCTION__, 'input', $attributes, $validators);
    }

    public function create(): ReactForm {
        return $this;
    }

    public function getOffset(string $key): array {
        return $this->data[$key];
    }

    public function getData(): array {
        return $this->data;
    }

    public function getRequest(): IRequest {
        return $this->request;
    }

    public function isSignalled(): bool {
        return !empty($this->request->getUrl()->getQueryParameter('do'));
    }

    public function unsetOffset(string $key): void {
        unset($this->data[$key]);
    }

    public function render(...$args): void {
        $this->template->component = $this->getName();
        $this->template->data = json_encode(['row' => $this->data, 'validators' => []]);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->links = json_encode($this->addHandlers(['delete', 'done', 'export', 'import', 'prepare', 'run', 'save', 'submit', 'validate']));
        $this->template->setFile(__DIR__ . '/../templates/react.latte');
        $this->template->render();
    }

}

interface IReactFormFactory {

    public function create(): ReactForm;
}
