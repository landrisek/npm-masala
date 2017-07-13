<?php

namespace Masala;

use Nette\Application\UI\Presenter,
    Nette\Caching\IStorage,
    Nette\Caching\Cache,
    Nette\Database\Context,
    Nette\Database\Table\ActiveRow,
    Nette\Database\Table\Selection,
    Nette\InvalidStateException,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class Builder implements IBuilder {

    /** @var array */
    private $annotations;

    /** @var array */
    private $arguments = [];

    /** @var IBuild */
    private $build;

    /** @var Cache */
    private $cache;

    /** @var array */
    private $config;

    /** @var string */
    private $control;

    /** @var array */
    private $columns = [];

    /** @var Context */
    private $database;

    /** @var array */
    private $defaults;

    /** @var IEdit */
    private $edit;

    /** @var IProcess */
    private $export;

    /** @var IProcess */
    private $exportService;

    /** @var callable */
    private $fetch;

    /** @var IFilter */
    private $filter;

    /** @var int */
    private $group;

    /** @var array */
    private $groups = [];

    /** @var string */
    private $having;

    /** @var IProcess */
    private $import;

    /** @var array */
    private $innerJoin = [];

    /** @var array */
    private $join = [];

    /** @var array */
    private $leftJoin = [];

    /** @var int */
    private $limit;

    /** @var int */
    private $offset;

    /** @var array */
    private $order;

    /** @var Presenter */
    private $presenter;

    /** @var IProcess */
    private $service;

    /** @var string */
    private $sort;

    /** @var string */
    private $select;

    /** @var string */
    private $sum;

    /** @var array */
    private $where = [];

    /** @var array */
    private $table;

    /** @var IUpdate */
    private $update;

    /** @var ITranslator */
    private $translatorModel;

    /** @var string */
    private $query;

    public function __construct(array $config, ExportService $exportService, Context $database, IStorage $storage, IRequest $httpRequest, ITranslator $translatorModel) {
        $this->config = $config;
        $this->exportService = $exportService;
        $this->database = $database;
        $this->cache = new Cache($storage);
        $this->translatorModel = $translatorModel;
    }

    public function attached(Masala $masala) {
        $this->presenter = $masala->getPresenter();
        $this->control = $masala->getName();
        /** import */
        if ($this->import instanceof IProcess and ( $setting = $this->getSetting('import')) instanceof ActiveRow) {
            $this->import->setSetting($setting);
        } elseif ($this->import instanceof IProcess) {
            throw new InvalidStateException('Missing definition of import setting in table ' . $this->config['feedFeeds'] . ' in call ' .
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
                throw new InvalidStateException('Style and groups keywords are reserved for callbacks. See https://github.com/landrisek/Masala/wiki/Methods. Use different alias.');
            }
            $this->inject($annotation, $column);
        }
        foreach ($this->getDrivers($this->table) as $column) {
            if (!isset($this->columns[$column['name']])) {
                $this->inject($column['vendor']['Comment'] . '@' . $column['vendor']['Type'], $column['name']);
                isset($this->defaults[$column['name']]) ? $this->columns[$column['name']] = $this->table . '.' . $column['name'] : null;
            }
        }
        if(isset($this->config['settings']) and
            $this->presenter->getUser()->isLoggedIn() and
            is_object($setting = json_decode($this->presenter->getUser()->getIdentity()->getData()[$this->config['settings']]))) {
            foreach($setting as $source => $annotations) {
                if($this->presenter->getName() . ':' . $this->presenter->getAction() == $source) {
                    foreach($annotations as $annotationId => $annotation) {
                        if(!preg_match('/' . $annotation . '/', $this->columns[$annotationId])) {
                            $this->inject($this->columns[$annotationId] . $annotation, $annotationId);
                        }
                    }
                }
            }
        }
        /** query */
        $select = 'SELECT ';
        $primary = $this->getPrimary();
        foreach ($this->columns as $alias => $column) {
            $column = (preg_match('/\.|\s| |\(|\)/', trim($column))) ? $column : $this->table . '.' . $column;
            if(isset($primary[$column])) {
                unset($primary[$column]);
            }
            $select .= ' ' . $column . ' AS `' . $alias . '`, ';
        }
        foreach($primary as $column => $alias) {
            if(isset($this->columns[$alias]) and !preg_match('/\./', $this->columns[$alias])) {
                throw new InvalidStateException('Alias ' . $alias . ' is reserved for primary key.');
            }
            $select .= ' ' . $column . ' AS `' . $alias . '`, ';
        }
        $this->query = rtrim($select, ', ');
        $this->sum = rtrim($select, ', COUNT(*) AS sum ');
        $this->select = rtrim(ltrim($select, 'SELECT '), ', ');
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
        $this->sum = 'SELECT COUNT(*) AS sum ' . $from;
    }

    /** @return IBuilder */
    public function build(IBuild $build) {
        $this->build = $build;
        return $this;
    }

    /** @return IBuilder */
    public function copy() {
        return new Builder($this->config, $this->exportService, $this->database, $this->storage, $this->httpRequest);
    }
    
    private function column($column) {
        if (true == $this->getAnnotation($column, 'hidden')) {
            
        } elseif (true == $this->getAnnotation($column, ['addSelect', 'addMultiSelect']) and ! preg_match('/\(/', $this->columns[$column])) {
            $this->defaults[$column] = $this->getList($this->columns[$column]);
        } elseif (true == $this->getAnnotation($column, ['addSelect', 'addMultiSelect'])) {
            $this->defaults[$column] = $this->getList($this->table . '.' . $column);
        } elseif (is_array($enum = $this->getAnnotation($column, 'enum')) and false == $this->getAnnotation($column, 'unfilter')) {
            $this->defaults[$column] = $enum;
        } else {
            $this->defaults[$column] = '';
        }
        return $this;
    }

    public function export($export) {
        $this->export = ($export instanceof IProcess) ? $export : $this->exportService;
        return $this;
    }

    /** @return IBuilder */
    public function edit(IEdit $edit) {
        $this->edit = $edit;
        return $this;
    }

    /** @return IBuilder */
    public function fetch(callable $fetch) {
        $this->fetch = $fetch;
        return $this;
    }

    /** @return IBuilder */
    public function filter(IFilter $filter) {
        $this->filter = $filter;
        return $this;
    }

    /** @return bool */
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

    /** @return array */
    public function getArguments() {
        return $this->arguments;
    }

    /** @return string | Bool */
    public function getColumn($key) {
        if (isset($this->columns[$key])) {
            return $this->columns[$key];
        }
        return false;
    }

    /** @return array */
    public function getColumns() {
        return $this->columns;
    }

    /** @return array */
    public function getConfig($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        return [];
    }

    /** @return array */
    public function getDefaults() {
        return $this->defaults;
    }

    /** @return array */
    public function getDrivers($table) {
        $driverId = $this->getKey('attached', $table);
        if (null == $drivers = $this->cache->load($driverId)) {
            foreach($this->database->getConnection()->getSupplementalDriver()->getColumns($table) as $driver) {
                $drivers[$driver['name']] = $driver;
            }
            $this->cache->save($driverId, $drivers);
        }
        return $drivers;
    }

    /** @return void | IEdit */
    public function getEdit() {
        return $this->edit;
    }

    /** @return void | IProcess */
    public function getExcel() {
        return $this->export;
    }

    /** @return void | IProcess */
    public function getExport() {
        return $this->export;
    }

    public function getFilter($key) {
        if (isset($this->where[trim($key)])) {
            return preg_replace('/\%/', '', $this->where[$key]);
        }
        return false;
    }

    /** @return array */
    public function getFilters() {
        return $this->where;
    }

    /** @return string */
    public function getFormat($table, $column) {
        $drivers = $this->getDrivers($table);
        if('DATE' == $drivers[$column]['nativetype']) {
            $select = 'DATE_FORMAT(' . $column . ', ' . $this->config['format']['date']['select'] . ')';
        } else if('TIMESTAMP' == $drivers[$column]['nativetype']) {
            $select = 'DATE_FORMAT(' . $column . ', ' . $this->config['format']['date']['select'] . ')';
        } else {
            $select = $column;
        }
        return 'DISTINCT(' . $select . ') AS ' . $column;

    }

    /** @return array */
    public function getGroup() {
        return $this->groups;
    }

    /** @return string */
    public function getId($status) {
        return md5($this->control . ':' . $this->presenter->getName() . ':' . $this->presenter->getAction()  . ':' . $status . ':' . $this->presenter->getUser()->getId());
    }

    /** @return IProcess | bool */
    public function getImport() {
        return $this->import;
    }

    /** @return string */
    private function getKey($method, $parameters) {
        return str_replace('\\', ':', get_class($this)) . ':' . $method . ':' . $parameters;
    }

    /** @return array */
    public function getList($annotation) {
        $table = preg_match('/\./', $annotation) ? trim(preg_replace('/\.(.*)/', '', $annotation)) : $this->table;
        $column = preg_match('/\./', $annotation) ? trim(preg_replace('/(.*)\./', '', $annotation)) : trim($annotation);
        $key = $this->getKey('getList', $annotation);
        if (null == $list = $this->cache->load($key)) {
            $list = $this->database->table($table)
                ->select($this->getFormat($table, $column))
                ->where($column . ' IS NOT NULL')
                ->where($column . ' !=', '')
                ->fetchPairs($column, $column);
            $this->cache->save($key, $list);
        }
        return $list;
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

    /** @return array */
    public function getOffsets() {
        if(null == $data = $this->cache->load($hash = md5(strtolower(preg_replace('/\s+| +/', '', trim($this->query . $this->offset)))))) {
            $this->arguments[] = intval($this->limit);
            $this->arguments[] = intval($this->offset);
            $build = $this->build instanceof IBuild;
            if (empty($this->join) and empty($this->leftJoin) and empty($this->innerJoin)) {
                $resource = $this->getResource()
                    ->limit($this->limit, $this->offset)
                    ->order($this->sort)
                    ->fetchAll();
                $data = [];
                foreach($resource as $row) {
                    $data[] = $build ? $this->build->build($row->toArray()) : $row->toArray();
                }
            } else {
                $arguments = array_values($this->arguments);
                $resource = $this->database->query($this->query . ' LIMIT ? OFFSET ? ', ...$arguments)->fetchAll();
                $data = [];
                foreach($resource as $row) {
                    $data[] = $build ? $this->build->build((array) $row) : $row;
                }
            }
            if(is_callable($fetch = $this->fetch)) {
                $data = $fetch($this, $data);
            }
            /** if(!empty($data)) {
                $this->cache->save($this->control . ':' . $hash . ':' . $this->offset, $data, [Cache::EXPIRE => '+1 hour']);
             }*/
        }
        $this->logQuery($hash);
        return $data;
    }

    /** @return int */
    public function getPagination() {
        return $this->config['pagination'];
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

    /** @return Selection */
    public function getResource() {
        $dataSource = $this->database->table($this->table);
        (null == $this->select) ? null : $dataSource->select($this->select);
        foreach ($this->where as $column => $value) {
            is_numeric($column) ? $dataSource->where($value) : $dataSource->where($column, $value);
        }
        if(isset($this->groups[$this->group])) {
            foreach(explode(',', $this->groups[$this->group]) as $group) {
                $dataSource->where(trim($group) . ' IS NOT NULL');
            }
            $dataSource->group($this->groups[$this->group]);
        }
        empty($this->having) ? null : $dataSource->having($this->having);
        return $dataSource;
    }

    /** @return IProcess */
    public function getService() {
        return $this->service;
    }

    /** @return int */
    public function getSum() {
        if(empty($this->where)) {
            return $this->database->query('SHOW TABLE STATUS WHERE Name = "' . $this->table . '"')->fetch()->Rows;
        } elseif (empty($this->join) && empty($this->leftJoin) && empty($this->innerJoin)) {
            return $this->getResource()->count();
        } else {
            $arguments = [];
            foreach ($this->arguments as $key => $argument) {
                is_numeric($key) ? $arguments[] = $argument : null;
            }
            return $this->database->query($this->sum, ...$arguments)->fetch()->sum;
        }
    }

    /** @return int */
    public function getSummary() {
        if(!preg_match('/SUM\(/', $summary = $this->columns[$this->presenter->request->getPost('summary')])) {
            $summary = 'SUM(' . $summary . ')';
        }
        $query = preg_replace('/SELECT(.*)FROM/', 'SELECT ' . $summary . ' AS sum FROM', $this->sum);
        if(null == $sum = $this->database->query($query, ...$this->arguments)->fetch()->sum) {
            return 0;
        }
        return $sum;
    }

    /** @return ActiveRow */
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

    /** @return string */
    public function getQuery() {
        return $this->query;
    }

    /** @return IBuilder */
    public function group(array $groups) {
        $this->groups = $groups;
        return $this;
    }

    public function having($having) {
        $this->having = (string) $having;
        return $this;
    }

    /** @return void */
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

    /** @return IBuilder */
    public function import(IProcess $import) {
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

    /** @return IBuilder */
    public function select(array $columns) {
        $this->columns = $columns;
        return $this;
    }

    /** @return array */
    public function submit(array $row) {
        if($this->update instanceof IUpdate) {
            $row = $this->update->update($row);
        }
        return $row;
    }

    /** @return IBuilder */
    public function table($table) {
        $this->table = (string) $table;
        return $this;
    }
    
    /** @return IBuilder */
    public function update(IUpdate $update) {
        $this->update = $update;
        return $this;
    }

    /** @return IBuilder */
    public function where($key, $column = null, $condition = null) {
        if(is_bool($condition) and false == $condition) {
        } elseif ('?' == $column and isset($this->where[$key])) {
            $this->arguments[$key] = $this->where[$key];
        } elseif (preg_match('/\>/', $key) and is_string($condition)) {
            $this->where[$key] = $column;
            $this->annotations[preg_replace('/ (.*)|(.*)\./', '', $key)]['unrender'] = true;
        } elseif (preg_match('/\</', $key) and is_string($condition)) {
            $this->where[$key] = $column;
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

    /** @return IBuilder */
    public function order(array $order) {
        foreach($order as $column => $value) {
            if(!isset($this->columns[$column])) {
                throw new InvalidStateException('You muse define order column ' . $column . ' in select method of Masala\IBuilder.');
            } else if('DESC' != $value && 'ASC' != $value) {
                throw new InvalidStateException('Order value can be only DESC or ASC.');
            }
        }
        $this->order = $order;
        return $this;
    }

    /** @return IBuilder */
    public function setRow($primary, $data) {
        $this->database->table($this->table)
                ->wherePrimary($primary)
                ->update($data);
        return $this;
    }

    /** @return IBuilder */
    public function process(IProcess $service) {
        $this->service = $service;
        return $this;
    }

    public function prepare() {
        if(null == $filters = $this->presenter->request->getPost('filters')) {
            $filters = [];
        }
        if(isset($filters['groups'])) {
            $this->group = preg_replace('/_/', '', $filters['groups']);
            unset($filters['groups']);
        } else if(!empty($this->groups)) {
            $this->group = 0;
        }
        if(null == $sort = $this->presenter->request->getPost('sort') and null == $this->order) {
            foreach($this->columns as $name => $column) {
                if(false == $this->getAnnotation($name, 'unrender')) {
                    $sort = [$name => 'DESC'];
                    break;
                }
            }            
        } else if(null == $sort) {
            $sort = $this->order;
        }
        $this->sort = '';
        foreach($sort as $order => $sorted) {
            $this->sort .= ' ' . $order . ' ' . strtoupper($sorted) . ', ';
        }
        $this->sort = rtrim($this->sort, ', ');
        $offset = $this->presenter->request->getPost('offset');
        if($this->filter instanceof IFilter) {
            $filters = $this->filter->filter($filters);
        }
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
            } elseif (preg_match('/\s\>\=/', $column) and (bool) strpbrk($value, 1234567890) and is_int($converted = strtotime($value))) {
                $this->where[$this->columns[$key] . ' >='] = date($this->config['format']['date']['query'], $converted);
            } elseif (preg_match('/\s\>\=/', $column)) {
                $this->where[$this->columns[$key] . ' >='] = $value;
            } elseif (preg_match('/\s\<\=/', $column) and (bool) strpbrk($value, 1234567890) and is_int($converted = strtotime($value))) {
                $this->where[$this->columns[$key] . ' <='] = date($this->config['format']['date']['query'], $converted);
            } elseif (preg_match('/\s\<\=/', $column)) {
                $this->where[$this->columns[$key] . ' <='] = $value;
            } elseif (preg_match('/\s\>/', $column) and (bool) strpbrk($value, 1234567890) and is_int($converted = strtotime($value))) {
                $this->where[$this->columns[$key] . ' >'] = date($this->config['format']['date']['query'], $converted);
            } elseif (preg_match('/\s\>/', $column)) {
                $this->where[$this->columns[$key] . ' >'] = $value;
            } elseif (preg_match('/\s\</', $column) and (bool) strpbrk($value, 1234567890) and is_int($converted = strtotime($value))) {
                $this->where[$this->columns[$key] . ' <'] = date($this->config['format']['date']['query'], $converted);
            } elseif (preg_match('/\s\</', $column)) {
                $this->where[$this->columns[$key] . ' <'] = $value;
            } elseif (preg_match('/\(/', $this->columns[$column]) and (bool) strpbrk($value, 1234567890) and is_int($converted = strtotime($value))) {
                $this->having .= $column . ' = "' . $value . '" AND ';
            } elseif (preg_match('/\(/', $this->columns[$column]) and is_numeric($value)) {
                $this->having .= $column . ' = ' . $value . ' AND ';
            } elseif (preg_match('/\(/', $this->columns[$column])) {
                $this->having .= $column . ' LIKE "%' . $value . '%" AND ';
            } elseif ((bool) strpbrk($value, 1234567890) and is_int($converted = strtotime($value))) {
                $this->where[$this->columns[$column]] = date($this->config['format']['date']['query'], $value);
            } elseif (is_numeric($value)) {
                $this->where[$this->columns[$column]] = $value;
            } else {
                $this->where[$this->columns[$column] . ' LIKE'] = '%' . $value . '%';
            }
        }
        /** where and having */
        $where = (!empty($this->where)) ? ' WHERE ' : '';
        foreach ($this->where as $column => $value) {
            $column = (preg_match('/\./', $column) or is_numeric($column)) ? $column : '`' . $column . '`';
            if (is_numeric($column)) {
                $where .= ' ' . $value . ' AND ';
            } elseif (is_array($value) and preg_match('/\sIN|\sNOT/', strtoupper($column))) {
                $where .= ' ' . $column . ' (?) AND ';
                $this->arguments[] = $value;
            } elseif (!is_array($value) and preg_match('/(>|<|=|\sLIKE|\sIN|\sIS|\sNOT|\sNULL|\sNULL)/', strtoupper($column))) {
                $where .= ' ' . str_replace('`', '', $column) . ' ? AND ';
                $this->arguments[] = $value;
            } elseif (is_array($value)) {
                $where .= ' ' . $column . ' IN (?) AND ';
                $this->arguments[] = $value;
            } else {
                $where .= ' ' . $column . ' = ? AND ';
                $this->arguments[] = $value;
            }
        }
        /** group, having */
        $this->having = rtrim($this->having, 'AND ');
        $this->query .= rtrim($where, 'AND ');
        $this->sum .= rtrim($where, 'AND ');
        if(isset($this->groups[$this->group])) {
            $this->query .= ' GROUP BY ' . $this->groups[$this->group] . ' ';
            $this->sum = str_replace('COUNT(*) AS sum', $this->groups[$this->group] . ', COUNT(DISTINCT ' . $this->groups[$this->group] . ') AS sum', $this->sum);
        }
        if(!empty($this->having)) {
            $this->query .= ' HAVING ' . $this->having . ' ';
            $this->sum .= ' HAVING ' . $this->having . ' ';
        }
        /** sort */
        $this->query .= ' ORDER BY ' . $this->sort . ' ';
        /** offset */
        if(in_array($this->presenter->request->getPost('status'), ['excel', 'export'])) {
            $this->offset = $offset;
            $this->limit = $this->config['exportSpeed'];
        } else {
            $this->offset = ($offset - 1) * $this->config['pagination'];
            $this->limit = $this->config['pagination'];
        }
        return $this;
    }

    /** @return string */
    public function translate($name, $annotation) {
        if ($this->presenter->getName() . ':' . $this->presenter->getAction() . ':' . $name != $label = $this->translatorModel->translate($this->presenter->getName() . ':' . $this->presenter->getAction() . ':' . $name)) {
        } elseif ($this->presenter->getName() . ':' . $name != $label = $this->translatorModel->translate($this->presenter->getName() . ':' . $name)) {
        } elseif ($annotation != $label = $this->translatorModel->translate($annotation)) {
        } elseif ($label = $this->translatorModel->translate($name)) {
        }
        return $label;
    }
}
