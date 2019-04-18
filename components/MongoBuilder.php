<?php

namespace Impala;

use MongoDB\BSON\UTCDateTime,
    MongoDB\BSON\Regex,
    MongoDB\Client,
    MongoDB\Cursor;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Control;
use Nette\Application\IPresenter,
    Nette\Caching\IStorage,
    Nette\Caching\Cache,
    Nette\InvalidStateException,
    Nette\Localization\ITranslator,
    Nette\Utils\Validators;

/** @author Lubomir Andrisek */
class MongoBuilder extends Control implements IBuilderFactory {

    /** @var array */
    private $actions = [];

    /** @var IAdd */
    private $add;

    /** @var array */
    private $annotations;

    /** @var IBuild */
    private $build;

    /** @var IButton */
    private $button;

    /** @var Cache */
    private $cache;

    /** @var Client */
    private $client;

    /** @var string */
    private $collection = '';

    /** @var array */
    private $config;

    /** @var string */
    private $control;

    /** @var array */
    private $columns = [];

    /** @var array */
    private $dialogs = [];

    /** @var array */
    private $defaults;

    /** @var IEdit */
    private $edit;

    /** @var IProcess */
    private $export;

    /** @var IProcess */
    private $exportService;

    /** @var IFetch */
    private $fetch;

    /** @var IFilter */
    private $filter;

    /** @var array */
    private $filters = [];

    /** @var IChart */
    private $chart;

    /** @var int */
    private $group;

    /** @var array */
    private $groups = [];

    /** @var IProcess */
    private $import;

    /** @var IListener */
    private $listener;

    /** @var array */
    private $options;

    /** @var array */
    private $post;

    /** @var IPresenter */
    private $presenter;

    /** @var string */
    private $primary;

    /** @var string */
    private $spice;

    /** @var IRemove */
    private $remove;

    /** @var IRowFormFactory */
    private $row;

    /** @var IProcess */
    private $service;

    /** @var IStorage */
    private $storage;

    /** @var IUpdate */
    private $update;

    /** @var ITranslator */
    private $translatorRepository;

    /** @var string */
    private $query;

    public function __construct(array $config, Client $client, ExportService $exportService, IRowFormFactory $row, IStorage $storage, 
        ITranslator $translatorRepository) {
        $this->cache = new Cache($storage);
        $this->client = $client;
        $this->config = $config;
        $this->exportService = $exportService;
        $this->row = $row;
        $this->storage = $storage;
        $this->translatorRepository = $translatorRepository;
    }

    public function action(string $key, array $actions): IBuilder {
        $this->actions[$key] = $actions;
        return $this;
    }

    public function add(): array {
        $data = $this->getRow();
        if($this->add instanceof IAdd) {
            return $this->add->insert($data);
        }
        return $this->client->selectCollection($this->config['database'], $this->collection)->insertOne($data);
    }

    public function attached(Impala $impala): void {
        $this->presenter = $impala->getPresenter();
        $this->control = $impala->getName();
        /** import */
        if ($this->import instanceof IProcess && !empty($setting = $this->getSetting('import'))) {
            $this->import->setSetting($setting);
        } elseif ($this->import instanceof IProcess) {
            throw new InvalidStateException('Missing definition of import setting in table ' . $this->config['feeds'] . ' in call ' .
                $this->presenter->getName() . ':' . $this->presenter->getAction());
        }
        /** export */
        if ($this->export instanceof IProcess and false != $setting = $this->getSetting('export')) {
            $this->export->setSetting($setting);
        }
        /** process */
        if (false != $setting = $this->getSetting('process')) {
            $this->service->setSetting($setting);
        }
        /** select */
        foreach ($this->columns as $column => $annotation) {
            if (preg_match('/\sAS\s/', $annotation)) {
                throw new InvalidStateException('Use intented alias as key in column ' . $column . '.');
            } elseif (in_array($column, ['style', 'groups'])) {
                throw new InvalidStateException('Style and groups keywords are reserved for callbacks and column for Grid.jsx:update method. See https://github.com/landrisek/Impala/wiki/Select-statement. Use different alias.');
            }
            $this->inject($annotation, $column);
        }
        foreach ($this->getDrivers($this->collection) as $driver) {
            if (!isset($this->columns[$driver->getName()])) {
                $driver->isUnique() ? $this->inject('@required', $driver->getName()) : null;
                '_id_' == $driver->getName() ? $this->inject('@pri', '_id') : null;
                isset($this->defaults[$driver->getName()]) ? $this->columns[$driver->getName()] = $this->collection . '.' . $driver->getName() : null;
            }
        }
        if(isset($this->config['settings']) &&
            $this->presenter->getUser()->isLoggedIn() &&
            $this->presenter->getUser()->isLoggedIn() &&
            is_object($setting = $this->presenter->getUser()->getIdentity()->getData()[$this->config['settings']])) {
            foreach($setting as $source => $annotations) {
                if($this->presenter->getName() . ':' . $this->presenter->getAction() == $source) {
                    foreach($annotations as $annotationId => $annotation) {
                        if(empty($annotation) && isset($this->annotations[$annotationId]['unrender'])) {
                            $this->annotations[$annotationId]['filter'] = false;
                            unset($this->annotations[$annotationId]['unrender']);
                        } else if(!preg_match('/' . $annotation . '/', $this->columns[$annotationId])) {
                            $this->inject($this->columns[$annotationId] . $annotation, $annotationId);
                        }
                    }
                }
            }
        }
    }

    public function build(IBuild $build): IBuilder {
        $this->build = $build;
        return $this;
    }

    public function button(IButton $button): IBuilder {
        $this->button = $button;
        return $this;
    }

    public function collection(string $collection): IBuilder {
        $this->collection = $collection;
        return $this;
    }

    public function copy(): IBuilder {
        return new Builder($this->config, $this->client, $this->exportService, $this->row, $this->storage, $this->translatorRepository);
    }

    private function column(string $column): IBuilder {
        if (true == $this->getAnnotation($column, 'hidden')) {
        } elseif (true == $this->getAnnotation($column, ['addSelect', 'addMultiSelect'])) {
            $this->defaults[$column] = $this->getList($column);
        } elseif (is_array($enum = $this->getAnnotation($column, 'enum')) and false == $this->getAnnotation($column, 'unfilter')) {
            $this->defaults[$column] = $enum;
        } else {
            $this->defaults[$column] = '';
        }
        return $this;
    }

    public function dialogs(array $dialogs): IBuilder {
        $this->dialogs = $dialogs;
        return $this;
    }

    public function delete(): array {
        $data = $this->getRow();
        if(empty($this->primary)) {
            throw new InvalidStateException('Primary is not set.');
        }
        if($this->remove instanceof IRemove) {
            $this->remove->remove($this->primary, $data);
        } else {
            $this->client->selectCollection($this->config['database'], $this->collection)
                ->find(['_id' => $this->primary])
                ->deleteOne();
        }
        return ['remove' => true];
    }

    public function export($export): IBuilder {
        $this->export = ($export instanceof IProcess) ? $export : $this->exportService;
        return $this;
    }

    public function edit($edit): IBuilder {
        $this->edit = $edit;
        $this->actions['add'] = 'add';
        $this->actions['edit'] = 'edit';
        return $this;
    }

    public function fetch(IFetch $fetch): IBuilder {
        $this->fetch = $fetch;
        return $this;
    }

    public function filter(IFilter $filter): IBuilder {
        $this->filter = $filter;
        return $this;
    }

    public function getAnnotation($column, $annotation): bool {
        if (is_array($annotation)) {
            foreach ($annotation as $annotationId) {
                if (isset($this->annotations[$column][$annotationId])) {
                    return true;
                }
            }
            return false;
        } elseif (isset($this->annotations[$column][$annotation]) and is_array($this->annotations[$column][$annotation])) {
            return $this->annotations[$column][$annotation];
        } elseif (isset($this->annotations[$column][$annotation])) {
            return true;
        } else {
            return false;
        }
    }

    public function getActions(): array {
        return $this->actions;
    }

    public function getButton(): IButton {
        return $this->button;
    }

    public function getColumn($key): string {
        if (isset($this->columns[$key])) {
            return $this->columns[$key];
        }
        return '';
    }

    public function getCollection(): string {
        return $this->collection;
    }

    public function getColumns(): array {
        return $this->columns;
    }

    public function getConfig(string $key): array {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        return [];
    }

    public function getDefaults(): array {
        if(null == $this->defaults) {
            return [];
        }
        return $this->defaults;
    }

    public function getDialogs(): array {
        if($this->edit instanceof IEdit || true == $this->edit) {
            $this->dialogs['add'] = 'add';
            $this->dialogs['edit'] = 'edit';
        }
        return $this->dialogs;
    }

    public function getDrivers(string $collection): array {
        return iterator_to_array($this->client->selectCollection($this->config['database'], $collection)->listIndexes());
    }

    public function getEdit(): IEdit {
        return $this->edit;
    }

    public function getExcel(): IProcess {
        return $this->export;
    }

    public function getExport(): IProcess {
        return $this->export;
    }

    public function getFilter(string $key): string {
        if (isset($this->filters[trim($key)])) {
            return preg_replace('/\%/', '', $this->filters[$key]);
        }
        return '';
    }

    public function getFilters(): array {
        return $this->filters;
    }

    public function getChart(): IChart {
        return $this->chart;
    }

    public function getGroups(): array {
        return $this->groups;
    }

    public function getId(string $status): string {
        return md5($this->control . ':' . $this->presenter->getName() . ':' . $this->presenter->getAction()  . ':' . $status . ':' . $this->presenter->getUser()->getId());
    }

    public function getImport(): IProcess {
        return $this->import;
    }

    private function getKey(string $method, string $parameters): string {
        return str_replace('\\', ':', get_class($this)) . ':' . $method . ':' . $parameters;
    }

    public function getListener(): IListener {
        return $this->listener;
    }

    public function getList($alias): array {
        if(!preg_match('/\(/', $this->columns[$alias]) && preg_match('/\./', $this->columns[$alias])) {
            $table = trim(preg_replace('/\.(.*)/', '', $this->columns[$alias]));
            $column = trim(preg_replace('/(.*)\./', '', $this->columns[$alias]));
        } else {
            $table = $this->collection;
            $column = $alias;
        }
        $key = $this->getKey('getList', $this->columns[$alias]);
        $list = $this->cache->load($key);
        if(isset($this->filters[$table . '.' . $column]) && is_array($this->filters[$table . '.' . $column])) {
            return array_combine($this->filters[$table . '.' . $column], $this->filters[$table . '.' . $column]);
        } else if(isset($this->filters[$column]) && is_array($this->filters[$column])) {
            return array_combine($this->filters[$column], $this->filters[$column]);
        } else if($this->filter instanceof IFilter && !empty($list = $this->filter->getList($alias))) {
        } else if (null == $list) {
            $list = $this->client->selectCollection($this->config['table'], $this->collection)->find([], ['sort' => $column]);
            $this->cache->save($key, $list);
        } else if(null == $list) {
            $list = [];
        }
        return $list;
    }

    public function getOffset(): array {
        if(null == $row = $this->client->selectCollection($this->config['database'], $this->collection)
                            ->findOne($this->filters, $this->options)) {
            return [];
        } else {
            return $row->toArray();
        }
    }

    public function getOffsets(): array {
        if($this->fetch instanceof IFetch) {
            $data = $this->fetch->fetch($this);
        } else if(null == $data = $this->cache->load($hash = md5($this->control . ':' . $this->spice))) {
            if(!empty($this->groups) &&
                isset($this->groups[$this->group]) &&
                null == $results = $this->client->selectCollection($this->config['database'], $this->collection)->aggregate(['$match' => $this->filters], ['$group' => [$this->groups[$this->group] => null]])) {
                $resource = [];
            } else if(null == $results = $this->client->selectCollection($this->config['database'], $this->collection)
                    ->find($this->filters, $this->options)) {
                $resource = [];
            }
            if(is_object($results)) {
                $resource = $results->toArray();
            }
            $data = [];
            foreach($resource as $row) {
                $row = $row->getArrayCopy();
                foreach($this->columns as $column) {
                    if(isset($row[$column]) && $row[$column] instanceof UTCDateTime) {
                        $row[$column] = date($this->config['format']['date']['build'], $copy[$column]->__toString() / 1000);
                    } else if(!isset($row[$column]) && !empty($column)) {
                        $row[$column] = '';
                    }
                }
                $data[] = $this->build instanceof IBuild ? $this->build->build($row) : $row;
            }
            /** if(!empty($data)) {
                $this->cache->save($this->control . ':' . $this->spice, $data, [Cache::EXPIRE => '+1 hour']);
             }*/
            $this->spice();
        }
        return $data;
    }

    public function getOptions(): array {
        return $this->options;
    }

    public function getPagination(): int {
        return $this->config['pagination'];
    }

    public function getPost(string $key) {
        if(empty($key) && empty($this->post)) {
            $this->post = json_decode(file_get_contents('php://input'), true);
            foreach($this->post as $column => $value) {
                if(is_string($value)) {
                    $this->post[$column] = ltrim($value, '_');
                }
            }
            return $this->post;
        } else if(empty($key)) {
            return $this->post;
        }
        if(isset($this->post[$key])) {
            return $this->post[$key];
        }
        $this->post = json_decode(file_get_contents('php://input'), true);
        if(!isset($this->post[$key])) {
            return [];
        }
        return $this->post[$key];
    }

    public function getRemove(): IRemove {
        return $this->remove;
    }

    public function getRow(): array {
        foreach($row = $this->getPost('row') as $column => $value) {
            if(is_array($value) && isset($value['Label'])) {
                $row[$column] = $value['Label'];
            } else if (is_array($value) && isset($value['Attributes']) && $this->getAnnotation($column, ['int', 'tinyint'])) {
                $row[$column] = intval($value['Attributes']['value']);
            } else if (is_array($value) && isset($value['Attributes']) && $this->getAnnotation($column, ['decimal', 'float'])) {
                $row[$column] = floatval($value['Attributes']['value']);
            } else if (is_array($value) && isset($value['Attributes'])) {
                $row[$column] = $value['Attributes']['value'];
            } else if(is_array($value) && empty($value)) {
                unset($row[$column]);
            } else if($this->getAnnotation($column, 'pri') && null == $value) {
                unset($row[$column]);
            } else if($this->getAnnotation($column, 'pri')) {
                $this->primary = $value;
                unset($row[$column]);
            } else if($this->getAnnotation($column, 'unedit')) {
                unset($row[$column]);
            } else if($this->getAnnotation($column, ['date', 'datetime', 'decimal', 'float', 'int', 'tinyint']) && empty(ltrim($value, '_'))) {
                unset($row[$column]);
            } else if (is_float($value) || $this->getAnnotation($column, ['decimal', 'float'])) {
                $row[$column] = floatval($value);
            } else if ((bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $row[$column] = date($this->config['format']['date']['query'], $converted);
            } else if(is_string($value)) {
                $row[$column] = ltrim($value, '_');
            }
        }
        return $row;
    }

    public function getService(): IProcess {
        return $this->service;
    }

    public function getSort(): array {
        return $this->options['sort'];
    }

    public function getSpice(): array {
        $spices = (array) json_decode($this->presenter->request->getParameter(strtolower($this->control) . '-spice'));
        foreach($spices as $key => $spice) {
            if(is_array($spice)) {
                $allowed = $this->getList($key);
                foreach($spice as $id => $core) {
                    if(!isset($allowed[preg_replace('/(\_)*/', '', $core)])) {
                        unset($spices[$key][$id]);
                    }
                }
            }
        }
        return $spices;
    }

    public function getSum(): int {
        if($this->service instanceof IFetch) {
            return $this->service->sum($this);
        } else {
            return $this->client->selectCollection($this->config['database'], $this->collection)->count($this->filters);
        }
    }

    public function getSummary(): int {
        return $this->client->selectCollection($this->config['database'], $this->collection)
                    ->aggregate(['$match' => $this->filters], ['$group' => ['_id' => null, 'total' => ['$sum' => '$qte']]]);
    }

    private function getSetting(string $type): array {
        if(null == $row = $this->client->selectCollection($this->config['database'], $this->config['feeds'])
                    ->findOne(['type' => $type, 'source' => $this->presenter->getName() . ':' . $this->presenter->getAction()])) {
            return [];
        }
        return $row->getArrayCopy();
    }

    public function getQuery(): string {
        return $this->query;
    }

    public function chart(IChart $chart): IBuilder {
        $this->chart = $chart;
        return $this;
    }

    public function group(array $groups): IBuilder {
        $this->groups = $groups;
        return $this;
    }

    private function inject(string $annotation, string $column): void {
        $annotations = explode('@', $annotation);
        unset($annotations[0]);
        $this->annotations[$column] = isset($this->annotations[$column]) ? $this->annotations[$column] : [];
        foreach ($annotations as $annotationId) {
            if('enum' == $annotationId) {
            } else if ($this->presenter->getName() == $annotationId or $this->presenter->getName() . ':' . $this->presenter->getAction() == $annotationId) {
                $this->annotations[$column]['hidden'] = true;
            } elseif (preg_match('/\(/', $annotationId)) {
                $explode = explode(',', preg_replace('/(.*)\(|\)|\'/', '', $annotationId));
                $this->annotations[$column][preg_replace('/\((.*)/', '', $annotationId)] = array_combine($explode, $explode);
            } elseif (preg_match('/\{.*\}/', $annotationId)) {
                $this->annotations[$column][preg_replace('/\{(.*)\}/', '', $annotationId)] = (array) json_decode('{' . preg_replace('/(.*)\{/', '', $annotationId));
            } else {
                $this->annotations[$column][$annotationId] = true;
            }
        }
        if(true == $this->getAnnotation($column, 'hidden')) {
            unset($this->columns[$column]);
        } else {
            $this->columns[$column] = trim(preg_replace('/\@(.*)/', '', $annotation));
        }
        $this->column($column);
    }

    public function import(IProcess $import): IBuilder {
        $this->import = $import;
        return $this;
    }

    public function isButton(): bool {
        return $this->button instanceof IButton;
    }

    public function isEdit(): bool {
        return $this->edit instanceof IEdit;
    }

    public function isExport(): bool {
        return $this->export instanceof IProcess;
    }

    public function isChart(): bool {
        return $this->chart instanceof IChart;
    }

    public function isRemove(): bool {
        return $this->remove instanceof IRemove || true == $this->remove;
    }

    public function isListener(): bool {
        return $this->listener instanceof IListener;
    }

    public function isImport(): bool {
        return $this->import instanceof IProcess;
    }

    public function isProcess(): bool {
        return $this->service instanceof IProcess;
    }

    public function insert(IAdd $add): IBuilder {
        $this->add = $add;
        return $this;
    }

    public function limit(int $limit): IBuilder {
        $this->options['limit'] = $limit;
        return $this;
    }

    public function listen(IListener $listener): IBuilder {
        $this->listener = $listener;
        return $this;
    }

    private function spice(): void {
        if (null == $this->client->selectCollection($this->config['database'], $this->config['spice'])
                        ->findOne(['key' => $this->spice])) {
            $this->client->selectCollection($this->config['database'], $this->config['spice'])
                            ->insertOne(['key' => $this->spice,
                                'source' => $this->presenter->getName() . ':' . $this->presenter->getAction(),
                                'filters' => json_encode($this->filters),
                                'options' => json_encode($this->options)]);
        }
    }

    public function log(string $handle): void {
        if (isset($this->config['log'])) {
            $this->client->selectCollection($this->config['database'], $this->config['log'])
                ->insertOne(['users_id' => $this->presenter->getUser()->getIdentity()->getId(),
                        'source' => $this->presenter->getName() . ':' . $this->presenter->getAction(),
                        'handle' => $handle,
                        'date' => date('Y-m-d H:i:s', strtotime('now'))]);
        }
    }

    public function remove(IRemove $remove): IBuilder {
        $this->actions['remove'] = 'remove';
        $this->remove = $remove;
        return $this;
    }

    public function row(int $id, array $row): IRowFormFactory {
        foreach($row as $column => $status) {
            $label = $this->translate($column);
            if(0 == $label = $this->translatorRepository->getScore($this->collection . '.' . $column)) {
                $label = $this->collection . '.' . $column;
            } else {
                $label = ucfirst($this->translatorRepository->translate($label));
            }
            $value = $this->getPost('add') ? null : $status;
            $attributes =  ['className' => 'form-control', 'name' => intval($id), 'value' => is_null($value) ? '' : $value];
            $this->getAnnotation($column, 'disable') ? $attributes['readonly'] = 'readonly' : null;
            $this->getAnnotation($column, 'onchange') ? $attributes['onChange'] = 'submit' : null;
            if (($this->getAnnotation($column, 'pri') && null == $value) || $this->getAnnotation($column, ['unedit', 'unrender'])) {
                $this->row->addHidden($column, $column, ['value' => $value]);
            } else if ($this->getAnnotation($column, 'pri')) {
                $this->row->addHidden($column, $column, ['value' => $value->__toString()]);
            } elseif ($this->getAnnotation($column, 'unedit')) {
            } elseif (!empty($default = $this->getAnnotation($column, 'enum'))) {
                $attributes['data'] = [null => $this->translatorModel->translate('--unchosen--')];
                foreach($default as $option => $status) {
                    $translation = $this->translatorModel->translate($status);
                    if($value == $translation || $value == $status) {
                        $attributes['value'] = $option;
                    }
                    $attributes['data'][$option] = $translation;
                }
                $attributes['style'] = ['height' => '100%'];
                $this->row->addSelect($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, ['datetime', 'timestamp'])) {
                $attributes['format'] = $this->config['format']['time']['edit'];
                $attributes['locale'] = preg_replace('/(\_.*)/', '', $this->translatorRepository->getLocalization());
                $attributes['value'] = is_null($value) ? null : date($this->config['format']['time']['edit'], strtotime($value));
                $this->row->addDateTime($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, ['date'])) {
                $attributes['format'] = $this->config['format']['date']['edit'];
                $attributes['locale'] = preg_replace('/(\_.*)/', '', $this->translatorRepository->getLocalization());
                $attributes['value'] = is_null($value) ? null : date($this->config['format']['date']['edit'], strtotime($value));
                $this->row->addDateTime($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, 'tinyint') && 1 == $value) {
                $attributes['checked'] = 'checked';
                $this->row->addCheckbox($column, $label, $attributes, []);
            } elseif ($this->getAnnotation($column, 'tinyint')) {
                $this->row->addCheckbox($column, $label, $attributes, []);
            } elseif ($this->getAnnotation($column, 'textarea')) {
                $this->row->addTextArea($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, 'text')) {
                /** @todo https://www.npmjs.com/package/ckeditor-react */
                $this->row->addTextArea($column, $label . ':', $attributes, []);
            } elseif (is_array($value) && $this->getAnnotation($column, 'int')) {
                $attributes['data'] = $value;
                $attributes['style'] = ['height' => '100%'];
                $this->row->addSelect($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, 'addMultiSelect') || (!empty($value) && is_array($value))) {
                $attributes['data'] = $value;
                $this->row->addMultiSelect($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, 'addMultiSelect') && is_string($value)) {
                $attributes['data'] = json_decode($value);
                $this->row->addMultiSelect($column, $label . ':', $attributes, []);
            } elseif (!empty($value) and is_array($value)) {
                $attributes['data'] = $value;
                $attributes['style'] = ['height' => '100%'];
                $this->row->addSelect($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, ['decimal', 'float', 'int'])) {
                $attributes['type'] = 'number';
                $this->row->addText($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, 'upload')) {
                $this->row->addUpload($column, $label, [], ['required' => $this->translatorRepository->translate(1147.0), 'text' => $this->translatorRepository->translate(1148.0)]);
            } elseif ($this->getAnnotation($column, 'multiupload')) {
                $attributes['max'] = $this->config['upload'];
                $this->row->addMultiUpload($column, $label, $attributes, []);
            } else {
                $this->row->addText($column, $label . ':', $attributes, []);
            }
        }
        $this->row->addMessage('_message', $this->translatorRepository->translate(1172.0), ['className' => 'alert alert-success']);
        $this->row->addSubmit('_submit', ucfirst($this->translatorRepository->translate(1124.0)),
                    ['className' => 'btn btn-success', 'id' => 'add', 'name' => intval($id), 'onClick' => 'submit']);
        return $this->row;
    }

    public function select(array $columns): IBuilder {
        $this->columns = $columns;
        return $this;
    }

    public function setConfig(string $key, $value): IBuilder {
        $this->config[$key] = $value;
        return $this;
    }

    public function submit(bool $submit): array {
        $row = $this->getRow();
        if(true == $submit && $this->edit instanceof IEdit) {
            $new = $this->edit->submit($this->primary, $this->getPost('row'));
        } else if(false == $submit && $this->update instanceof IUpdate) {
            $new = $this->update->update($this->getPost('id'), $this->getPost('row'));
        }
        if(!isset($this->filters['score'])) {
            throw new InvalidStateException('Score is not set.');
        }
        if(isset($new['_id'])) {
            unset($new['_id']);    
        }
        $resource = $this->client->selectCollection($this->config['database'], $this->collection);
        $resource->updateOne($this->filters, ['$set' => $new]);            
        if(isset($new)) {
            return $new;
        } else if(null == $resource->findOne($this->filters)) {
            return [];
        } else {
            return $this->getPost('row');
        }
    }

    public function update(IUpdate $update): IBuilder {
        $this->update = $update;
        return $this;
    }

    public function validate(): array {
        $validators = [];
        $row = $this->getRow();
        foreach($row as $column => $value) {
            if($this->getAnnotation($column, 'unedit')) {
            } else if($this->getAnnotation($column, 'required') && empty($value)) {
                $validators[$column] = ucfirst($this->translatorRepository->translate($column)) . ' ' . $this->translatorRepository->translate('is required.');
            } else if($this->getAnnotation($column, 'uni')) {
                if($this->client->table($this->collection)->findOne(['_id'=> ['$ne'=>$this->primary],$column => $value]) instanceof Cursor) {
                    $validators[$column] =  ucfirst($this->translatorRepository->translate('unique item'))  . ' ' . $this->translatorRepository->translate($column) . ' ' . $this->translatorRepository->translate('already defined in source table.');
                }
            } else if($this->getAnnotation($column, 'email') && Validators::isEmail($value)) {
                $validators[$column] = $this->translatorRepository->translate($column) . ' ' . $this->translatorRepository->translate('is not valid email.');
            } else if($this->getAnnotation($column, ['int', 'decimal', 'double', 'float']) && !is_numeric($value)) {
                $validators[$column] = $this->translatorRepository->translate($column) . ' ' . $this->translatorRepository->translate('is not valid number.');
            }
        }
        return $validators;
    }

    public function where(string $key, $column, bool $condition = null): IBuilder {
        if(is_bool($condition) and false == $condition) {
        } elseif (is_callable($condition) and false != $value = $condition()) {
            $this->filters[$key] = $value;
        } elseif (is_bool($condition) and true == $condition and is_array($column)) {
            $this->filters[$key] = $column;
            $this->defaults[$key] = $column;
        } else {
            $this->filters[$key] = $column;
        }
        return $this;
    }

    public function order(array $order): IBuilder {
        foreach($order as $column => $value) {
            if(-1 != $value && 1 != $value) {
                throw new InvalidStateException('Order value can be only 1 (ascending) or -1 (descending).');
            }
        }
        $this->options['sort'] = $order;
        return $this;
    }

    public function setRow(float $score, array $data): IBuilder {
        $this->client->selectCollection($this->config['database'], $this->collection)
                ->find(['score' => $score])
                ->updateOne($data);
        return $this;
    }

    public function process(IProcess $service): IBuilder {
        $this->service = $service;
        return $this;
    }

    public function prepare(): IBuilder {
        if(null == $filters = $this->getPost('filters')) {
            $filters = [];
        }
        if(isset($filters['groups'])) {
            $this->group = rtrim($filters['groups'], '_');
            unset($filters['groups']);
        } else if(!empty($this->groups)) {
            $this->group = 0;
        }
        if(empty($sort = $this->getPost('sort')) && !isset($this->options['sort'])) {
            foreach($this->columns as $name => $column) {
                if(false == $this->getAnnotation($name, 'unrender')) {
                    $this->options['sort'] = [$name => -1];
                    break;
                }
            }
        } else if(!empty($sort)) {
            foreach($sort as $column => $direction) {
                $sort[$column] = 'asc' == $direction ? 1 : -1;
            }
            $this->options['sort'] = $sort;
        }
        if(!is_numeric($offset = $this->getPost('offset'))) {
            $offset = 1;
        }
        /** where */
        foreach ($filters as $column => $value) {
            $key = preg_replace('/\s(.*)/', '', $column);
            if(is_array($value) && [""] != $value && !empty($value)) {
                foreach($value as $underscoreId => $underscore) {
                    $value[$underscoreId] = ltrim($value[$underscoreId], '_');
                }
                $this->filters[$this->columns[$key]] = $value;
                continue;
            } else if([""] == $value || empty($value)) {
                continue;
            }
            $value = ltrim(preg_replace('/\;/', '', htmlspecialchars($value)), '_');
            if (preg_match('/\s\>\=/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->filters[$this->columns[$key]] = ['$gte' => date($this->config['format']['date']['find'], $converted)];
            } elseif (preg_match('/\s\>\=/', $column)) {
                $this->filters[$this->columns[$key]] = ['$gte' => $value];
            } elseif (preg_match('/\s\<\=/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*\./', $value)) {
                $this->filters[$this->columns[$key]] = ['$lte' => date($this->config['format']['date']['query'], $converted)];
            } elseif (preg_match('/\s\<\=/', $column)) {
                $this->filters[$this->columns[$key]] = ['$lte' => $value];
            } elseif (preg_match('/\s\>/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->filters[$this->columns[$key]] = ['$gt' => date($this->config['format']['date']['query'], $converted)];
            } elseif (preg_match('/\s\>/', $column)) {
                $this->filters[$this->columns[$key]] = ['$gt' => $value];
            } elseif (preg_match('/\s\</', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->filters[$this->columns[$key]] = ['$lt' => date($this->config['format']['date']['query'], $converted)];
            } elseif (preg_match('/\s\</', $column)) {
                $this->filters[$this->columns[$key]] = ['$gt' => $value];
            } elseif ((bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->filters[$this->columns[$column]] = date($this->config['format']['date']['query'], $converted);
            } elseif (is_numeric($value)) {
                $this->filters[$this->columns[$column]] = $value;
            } else {
                $this->filters[$this->columns[$column]] = new Regex($value, 's');
            }
        }
        if($this->filter instanceof IFilter) {
            $this->filters = $this->filter->filter($this->filters);
        }
        /** offset */
        if(empty($status = $this->getPost('status'))) {
            $this->options['skip'] = ($offset - 1) * $this->config['pagination'];
            $this->options['limit'] = $this->config['pagination'];
        } else {
            if(in_array($status, ['excel', 'export'])) {
                $this->options['limit'] = $this->export->speed($this->config['speed']);
            } else if('import' == $status) {
                $this->options['limit'] = $this->import->speed($this->config['speed']);
            } else {
                $this->options['limit'] = $this->service->speed($this->config['speed']);
            }
            $this->options['skip'] = $offset;
            $this->options['sort'] = ['score' => 1];
        }
        /** spice */
        $this->spice = '';
        foreach ($this->filters as $column => $value) {
            if(is_array($value)) {
                $this->spice .= $column . '|' . implode('|', $value);
            } else {
                $this->spice .= $column . '|' . $value;
            }
        }
        foreach($this->options as $option => $value) {
            if(is_array($value)) {
                $this->spice .= $option . '|' . implode('|', $value);
            } else {
                $this->spice .= $option . '|' . $value;
            }
        }
        return $this;
    }

    public function translate(string $name): string {
        $score = $this->translatorRepository->getScore($name);
        if (0.0 < $score = $this->translatorRepository->getScore($this->presenter->getName() . ':' . $this->presenter->getAction() . ':' . $name )) {
        } elseif (0.0 < $score = $this->translatorRepository->getScore($this->presenter->getName() . ':' . $name)) {
        } elseif (0.0 < $score = $this->translatorRepository->getScore($this->collection . '.' . $name)) {    
        } elseif (0.0 < $score = $this->translatorRepository->getScore($name)) {
        }
        $label = $this->translatorRepository->translate($score);
        return $label;
    }

}
