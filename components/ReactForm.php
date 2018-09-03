<?php
namespace Masala;

use Nette\Application\IPresenter,
    Nette\Application\UI\ISignalReceiver,
    Nette\Application\UI\Control,
    Nette\ComponentModel\IComponent,
    Nette\Application\Responses\JsonResponse,
    Nette\Http\IRequest,
    Nette\InvalidStateException;

/** @author Lubomir Andrisek */
class ReactForm extends Control implements IReactFormFactory {

    /** @var array */
    private $compulsory = [];
    
    /** @var array */
    private $data  = [];

    /** @var string */
    protected const EMAIL = 'isEmail';

    /** @var string */
    private $id;
    
    /** @var string */
    private $js;

    /** @var IRequest */
    private $request;
    
    /** @var array */
    private $rules = [];

    public function __construct(string $js, IRequest $request) {
        $this->js = $js;
        $this->request = $request;
    }

    private function add(string $key, string $label, string $method, string $tag, array $attributes = []): IReactFormFactory {
        $attributes['id'] = $key;
        if(isset($attributes['value']) && is_array($attributes['value']) &&  !empty($attributes['value'])) {
            $attributes['value'] = $this->underscore($attributes['value']);
        } else if(isset($attributes['value']) && '' !== $attributes['value'] && isset($attributes['data']) && !empty($attributes['data'])) {
            $attributes['value'] = '_' . $attributes['value'];
        }
        if(isset($attributes['data']) && !empty($attributes['data'])) {
            $attributes['data'] = $this->underscore($attributes['data']);
        }
        foreach($attributes as $attributeId => $attribute) {
            if(null === $attribute) {
                unset($attributes[$attributeId]);
            }
        }
        $attributes['className'] = !isset($attributes['className']) ? 'form-control' : $attributes['className'];
        $this->data[$key] = ['Attributes' => $attributes,
                            'Label' => $label,
                            'Method' => $method,
                            'Tag' => $tag];
        return $this;
    }
    
    public function addAction(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['style']['marginLeft'] = '10px';
        return $this->add($key, $label, __FUNCTION__, 'a', $attributes);
    }

    public function addCheckbox(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = 'checkbox';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addDateTime(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addEmpty(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addGallery(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['style']['clear'] = 'both';
        if(!isset($attributes['crop']) || !isset($attributes['content']) || !isset($attributes['delete'])) {
            throw new InvalidStateException('Name and content attribute intended for delete button and proceed message were not set.');
        }
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addGoogleMap(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['value']['Latitude'] = round($attributes['value']['Latitude'], 2);
        $attributes['value']['Longitude'] = round($attributes['value']['Longitude'], 2);
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    private function addHandlers(array $links): array {
        $handlers = [];
        $methods = array_flip(get_class_methods($this->getParent()));
        $calls = array_flip(get_class_methods($this));
        foreach($links as $link) {
            if($this instanceof ISignalReceiver && isset($calls['handle' . ucfirst($link)])) {
                $handlers[$link] = $this->link($link);
            } else if($this->getParent() instanceof IPresenter && isset($methods['handle' . ucfirst($link)])) {
                $handlers[$link] = $this->getParent()->link($link . '!');
            } else if($this->getParent() instanceof ISignalReceiver && isset($methods['handle' . ucfirst($link)])) {
                $handlers[$link] = $this->getParent()->link($link);
            }
        }
        return $handlers;
    }

    public function addHidden(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addMessage(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['style'] = ['display' => 'none'];
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addMultiSelect(string $key, string $label, array $attributes = []): IReactFormFactory {
        if(null == $attributes['value']) {
            $attributes['value'] = [];
        }
        return $this->add($key, $label, __FUNCTION__, 'select', $attributes);
    }

    public function addMultiUpload($key, $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addRadioList(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = 'radio';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addRule(string $validator): IReactFormFactory {
        end($this->data); 
        $this->rules[key($this->data)] = $validator;
        return $this;
    }

    public function addSubmit(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = 'submit';
        $attributes['onClick'] = isset($attributes['onClick']) ? $attributes['onClick'] : 'submit';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addSelect(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'select', $attributes);
    }

    public function addProgressBar(string $key, string $label = '',  array $attributes = []): IReactFormFactory {
        $attributes['width'] = 0;
        return $this->add($key . '-progress', $label, __FUNCTION__, 'div', $attributes);
    }

    public function addSubtitle(string $key, string $label, array $attributes): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addTitle(string $key, string $label, array $attributes): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addText(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = isset($attributes['type']) ? $attributes['type'] : 'text';
        return $this->add($key, $label, __FUNCTION__, 'input', $this->class($attributes));
    }
    
    public function addTextArea(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = 'textarea';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }
    
    public function addUpload(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }
    
    private function class(array $attributes): array {
        $attributes['className'] = isset($attributes['className']) ? $attributes['className'] : 'form-control';
        return $attributes;
    }

    public function create(): ReactForm {
        return $this;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
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
    
    public function handleValidate(): void {
        $values = json_decode(file_get_contents('php://input'), true);
        $validators = [];
        foreach($this->rules as $key => $rule) {
            if(true == $this->compulsory[$key] && empty($values['row'][$key])) {
                $validators[$key] = $values['row'][$key];
            } else if(false == $this->compulsory[$key] && !empty($values['row'][$key]) && false == Validators::$rule($values['row'][$key])) {
                $validators[$key] = $values['row'][$key];
            } else if(false == Validators::$rule($values['row'][$key]) && !empty($values['row'][$key])) {
                $validators[$key] = $values['row'][$key];
            };
        }
        $this->getPresenter()->sendResponse(new JsonResponse($validators));
    }
    
    public function isSignalled(): bool {
        return !empty($this->request->getUrl()->getQueryParameter('do'));
    }
    
    public function replace(string $key, array $component): IReactFormFactory {
        $this->data[$key] = $component;
        return $this;
    }
    
    public function unsetOffset(string $key): IReactFormFactory {
        unset($this->data[$key]);
        return $this;
    }
    
    public function render(...$args): void {
        $this->template->component = $this->getName();
        $this->template->data = json_encode(['row' => $this->data, 'validators' => []]);
        $this->template->links = json_encode($this->addHandlers(['crop', 'delete', 'done', 'export', 'import', 'move', 'prepare', 'put', 'resize', 'run', 'save', 'submit', 'validate']));
        $this->template->js = $this->template->basePath . '/' . $this->js;
        if(!preg_match('/\./', $this->js)) {
            $this->template->component = 'generalForm';
            $this->template->js .= '/GeneralForm.js';
        } 
        $this->template->setFile(__DIR__ . '/../templates/react.latte');
        $this->template->render();
    }
    
    public function setRequired(bool $value) {
        end($this->data); 
        $this->compulsory[key($this->data)] = $value;
    }

    /** keep given order in javascript */
    private function underscore(array $data): array {
        foreach($data as $overwriteId => $overwrite) {
            $data['_' . $overwriteId] = $overwrite;
            unset($data[$overwriteId]);
        }
        return $data;
    }
    
}
interface IReactFormFactory {

    public function create(): ReactForm;
}