<?php

namespace Masala;

use Nette\Application\UI,
    Nette\Database\Table\ActiveRow,
    Nette\Http\IRequest,
    Nette\Http\UrlScript,
    Models\TranslatorModel;

/** @author Lubomir Andrisek */
final class GridForm extends UI\Form implements IGridFormFactory {

    /** @var IBuilder */
    private $grid;

    /** @var TranslatorModel */
    private $translatorModel;

    /** @var MockService */
    private $mockService;

    /** @var UrlScript */
    private $url;

    /** @var Array */
    private $parent = [];

    /** @var Array */
    private $defaults = [];

    /** @var Array */
    private $components = [];

    public function __construct(TranslatorModel $translatorModel, MockService $mockService, IRequest $request) {
        parent::__construct(null, null);
        $this->translatorModel = $translatorModel;
        $this->mockService = $mockService;
        $this->url = $request->getUrl();
    }

    /** @return IGridFormFactory */
    public function create() {
        return $this;
    }

    /** setters */
    public function setGrid(IBuilder $grid) {
        $this->grid = $grid;
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof UI\Presenter) {
            $this->setMethod('post');
            $types = [];
            foreach ($this->grid->getColumns() as $column => $annotation) {
                if (true == $this->grid->getAnnotation($column, 'select')) {
                    $config = $this->grid->getConfig($this->grid->getTable() . '.' . $column);
                    $default = $config['getDefaults'];
                    $this->defaults[$column] = $this->mockService->getCall($default['service'], $default['method'], $default['parameters'], $this);
                    $types[$column] = 'select';
                } elseif (!empty($annotation = $this->grid->getAnnotation($column, '[SELECT:'))) {
                    $config = $this->grid->getConfig($this->grid->getTable() . '.' . $column);
                    $default = $config['getDefaults'];
                    $this->parent[$column] = preg_replace('/(.*)\[SELECT\:|\](.*)/', '', $annotation);
                    $types[$column] = 'child';
                } elseif (true == $this->grid->getAnnotation($column, '[ONCHANGE]')) {
                    $config = $this->grid->getConfig($this->grid->getTable() . '.' . $column);
                    $default = $config['getDefaults'];
                    $this->defaults[$column] = $this->mockService->getCall($default['service'], $default['method'], $default['parameters'], $this);
                    $types[$column] = 'onchange';
                }
            }
            $service = $this->grid->getGridService();
            $service instanceof IEditFormService ? $service->beforeAttached($this) : null;
            if (!empty($this->defaults) or $service instanceof IEditFormService) {
                foreach ($this->grid->getData() as $column => $row) {
                    $primary = ($row instanceof ActiveRow) ? $row->getPrimary() : $row->id;
                    foreach ($row as $id => $value) {
                        if (isset($types[$id]) and 'child' == $types[$id]) {
                            $config = $this->grid->getConfig($this->grid->getTable() . '.' . $id);
                            $default = $config['getDefaults'];
                            $parentId = $default['parameters'];
                            if (!isset($this->defaults[$this->parent[$id] . '_' . $row->$parentId])) {
                                $this->defaults[$this->parent[$id] . '_' . $row->$parentId] = $this->mockService->getCall($default['service'], $default['method'], $row->$parentId, $this);
                            }
                            $this->addSelect($id . '_' . $primary, ucfirst($this->translatorModel->translate($column)), $this->defaults[$this->parent[$id] . '_' . $row->$parentId])
                                    ->setDefaultValue($row->$id)
                                    ->setAttribute('class', 'form-control');
                            $this->components[$id . '_' . $primary] = $row->$id;
                        } elseif (!isset($this->defaults[$id])) {
                            
                        } elseif ('select' == $types[$id]) {
                            $this->addSelect($id . '_' . $primary, ucfirst($this->translatorModel->translate($column)), $this->defaults[$id])
                                    ->setDefaultValue($row->$id)
                                    ->setAttribute('class', 'form-control');
                            $this->components[$id . '_' . $primary] = $row->$id;
                        } elseif ('onchange' == $types[$id]) {
                            $this->addSelect($id . '_' . $primary, ucfirst($this->translatorModel->translate($column)), $this->defaults[$id])
                                    ->setDefaultValue($row->$id)
                                    ->setAttribute('class', 'form-control')
                                    ->setAttribute('onchange', 'submit("test")');
                            $this->components[$id . '_' . $primary] = $row->$id;
                        }
                    }
                    $service instanceof IEditFormService ? $service->add($this, $row) : null;
                }
            }
            $this->parent = array_flip($this->parent);
        }
    }

}

interface IGridFormFactory {

    /** @return GridForm */
    function create();
}
