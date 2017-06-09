<?php

namespace Masala;

use Nette\Application\IPresenter,
    Nette\Application\Responses\JsonResponse,
    Nette\Application\Responses\TextResponse,
    Nette\Application\UI\Control,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class Grid extends Control implements IGridFactory {

    /** @var string */
    private $appDir;

    /** @var IEditFormFactory */
    private $editForm;

    /** @var IReactFormFactory */
    private $filterForm;

    /** @var IBuilder */
    private $grid;

    /** @var string */
    private $jsDir;

    /** @var IRequest */
    private $request;

    /** @var IRowBuilder */
    private $row;

    /** @var array */
    private $spice;

    /** @var ITranslator */
    private $translatorModel;

    public function __construct($appDir, $jsDir, FilterForm $filterForm, IEditFormFactory $editForm, IRequest $request, IRowBuilder $row, ITranslator $translatorModel) {
        $this->appDir = $appDir;
        $this->jsDir = $jsDir;
        $this->editForm = $editForm;
        $this->filterForm = $filterForm;
        $this->request = $request;
        $this->row = $row;
        $this->translatorModel = $translatorModel;
    }

    private function addDate($name, $label, $attributes) {
        $operators = ['>' => 'from', '<' => 'to', '>=' => 'from', '<=' => 'to'];
        $attributes['class'] = 'form-control datetimepicker';
        $attributes['onBlur'] = 'change';
        $attributes['filter'] = true;
        foreach($operators as $operator => $sign) {
            if(!empty($value = $this->grid->getFilter($this->grid->getColumn($name) . ' ' . $operator)) and null == $spice = $this->getSpice($name . ' ' . $operator)) {
                $attributes['value'] = $value;
                $this->filterForm->addText($name . ' ' . $operator, $label . ' ' . $this->translatorModel->translate($sign), $attributes);
            } else if (!empty($value = $this->grid->getFilter($this->grid->getColumn($name) . ' ' . $operator))) {
                $attributes['value'] = $spice;
                $this->filterForm->addText($name . ' ' . $operator, $label . ' ' . $this->translatorModel->translate($sign), $attributes);
            }
        }
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            $data = $this->grid->getDefaults();
            $this->spice = json_decode(urldecode($this->request->getUrl()->getQueryParameter(strtolower($this->getParent()->getName()) . '-spice')));
            $ordered = json_decode(urldecode($this->request->getUrl()->getQueryParameter(strtolower($this->getParent()->getName()) . '-sort')));
            foreach ($this->grid->getColumns() as $name => $annotation) {
                $order = (isset($ordered->$name)) ? $ordered->$name : null;
                if ($presenter->getName() . ':' . $presenter->getAction() . ':' . $name != $label = $this->translatorModel->translate($presenter->getName() . ':' . $presenter->getAction() . ':' . $name)) {
                } elseif ($presenter->getName() . ':' . $name != $label = $this->translatorModel->translate($presenter->getName() . ':' . $name)) {
                } elseif ($annotation != $label = $this->translatorModel->translate($annotation)) {
                } elseif ($label = $this->translatorModel->translate($name)) {
                }
                $attributes = ['class' =>'form-control',
                                'data' => $data[$name],
                                'filter' => $this->grid->getAnnotation($name, 'filter'),
                                'order' => $order,
                                'unrender' => $this->grid->getAnnotation($name, 'unrender'),
                                'unfilter' => $this->grid->getAnnotation($name, 'unfilter'),
                                'value' => $this->getSpice($name)];
                if(true == $attributes['unfilter'] && true == $this->grid->getAnnotation($name, ['addCheckbox', 'addDate', 'addMultiSelect', 'addSelect', 'addText'])) {
                    $attributes['filter'] = true;
                }
                if (true == $this->grid->getAnnotation($name, 'hidden')) {
                } elseif (true == $this->grid->getAnnotation($name, 'addCheckbox')) {
                    $this->filterForm->addCheckbox($name, $label, $attributes);
                } elseif (true == $this->grid->getAnnotation($name, 'addDate')) {
                    $this->addDate($name, $label, $attributes);
                } elseif (true == $this->grid->getAnnotation($name, 'range') or is_array($this->grid->getRange($name))) {
                    $this->filterForm->addRange($name, $label, $attributes);
                } elseif(true == $this->grid->getAnnotation($name, 'addMultiSelect')) {
                    $attributes['data'] = [null => $this->translatorModel->translate('--unchosen--')] + $attributes['data'];
                    $attributes['min-width'] = '10px';
                    $this->filterForm->addMultiSelect($name, $label, $attributes);
                } elseif (is_array($data[$name]) and ! empty($data[$name])) {
                    $attributes['data'] = [null => $this->translatorModel->translate('--unchosen--')] + $attributes['data'];
                    $this->filterForm->addSelect($name, $label, $attributes);
                } elseif(true == $this->grid->getAnnotation($name, 'addText')) {
                    $this->filterForm->addText($name, $label, $attributes);
                } elseif(true == $this->grid->getAnnotation($name, 'addSelect')) {
                    $attributes['data'] = [null => $this->translatorModel->translate('--unchosen--')] + $attributes['data'];
                    $this->filterForm->addSelect($name, $label, $attributes);
                } elseif(true == $this->grid->getAnnotation($name, 'unrender')) {
                    $this->filterForm->addEmpty($name, $label, $attributes);
                } elseif(false == $this->grid->getAnnotation($name, 'unrender')) {
                    $this->filterForm->addText($name, $label, $attributes);
                }
            }
        }
    }

    /** @return IGridFactory */
    public function create() {
        return $this;
    }

    /** @return array */
    private function getActions() {
        $template = $this->appDir . '/' . preg_replace('/\:/', 'Module/templates/', $this->presenter->getName()) . '/';
        if (is_file($json = $template . 'actions.' . $this->presenter->getAction() . '.json') && is_object($rows = json_decode(file_get_contents($json)))) {
        } elseif (is_file($json = $template . 'actions.json') && is_object($rows = json_decode(file_get_contents($json)))) {
        } else  {
            $rows = [];
        }
        $actions = [];
        foreach($rows as $rowId => $row) {
            if(!isset($row->presenter) and !isset($row->action)) {
                $actions[$rowId]['href'] = $this->getPresenter()->link('this');
            } elseif(!isset($row->presenter)) {
                $actions[$rowId]['href'] = $this->getPresenter()->link(':' . $this->getPresenter()->getName() . ':' . $row->action);
            } elseif(!isset($row->action)) {
                $actions[$rowId]['href'] = $this->getPresenter()->link(':' . $row->presenter . ':' . $this->getPresenter()->getAction());
            } else {
                $actions[$rowId]['href'] = $this->getPresenter()->link(':' . $row->presenter . ':' . $row->action);
            }
            $actions[$rowId]['label'] = isset($row->label) ? $this->translatorModel->translate($row->label) : '';
            $actions[$rowId]['class'] = isset($row->class)? $row->class : 'hide';
            $actions[$rowId]['parameters'] = isset($row->parameters) ? $row->parameters : [];
            $actions[$rowId]['url'] = isset($row->url) ? $row->url : '';
        }
        return $actions;
    }

    /** @return array | string */
    private function getSpice($column) {
        if(isset($this->spice->$column)) {
            return $this->spice->$column;
        }
    }

    public function handleDelete() {

    }

    /** @return TextResponse */
    public function handleEdit() {
        $primary = $this->setRow();
        $this->editForm->create()->setRow($this->row)->setPrimary($primary);
        $this->addComponent($this->editForm, 'editForm');
        return $this->presenter->sendResponse(new TextResponse($this->editForm->attached($this->presenter)->render()));
    }

    /** @return JsonResponse */
    public function handleFilter() {
        $rows = $this->grid->filter()->getOffsets();
        $response = new JsonResponse($rows);
        return $this->presenter->sendResponse($response);
    }

    /** @return TextResponse */
    public function handlePaginate() {
        $sum = $this->grid->filter()->getSum();
        $total = ($sum > $this->grid->getPagination()) ? intval(round($sum / $this->grid->getPagination())) : 1;
        $response = new TextResponse($total);
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handleSubmit() {
        $this->setRow();
        $response = $this->row->submit($this->request->getPost());
        $primary = $response['primary'];
        unset($response['primary']);
        $this->row->update($primary, $response);
        $this->presenter->sendResponse(new JsonResponse($response));
    }

    /** @return IGridFactory */
    public function setGrid(IBuilder $grid) {
        $this->grid = $grid;
        return $this;
    }

    /** @return array */
    private function setRow() {
        $this->row->table($this->grid->getTable());
        $primary = array_flip($this->grid->getPrimary());
        $result = [];
        foreach ($this->request->getPost() as $column => $value) {
            if(isset($primary[$column])) {
                $this->row->where($column, $value);
                $result[$primary[$column]] = $value;
            }
        }
        return $result;
    }

    /** @return void */
    public function render(...$args) {
        $this->template->setFile(__DIR__ . '/../templates/grid.latte');
        $this->template->component = $this->getName();
        $url = $this->request->getUrl();
        $parameters = $url->getQueryParameters();
        $spice = strtolower($this->getParent()->getName()) . '-spice';
        $pagination = strtolower($this->getParent()->getName()) . '-page';
        $page = isset($parameters[$pagination]) ? intval($parameters[$pagination]) : 1;

        unset($parameters[$spice]);
        unset($parameters[$pagination]);
        unset($parameters[strtolower($this->getParent()->getName()) . '-sort']);
        $link = $url->scheme . '://' . $url->host . $url->path . '?';
        foreach($parameters as $parameterId => $parameter) {
            $link .= $parameterId . '=' . $parameter . '&';
        }
        $link .= $spice . '=';
        $data = $this->filterForm->getData();
        $export = [];
        if(is_object($this->getParent()->getGrid()->getExport())) {
            $export = ['class' => 'btn btn-success',
                'label'=>'export',
                'link' =>$this->getParent()->link('export'),
                'width' => 0,
                'onClick' => 'prepare'];
        }
        $this->template->data = json_encode(['actions' => $this->getActions(),
                                                'columns' => $data,
                                                'buttons' => [
                                                    'delete' => $this->link('delete'),
                                                    'done' => ['class' => 'alert alert-success',
                                                                'label' => $this->translatorModel->translate('Click here to download your file.'),
                                                                'link' => $this->getParent()->link('done'),
                                                                'style' => ['display'=>'none', 'marginRight' => '10px']],
                                                    'edit' => $this->link('edit'),
                                                    'export' => $export,
                                                    'filter' => $this->link('filter'),
                                                    'link' => $link,
                                                    'page' => $page,
                                                    'pages' => 2,
                                                    'paginate' => $this->link('paginate'),
                                                    'reset' => ['label' =>$this->translatorModel->translate('reset form'),
                                                                'class' => 'btn btn-warning',
                                                                'onClick' => 'reset'],
                                                    'send' => ['label' => $this->translatorModel->translate('filter data'),
                                                                'class' => 'btn btn-success',
                                                                'onClick' => 'submit'],
                                                    'run' => $this->getParent()->link('run'),
                                                    'submit' => $this->link('submit')],
                                                'rows' => []]);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->render();
    }

}

interface IGridFactory {

    /** @return Grid */
    function create();
}
