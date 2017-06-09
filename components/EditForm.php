<?php

namespace Masala;

use Models\TranslatorModel,
    Nette\Http\IRequest,
    Nette\Application\IPresenter;

/** @author Lubomir Andrisek */
final class EditForm extends ReactForm implements IEditFormFactory {

    /** @var MockService */
    private $mockService;

    /** @var IPresenter */
    private $presenter;

    /** @var array */
    private $primary;

    /** @var IRowBuilder */
    protected $row;

    /** @var IEditService */
    private $service;

    /** @var TranslatorModel */
    private $translatorModel;

    /** @var int */
    private $upload;

    public function __construct($jsDir, $upload, IRequest $request, MockService $mockService, TranslatorModel $translatorModel) {
        parent::__construct($jsDir, $request, $translatorModel);
        $this->mockService = $mockService;
        $this->upload = $upload;
        $this->translatorModel = $translatorModel;
    }

    /** @return IEditFormFactory */
    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            $this->row->beforeAttached($this);
            $this->presenter = $presenter;
            foreach ($this->row->getColumns() as $column) {
                $name = $column['name'];
                $label = ucfirst($this->translatorModel->translate($this->row->getTable() . '.' . $name));
                $defaults = $this->getDefaults($name);
                $attributes = $this->getAttributes($column);
                if ('SUBMIT' == $column['nativetype']) {
                    $this->addSubmit($name, $label);
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
                    $attributes['data'] = $defaults;
                    $attributes['style'] = ['height' => '100%'];
                    $this->addSelect($name, $label . ':', $attributes);
                } elseif (in_array($column['nativetype'], ['DATETIME', 'TIMESTAMP', 'DATE'])) {
                    $attributes['value'] = (is_object($this->row->$name)) ? $this->row->$name->__toString() : date('Y-m-d H:i:s', strtotime('now'));
                    $this->addDateTimePicker($name, $label . ':', $attributes);
                } elseif ($column['nativetype'] == 'TINYINT') {
                    (1 == $this->row->$name) ? $attributes['checked'] = 'checked' : null;
                    $this->addCheckbox($name, $label, $attributes);
                } elseif (0 < substr_count($column['vendor']['Comment'], '@textarea')) {
                    $this->addTextArea($name, $label . ':', $attributes);
                } elseif (0 < substr_count($column['nativetype'], 'TEXT')) {
                    $this->addTextArea($name, $label . ':', $attributes);
                    /** @todo
                    $this[$name] = new WysiwygEditor($label, $this->presenter);
                    0 === substr_count($column['vendor']['Comment'], '@cke3') ? $this[$name]->setVersion(4) : $this[$name]->setVersion(3);
                    $this[$name]->setDefaultValue($this->row->$name);
                    $this->components[$name] = $column['nativetype'];*/
                } elseif (!empty($defaults) and is_array($defaults) and is_array($this->row->$name) and 'INT' == $column['nativetype']) {
                    $attributes['data'] = $defaults;
                    $attributes['style'] = ['height' => '100%'];
                    $this->addSelect($name, $label . ':', $attributes);
                } elseif (0 < substr_count($column['vendor']['Comment'], '@addMultiSelect') or ( !empty($defaults) and is_array($defaults) and is_array($this->row->$name))) {
                    $attributes['data'] = is_array($this->row->$name) ? $this->row->$name : json_decode($this->row->$name);
                    $this->addMultiSelect($name, $label . ':', $attributes);
                } elseif (!empty($defaults) and is_array($defaults)) {
                    $attributes['data'] = $defaults;
                    $attributes['style'] = ['height' => '100%'];
                    $this->addSelect($name, $label . ':', $attributes);
                } elseif ('DECIMAL' == $column['nativetype'] or 'FLOAT' == $column['nativetype']) {
                    $attributes['float'] = $this->translatorModel->translate('Set only numbers.');
                    $this->addText($name, $label . ':', [], $attributes);
                } elseif (0 < substr_count($column['vendor']['Comment'], '@upload')) {
                    $this->addUpload($name, $label);
                } elseif (0 < substr_count($column['vendor']['Comment'], '@multiupload')) {
                    $attributes['max'] = $this->upload;
                    $this->addMultiUpload($name, $label, $attributes);
                } elseif ('INT' == $column['nativetype']) {
                    $attributes['type'] = 'number';
                    $this->addText($name, $label . ':', $attributes);
                } else {
                    $this->addText($name, $label . ':', $attributes);
                }
            }
            if(!empty($this->row->getColumns())) {
                $this->addHidden('primary', 'primary', ['value' => $this->primary]);
                $this->addSubmit('save', ucfirst($this->translatorModel->translate('save')), ['class' => 'btn btn-success', 'onClick' => 'submit']);
            }
            $this->row->afterAttached($this);
        }
        return $this;

    }

    /** @return IEditFormFactory */
    public function create() {
        return $this;
    }

    /** @return array */
    private function getAttributes(array $column) {
        $name = $column['name'];
        $attributes = ['class' => 'form-control', 'value' => $this->row->$name];
        if(false == $column['nullable']) {
            $attributes['required'] = $column['name'] . ' ' . $this->translatorModel->translate('is required.');
        }
        if(substr_count($column['vendor']['Comment'], '@disable') > 0) {
            $attributes['readonly'] = 'readonly';
        }
        if(substr_count($column['vendor']['Comment'], '@onchange') > 0) {
            $attributes['onChange'] = 'submit';
        }
        return $attributes;
    }

    /** @return array */
    private function getDefaults($name) {
        $config = $this->row->getConfig($this->row->getTable() . '.' . $name);
        if(isset($config['getDefaults'])) {
            return $this->mockService->getCall($config['getDefaults']['service'], $config['getDefaults']['method'], $config['getDefaults']['parameters'], $this);
        } elseif(empty($config)) {
            foreach ($config as $method => $data) {
                if ('getDefaults' != $method) {
                    $result = $this->mockService->getCall($data['service'], $data['method'], $data['parameters'], $this);
                    $this[$name]->$method($result);
                }
            }
        }
        return [];
    }

    /** @return IEditFormFactory */
    public function setRow(IRowBuilder $row) {
        $this->row = $row;
        return $this;
    }

    /** @return IEditFormFactory */
    public function setService(IEditService $service) {
        $this->service = $service;
        return $this;
    }

    /** @return IEditFormFactory */
    public function setPrimary(array $primary) {
        $this->primary = $primary;
        return $this;
    }

}

interface IEditFormFactory {

    /** @return EditForm */
    function create();
}
