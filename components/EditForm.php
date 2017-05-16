<?php

namespace Masala;

use Nette\Application\UI\Form,
    Latte\Engine,
    Nette\Application\IPresenter,
    Nette\Bridges,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class EditForm extends Form implements IEditFormFactory {

    /** @var TranslatorModel */
    private $translatorModel;

    /** @var MockService */
    private $mockService;

    /** @var IRowBuilder */
    protected $row;

    /** @var IRequest */
    private $request;
    
    /** @var IPresenter */
    private $presenter;

    /** @var Array */
    private $components = [];

    /** @var int */
    private $upload;
    
    /** @var bool */
    private $signal;

    public function __construct($upload, ITranslator $translatorModel, MockService $mockService, IRequest $request) {
        $this->upload = intval($upload);
        $this->translatorModel = $translatorModel;
        $this->mockService = $mockService;
        $this->request = $request;
    }

    /** @return IRowBuilder */
    public function getRow() {
        return $this->row;
    }

    /** @return IEditFormFactory */
    public function setRow(IRowBuilder $row) {
        $this->row = $row;
        return $this;
    }
    
    /** @return IEditFormFactory */    
    public function signalled($signal) {
        $this->signal = (string) $signal;
        return $this;
    }

    /** @return IEditFormFactory */
    public function create() {
        return $this;
    }

    /** @return IEditFormFactory */
    public function attached($masala) {
        parent::attached($masala);
        if($masala instanceof Masala) {
            $this->presenter = $masala->getPresenter();
        } elseif($masala instanceof IPresenter) {
            $this->presenter = $masala;
        }
        if ($this->presenter instanceof IPresenter) {
            $this->setMethod('post');
            $this->row->beforeAttached($this, true);
            foreach ($this->row->getColumns() as $column) {
                $name = $column['name'];
                $config = $this->row->getConfig($this->row->getTable() . '.' . $name);
                $labelComponent = ucfirst($this->translatorModel->translate(preg_replace('([0-9]+)', '', $this->row->getTable() . '.' . $name)));
                /** default values */
                $defaults = (isset($config['getDefaults'])) ? $this->mockService->getCall($config['getDefaults']['service'], $config['getDefaults']['method'], $config['getDefaults']['parameters'], $this) : [];
                /** components */
                if ('SUBMIT' == $column['nativetype']) {
                    $this->addSubmit($name, $labelComponent);
                    $this->components[$name] = $column['nativetype'];
                } elseif ('PRI' == $column['vendor']['Key'] or
                        0 < substr_count($column['vendor']['Comment'], '@unedit') or
                        0 < substr_count($column['vendor']['Comment'], '[' . $this->presenter->getName() . ']') or
                        0 < substr_count($column['vendor']['Comment'], '[' . $this->presenter->getName() . ':' . $this->presenter->getAction() . ']')) {
                } elseif ('ENUM' == $column['nativetype']) {
                    if(is_array($this->row->$name)) {
                        $defaults = $this->row->$name;
                    } elseif (empty($defaults)) {
                        $selects = explode(',', preg_replace('/(enum)|\(|\)|\"|\'/', '', $column['vendor']['Type']));
                        foreach ($selects as $select) {
                            $defaults[$select] = $this->translatorModel->translate($select);
                        }
                    }
                    $this->addSelect($name, $labelComponent . ':', $defaults)->setAttribute('style', 'height:100%');
                    (null == $this->row->$name or '' == $this->row->$name or is_array($this->row->$name) or ! isset($defaults[$this->row->$name])) ? null : $this[$name]->setDefaultValue($this->row->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif (in_array($column['nativetype'], ['DATETIME', 'TIMESTAMP', 'DATE'])) {
                    /** https://github.com/radekdostal/Nette-DateTimePicker */
                    $default = (is_object($this->row->$name)) ? $this->row->$name->__toString() : date('Y-m-d H:i:s', strtotime('now'));
                    $this->addDateTimePicker($name, $labelComponent . ':', 100)
                            ->setAttribute('id', 'datetimepicker_' . $name)
                            ->setReadOnly(false)
                            ->setDefaultValue($default);
                    $this->components[$name] = $column['nativetype'];
                } elseif ($column['nativetype'] == 'TINYINT') {
                    $this->addCheckbox($name, $labelComponent)->setAttribute('name', $name);
                    $this[$name]->setDefaultValue($this->row->$name);
                    1 == $this->row->$name ? $this[$name]->setAttribute('checked', 'checked') : null;
                    $this->components[$name] = $column['nativetype'];
                } elseif (0 < substr_count($column['vendor']['Comment'], '@textarea')) {
                    $this->addTextArea($name, $labelComponent . ':');
                    $this[$name]->setDefaultValue($this->row->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif (0 < substr_count($column['nativetype'], 'TEXT')) {
                    $this[$name] = new WysiwygEditor($labelComponent, $this->presenter);
                    0 === substr_count($column['vendor']['Comment'], '@cke3') ? $this[$name]->setVersion(4) : $this[$name]->setVersion(3);
                    $this[$name]->setDefaultValue($this->row->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif (!empty($defaults) and is_array($defaults) and is_array($this->row->$name) and 'INT' == $column['nativetype']) {
                    $this->addSelect($name, $labelComponent . ':', $defaults)->setAttribute('style', 'height:100%');
                    $this[$name]->setDefaultValue($this->row->$name[key($this->row->$name)]);
                    $this->components[$name] = $column['nativetype'];
                } elseif (0 < substr_count($column['vendor']['Comment'], '@addMultiSelect') or ( !empty($defaults) and is_array($defaults) and is_array($this->row->$name))) {
                    $this->addMultiSelect($name, $labelComponent . ':', $defaults);
                    $multiDefault = is_array($this->row->$name) ? $this->row->$name : json_decode($this->row->$name);
                    $this[$name]->setDefaultValue($multiDefault);
                    $this->components[$name] = 'MULTI';
                } elseif (!empty($defaults) and is_array($defaults)) {
                    $this->addSelect($name, $labelComponent . ':', $defaults)->setAttribute('style', 'height:100%');
                    (null == $this->row->$name or '' == $this->row->$name or ! isset($defaults[$this->row->$name])) ? $this[$name]->setPrompt('---') : $this[$name]->setDefaultValue($this->row->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif ('DECIMAL' == $column['nativetype'] or 'FLOAT' == $column['nativetype']) {
                    $this->addText($name, $labelComponent . ':')->setRequired(false)
                            ->addRule(Form::FLOAT, $this->translatorModel->translate('Set only numbers.'));
                    $this[$name]->setDefaultValue($this->row->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif (0 < substr_count($column['vendor']['Comment'], '@upload')) {
                    $this->addUpload($name, $labelComponent, 1000);
                    $this->components[$name] = '[UPLOAD]';
                } elseif (0 < substr_count($column['vendor']['Comment'], '@multiupload')) {
                    $this->addMultipleFileUpload($name, $labelComponent, $this->upload);
                    $this->components[$name] = '[UPLOAD]';
                } elseif ('INT' == $column['nativetype']) {
                    $this->addText($name, $labelComponent . ':')->setType('number');
                    $this[$name]->setDefaultValue($this->row->$name);
                    $this->components[$name] = $column['nativetype'];
                } else {
                    $this->addText($name, $labelComponent . ':');
                    $this[$name]->setDefaultValue($this->row->$name);
                    $this->components[$name] = $column['nativetype'];
                }
                /** components methods */
                if (isset($this[$name])) {
                    $this[$name]->setAttribute('class', 'form-control');
                    if(false == $column['nullable'] and null === $column['default'] and 0 === substr_count($column['vendor']['Comment'], '@multiupload')) {
                        $this[$name]->setRequired($this->translatorModel->translate($name));
                    }
                    0 === substr_count($column['vendor']['Comment'], '@disable') ? null : $this[$name]->controlPrototype->readonly = 'readonly';
                    0 === substr_count($column['vendor']['Comment'], '@onchange') ? null : $this[$name]->setAttribute('onchange', 'submit()');
                    foreach ($config as $method => $data) {
                        if ('getDefaults' != $method) {
                            $result = $this->mockService->getCall($data['service'], $data['method'], $data['parameters'], $this);
                            $this[$name]->$method($result);
                        }
                    }
                }
            }
            if(!empty($this->signal) and false == $this->row->getData()) {
                $this->getElementPrototype()->setId('frm-' . strtolower(__NAMESPACE__) . 'Form');
                $this->addHandler('new');
            } else {
                $this->addSubmit('grid', ucfirst($this->translatorModel->translate('save and on grid')))->setAttribute('class', 'btn btn-success');
                $this->addSubmit('save', ucfirst($this->translatorModel->translate('save')))->setAttribute('class', 'btn btn-success');
                $this->addSubmit('delete', ucfirst($this->translatorModel->translate('delete')))
                        ->setAttribute('class', 'btn btn-danger pull-right')
                        ->setAttribute('data-confirm', $this->translatorModel->translate('Do you really wish to delete?'));    
            }
            if(!empty($this->signal) and false != $this->row->getData()) {
                $this->getElementPrototype()->setId('frm-' . strtolower(__NAMESPACE__) . 'Form');
                $this['save']->setAttribute('onclick', 'handleSubmit(' 
                        . json_encode(['id' => '#' . $this->getElementPrototype()->getId(), 'spice' => $this->row->getSpice(), 'signal' => 'save']) .'); return false;');
                $this['delete']->setAttribute('onclick', 'return handleSubmit(' 
                        . json_encode(['id' => '#' . $this->getElementPrototype()->getId(), 'spice' => $this->row->getSpice(), 'signal' => 'delete']) .'); return false;');
                $this->addButton('close', ucfirst($this->translatorModel->translate('close')))->setAttribute('class', 'btn btn-success')
                        ->setAttribute('data-dismiss', 'modal')
                        ->setAttribute('aria-hidden', 'true');
            }
            $this->row->afterAttached($this);
            $this->onSuccess[] = [$this, 'succeeded'];
        }
        return $this;
    }

    /** @return Handler */
    private function addHandler($name) {
        return $this[$name] = new Handler($name, $this->row->getSpice(), $this->getElementPrototype()->getId(), $this->translatorModel);
    }
    
    /** @return string */
    public function succeeded($form) {
        $values = $this->row->beforeSucceeded($form);
        $columns = [];
        foreach ($this->row->getColumns() as $column => $row) {
            $column = $row['name'];
            if (!isset($this->components[$column]) and isset($values->$column)) {
                $columns[$column] = $values->$column;
            } elseif (!isset($this->components[$column]) and isset($this->row->$column)) {
                $columns[$column] = $this->row->$column;
            } elseif (isset($this->components[$column]) and ( 'DATE' == $this->components[$column] or 'DATETIME' == $this->components[$column])) {
                $columns[$column] = $values->$column->__toString();
            } elseif (!isset($this->components[$column]) and ! isset($values->$column)) {
            } elseif (isset($this->components[$column]) and ( 'INT' == $this->components[$column] or 'TINYINT' == $this->components[$column])) {
                $columns[$column] = intval($values->$column);
            } elseif (isset($this->components[$column]) and 'MULTI' == $this->components[$column]) {
                $columns[$column] = json_encode($values->$column);
            } elseif (isset($values->$column)) {
                $columns[$column] = $values->$column;
            }
        }
        if ((true == $form->isAnchored() and is_object($form->isSubmitted()) and 'delete' == $form->isSubmitted()->getName()) or 'delete' == $this->signal) {
            $this->row->delete();
            $message = ucfirst($this->translatorModel->translate('choosen item with ID')) . ' ' . $this->row->getData()->getPrimary() . ' ' .
                    $this->translatorModel->translate('has been erased from table') . ' ' .
                    $this->translatorModel->translate($this->row->getTable()) . '.';
        } elseif (is_object($this->row->getData()) or 'save' == $this->signal) {
            $this->row->update($columns);
            $message = ucfirst($this->translatorModel->translate('choosen item with ID')) . ' ' . $this->row->getData()->getPrimary() . ' ' .
                    $this->translatorModel->translate('has been edited in table') . ' ' . 
                    $this->translatorModel->translate($this->row->getTable()) . '.';
        } elseif (is_object($this->row->getResource())) {
            $primary = $this->row->add($columns);
            $message = ucfirst($this->translatorModel->translate('new item with ID')) . ' ' . $primary . ' ' .
                    $this->translatorModel->translate('has been added to the table') . ' ' . 
                    $this->translatorModel->translate($this->row->getTable()) . '.';
        }
        $this->row->flush();
        $this->row->afterSucceeded($form);
        if(null == $this->signal) {
            $this->redirect($message);
        }
        return $message;
    }

    private function redirect($message) {
        $this->getPresenter()->flashMessage($message);
        if (!empty($this->row->getParameters())) {
            $presenter = isset($redirect['presenter']) ? $redirect['presenter'] : $this->getPresenter()->getName();
            $action = isset($redirect['action']) ? $redirect['action'] : $this->row->getAction();
            $parameters = isset($redirect['parameters']) ? $redirect['parameters'] : $this->row->getParameters();
            $this->getPresenter()->redirect(':' . $presenter . ':' . $action, $parameters);
        } elseif (is_object($referer = $this->request->getReferer())) {
            foreach ($referer->getQueryParameters() as $key => $parameter) {
                ($key != 'do') ? $this->row->setParameter($key, $parameter) : null;
            }
        }
        if (null == $action = $this->row->getAction() and is_object($this->isSubmitted()) and in_array($this->isSubmitted()->getName(), ['delete', 'grid'])) {
            $this->getPresenter()->redirect(':' . $this->getPresenter()->getName() . ':' . $action, $this->row->getParameters());
        } elseif (is_object($this->isSubmitted()) and in_array($this->isSubmitted()->getName(), ['delete', 'grid'])) {
            $this->getPresenter()->redirect(':' . $this->getPresenter()->getName() . ':' . $this->row->getAction(), $this->row->getParameters());
        } else {
            $this->getPresenter()->redirect(':' . $this->getPresenter()->getName() . ':' . $this->getPresenter()->action, $this->row->getParameters());
        }
    }

    /** @return Template | string */
    protected function beforeRender() {
        if(null == $this->signal) {
            parent::beforeRender();
        }
    }
    
    public function render(...$args) {
        $latte = new Engine();
        $latte->onCompile[] = function($latte) {
            Bridges\FormsLatte\FormMacros::install($latte->getCompiler());
        };
        $template = new Bridges\ApplicationLatte\Template($latte);
        $template->setFile(__DIR__ . '/../templates/edit.latte');
        $template->setTranslator($this->translatorModel);
        $template->form = $this;
        $template->basePath = $this->presenter->template->basePath;
        $template->row = $this->row;
        $template->title = $this->row->getTitle();
        $template->components = $this->components;
        $template->submits = [];
        $template->datetimepickers = [];
        if(!empty($this->signal)) {
            return $template->__toString();
        } else {
            $template->render();
            return $template;
        }
    }

}

interface IEditFormFactory {

    /** @return EditForm */
    function create();
}
