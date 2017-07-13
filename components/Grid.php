<?php

namespace Masala;

use Nette\Application\IPresenter,
    Nette\Application\Responses\JsonResponse,
    Nette\Application\Responses\TextResponse,
    Nette\Application\UI\Control,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator,
    Nette\Security\User;

/** @author Lubomir Andrisek */
final class Grid extends Control implements IGridFactory {

    /** @var string */
    private $appDir;

    /** @var string */
    private $config;

    /** @var IEditFormFactory */
    private $editForm;

    /** @var IReactFormFactory */
    private $filterForm;

    /** @var IBuilder */
    private $builder;

    /** @var string */
    private $jsDir;

    /** @var IRequest */
    private $request;

    /** @var IRow */
    private $row;

    /** @var array */
    private $spice;

    /** @var ITranslator */
    private $translatorModel;

    /** @var User */
    private $user;

    /** @var IUser */
    private $usersModel;

    public function __construct($appDir, $jsDir, array $config, FilterForm $filterForm, IEditFormFactory $editForm, IRequest $request, IRow $row, ITranslator $translatorModel, IUser $usersModel, User $user) {
        $this->appDir = $appDir;
        $this->jsDir = $jsDir;
        $this->config = $config;
        $this->editForm = $editForm;
        $this->filterForm = $filterForm;
        $this->request = $request;
        $this->row = $row;
        $this->translatorModel = $translatorModel;
        $this->user = $user;
        $this->usersModel = $usersModel;
    }

    private function addDate($name, $label, $attributes) {
        $operators = ['>' => 'from', '<' => 'to', '>=' => 'from', '<=' => 'to'];
        $attributes['class'] = 'form-control datetimepicker';
        $attributes['onBlur'] = 'change';
        $attributes['filter'] = true;
        $attributes['unfilter'] = true;
        $attributes['data'] = preg_replace('/\.m\./', '.MM.',
                                preg_replace('/d/', 'DD',
                                preg_replace('/\.Y/', '.YYYY', $this->config['format']['date']['text'])));
        foreach($operators as $operator => $sign) {
            if(!empty($value = $this->builder->getFilter($this->builder->getColumn($name) . ' ' . $operator)) and null == $spice = $this->getSpice($name . ' ' . $operator)) {
                $attributes['value'] = date($this->config['format']['date']['text'], strtotime($value));
                $this->filterForm->addText($name . ' ' . $operator, $label . ' ' . $this->translatorModel->translate($sign), $attributes);
            } else if (!empty($value = $this->builder->getFilter($this->builder->getColumn($name) . ' ' . $operator))) {
                $attributes['value'] = date($this->config['format']['date']['text'], strtotime($spice));
                $this->filterForm->addText($name . ' ' . $operator, $label . ' ' . $this->translatorModel->translate($sign), $attributes);
            }
        }
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            $data = $this->builder->getDefaults();
            $this->spice = json_decode(urldecode($this->request->getUrl()->getQueryParameter(strtolower($this->getParent()->getName()) . '-spice')));
            $ordered = json_decode(urldecode($this->request->getUrl()->getQueryParameter(strtolower($this->getParent()->getName()) . '-sort')));
            foreach ($this->builder->getColumns() as $name => $annotation) {
                $order = (isset($ordered->$name)) ? $ordered->$name : null;
                $label = $this->builder->translate($name, $annotation);
                $attributes = ['class' =>'form-control',
                                'data' => $data[$name],
                                'filter' => $this->builder->getAnnotation($name, 'filter'),
                                'order' => $order,
                                'summary' => $this->builder->getAnnotation($name, 'summary'),
                                'unrender' => $this->builder->getAnnotation($name, 'unrender'),
                                'unfilter' => $this->builder->getAnnotation($name, 'unfilter'),
                                'value' => $this->getSpice($name)];
                if(true == $attributes['unfilter'] && true == $this->builder->getAnnotation($name, ['addCheckbox', 'addDate', 'addMultiSelect', 'addSelect', 'addText'])) {
                    $attributes['filter'] = true;
                }
                if (true == $this->builder->getAnnotation($name, 'hidden')) {
                } elseif (true == $this->builder->getAnnotation($name, 'addCheckbox')) {
                    $this->filterForm->addCheckbox($name, $label, $attributes);
                } elseif (true == $this->builder->getAnnotation($name, 'addDate')) {
                    $this->addDate($name, $label, $attributes);
                } elseif(true == $this->builder->getAnnotation($name, 'addMultiSelect')) {
                    $attributes['data'] = [null => $this->translatorModel->translate('--unchosen--')] + $attributes['data'];
                    $attributes['min-width'] = '10px';
                    $this->filterForm->addMultiSelect($name, $label, $attributes);
                } elseif (is_array($data[$name]) and ! empty($data[$name]) && false == $attributes['unrender']) {
                    $attributes['data'] = [null => $this->translatorModel->translate('--unchosen--')] + $attributes['data'];
                    $this->filterForm->addSelect($name, $label, $attributes);
                } elseif(true == $this->builder->getAnnotation($name, 'addText')) {
                    $this->filterForm->addText($name, $label, $attributes);
                } elseif(true == $this->builder->getAnnotation($name, 'addSelect')) {
                    $attributes['data'] = [null => $this->translatorModel->translate('--unchosen--')] + $attributes['data'];
                    $this->filterForm->addSelect($name, $label, $attributes);
                } elseif(false == $attributes['unrender'] && true == $attributes['unfilter']) {
                    $this->filterForm->addEmpty($name, $label, $attributes);
                } elseif(false == $this->builder->getAnnotation($name, 'unrender')) {
                    $this->filterForm->addText($name, $label, $attributes);
                }  elseif(true == $this->builder->getAnnotation($name, 'unrender')) {
                    $this->filterForm->addHidden($name, $label, $attributes);
                }
            }
            if(sizeof($groups = $this->builder->getGroup()) > 1) {
                $attributes['data'] = [];
                foreach($groups as $group) {
                    $attributes['data'][] = $this->translatorModel->translate('grouping:' . $group);
                }
                $attributes['value'] = '_0';
                $attributes['filter'] = true;
                $this->filterForm->addSelect('groups', $this->translatorModel->translate('grouping'), $attributes);
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
            if(isset($row->signal) && isset($row->presenter)) {
                $actions[$rowId]['onClick'] = 'signal';
                $actions[$rowId]['href'] = $this->getPresenter()->link('this', ['do' => $row->signal]);
            } else if(isset($row->signal)) {
                    $actions[$rowId]['onClick'] = $row->signal;
                    $actions[$rowId]['href'] = $this->link('this', ['do' => $row->signal]);
            } else if(!isset($row->presenter) and !isset($row->action)) {
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

    public function handleRemove() {

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
        $rows = $this->builder->prepare()->getOffsets();
        $response = new JsonResponse($rows);
        return $this->presenter->sendResponse($response);
    }

    /** @return TextResponse */
    public function handlePaginate() {
        $sum = $this->builder->prepare()->getSum();
        $total = ($sum > $this->builder->getPagination()) ? intval(ceil($sum / $this->builder->getPagination())) : 1;
        $response = new TextResponse($total);
        return $this->presenter->sendResponse($response);
    }

    /** @return TextResponse */
    public function handleSetting() {
        $annotation = 'unrender';
        $path = $this->presenter->getName() . ':' . $this->presenter->getAction();
        $setting = (array) json_decode($this->user->getIdentity()->getData()[$this->config['settings']]);
        foreach($this->presenter->request->getPost() as $key => $value) {
            if(isset($setting[$path]->$key) && 'true' == $value && !preg_match('/@' . $annotation . '/', $setting[$path]->$key)) {
                $setting[$path]->$key = $setting[$path]->$key . '@' . $annotation;
            } else if(isset($setting[$path]->$key) && 'false' == $value && '@' . $annotation == $setting[$path]->$key && 1 == sizeof($setting[$path])) {
                unset($setting[$path]);
            } else if(isset($setting[$path]->$key) && 'false' == $value && '@' . $annotation == $setting[$path]->$key) {
                unset($setting[$path]->$key);
            } else if(isset($setting[$path]->$key)) {
                $setting[$path]->$key = preg_replace('/@' . $annotation . '/', '', $setting[$path]->$key);
            } else if(isset($setting[$path]) && 'true' == $value) {
                $setting[$path]->$key = '@' . $annotation;
            } else if('true' == $value) {
                $setting[$path] = [$key => '@' . $annotation];
            }
        }
        $this->user->getIdentity()->__set($this->config['settings'], $user = json_encode($setting));
        $response = new TextResponse($this->usersModel->updateUser($this->user->getId(), [$this->config['settings'] => $user]));
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handleSubmit() {
        $this->setRow();
        $this->presenter->sendResponse(new JsonResponse($this->row->update($this->request->getPost())));
    }
    
    /** @return TextResponse */
    public function handleSummary() {
        $response = new TextResponse($this->builder->prepare()->getSummary());
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handleUpdate() {
        $response = $this->builder->submit($this->request->getPost());
        $this->presenter->sendResponse(new JsonResponse($response));
    }

    /** @return IGridFactory */
    public function setGrid(IBuilder $grid) {
        $this->builder = $grid;
        return $this;
    }

    /** @return array */
    private function setRow() {
        $this->row->table($this->builder->getTable());
        $primary = array_flip($this->builder->getPrimary());
        $result = [];
        foreach ($this->request->getPost() as $column => $value) {
            if(isset($primary[$column])) {
                $this->row->where($column, $value);
                $result[$primary[$column]] = $value;
            }
        }
        if($this->builder->getEdit() instanceof IEdit) {
            $this->row->process($this->builder->getEdit());
        }
        return $result;
    }

    /** @return void */
    public function render(...$args) {
        $this->template->setFile(__DIR__ . '/../templates/grid.latte');
        $this->template->component = $this->getName();
        $this->template->locale = preg_replace('/(\_.*)/', '', $this->translatorModel->getLocale());
        $url = $this->request->getUrl();
        $parameters = $url->getQueryParameters();
        $spice = strtolower($this->getParent()->getName()) . '-spice';
        $pagination = strtolower($this->getParent()->getName()) . '-page';
        $page = isset($parameters[$pagination]) ? intval($parameters[$pagination]) : 1;
        unset($parameters[$spice]);
        unset($parameters[$pagination]);
        unset($parameters[strtolower($this->getParent()->getName()) . '-sort']);
        $port = is_int($url->port) ? ':' . $url->port : '';
        $link = $url->scheme . '://' . $url->host . $port . $url->path . '?';
        foreach($parameters as $parameterId => $parameter) {
            $link .= $parameterId . '=' . $parameter . '&';
        }
        $link .= $spice . '=';
        $data = $this->filterForm->getData();
        $export = [];
        $excel = [];
        if(is_object($this->getParent()->getGrid()->getExport())) {
            $excel = ['class' => 'btn btn-success',
                'label'=>'excel',
                'link' =>$this->getParent()->link('excel'),
                'width' => 0,
                'onClick' => 'prepare'];
            $export = ['class' => 'btn btn-success',
                'label'=>'export',
                'link' =>$this->getParent()->link('export'),
                'width' => 0,
                'onClick' => 'prepare'];
        }
        $this->template->data = json_encode(['actions' => $this->getActions(),
                                                'columns' => $data,
                                                'buttons' => [
                                                    'done' => ['class' => 'alert alert-success',
                                                                'label' => $this->translatorModel->translate('Click here to download your file.'),
                                                                'link' => $this->getParent()->link('done'),
                                                                'style' => ['display'=>'none', 'marginRight' => '10px']],
                                                    'edit' => $this->link('edit'),
                                                    'export' => $export,
                                                    'excel' => $excel,
                                                    'filter' => $this->link('filter'),
                                                    'link' => $link,
                                                    'page' => $page,
                                                    'pages' => 2,
                                                    'paginate' => $this->link('paginate'),
                                                    'remove' => $this->link('remove'),
                                                    'reset' => ['label' =>$this->translatorModel->translate('reset form'),
                                                                'class' => 'btn btn-warning',
                                                                'onClick' => 'reset'],
                                                    'run' => $this->getParent()->link('run'),
                                                    'send' => ['label' => $this->translatorModel->translate('filter data'),
                                                        'class' => 'btn btn-success',
                                                        'onClick' => 'submit'],
                                                    'setting' => ['class' => 'btn btn-success',
                                                        'display' => ['none'],
                                                        'enabled' => isset($this->user->getIdentity()->getData()[$this->config['settings']]),
                                                        'label'=> $this->translatorModel->translate('setting'),
                                                        'link' =>$this->link('setting'),
                                                        'onClick' => 'setting'],
                                                    'summary' => $this->link('summary'),
                                                    'update' => $this->link('update')],
                                                'rows' => []]);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->render();
    }

}

interface IGridFactory {

    /** @return Grid */
    function create();
}
