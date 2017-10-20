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

    /** @var array */
    private $lists = [];

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
        $this->appDir = (string) $appDir;
        $this->jsDir = (string) $jsDir;
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
            if(!empty($value = preg_replace('/\s(.*)/', '', $this->builder->getFilter($this->builder->getColumn($name) . ' ' . $operator)))
                && null == $spice = $this->getSpice($name . ' ' . $operator)) {
                $attributes['value'] = date($this->config['format']['date']['text'], strtotime($value));
                $this->filterForm->addText($name . ' ' . $operator, $label . ' ' . $this->translatorModel->translate($sign), $attributes);
            } else if (!empty($value)) {
                $attributes['value'] = date($this->config['format']['date']['text'], strtotime($spice));
                $this->filterForm->addText($name . ' ' . $operator, $label . ' ' . $this->translatorModel->translate($sign), $attributes);
            }
        }
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            $data = $this->builder->getDefaults();
            $this->spice = $this->builder->getSpice();
            $ordered = json_decode(urldecode($this->request->getUrl()->getQueryParameter(strtolower($this->getParent()->getName()) . '-sort')));
            foreach ($this->builder->getColumns() as $name => $annotation) {
                $order = (isset($ordered->$name)) ? $ordered->$name : null;
                $label = $this->builder->translate($name, $annotation);
                $style = $this->builder->getAnnotation($name, 'style');
                $attributes = ['class' =>'form-control',
                                'data' => $data[$name],
                                'filter' => $this->builder->getAnnotation($name, 'filter'),
                                'order' => $order,
                                'summary' => $this->builder->getAnnotation($name, 'summary'),
                                'style' => is_array($style) ? $style : null,
                                'unrender' => $this->builder->getAnnotation($name, 'unrender'),
                                'unfilter' => $this->builder->getAnnotation($name, 'unfilter'),
                                'unsort' => $this->builder->getAnnotation($name, 'unsort'),
                                'value' => $this->getSpice($name)];
                if(is_array($overwrite = $this->builder->getAnnotation($name, 'attributes'))) {
                    foreach($overwrite as $key => $attribute) {
                        $attributes[$key] = $attribute;
                    }
                }
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
                    $attributes['autocomplete'] = '';
                    $attributes['min-width'] = '10px';
                    $attributes['position'] = 0;
                    $attributes['placeholder'] = $this->translatorModel->translate('find') . ' ' . $this->translatorModel->translate($name);
                    $attributes['style'] = ['display' => 'none'];
                    $attributes['value'] = (array) $attributes['value'];
                    $this->lists[$name] = $name;
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
                $attributes['unrender'] = true;
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
        if($this->builder->getRemove() instanceof IRemove) {
            $size = sizeof($actions);
            $actions[$size]['onClick'] = 'signal';
            $actions[$size]['label'] = ucfirst($this->translatorModel->translate('remove'));
            $actions[$size]['class'] = 'fa-hover fa fa-remove';
            $actions[$size]['url'] = '';
            $actions[$size]['href'] = $this->link('remove');
        }
        if($this->builder->getGraph() instanceof IGraph) {
            $size = sizeof($actions);
            $actions[$size]['onClick'] = 'signal';
            $actions[$size]['label'] = ucfirst($this->translatorModel->translate('graph'));
            $actions[$size]['class'] = 'fa-hover fa fa-bar-chart';
            $actions[$size]['url'] = '';
            $actions[$size]['href'] = $this->link('graph');
        }
        return $actions;
    }

    /** @return array | string */
    private function getSpice($column) {
        if(isset($this->spice[$column])) {
            return $this->spice[$column];
        }
    }

    /** @return TextResponse */
    public function handleAdd() {
        $this->row->table($this->builder->getTable());
        $this->editForm->create()->setRow($this->row);
        $this->addComponent($this->editForm, 'editForm');
        return $this->presenter->sendResponse(new TextResponse($this->editForm->render()));
    }

    /** @return TextResponse */
    public function handleEdit() {
        $primary = $this->setRow();
        $this->editForm->create()->setRow($this->row)->setPrimary($primary)->addHidden('offset-row', 'offset-row', ['value' => $this->builder->getPost('hidden')]);
        $this->addComponent($this->editForm, 'editForm');
        return $this->presenter->sendResponse(new TextResponse($this->editForm->render()));
    }

    /** @return JsonResponse */
    public function handleFilter() {
        $rows = $this->builder->prepare()->getOffsets();
        $response = new JsonResponse($rows);
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handleGraph() {
        $data = [];
        $graph = $this->builder->getGraph()->graph($this->builder->getPost('spice'), $this->builder->getPost('row'));
        $percent = max($graph) / 100;
        foreach($graph as $key => $value) {
            if('position' == $key) {
            } else if($percent > 0) {
                $data[$key] = ['percent' => $value / $percent, 'value' => $value];
            } else {
                $data[$key] = ['percent' => 0, 'value' => $value];
            }
        }
        return $this->presenter->sendResponse(new JsonResponse(['graph'=>isset($graph['position']) ? $graph['position'] : '','data'=>$data]));
    }

    /** @return JsonResponse */
    public function handleListen() {
        $response = new JsonResponse($this->builder->getListener()->listen($this->builder->getPost('')));
        return $this->presenter->sendResponse($response);
    }

    /** @return TextResponse */
    public function handlePaginate() {
        $sum = $this->builder->prepare()->getSum();
        $total = ($sum > $this->builder->getPagination()) ? intval(ceil($sum / $this->builder->getPagination())) : 1;
        $response = new TextResponse($total);
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handlePush() {
        $response = new JsonResponse($this->builder->getButton()->push($this->builder->getPost('')));
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handleRemove() {
        $this->setRow();
        $this->row->check();
        $this->row->remove($this->builder->getPost('row'));
        $this->presenter->sendResponse(new JsonResponse(['remove'=>true]));
    }

    /** @return TextResponse */
    public function handleSetting() {
        $annotation = 'unrender';
        $path = $this->presenter->getName() . ':' . $this->presenter->getAction();
        $setting = (array) json_decode($this->user->getIdentity()->getData()[$this->config['settings']]);
        foreach($this->builder->getPost('') as $key => $value) {
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
        $post = $this->builder->getPost('');
        if(isset($post['offset-row'])) {
            $row = $post['offset-row'];
            unset($post['offset-row']);
        } else {
            $this->row->add($post);
            $this->presenter->sendResponse(new JsonResponse([]));
        }
        $this->row->update($post);
        $parameters = [];
        foreach($this->builder->getPost('') as $column => $value) {
            if(is_array($this->builder->getFilter($column)) ||
                is_array($this->builder->getFilter($this->builder->getTable() . '.' . $column)) ||
                strlen($this->builder->getFilter($column)) > 0 ||
                strlen($this->builder->getFilter($this->builder->getTable() . '.' . $column)) > 0) {
                $this->row->where($column, $value);
                $parameters[$column] = $value;
            }
        }
        $conditions = array_flip($this->row->getResource()->getSqlBuilder()->getConditions());
        $response = ['remove' => false];
        foreach($this->builder->getFilters() as $column => $value) {
            if(isset($conditions[$column]) && isset($parameters[$column]) && $parameters[$column] != $value) {
                $response = ['remove' => $row];
            }
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }
    
    /** @return TextResponse */
    public function handleSummary() {
        $response = new TextResponse($this->builder->prepare()->getSummary());
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handleUnique() {
        $this->setRow();
        $response = new JsonResponse($this->row->unique($this->builder->getPost('primary'), $this->builder->getPost('value')));
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handleUpdate() {
        $response = $this->builder->submit($this->builder->getPost(''));
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
        foreach ($this->builder->getPost('row') as $column => $value) {
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
        $columns = $this->filterForm->getData();
        if($this->builder->getGraph() instanceof IGraph) {
            $columns['graphs'] = ['Label'=> $this->translatorModel->translate('graphs'),'Method'=>'addButton','Attributes'=>
                ['class'=>'fa-hover fa fa-bar-chart','filter'=> false,'link'=>$this->link('graph'),'onClick'=>'graphs','summary' => false,'unrender' => false, 'unfilter' => false,'unsort'=>true]];
        }
        $export = [];
        $excel = [];
        if(is_object($this->getParent()->getGrid()->getExport())) {
            $excel = ['class' => 'btn btn-success',
                'label'=>'excel',
                'link' =>$this->getParent()->link('excel'),
                'style' => ['marginRight'=>'10px','float'=>'left'],
                'onClick' => 'prepare',
                'width' => 0];
            $export = ['class' => 'btn btn-success',
                'label'=> 'export',
                'link' => $this->getParent()->link('export'),
                'onClick' => 'prepare',
                'style' => ['marginRight'=>'10px','float'=>'left'],
                'width' => 0];
        }
        $dialogs = [];
        if(is_object($this->builder->getEdit())) {
            $dialogs['edit'] = ['label'=>$this->translatorModel->translate('add item'),'class'=>'btn btn-warning','link' => $this->link('add'), 'onClick'=>'add'];
        }
        foreach($this->builder->getDialogs() as $key) {
            $dialogs[$key] = ['label'=>$this->translatorModel->translate('dialog:' . $key),'class'=>'btn btn-warning','link' => $this->link($key), 'onClick'=>$key];
        }
        $buttons = is_object($this->builder->getEdit()) ? ['edit' => $this->link('edit')] : [];
        foreach($this->builder->getActions() as $key) {
            $buttons[$key] = $this->link($key);
        }
        $this->template->triggers = ['setting','reset','send','excel','export','process','done'];
        if($this->builder->getButton() instanceof IButton) {
            foreach($this->builder->getButton()->getButtons() as $buttonId => $button) {
                $this->template->triggers[] = $buttonId;
            }
        }
        $data = ['actions' => $this->getActions(),
                'buttons' => [
                    'done' => ['class' => 'alert alert-success',
                        'label' => $this->translatorModel->translate('Click here to download your file.'),
                        'link' => $this->getParent()->link('done'),
                        'style' => ['display'=>'none', 'marginRight' => '10px']],
                    'dialogs' => $buttons,
                    'export' => $export,
                    'excel' => $excel,
                    'filter' => $this->link('filter'),
                    'link' => $link,
                    'listen' => $this->link('listen'),
                    'page' => $page,
                    'pages' => 2,
                    'paginate' => $this->link('paginate'),
                    'process' => [],
                    'proceed' => $this->translatorModel->translate('Do you really want to proceed?'),
                    'push' => $this->link('push'),
                    'remove' => $this->link('remove'),
                    'reset' => ['label' =>$this->translatorModel->translate('reset form'),
                        'class' => 'btn btn-warning',
                        'onClick' => 'reset',
                        'style' => ['marginRight'=>'10px','float'=>'left']],
                    'run' => $this->getParent()->link('run'),
                    'send' => ['label' => $this->translatorModel->translate('filter data'),
                        'class' => 'btn btn-success',
                        'onClick' => 'submit',
                        'style' => ['marginRight'=>'10px', 'float'=>'left']],
                    'setting' => isset($this->user->getIdentity()->getData()[$this->config['settings']]) ? ['class' => 'btn btn-success',
                        'display' => ['none'],
                        'label'=> $this->translatorModel->translate('setting'),
                        'link' =>$this->link('setting'),
                        'onClick' => 'setting',
                        'style' => ['marginRight'=>'10px','float'=>'left']] : false,
                    'summary' => $this->link('summary'),
                        'triggers' => $this->template->triggers,
                        'update' => $this->link('update')],
                    'columns' => $columns,
                    'dialogs' => $dialogs,
                    'graphs' => [],
                    'listeners' => [],
                    'lists' => $this->lists,
                    'rows' => []];
        if($this->builder->getListener() instanceof IListener) {
            $data['listeners'] = $this->builder->getListener()->getKeys();
        }
        if($this->builder->getButton() instanceof IButton) {
            foreach($this->builder->getButton()->getButtons() as $buttonId => $button) {
                $data['buttons'][$buttonId] = $button;
                $data['buttons'][$buttonId]['onClick'] = 'push';
            }
        }
        if($this->builder->getService() instanceof IProcess) {
            $data['buttons']['process'] = ['class' => 'btn btn-success',
                'label' => $this->translatorModel->translate('process'),
                'link' => $this->getParent()->link('prepare'),
                'style' => ['marginRight'=>'10px','float'=>'left'],
                'onClick' => 'prepare'];
        }
        $this->template->data = json_encode($data);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->render();
    }

}

interface IGridFactory {

    /** @return Grid */
    function create();
}