<?php

namespace Masala;

use Models\TranslatorModel,
    Nette\Application\UI\Form,
    Nette\Application\UI\Presenter,
    Latte\Engine,
    Nette\Bridges,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
class EditForm extends Form implements IEditFormFactory {

    /** @var TranslatorModel */
    private $translatorModel;

    /** @var MockService */
    private $mockService;

    /** @var RowBuilder */
    protected $setting;

    /** @var IRequest */
    private $request;

    /** @var Array */
    private $components;

    public function __construct(Array $components = [], TranslatorModel $translatorModel, MockService $mockService, IRequest $request) {
        $this->components = [];
        $this->translatorModel = $translatorModel;
        $this->mockService = $mockService;
        $this->request = $request;
    }

    /** getters */
    public function getSetting() {
        return $this->setting;
    }

    /** setters */
    public function setSetting(RowBuilder $setting) {
        $this->setting = $setting;
        return $this;
    }

    /** @return IEditFormFactory */
    public function create() {
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof Presenter) {
            $this->setMethod('post');
            $this->setting->beforeAttached($this);
            foreach ($this->setting->getColumns() as $column) {
                $name = $column['name'];
                $config = $this->setting->getConfig($this->setting->getTable() . '.' . $name);
                $labelComponent = ucfirst($this->translatorModel->translate(preg_replace('([0-9]+)', '', $this->setting->getTable() . '.' . $name)));
                /** default values */
                $defaults = (isset($config['getDefaults'])) ? $this->mockService->getCall($config['getDefaults']['service'], $config['getDefaults']['method'], $config['getDefaults']['parameters'], $this) : [];
                /** components */
                if ('SUBMIT' == $column['nativetype']) {
                    $this->addSubmit($name, $labelComponent);
                    $this->components[$name] = $column['nativetype'];
                } elseif ('PRI' == $column['vendor']['Key'] or
                        0 < substr_count($column['vendor']['Comment'], '@unedit') or
                        0 < substr_count($column['vendor']['Comment'], '[' . $presenter->getName() . ']') or
                        0 < substr_count($column['vendor']['Comment'], '[' . $presenter->getName() . ':' . $presenter->getAction() . ']')) {
                } elseif ('ENUM' == $column['nativetype']) {
                    if(is_array($this->setting->$name)) {
                        $defaults = $this->setting->$name;
                    } elseif (empty($defaults)) {
                        $selects = explode(',', preg_replace('/(enum)|\(|\)|\"|\'/', '', $column['vendor']['Type']));
                        foreach ($selects as $select) {
                            $defaults[$select] = $this->translatorModel->translate($select);
                        }
                    }
                    $this->addSelect($name, $labelComponent . ':', $defaults);
                    (null == $this->setting->$name or '' == $this->setting->$name or is_array($this->setting->$name) or ! isset($defaults[$this->setting->$name])) ? null : $this[$name]->setDefaultValue($this->setting->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif (in_array($column['nativetype'], ['DATETIME', 'TIMESTAMP', 'DATE'])) {
                    /** https://github.com/radekdostal/Nette-DateTimePicker */
                    $default = (is_object($this->setting->$name)) ? $this->setting->$name->__toString() : date('Y-m-d H:i:s', strtotime('now'));
                    $this->addDateTimePicker($name, $labelComponent . ':', 10)
                            ->setAttribute('id', 'datetimepicker_' . $name)
                            ->setReadOnly(false)
                            ->setDefaultValue($default);
                    $this->components[$name] = $column['nativetype'];
                } elseif ($column['nativetype'] == 'TINYINT') {
                    $this->addCheckbox($name, $labelComponent);
                    $this[$name]->setDefaultValue($this->setting->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif (0 < substr_count($column['nativetype'], 'TEXT')) {
                    $this[$name] = new WysiwygEditor($labelComponent, $presenter);
                    0 === substr_count($column['vendor']['Comment'], '@cke3') ? $this[$name]->setVersion(4) : $this[$name]->setVersion(3);
                    $this[$name]->setDefaultValue($this->setting->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif (!empty($defaults) and is_array($defaults) and is_array($this->setting->$name) and 'INT' == $column['nativetype']) {
                    $this->addSelect($name, $labelComponent . ':', $defaults);
                    $this[$name]->setDefaultValue($this->setting->$name[key($this->setting->$name)]);
                    $this->components[$name] = $column['nativetype'];
                } elseif (0 < substr_count($column['vendor']['Comment'], '@multi') or ( !empty($defaults) and is_array($defaults) and is_array($this->setting->$name))) {
                    $this->addMultiSelect($name, $labelComponent . ':', $defaults);
                    $multiDefault = is_array($this->setting->$name) ? $this->setting->$name : json_decode($this->setting->$name);
                    $this[$name]->setDefaultValue($multiDefault);
                    $this->components[$name] = 'MULTI';
                } elseif (!empty($defaults) and is_array($defaults)) {
                    $this->addSelect($name, $labelComponent . ':', $defaults);
                    (null == $this->setting->$name or '' == $this->setting->$name or ! isset($defaults[$this->setting->$name])) ? $this[$name]->setPrompt('---') : $this[$name]->setDefaultValue($this->setting->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif ('DECIMAL' == $column['nativetype'] or 'FLOAT' == $column['nativetype']) {
                    $this->addText($name, $labelComponent . ':')->setRequired(false)->setRequired(false)
                            ->addRule(Form::FLOAT, $this->translatorModel->translate('Set only numbers.'));
                    $this[$name]->setDefaultValue($this->setting->$name);
                    $this->components[$name] = $column['nativetype'];
                } elseif (0 < substr_count($column['vendor']['Comment'], '@upload')) {
                    $this->addUpload($name, $labelComponent, 1000);
                    $this->components[$name] = '[UPLOAD]';
                } elseif (0 < substr_count($column['vendor']['Comment'], '@multiupload')) {
                    $this->addMultipleFileUpload($name, $labelComponent, 10);
                    $this->components[$name] = '[UPLOAD]';
                } elseif ('INT' == $column['nativetype']) {
                    $this->addText($name, $labelComponent . ':')->setType('number');
                    $this[$name]->setDefaultValue($this->setting->$name);
                    $this->components[$name] = $column['nativetype'];
                } else {
                    $this->addText($name, $labelComponent . ':');
                    $this[$name]->setDefaultValue($this->setting->$name);
                    $this->components[$name] = $column['nativetype'];
                }
                /** components methods */
                if (isset($this[$name])) {
                    $this[$name]->setAttribute('class', 'form-control');
                    false == $column['nullable'] and null === $column['default'] and 0 === substr_count($column['vendor']['Comment'], '@multiupload') ? $this[$name]->setRequired(true) : null;
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
            $this->addSubmit('save', ucfirst($this->translatorModel->translate('save')))->setAttribute('class', 'btn btn-success');
            $this->addSubmit('grid', ucfirst($this->translatorModel->translate('save and on grid')))->setAttribute('class', 'btn btn-success');
            $this->addSubmit('delete', ucfirst($this->translatorModel->translate('delete')))
                    ->setAttribute('class', 'btn btn-danger pull-right')
                    ->setAttribute('data-confirm', $this->translatorModel->translate('Do you really wish to delete?'));
            $this->setting->afterAttached($this);
            $this->onSuccess[] = [$this, 'succeeded'];
        }
    }

    public function succeeded($form) {
        /** values */
        $values = $this->setting->beforeSucceeded($form);
        $columns = [];
        foreach ($this->setting->getColumns() as $column => $row) {
            $column = $row['name'];
            if (!isset($this->components[$column]) and isset($this->setting->$column)) {
                $columns[$column] = $this->setting->$column;
            } elseif (isset($this->components[$column]) and ( 'DATE' == $this->components[$column] or 'DATETIME' == $this->components[$column])) {
                $columns[$column] = $values->$column->__toString();
            } elseif (!isset($this->components[$column]) or ! isset($values->$column)) {
                
            } elseif (isset($this->components[$column]) and ( 'INT' == $this->components[$column] or 'TINYINT' == $this->components[$column])) {
                $columns[$column] = intval($values->$column);
            } elseif (isset($this->components[$column]) and 'MULTI' == $this->components[$column]) {
                $columns[$column] = json_encode($values->$column);
            } elseif (isset($values->$column)) {
                $columns[$column] = $values->$column;
            }
        }
        /** pure parameters */
        $this->setting->submit($form);
        $primary = $this->setting->getData()->getPrimary();
        /** delete ; */
        if ('delete' === $this->setting->getSubmit()) {
            $this->setting->delete($this->setting->getTable(), $primary);
            $this->getPresenter()->flashMessage(ucfirst($this->translatorModel->translate('item with')) . ' ID ' .
                    $primary . ' ' . $this->translatorModel->translate('has been erased from table') . ' ' .
                    $this->translatorModel->translate($this->setting->getTable()) . '.');
            /** insert */
        } elseif ('new' == $this->setting->getSubmit()) {
            $this->setting->add($columns);
            $this->getPresenter()->flashMessage($this->translatorModel->translate('New item with') . ' ID ' . $primary . ' ' .
                    $this->translatorModel->translate('had been added to the table') . ' ' . $this->translatorModel->translate($this->setting->getTable()) . '.');
            /** update */
        } elseif(null != $this->setting->getResource()) {
            $this->setting->update($columns);
            $this->getPresenter()->flashMessage(ucfirst($this->translatorModel->translate('item with')) . ' ID ' . $primary . ' '
                    . $this->translatorModel->translate('from table') . ' ' . $this->translatorModel->translate($this->setting->getTable()) . ' ' . $this->translatorModel->translate('had been edited') . '.');
        }
        $this->setting->afterSucceeded($form);
        /** redirect */
        $this->redirect();
    }

    private function redirect($redirect = null) {
        if (is_array($redirect)) {
            $presenter = isset($redirect['presenter']) ? $redirect['presenter'] : $this->getPresenter()->getName();
            $action = isset($redirect['action']) ? $redirect['action'] : $this->setting->getAction();
            $parameters = isset($redirect['parameters']) ? $redirect['parameters'] : $this->setting->getParameters();
            $this->getPresenter()->redirect(':' . $presenter . ':' . $action, $parameters);
        } elseif (is_object($referer = $this->request->getReferer())) {
            foreach ($referer->getQueryParameters() as $key => $parameter) {
                ($key != 'do') ? $this->setting->setParameter($key, $parameter) : null;
            }
        }
        if (in_array($this->setting->getSubmit(), ['delete', 'grid']) and null == $action = $this->setting->getAction()) {
            $this->getPresenter()->redirect(':' . $this->getPresenter()->getName() . ':' . $action, $this->setting->getParameters());
        } elseif (in_array($this->setting->getSubmit(), ['delete', 'grid'])) {
            $this->getPresenter()->redirect(':' . $this->getPresenter()->getName() . ':' . $this->setting->getAction(), $this->setting->getParameters());
        } else {
            $this->getPresenter()->redirect(':' . $this->getPresenter()->getName() . ':' . $this->getPresenter()->action, $this->setting->getParameters());
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
        $template->basePath = $this->getPresenter()->template->basePath;
        $template->setting = $this->setting;
        $template->title = $this->setting->getTitle();
        $template->subtitle = $this->setting->getSubtitle();        
        $template->components = $this->components;
        $template->render();
        return $template;
    }

}

interface IEditFormFactory {

    /** @return EditForm */
    function create();
}
