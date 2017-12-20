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

    /** @var IBuilder */
    private $builder;

    /** @var string */
    private $config;

    /** @var IReactFormFactory */
    private $filterForm;

    /** @var string */
    private $jsDir;

    /** @var array */
    private $lists = [];

    /** @var IRequest */
    private $request;

    /** @var array */
    private $row = [];

    /** @var array */
    private $spice;

    /** @var ITranslator */
    private $translatorModel;

    /** @var User */
    private $user;

    /** @var IUser */
    private $usersModel;

    public function __construct($appDir, $jsDir, array $config, IFilterFormFactory $filterForm, IRequest $request, ITranslator $translatorModel, IUser $usersModel, User $user) {
        $this->appDir = Types::string($appDir);
        $this->jsDir = Types::string($jsDir);
        $this->config = $config;
        $this->filterForm = $filterForm;
        $this->request = $request;
        $this->translatorModel = $translatorModel;
        $this->user = $user;
        $this->usersModel = $usersModel;
    }

    /** @return array */
    private function action($key) {
        return ['Attributes' => ['className' => 'fa-hover fa fa-' . Types::string($key), 'link' => $this->link($key)],
            'Tag' => 'a',
            'Label' => $this->translatorModel->translate($key)];
    }

    private function addDate($name, $label, $attributes) {
        $operators = ['>' => 'from', '<' => 'to', '>=' => 'from', '<=' => 'to'];
        $attributes['class'] = 'form-control';
        $attributes['filter'] = true;
        $attributes['unfilter'] = true;
        $attributes['format'] = $this->config['format']['date']['edit'];
        $attributes['locale'] = preg_replace('/(\_.*)/', '', $this->translatorModel->getLocale());
        foreach($operators as $operator => $sign) {
            if(!empty($value = preg_replace('/\s(.*)/', '', $this->builder->getFilter($this->builder->getColumn($name) . ' ' . $operator)))
                && null == $spice = $this->getSpice($name . ' ' . $operator)) {
                $attributes['value'] = date($this->config['format']['date']['edit'], strtotime($value));
                $this->filterForm->addDateTime($name . ' ' . $operator, $label . ' ' . $this->translatorModel->translate($sign), $attributes);
            } else if (!empty($value)) {
                $attributes['value'] = date($this->config['format']['date']['edit'], strtotime($spice));
                $this->filterForm->addDateTime($name . ' ' . $operator, $label . ' ' . $this->translatorModel->translate($sign), $attributes);
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
                $this->row[$name] = $this->builder->getAnnotation($name, ['enum', 'addSelect', 'addMultiSelect']) ? '_' : null;
                $order = (isset($ordered->$name)) ? $ordered->$name : null;
                $label = $this->builder->translate($name, $annotation);
                $style = $this->builder->getAnnotation($name, 'style');
                $attributes = ['className' =>'form-control',
                                'data' => $data[$name],
                                'filter' => $this->builder->getAnnotation($name, 'filter'),
                                'order' => $order,
                                'summary' => $this->builder->getAnnotation($name, 'summary'),
                                'style' => is_array($style) ? $style : null,
                                'unrender' => $this->builder->getAnnotation($name, 'unrender') || $this->builder->getAnnotation($name, 'pri'),
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
                } elseif (true == $this->builder->getAnnotation($name, 'addDateTime')) {
                    $attributes['format'] = $this->config['format']['time']['edit'];
                    $attributes['locale'] = preg_replace('/(\_.*)/', '', $this->translatorModel->getLocale());
                    $attributes['value'] = is_null($attributes['value']) ? $attributes['value'] : date($attributes['format'], strtotime($attributes['value']));
                    $this->filterForm->addDateTime($name, $label, $attributes);
                } elseif (true == $this->builder->getAnnotation($name, 'addDate')) {
                    $attributes['format'] = $this->config['format']['date']['edit'];
                    $attributes['locale'] = preg_replace('/(\_.*)/', '', $this->translatorModel->getLocale());
                    $attributes['value'] = is_null($attributes['value']) ? $attributes['value'] : date($attributes['format'], strtotime($attributes['value']));
                    $this->filterForm->addDateTime($name, $label, $attributes);
                } elseif (true == $this->builder->getAnnotation($name, 'addRange')) {
                    $this->addDate($name, $label, $attributes);
                } elseif(true == $this->builder->getAnnotation($name, 'addMultiSelect')) {
                    $attributes['data'] = $this->translate($attributes['data']);
                    $attributes['autocomplete'] = '';
                    $attributes['min-width'] = '10px';
                    $attributes['position'] = 0;
                    $attributes['placeholder'] = $this->translatorModel->translate('find') . ' ' . $this->translatorModel->translate($name);
                    $attributes['style'] = ['display' => 'none'];
                    $attributes['value'] = (array) $attributes['value'];
                    $this->lists[$name] = $name;
                    $this->filterForm->addMultiSelect($name, $label, $attributes);
                } elseif (is_array($data[$name]) and ! empty($data[$name]) && false == $attributes['unrender']) {
                    $attributes['data'] = $this->translate($attributes['data']);
                    $this->filterForm->addSelect($name, $label, $attributes);
                } elseif(true == $this->builder->getAnnotation($name, 'addText')) {
                    $this->filterForm->addText($name, $label, $attributes);
                } elseif(true == $this->builder->getAnnotation($name, 'addSelect')) {
                    $attributes['data'] = $this->translate($attributes['data']);
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

    /** @return array | string */
    private function getSpice($column) {
        if(isset($this->spice[$column])) {
            return $this->spice[$column];
        }
    }

    /** @return void */
    public function handleAdd() {
        $this->presenter->sendResponse(new JsonResponse($this->builder->add()));
    }

    /** @return void */
    public function handleEdit() {
        $row = $this->builder->row($this->builder->getPost('id'), $this->builder->getPost('row'));
        if($this->builder->isEdit()) {
            $this->builder->getEdit()->after($row);
        }
        $this->presenter->sendResponse(new JsonResponse($row->getData()));
    }

    /** @return void */
    public function handleFilter() {
        $rows = $this->builder->prepare()->getOffsets();
        $response = new JsonResponse($rows);
        $this->presenter->sendResponse($response);
    }

    /** @return void */
    public function handleChart() {
        $data = [];
        $chart = $this->builder->getChart()->chart($this->builder->getPost('spice'), $this->builder->getPost('row'));
        $percent = max($chart) / 100;
        foreach($chart as $key => $value) {
            if('position' == $key) {
            } else if($percent > 0) {
                $data[$key] = ['percent' => $value / $percent, 'value' => $value];
            } else {
                $data[$key] = ['percent' => 0, 'value' => $value];
            }
        }
        $this->presenter->sendResponse(new JsonResponse(['chart'=>isset($chart['position']) ? $chart['position'] : '','data'=>$data]));
    }

    /** @return JsonResponse */
    public function handleListen() {
        $response = new JsonResponse($this->builder->getListener()->listen($this->builder->getPost('')));
        $this->presenter->sendResponse($response);
    }

    /** @return void */
    public function handlePaginate() {
        $sum = $this->builder->prepare()->getSum();
        $total = ($sum > $this->builder->getPagination()) ? intval(ceil($sum / $this->builder->getPagination())) : 1;
        $response = new TextResponse($total);
        $this->presenter->sendResponse($response);
    }

    /** @return void */
    public function handlePush() {
        $response = new JsonResponse($this->builder->getButton()->push($this->builder->getPost('')));
        $this->presenter->sendResponse($response);
    }

    /** @return void */
    public function handleRemove() {
        $this->presenter->sendResponse(new JsonResponse($this->builder->delete()));
    }

    /** @return void */
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
        $this->presenter->sendResponse($response);
    }

    /** @return void */
    public function handleSummary() {
        $this->presenter->sendResponse(new TextResponse($this->builder->prepare()->getSummary()));
    }

    /** @return void */
    public function handleUpdate() {
        $this->presenter->sendResponse(new JsonResponse($this->builder->submit($this->builder->getPost('submit'))));
    }

    /** @return void */
    public function handleValidate() {
        $this->presenter->sendResponse(new JsonResponse($this->builder->validate()));
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
        $port = is_int($url->port) ? ':' . $url->port : '';
        $link = $url->scheme . '://' . $url->host . $port . $url->path . '?';
        foreach($parameters as $parameterId => $parameter) {
            $link .= $parameterId . '=' . $parameter . '&';
        }
        $link .= $spice . '=';
        $columns = $this->filterForm->getData();
        if($this->builder->getChart() instanceof IChart) {
            $columns['charts'] = ['Label'=> $this->translatorModel->translate('charts'), 'Method'=>'addButton','Attributes'=>
                ['className'=>'fa-hover fa fa-bar-chart','filter'=> false,'link' => $this->link('chart'),'onClick'=>'charts','summary' => false,'unrender' => false, 'unfilter' => false,'unsort'=>true]];
        }
        $export = ['style' => ['marginRight'=>'10px','float'=>'left']];
        $excel = ['style' => ['marginRight'=>'10px','float'=>'left']];
        if(is_object($this->getParent()->getGrid()->getExport())) {
            $excel['className'] = 'btn btn-success';
            $excel['label'] = 'excel';
            $excel['link'] = $this->getParent()->link('excel');
            $excel['onClick'] = 'prepare';
            $excel['width'] = 0;
            $export['className'] = 'btn btn-success';
            $export['label'] = 'export';
            $export['link'] = $this->getParent()->link('export');
            $export['onClick'] = 'prepare';
            $export['width'] = 0;
        }
        $this->template->dialogs = ['setting','reset','send','excel','export','process','done','add'];
        if($this->builder->getButton() instanceof IButton) {
            foreach($this->builder->getButton()->getButtons() as $buttonId => $button) {
                $this->template->dialogs[] = $buttonId;
            }
        }
        $data = ['buttons' => [
                    'add' => $this->builder->isEdit() ? ['Attributes' => ['className'=>'btn btn-warning',
                        'id' => -1,
                        'label'=> $this->translatorModel->translate('add item'),
                        'link' => $this->link('add'),
                        'onClick'=> 'add'],
                        'Label' => $this->translatorModel->translate('add item')] : [],
                    'chart' => $this->builder->isChart() ? $this->action('chart') : [],
                    'dialogs' => $this->template->dialogs,
                    'done' => ['className' => 'alert alert-success',
                        'label' => $this->translatorModel->translate('Click here to download your file.'),
                        'link' => $this->getParent()->link('done'),
                        'style' => ['display'=>'none', 'float' => 'left', 'marginRight' => '10px']],
                    'edit' => $this->builder->isEdit() ? $this->action('edit') : [],
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
                    'remove' => $this->builder->isRemove() ? $this->action('remove') : [],
                    'reset' => ['label' =>$this->translatorModel->translate('reset form'),
                        'className' => 'btn btn-warning',
                        'onClick' => 'reset',
                        'style' => ['marginRight'=>'10px','float'=>'left']],
                    'run' => $this->getParent()->link('run'),
                    'send' => ['label' => $this->translatorModel->translate('filter data'),
                        'className' => 'btn btn-success',
                        'onClick' => 'submit',
                        'style' => ['marginRight'=>'10px', 'float'=>'left']],
                    'setting' => isset($this->user->getIdentity()->getData()[$this->config['settings']]) ? ['className' => 'btn btn-success',
                        'display' => ['none'],
                        'label'=> $this->translatorModel->translate('setting'),
                        'link' =>$this->link('setting'),
                        'onClick' => 'setting',
                        'style' => ['marginRight'=>'10px','float'=>'left']] : false,
                    'summary' => $this->link('summary'),
                    'update' => $this->link('update'),
                    'validate' => $this->link('validate')],
                'columns' => $columns,
                'edit' => [],
                'charts' => [],
                'listeners' => [],
                'lists' => $this->lists,
                'row' => ['add' => $this->builder->row(-1, $this->row)->getData(), 'edit' => []],
                'rows' => [],
                'validators' => []];
        if($this->builder->getListener() instanceof IListener) {
            $data['listeners'] = $this->builder->getListener()->getKeys();
        }
        if($this->builder->getButton() instanceof IButton) {
            foreach($this->builder->getButton()->getButtons() as $buttonId => $button) {
                $data['buttons'][$buttonId] = $button;
                $data['buttons'][$buttonId]['onClick'] = 'push';
            }
        }
        if($this->presenter->getName()  . ':' . $this->presenter->getAction() . ':process' == $process = $this->translatorModel->translate($this->presenter->getName()  . ':' . $this->presenter->getAction() . ':process')) {
            $process = $this->translatorModel->translate('process');
        }
        if($this->builder->getService() instanceof IProcess) {
            $data['buttons']['process'] = ['className' => 'btn btn-success',
                'label' => $process,
                'link' => $this->getParent()->link('prepare'),
                'style' => ['marginRight'=>'10px','float'=>'left'],
                'onClick' => 'prepare'];
        }
        $this->template->data = json_encode($data);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->render();
    }

    /** @return IGridFactory */
    public function setGrid(IBuilder $grid) {
        $this->builder = $grid;
        return $this;
    }

    /** @return array */
    private function translate(array $data) {
        foreach($data as $key => $value) {
            $data[$key] = $this->translatorModel->translate($value);
        }
        return [null => $this->translatorModel->translate('--unchosen--')] + $data;
    }

}

interface IGridFactory {

    /** @return Grid */
    function create();
}
