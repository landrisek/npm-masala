<?php

namespace Masala;

use Nette\Application\LinkGenerator,
    Nette\Application\UI\Presenter,
    Nette\Caching\IStorage,
    Nette\Caching\Cache,
    Nette\Database\Context,
    Nette\Database\Table\ActiveRow,
    Nette\InvalidStateException,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class NetteBuilder extends BaseBuilder implements IBuilder {

    /** @var Cache */
    private $cache;

    /** @var Context */
    private $database;

    /** @var IProcessService */
    private $export;

    /** @var IProcessService */
    private $exportService;

    /** @var IEditService */
    private $gridService;

    /** @var IProcessService */
    private $import;

    /** @var LinkGenerator */
    private $linkGenerator;

    /** @var MockService */
    private $mockService;

    /** @var Presenter */
    private $presenter;

    /** @var IProcessService */
    private $service;

    /** @var Array */
    private $annotations;

    /** @var Array */
    private $alternate = [];

    /** @var Array */
    private $arguments = [];

    /** @var Array */
    private $concat = [];

    /** @var string */
    private $control;

    /** @var Array */
    private $columns = [];

    /** @var Array */
    private $defaults;

    /** @var string */
    private $group;

    /** @var string */
    private $hash;

    /** @var string */
    private $having = '';

    /** @var int */
    private $offset;

    /** @var Array */
    private $innerJoin = [];

    /** @var Array */
    private $join = [];

    /** @var Array */
    private $leftJoin = [];

    /** @var string */
    private $sort;

    /** @var Array */
    private $range;

    /** @var Array */
    private $where = [];

    /** @var Array */
    private $table;

    /** callback */
    private $redraw;

    /** @var int */
    private $limit;

    /** @var string */
    private $query;

    /** @var string */
    private $select;

    /** @var string */
    private $spice;

    /** @var string */
    private $sumQuery;

    /** @var int */
    private $sum;

    public function __construct(Array $config, ITranslator $translatorModel, ExportService $exportService, MockService $mockService, Context $database, IStorage $storage, IRequest $httpRequest, LinkGenerator $linkGenerator) {
        $this->config = $config;
        $this->translatorModel = $translatorModel;
        $this->exportService = $exportService;
        $this->mockService = $mockService;
        $this->database = $database;
        $this->cache = new Cache($storage);
        $this->linkGenerator = $linkGenerator;
    }

    /** @return string */
    private function build($row, $column) {
        $cell = $row[$column];
        if (isset($this->alternate[$column]) and null == $cell) {
            $call = $this->alternate[$column];
            $cell = $row[$call];
        }
        if (isset($this->concat[$column]) and isset($this->concat[$column][$cell])) {
            $cell .= $this->concat[$column][$cell];
        } else if (is_array($href = $this->getAnnotation($column, 'url')) and preg_match('/\//', $href[0])) {
            return '<a href="' . $href[0] . '=' . $row['url_id'] . '" target="_blank">' . $cell . '</a>';
        } else if (is_array($href = $this->getAnnotation($column, 'url'))) {
            return '<a href="' . $this->linkGenerator->link($href[0], ['id' => $row['url_id']]) . '" target="_blank">' . $cell . '</a>';
        } elseif (is_array($joinColumns = $this->getAnnotation($column, 'join'))) {
            foreach ($joinColumns as $annotation) {
                $joinTable = (preg_match('/\./', $annotation)) ? preg_replace('/\.(.*)/', '', $annotation) : $this->table;
                $joinColumn = preg_replace('/(.*)\./', '', $annotation);
                $where = (preg_match('/\./', $annotation) and $joinTable != $this->table) ? $this->table . '_' . $this->columns[$column] : $this->columns[$column];
                $joins = $this->database->table($joinTable)->where($where, $cell)->fetchAll();
                $cell = '';
                foreach ($joins as $primary => $join) {
                    $cell .= (true == $this->getAnnotation($column, 'style')) ? '<div style="background:green;max-width:20px;height:20px;position:relative;top:50px;left:50px;"></div>' : '';
                    $cell .= (true == $this->getAnnotation($column, 'image')) ? '<img class="masala" src="' . $row['path'] . '" style="max-width:600px; max-height:600px;">' : '';
                    $cell .= (true == $this->getAnnotation($column, 'text') and isset($join->key)) ? '<strong>' . ucfirst($this->translatorModel->translate($join->key)) . '</strong>:' : '';
                    $cell .= (true == $this->getAnnotation($column, 'text')) ? '<input style="width:100%;" type="text" name="' . $this->presenter->getAction() . '_' . $join->id . '" value="' . $join->$joinColumn . '"><br>' : '';
                    $cell .= (false == $this->getAnnotation($column, ['style', 'image', 'text'])) ? $join->id . ' ' : '';
                    $cell .= empty($cell) ? ', ' . $primary : '';
                }
            }
            return $cell;
        } elseif (true == $this->getAnnotation($column, 'image')) {
            return '<br><img src="' . $cell . '" style="max-width:200px; max-height:200px;">';
        } elseif (true == $this->getAnnotation($column, 'text')) {
            return '<input onkeyup="handleStorage($(this));" class="form-control" style="width:100%;" type="text" name="' . $column . '_' . $row['id'] . '" value="' . $cell . '">';
        } elseif (true == $this->getAnnotation($column, ['text', 'image'])) {
            return '<br><img src="' . $cell . '" style="max-width:200px; max-height:200px;"><input class="form-control" style="width:100%;" type="text" name="' . $column . '_' . $row['id'] . '" value="' . $cell . '">';
        } elseif (true == $this->getAnnotation($column, 'checkbox')) {
            return '<input class="form-control" style="width:100%;" type="checkbox" name="' . $column . '_' . $row['id'] . '" value="' . $cell . '">';
        } elseif (true == $this->getAnnotation($column, 'textarea')) {
            return '<textarea class="form-control" name="' . $column . '_' . $row['id'] . '" value="' . $cell . '">' . $cell . '</textarea>';
        } elseif (true == $this->getAnnotation($column, 'select')) {
            $defaults = $this->config[$this->table . '.' . $column]['getDefaults'];
            $components = $this->mockService->getCall($defaults['service'], $defaults['method'], $defaults['parameters'], $this);
            $select = '<select name="' . $column . '_' . $row['id'] . '" class="form-control">';
            foreach ($components as $id => $option) {
                $select .= '<option ';
                $select .= ($cell == $id) ? 'selected="selected" ' : '';
                $select .= 'value="' . $id . '">' . $option . '</option>';
            }
            $select .= '</select>';
            return $select;
        } elseif (true == $this->getAnnotation($column, 'style')) {
            return '<style>' . $cell . '</style>';
        } elseif (true == $this->getAnnotation($column, 'svg')) {
            $svg = '<svg>';
            foreach ((array) json_decode($cell) as $polyline) {
                $svg .= '<polyline style="fill:none;stroke:black;stroke-width:1" points="' . $polyline . '"></polyline>';
            }
            return $svg .'</svg>' ;
        } elseif (true == $this->getAnnotation($column, 'image')) {
            return '<br><img src="' . $cell . '" style="max-width:200px; max-height:200px;">';
        } else {
            return $cell;
        }
    }

    public function getAnnotation($column, $annotation) {
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

    public function getArguments() {
        return $this->arguments;
    }

    public function getConcat() {
        return $this->concat;
    }

    /** @return Array */
    public function getColumns() {
        return $this->columns;
    }

    /** @return string | Bool */
    public function getColumn($key) {
        if (isset($this->columns[$key])) {
            return $this->columns[$key];
        }
        return false;
    }

    public function getConfig($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        return [];
    }

    public function getDefaults() {
        return $this->defaults;
    }

    public function getDrivers() {
        $driverId = $this->getKey('attached', $this->table);
        if (null == $drivers = $this->cache->load($driverId)) {
            $this->cache->save($driverId, $drivers = $this->database->getConnection()
                ->getSupplementalDriver()
                ->getColumns($this->table));
        }
        return $drivers;
    }

    public function getExport() {
        return $this->export;
    }

    public function getFilter($key) {
        if (isset($this->where[trim($key)])) {
            return preg_replace('/\%/', '', $this->where[$key]);
        }
        return false;
    }

    public function getFilters() {
        return $this->where;
    }

    public function getGridService() {
        return $this->gridService;
    }

    public function getHash() {
        return $this->hash;
    }

    public function getId($status) {
        return md5($this->control . ':' . $this->presenter->getName() . ':' . $this->presenter->getAction()  . ':' . $status . ':' . $this->presenter->getUser()->getId());
    }

    public function getImport() {
        return $this->import;
    }

    private function getKey($method, $parameters) {
        return str_replace('\\', ':', get_class($this)) . ':' . $method . ':' . $parameters;
    }

    public function getList($annotation) {
        $table = preg_match('/\./', $annotation) ? trim(preg_replace('/\.(.*)/', '', $annotation)) : $this->table;
        $column = preg_match('/\./', $annotation) ? trim(preg_replace('/(.*)\./', '', $annotation)) : trim($annotation);
        $key = $this->getKey('getList', $annotation);
        if (null == $list = $this->cache->load($key)) {
            $this->cache->save($key, $list = $this->database->table($table)
                    ->select('DISTINCT (' . $column . ')')
                    ->where($column . ' IS NOT NULL')
                    ->where($column . ' !=', '')
                    ->fetchPairs($column, $column));
        }
        return $list;
    }

    public function getPagination() {
        return $this->config['pagination'];
    }

    public function getRange($key) {
        if (isset($this->range[$key])) {
            return $this->range[$key];
        }
        return false;
    }

    /** @return array */
    public function getPrimary() {
        $primary = [];
        if(is_array($keys = $this->database->table($this->table)->getPrimary())) {
            foreach($keys as $key) {
                $primary[$this->table . '.'  . $key] = $key;
            }
        } else {
            $primary = [$this->table . '.'  . $keys => $keys];
        }
        return $primary;
    }

    public function getSpice() {
        return $this->spice;
    }

    public function isRange() {
        if (is_array($this->range) and ! empty($this->range)) {
            return true;
        }
    }

    public function getRangeByTable($column) {
        $table = preg_match('/\./', $column) ? $this->table : preg_replace('/\.(.*)/', '', $column);
        $from = $this->database->table($table)->min($column)->__toString();
        $to = $this->database->table($table)->max($column)->__toString();
        return ['>' => $from, '<' => $to, 'min' => $from, 'max' => $to];
    }

    private function getSetting($type) {
        return $this->database->table($this->config['feeds'])
                        ->where('type', $type)
                        ->where('source', $this->presenter->getName() . ':' . $this->presenter->getAction())
                        ->fetch();
    }

    /** @return string */
    public function getTable() {
        return $this->table;
    }

    /** @return array */
    public function getOffset($offset) {
        if (empty($this->join) and empty($this->leftJoin) and empty($this->innerJoin)) {
            $row = $this->getResource()
                ->order($this->sort)
                ->limit(1, $offset)
                ->fetch();
        } else {
            $limit = count($this->arguments);
            $arguments = $this->arguments;
            $arguments[$limit] = 1;
            $arguments[$limit + 1] = intval($offset);
            $arguments = array_values($arguments);
            $row = $this->database->query($this->query . ' LIMIT ? OFFSET ? ', ...$arguments)->fetch();
        }
        if($row instanceof ActiveRow) {
            return $row->toArray();
        } elseif(is_object($row)) {
            return (array) $row;
        } else {
            return [];
        }
    }

    /** @return IBuilder */
    public function flushOffset($offset) {
        $this->flush($this->control . ':' . $this->hash . ':' . $offset);
        return $this;
    }

    /** @return IBuilder */
    public function redrawOffset($offset) {
        if(is_callable($this->redraw)) {
            call_user_func_array($this->redraw, [$this->getOffset($offset)]);            
        }
        return $this;
    }

    /** @return array */
    public function getOffsets() {
        if(null == $data = $this->cache->load($this->control . ':' . $this->hash . ':' . $this->offset)) {
            $this->arguments[] = intval($this->limit);
            $this->arguments[] = intval($this->offset);
            if (empty($this->join) and empty($this->leftJoin) and empty($this->innerJoin)) {
                $resource = $this->getResource()
                    ->limit($this->limit, $this->offset)
                    ->order($this->sort)
                    ->fetchAll();
                $data = [];
                foreach($resource as $row) {
                    $data[] = $row->toArray();
                }
            } else {
                $arguments = array_values($this->arguments);
                $resource = $this->database->query($this->query . ' LIMIT ? OFFSET ? ', ...$arguments)->fetchAll();
                $data = [];
                foreach($resource as $row) {
                    $data[] = (array) $row;
                }
            }
            //$this->cache->save($this->control . ':' . $this->hash . ':' . $this->offset, $data, [Cache::EXPIRE => '+1 hour']);
        }
        $this->logQuery($this->hash);
        return $data;
    }

    public function getResource() {
        $dataSource = $this->database->table($this->table);
        (null == $this->select) ? null : $dataSource->select($this->select);
        foreach ($this->where as $column => $value) {
            is_numeric($column) ? $dataSource->where($value) : $dataSource->where($column, $value);
        }
        (null == $this->group) ? null : $dataSource->where($this->group . ' IS NOT NULL')->group($this->group);
        (null == $this->having) ? null : $dataSource->having($this->having);
        return $dataSource;
    }

    public function getService() {
        return $this->service;
    }

    public function getSum() {
        if(is_int($this->sum)) {
            return $this->sum;
        } elseif(empty($this->where)) {
            return $this->sum = $this->database->query('SHOW TABLE STATUS WHERE Name = "' . $this->table . '"')->fetch()->Rows;
        } elseif (empty($this->join) and empty($this->leftJoin) and empty($this->innerJoin)) {
            $load = $this->getResource();
            return $this->sum = $load->count();
        } else {            
            $arguments = [];
            foreach ($this->arguments as $key => $argument) {
                is_numeric($key) ? $arguments[] = $argument : null;
            }
            $load = $this->database->query($this->sumQuery, ...$arguments)->fetch();
            return $this->sum = $load->sum;
        }
    }

    /** setters */
    public function action($service) {
        $this->gridService = $service;
        return $this;
    }

    public function alternate($key, $value) {
        if (isset($this->alternate[$key])) {
            throw new InvalidStateException('Alternate key ' . $key . ' already assigned in ' . __CLASS__ . '.');
        }
        $this->alternate[$key] = $value;
        return $this;
    }

    /** @return IBuilder */
    public function cloned() {
        return new NetteBuilder($this->config, $this->translatorModel, $this->exportService, $this->mockService, $this->database, $this->storage, $this->httpRequest, $this->linkGenerator);
    }
    
    private function column($column) {
        if (true == $this->getAnnotation($column, 'hidden')) {
            
        } elseif (true == $this->getAnnotation($column, ['addSelect', 'addMultiSelect']) and ! preg_match('/\(/', $this->columns[$column])) {
            $this->defaults[$column] = $this->getList($this->columns[$column]);
        } elseif (false != $range = $this->getRange($this->table . '.' . $column) or false != $range = $this->getRange($column)) {
            $this->annotations[$column]['range'] = true;
            $this->defaults[$column] = $range;
        } elseif (is_array($enum = $this->getAnnotation($column, 'enum')) and false == $this->getAnnotation($column, 'unfilter')) {
            $this->defaults[$column] = $enum;
        } elseif (true == $this->getAnnotation($column, 'range')) {
            $this->defaults[$column] = $this->getRangeByTable($this->columns[$column]);
        } else {
            $this->defaults[$column] = '';
        }
        return $this;
    }

    public function concat($key, $value) {
        if (isset($this->concate[$key])) {
            throw new InvalidStateException('Concate key ' . $key . ' already assigned in ' . __CLASS__ . '.');
        }
        $this->concat[$key] = $value;
        return $this;
    }

    public function export($export) {
        $this->export = ($export instanceof IProcessService) ? $export : $this->exportService;
        return $this;
    }

    public function flush($hash) {
        $this->cache->clean([Cache::ALL => [$hash]]);
        return $this;
    }

    public function having($having) {
        $this->having = (string) $having;
        return $this;
    }

    public function group($group) {
        $this->group = (string) $group;
        return $this;
    }

    private function inject($annotation, $column) {
        $annotations = explode('@', $annotation);
        unset($annotations[0]);
        $this->annotations[$column] = isset($this->annotations[$column]) ? $this->annotations[$column] : [];
        foreach ($annotations as $annotationId) {
            if ($this->presenter->getName() == $annotationId or $this->presenter->getName() . ':' . $this->presenter->getAction() == $annotationId) {
                $this->annotations[$column]['hidden'] = true;
            } elseif (preg_match('/\(/', $annotationId)) {
                $explode = explode(',', preg_replace('/(.*)\(|\)|\'/', '', $annotationId));
                $this->annotations[$column][preg_replace('/\((.*)/', '', $annotationId)] = array_combine($explode, $explode);
            } elseif (preg_match('/\=/', $annotationId)) {
                $this->annotations[$column][preg_replace('/\=(.*)/', '', $annotationId)][] = preg_replace('/(.*)\=/', '', $annotationId);
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

    public function import(IProcessService $import) {
        $this->import = $import;
        return $this;
    }

    public function innerJoin($innerJoin) {
        $this->innerJoin[] = (string) $innerJoin;
        return $this;
    }

    public function join($join) {
        $this->join[] = (string) $join;
        return $this;
    }

    public function leftJoin($leftJoin) {
        $this->leftJoin[] = (string) $leftJoin;
        return $this;
    }

    public function limit($limit) {
        $this->limit = (int) $limit;
        return $this;
    }

    private function logQuery($key) {
        if (false == $this->database->table($this->config['spice'])
                        ->where('key', $key)
                        ->fetch()
        ) {
            return $this->database->table($this->config['spice'])
                            ->insert(['key' => $key,
                                'source' => $this->presenter->getName() . ':' . $this->presenter->getAction(),
                                'query' => $this->query,
                                'arguments' => json_encode($this->arguments)]);
        }
    }

    public function log($handle) {
        if (isset($this->config['log'])) {
            return $this->database->table($this->config['log'])->insert(['users_id' => $this->presenter->getUser()->getIdentity()->getId(),
                        'source' => $this->presenter->getName() . ':' . $this->presenter->getAction(),
                        'handle' => $handle,
                        'date' => date('Y-m-d H:i:s', strtotime('now'))]);
        }
    }

    public function range($key, Array $value) {
        if (isset($this->range[$key])) {
            throw new InvalidStateException('Range key ' . $key . ' already assigned in ' . __CLASS__ . '.');
        }
        $this->range[$key] = $value;
        return $this;
    }

    public function redraw(callable $redraw) {
        $this->redraw = $redraw;
        return $this;
    }

    public function select(Array $columns) {
        $this->columns = $columns;
        return $this;
    }

    public function table($table) {
        $this->table = (string) $table;
        return $this;
    }

    public function where($key, $column = null, $condition = null) {
        if(is_bool($condition) and false == $condition) {

        } elseif ('?' == $column and isset($this->where[$key])) {
            $this->arguments[$key] = $this->where[$key];
        } elseif (preg_match('/\>/', $key) and is_string($condition)) {
            $this->where[$key] = $column;
            $this->range[preg_replace('/ (.*)/', '', $key)]['>'] = $column;
            $this->range[preg_replace('/ (.*)/', '', $key)]['min'] = $condition;
            $this->annotations[preg_replace('/ (.*)|(.*)\./', '', $key)]['unrender'] = true;            
        } elseif (preg_match('/\</', $key) and is_string($condition)) {
            $this->where[$key] = $column;
            $this->range[preg_replace('/ (.*)/', '', $key)]['<'] = $column;
            $this->range[preg_replace('/ (.*)/', '', $key)]['max'] = $condition;
            $this->annotations[preg_replace('/ (.*)|(.*)\./', '', $key)]['unrender'] = true;
        } elseif (is_bool($condition) and true == $condition and is_array($column)) {
            $this->where[$key] = $column;
            $this->annotations[preg_replace('/(.*)\./', '', $key)]['enum'] = array_combine($column, $column);
        } elseif (is_callable($column) and false != $value = $column()) {
            $this->where[$key] = $value;
        } elseif (is_array($column) and isset($this->columns[preg_replace('/(.*)\./', '', $key)])) {
            $this->where[$key] = $column;
            $this->annotations[preg_replace('/(.*)\./', '', $key)]['enum'] = array_combine($column, $column);
        } elseif (is_string($column) or is_numeric($column) or is_array($column)) {
            $this->where[$key] = $column;
        } elseif (null === $column) {
            $this->where[] = $key;
        } elseif (null === $condition) {
            $this->where[] = $key;
        }
        return $this;
    }

    public function order($order) {
        if (!isset($this->columns[$order])) {
            throw new InvalidStateException('You must define order column ' . $order . ' in select method of NetteBuilder.');
        }
        $this->order = (string) $order;
        return $this;
    }

    public function setRow($primary, $data) {
        $this->database->table($this->table)
                ->wherePrimary($primary)
                ->update($data);
        return $this;
    }

    public function process(IProcessService $service) {
        $this->service = $service;
        return $this;
    }

    public function update($key, $row) {
        $this->cache->save($key, $row);
    }

    public function attached(Masala $masala) {
        $this->presenter = $masala->getPresenter();
        $this->control = $masala->getName();
        /** import */
        if ($this->import instanceof IProcessService and ( $setting = $this->getSetting('import')) instanceof ActiveRow) {
            $this->import->setSetting($setting);
        } elseif ($this->import instanceof IProcessService) {
            throw new InvalidStateException('Missing definition of import setting in table ' . $this->config['feeds'] . ' in call ' .
                                            $this->presenter->getName() . ':' . $this->presenter->getAction());
        }
        /** export */
        if ($this->export instanceof IExportService and false != $setting = $this->getSetting('export')) {
            $this->export->setSetting($setting);
        }
        /** process */
        if (false != $setting = $this->getSetting('process')) {
            $this->service->setSetting($setting);
        }
        /** select */
        if(isset($this->config['user']) and
            $this->presenter->getUser()->isLoggedIn() and
            is_object($setting = json_decode($this->presenter->getUser()->getIdentity()->getData()[$this->config['user']]))) {
            foreach($setting as $source => $annotations) {
                if($this->presenter->getName() . ':' . $this->presenter->getAction() == $source) {
                    foreach($annotations as $annotationId => $annotation) {
                        $this->columns[$annotationId] = $annotation;
                    }
                }
            }
        }
        foreach ($this->columns as $column => $annotation) {
            if (preg_match('/\sAS\s/', $annotation)) {
                throw new InvalidStateException('Use intented alias as key in column ' . $column . '.');
            }
            $this->inject($annotation, $column);
        }
        foreach ($this->getDrivers() as $column) {
            if (!isset($this->columns[$column['name']])) {
                $this->inject($column['vendor']['Comment'] . '@' . $column['vendor']['Type'], $column['name']);
                isset($this->defaults[$column['name']]) ? $this->columns[$column['name']] = $this->table . '.' . $column['name'] : null;
            }
        }
        /** query */
        $select = 'SELECT ';
        $this->sumQuery = 'SELECT COUNT(';
        $primary = $this->getPrimary();
        foreach ($this->columns as $alias => $column) {
            $column = (preg_match('/\.|\s| |\(|\)/', trim($column))) ? $column : $this->table . '.' . $column;
            if(isset($primary[$column])) {
                unset($primary[$column]);
            }
            $select .= ' ' . $column . ' AS `' . $alias . '`, ';
        }
        foreach($primary as $column => $alias) {
            $select .= ' ' . $column . ' AS `' . $alias . '`, ';
        }
        $this->query = rtrim($select, ', ');
        $this->select = rtrim(ltrim($select, 'SELECT '), ', ');
        $this->sumQuery .= (null == $this->group) ? '*' : 'DISTINCT ' . $this->group;
        $this->sumQuery .= ') AS sum ';
        $from = ' FROM ' . $this->table . ' ';
        foreach ($this->join as $join) {
            $from .= ' JOIN ' . $join . ' ';
        }
        foreach ($this->leftJoin as $join) {
            $from .= ' LEFT JOIN ' . $join . ' ';
        }
        foreach ($this->innerJoin as $join) {
            $from .= ' INNER JOIN ' . $join . ' ';
        }
        $this->query .= $from;
        $this->sumQuery .= $from;
    }

    public function filter() {
        if(null == $filters = $this->presenter->request->getPost('filters')) {
            $filters = [];
        }
        if(null == $sort = $this->presenter->request->getPost('sort')) {
            foreach($this->columns as $name => $column) {
                if(false == $this->getAnnotation($name, 'unrender')) {
                    $sort = [$name => 'DESC'];
                    break;
                }
            }

        }
        $this->sort = '';
        foreach($sort as $order => $sorted) {
            $this->sort .= ' ' . $order . ' ' . strtoupper($sorted) . ', ';
        }
        $this->sort = rtrim($this->sort, ', ');
        $offset = $this->presenter->request->getPost('offset');
        foreach ($filters as $column => $value) {
            $key = preg_replace('/\s(.*)/', '', $column);
            if(is_array($value)) {
                $this->where[$this->columns[$key]] = $value;
                continue;
            }
            $value = preg_replace('/\;/', '', htmlspecialchars($value));
            if(is_array($subfilters = $this->getAnnotation($column, 'filter'))) {
                foreach ($subfilters as $filter) {
                    $this->where[$filter . ' LIKE'] = '%' . $value . '%';
                }
            } elseif (preg_match('/\s\>\=/', $column)) {
                $this->where[$this->columns[$key] . ' >='] = $value;
            } elseif (preg_match('/\s\<\=/', $column)) {
                $this->where[$this->columns[$key] . ' <='] = $value;
            } elseif (preg_match('/\s\>/', $column)) {
                $this->where[$this->columns[$key] . ' >'] = $value;
            } elseif (preg_match('/\s\</', $column)) {
                $this->where[$this->columns[$key] . ' <'] = $value;
            } elseif (preg_match('/\(/', $this->columns[$column]) and ( is_numeric($value) or ( (bool) strpbrk($value, 1234567890) and strtotime($value)))) {
                $this->having .= $this->columns[$column] . ' = ' . $value . ' AND ';
            } elseif (preg_match('/\(/', $this->columns[$column])) {
                $this->having .= $this->columns[$column] . ' LIKE "%' . $value . '%" AND ';
            } elseif (is_numeric($value) or ( (bool) strpbrk($value, 1234567890) and strtotime($value))) {
                $this->where[$this->columns[$column]] = $value;
            } else {
                $this->where[$this->columns[$column] . ' LIKE'] = '%' . $value . '%';
            }
        }
        /** where and having */
        $where = (!empty($this->where)) ? ' WHERE ' : '';
        $salt = '';
        foreach ($this->where as $column => $value) {
            $column = (preg_match('/\./', $column) or is_numeric($column)) ? $column : '`' . $column . '`';
            if (is_numeric($column)) {
                $where .= ' ' . $value . ' AND ';
            } elseif (is_array($value) and preg_match('/\sIN|\sNOT/', strtoupper($column))) {
                $where .= ' ' . $column . ' (?) AND ';
                $this->arguments[] = $value;
            } elseif (!is_array($value) and preg_match('/(>|<|=|\sLIKE|\sIN|\sIS|\sNOT|\sNULL|\sNULL)/', strtoupper($column))) {
                $where .= ' ' . $column . ' ? AND ';
                $this->arguments[] = $value;
            } elseif (is_array($value)) {
                $where .= ' ' . $column . ' IN (?) AND ';
                $this->arguments[] = $value;
            } else {
                $where .= ' ' . $column . ' = ? AND ';
                $this->arguments[] = $value;
            }
            $salt .= is_array($value) ? implode(',', $value) : $value;
        }
        $this->query .= rtrim($where, 'AND ');
        $this->sumQuery .= rtrim($where, 'AND ');
        /** group, having */
        $this->query .= (null == $this->group) ? '' : ' GROUP BY ' . $this->group . ' ';
        $salt .= '|' . $this->group;
        $this->query .= ('' == $this->having) ? '' : ' HAVING ' . $this->having . ' ';
        $salt .= '|' . $this->having;
        $this->sumQuery .= (null == $this->having) ? '' : ' HAVING ' . $this->having . ' ';
        /** sort */
        $this->query .= ' ORDER BY ' . $this->sort . ' ';
        $salt .= '|' . $this->sort;
        if('export' == $this->presenter->request->getPost('status')) {
            $this->offset = $offset;
            $this->limit = $this->config['exportSpeed'];
        } else {
            $this->offset = (in_array($offset, ['0', '1', null])) ? 0 : ($offset - 1) * $this->config['pagination'];
            $this->limit = $this->config['pagination'];
        }
        $this->hash = md5(strtolower(preg_replace('/\s+| +/', '', trim($this->query . '|' . $salt))));
        $this->spice = json_encode($filters);
        /** fetch */
        return $this;
    }

}
