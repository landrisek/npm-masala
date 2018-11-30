<?php

namespace Masala;

use Nette\Application\IPresenter,
    Nette\Caching\IStorage,
    Nette\Caching\Cache,
    Nette\Database\Context,
    Nette\Database\Table\ActiveRow,
    Nette\Database\Table\IRow,
    Nette\Database\Table\Selection,
    Nette\Utils\DateTime,
    Nette\InvalidStateException,
    Nette\Localization\ITranslator,
    Nette\Security\IIdentity,
    Nette\Utils\Validators;

/** @author Lubomir Andrisek */
final class Builder implements IBuilder {

    /** @var array */
    private $actions = [];

    /** @var IAdd */
    private $add;

    /** @var array */
    private $annotations;

    /** @var array */
    private $arguments = [];

    /** @var IBuild */
    private $build;

    /** @var IButton */
    private $button;

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
    private $dialogs = [];

    /** @var array */
    private $defaults;

    /** @var IEdit */
    private $edit;

    /** @var IProcess */
    private $export;

    /** @var IProcess */
    private $exportFacade;

    /** @var IProcess */
    private $facade;

    /** @var IFetch */
    private $fetch;

    /** @var IFilter */
    private $filter;

    /** @var IChart */
    private $chart;

    /** @var int */
    private $group;

    /** @var array */
    private $groups = [];

    /** @var string */
    private $having;

    /** @var IIdentity */
    private $identity;

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

    /** @var IListener */
    private $listener;

    /** @var array */
    private $keys = [];

    /** @var int */
    private $offset;

    /** @var array */
    private $order;

    /** @var array */
    private $post;

    /** @var IPresenter */
    private $presenter;

    /** @var array */
    private $primary = [];

    /** @var IRemove */
    private $remove;

    /** @var IRowFormFactory */
    private $row;

    /** @var string */
    private $sort;

    /** @var string */
    private $select;

    /** @var IStorage */
    private $storage;

    /** @var string */
    private $sum;

    /** @var array */
    private $where = [];

    /** @var string */
    private $table = '';

    /** @var IUpdate */
    private $update;

    /** @var ITranslator */
    private $translatorModel;

    /** @var string */
    private $query;

    public function __construct(array $config, ExportFacade $exportFacade, Context $database, IStorage $storage, IRowFormFactory $row, ITranslator $translatorModel) {
        $this->config = $config;
        $this->exportFacade = $exportFacade;
        $this->database = $database;
        $this->cache = new Cache($storage);
        $this->row = $row;
        $this->storage = $storage;
        $this->translatorModel = $translatorModel;
    }

    public function attached(IMasalaFactory $masala): void {
        $this->presenter = $masala->getPresenter();
        $this->identity = $this->presenter->getUser()->getIdentity();
        $this->control = $masala->getName();
        /** import */
        $setting = $this->getSetting('import');
        if ($this->import instanceof IProcess && $setting instanceof ActiveRow) {
            $this->import->setSetting($setting);
        } elseif ($this->import instanceof IProcess) {
            throw new InvalidStateException('Missing definition of import setting in table ' . $this->config['feeds'] . ' in call ' .
                $this->presenter->getName() . ':' . $this->presenter->getAction());
        }
        /** export */
        $setting = $this->getSetting('export');
        if ($this->export instanceof IProcess && $setting instanceof ActiveRow) {
            $this->export->setSetting($setting);
        }
        /** process */
        $setting = $this->getSetting('process');
        if ($setting instanceof ActiveRow) {
            $this->facade->setSetting($setting);
        }
        $this->setKeys();
        /** select */
        foreach ($this->columns as $column => $annotation) {
            if (preg_match('/\sAS\s/', $annotation)) {
                throw new InvalidStateException('Use intented alias as key in column ' . $column . '.');
            } elseif (in_array($column, ['style', 'groups', 'class', 'className'])) {
                throw new InvalidStateException('Style, groups and class and className keywords are reserved for callbacks and column for Grid.jsx:update method. See https://github.com/landrisek/Masala/wiki/Select-statement. Use different alias.');
            }
            $this->inject($annotation, $column);
        }
        foreach ($this->getDrivers($this->table) as $driver) {
            if (!isset($this->columns[$driver['name']])) {
                $driver['nullable'] ? null : $driver['vendor']['Comment'] .= '@required';
                $this->inject($driver['vendor']['Comment'] . '@' . $driver['vendor']['Type'] . '@' . strtolower($driver['vendor']['Key']) . '@' . strtolower($driver['nativetype']), $driver['name']);
                isset($this->defaults[$driver['name']]) ? $this->columns[$driver['name']] = $this->table . '.' . $driver['name'] : null;
            }
        }
        if(isset($this->config['settings']) && is_object($setting = json_decode($this->identity->getData()[$this->config['settings']]))) {
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
        $select = 'SELECT ';
        $primary = $this->keys;
        foreach ($this->columns as $alias => $column) {
            if(empty($column)) {
                $column = 'NULL';
            } else if (!preg_match('/\.|\s| |\(|\)/', trim($column))) {
                $column = $this->table . '.' . $column;
            }
            if(isset($primary[$column])) {
                unset($primary[$column]);
            }
            if($this->sanitize($column)) {
                $select .= ' ' . $column . ' AS `' . $alias . '`, ';
            }
        }
        foreach($primary as $column => $alias) {
            if(isset($this->columns[$alias]) and !preg_match('/\./', $this->columns[$alias])) {
                throw new InvalidStateException('Alias ' . $alias . ' is reserved for primary key.');
            }
            $select .= ' ' . $column . ' AS `' . $alias . '`, ';
        }
        $this->query = rtrim($select, ', ');
        $this->sum = 'SELECT COUNT(*) AS sum ';
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
        $this->sum .= $from;
    }

    public function build(IBuild $build): IBuilder {
        $this->build = $build;
        return $this;
    }

    public function button(IButton $button): IBuilder {
        $this->button = $button;
        return $this;
    }

    public function chart(IChart $chart): IBuilder {
        $this->chart = $chart;
        return $this;
    }

    public function copy(): IBuilder {
        return new Builder($this->config, $this->exportFacade, $this->database, $this->storage, $this->row, $this->translatorModel);
    }
    
    private function column(string $column): IBuilder {
        if (!empty($this->getAnnotation($column, 'hidden'))) {
        } elseif (!empty($this->getAnnotation($column, ['addSelect', 'addMultiSelect']))) {
            $this->defaults[$column] = $this->getList($column);
        } elseif (is_array($enum = $this->getAnnotation($column, 'enum')) and empty($this->getAnnotation($column, 'unfilter'))) {
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
            throw new InvalidStateException('Primary keys were not set.');
        }
        if($this->remove instanceof IRemove) {
            $this->remove->remove($this->primary, $data);
        } else {
            $resource = $this->database->table($this->table);
            foreach($this->primary as $column => $value) {
                $resource->where($column, $value);
            }
            $resource->delete();
        }
        return ['remove' => true];
    }

    public function export($export): IBuilder {
        $this->export = ($export instanceof IProcess) ? $export : $this->exportFacade;
        return $this;
    }

    public function edit($edit): IBuilder {
        if($edit instanceof IEdit) {
            $this->edit = $edit;
        } else {
            $this->edit = new Edit();
        }
        $this->actions['add'] = 'add';
        $this->actions['edit'] = 'edit';
        return $this;
    }
    
    public function fetch(IFetch $fetch): IBuilder {
        $this->fetch = $fetch;
        return $this;
    }

    private function format(array $row): array {
        foreach($row as $key => $format) {
            if($format instanceof DateTime) {                
                $row[$key] = $format instanceof DateTime ? date($this->config['format']['date']['build'], strtotime($format->__toString())) : $row[$key];
            }
        }
        return $row;
    }
    
    public function filter(IFilter $filter): IBuilder {
        $this->filter = $filter;
        return $this;
    }

    public function getAnnotation(string $column, $annotation): array {
        if (is_array($annotation)) {
            foreach ($annotation as $annotationId) {
                if (isset($this->annotations[$column][$annotationId])) {
                    return $this->annotations[$column];
                }
            }
            return [];
        } elseif (isset($this->annotations[$column][$annotation]) and is_array($this->annotations[$column][$annotation])) {
            return $this->annotations[$column][$annotation];
        } elseif (isset($this->annotations[$column][$annotation])) {
            return [true=>true];
        } else {
            return [];
        }
    }

    public function getActions(): array {
        return $this->actions;
    }

    public function getArguments(): array {
        return $this->arguments;
    }
    
    public function getButton(): IButton {
        return $this->button;
    }

    public function getChart(): IChart {
        return $this->chart;
    }

    /** @return string | bool */
    public function getColumn(string $key) {
        if (isset($this->columns[$key])) {
            return $this->columns[$key];
        }
        return false;
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
        return $this->defaults;
    }

    public function getDialogs(): array {
        if($this->edit instanceof IEdit || true == $this->edit) {
            $this->dialogs['add'] = 'add';
            $this->dialogs['edit'] = 'edit';
        }
        return $this->dialogs;
    }

    public function getDrivers(string $table): array {
        $driverId = $this->getKey('attached', $table);
        if (null == $drivers = $this->cache->load($driverId)) {
            foreach($this->database->getConnection()->getSupplementalDriver()->getColumns($table) as $driver) {
                $drivers[$driver['name']] = $driver;
            }
            $this->cache->save($driverId, $drivers);
        }
        return $drivers;
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
        if (isset($this->where[trim($key)])) {
            return preg_replace('/\%/', '', $this->where[$key]);
        }
        return '';
    }

    public function getFilters(): array {
        return $this->where;
    }
    
    public function getFormat(string $table, string $column): string {
        $drivers = $this->getDrivers($table);
        if(!isset($drivers[$column])) {
            $select = 'NULL';
        } else if('DATE' == $drivers[$column]['nativetype']) {
            $select = 'DISTINCT(DATE_FORMAT(' . $column . ', ' . $this->config['format']['date']['select'] . '))';
        } else if('TIMESTAMP' == $drivers[$column]['nativetype']) {
            $select = 'DISTINCT(DATE_FORMAT(' . $column . ', ' . $this->config['format']['date']['select'] . '))';
        } else {
            $select = $column;
        }
        return $select . ' AS ' . $column;

    }

    public function getGroup(): array {
        return $this->groups;
    }

    public function getId(string $status): string {
        return md5($this->control . ':' . $this->presenter->getName() . ':' . $this->presenter->getAction()  . ':' . $status . ':' . $this->identity->getId());
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

    public function getList(string $alias): array {
        if(!preg_match('/\(/', $this->columns[$alias]) && preg_match('/\./', $this->columns[$alias])) {
            $table = trim(preg_replace('/\.(.*)/', '', $this->columns[$alias]));
            $column = trim(preg_replace('/(.*)\./', '', $this->columns[$alias]));
        } else {
            $table = $this->table;
            $column = $alias;
        }
        $key = $this->getKey('getList', $this->columns[$alias]);
        $list = $this->cache->load($key);
        if(isset($this->where[$table . '.' . $column]) && is_array($this->where[$table . '.' . $column])) {
            return array_combine($this->where[$table . '.' . $column], $this->where[$table . '.' . $column]);
        } else if(isset($this->where[$column]) && is_array($this->where[$column])) {
            return array_combine($this->where[$column], $this->where[$column]);
        } else if($this->filter instanceof IFilter && !empty($list = $this->filter->getList($alias))) {
        } else if (null == $list && isset($this->getDrivers($table)[$column])) {
            if(true == $this->sanitize($this->columns[$alias]) || is_array($primary = $this->database->table($table)->getPrimary())) {
                $select = $this->getFormat($table, $column);
                $primary = $column;
            } else {
                $select = $this->getFormat($table, $column) . ', ' . $primary;
            }
            $list = $this->database->table($table)
                ->select($select)
                ->where($column . ' IS NOT NULL')
                ->where($column . ' !=', '')
                ->group($column)
                ->order($column)
                ->fetchPairs($primary, $column);
            $this->cache->save($key, $list);
        } else if(null == $list) {
            $list = [];
        }
        return $list;
    }

    public function getOffset(int $offset): array {
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

    public function getOffsets(): array {
        if($this->fetch instanceof IFetch) {
            $data = $this->fetch->fetch($this);
        } else if(null == $data = $this->cache->load($hash = md5(strtolower(preg_replace('/\s+| +/', '', trim($this->query . $this->offset)))))) {
            $this->arguments[] = intval($this->limit);
            $this->arguments[] = intval($this->offset);
            if (empty($this->join) and empty($this->leftJoin) and empty($this->innerJoin)) {
                $resource = $this->getResource()
                    ->limit($this->limit, $this->offset)
                    ->order($this->sort)
                    ->fetchAll();
                $data = [];
                foreach($resource as $row) {
                    $data[] = $this->build instanceof IBuild ? $this->build->build($this->format($row->toArray())) : $this->format($row->toArray());
                }
            } else {
                $arguments = array_values($this->arguments);
                $resource = $this->database->query($this->query . ' LIMIT ? OFFSET ? ', ...$arguments);
                $data = [];
                foreach($resource as $row) {
                    $data[] = $this->build instanceof IBuild ? $this->build->build($this->format((array) $row)) : $this->format((array) $row);
                }
            }
            /** if(!empty($data)) {
                $this->cache->save($this->control . ':' . $hash . ':' . $this->offset, $data, [Cache::EXPIRE => '+1 hour']);
             }*/
            $this->logQuery($hash);
        }
        if(!empty($data)) {
            foreach(reset($data) as $column => $value) {
                if(is_object($value)) {
                    $data[-1][$column]['Attributes']['value'] = '';
                } else {
                    $data[-1][$column] = '';
                }
            }
        }
        return $data;
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
        if(null == $this->post = json_decode(file_get_contents('php://input'), true)) {
            $this->post = $_POST;
        }
        if(!isset($this->post[$key])) {
            return [];
        }
        return $this->post[$key];
    }

    public function getRemove(): IRemove {
        return $this->remove;
    }
    
    public function getResource(): Selection {
        $dataSource = $this->database->table($this->table);
        (null == $this->select) ? null : $dataSource->select($this->select);
        foreach ($this->where as $column => $value) {
            is_numeric($column) ? $dataSource->where($value) : $dataSource->where($column, $value);
        }
        if(isset($this->groups[$this->group])) {
            $dataSource->group($this->groups[$this->group]);
        }
        empty($this->having) ? null : $dataSource->having($this->having);
        return $dataSource;
    }

    public function getRow(): array {
        foreach($row = $this->getPost('Row') as $column => $value) {
            if(!isset($this->columns[$column]) || empty($this->columns[$column]) || strlen($column) > strlen(ltrim($column, '_'))) {
                unset($row[$column]);
            } else if(!empty($this->getAnnotation($column, 'pri') && null == $value)) {
                unset($row[$column]);
            } else if(!empty($this->getAnnotation($column, 'pri')) && is_array($value)) {
                $this->primary[$column] = $value['Attributes']['value'];
                unset($row[$column]);
            } else if(!empty($this->getAnnotation($column, 'pri'))) {
                $this->primary[$column] = $value;
                unset($row[$column]);
            } else if(is_array($value) && isset($value['Attributes']) && !empty($this->getAnnotation($column, ['int', 'tinyint']))) {
                $row[$column] = intval($value['Attributes']['value']);
            } else if(is_array($value) && isset($value['Attributes']) && (!isset($value['Attributes']['value']) || empty(ltrim($value['Attributes']['value'], '_')))) {
                unset($row[$column]);
            } else if(is_array($value) && isset($value['Attributes']) && !empty($this->getAnnotation($column, ['decimal', 'float']))) {
                $row[$column] = floatval($value['Attributes']['value']);
            } else if(is_array($value) && isset($value['Attributes']) && !empty($this->getAnnotation($column, ['datetime']))) {
                $row[$column] = date($this->config['format']['time']['query'], strtotime($value['Attributes']['value']));
            } else if(is_array($value) && isset($value['Attributes']) && !empty($this->getAnnotation($column, ['date']))) {
                $row[$column] = date($this->config['format']['date']['query'], strtotime($value['Attributes']['value']));
            } else if(is_array($value) && isset($value['Method']) && isset($value['Attributes'])) {
                $row[$column] = ltrim($value['Attributes']['value'], '_');
            } else if(is_array($value) && isset($value['Attributes'])) {
                $row[$column] = ltrim($value['Attributes']['value'], '_');
            } else if(is_array($value) && isset($value['Label'])) {
                $row[$column] = $value['Label'];
            } else if(!empty($this->getAnnotation($column, 'unedit'))) {
                unset($row[$column]);
            } else if(!empty($this->getAnnotation($column, ['date', 'datetime', 'decimal', 'float', 'int', 'tinyint'])) && 0 == strlen(ltrim($value, '_'))) {
                unset($row[$column]);
            } else if(!empty($defaults = $this->getAnnotation($column,'enum')) && !isset($defaults[ltrim($value, '_')])) {
                unset($row[$column]);
            } else if(is_float($value) || !empty($this->getAnnotation($column, ['decimal', 'float']))) {
                $row[$column] = floatval($value);
            } else if((bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $row[$column] = date($this->config['format']['date']['query'], $converted);
            } else {
                $row[$column] = ltrim($value, '_');
            }
        }
        return $row;
    }

    public function getFacade(): IProcess {
        return $this->facade;
    }

    public function getSort(): string {
        return $this->sort;
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
        if($this->fetch instanceof IFetch) {
            return $this->fetch->sum($this);
        } else if(empty($this->where)) {
            return $this->database->query('SHOW TABLE STATUS WHERE Name = "' . $this->table . '"')->fetch()->Rows;
        } elseif (empty($this->join) && empty($this->leftJoin) && empty($this->innerJoin)) {
            return $this->getResource()->count();
        } else {
            $arguments = [];
            foreach ($this->arguments as $key => $argument) {
                is_numeric($key) ? $arguments[] = $argument : null;
            }
            if(empty($this->groups)) {
                return intval($this->database->query($this->sum, ...$arguments)->fetch()->sum);
            } else {
                return $this->database->query($this->sum, ...$arguments)->getRowCount();
            }
        }
    }

    public function getSummary(): int {
        if(!preg_match('/SUM\(/', $summary = $this->columns[$this->getPost('summary')])) {
            $summary = 'SUM(' . $summary . ')';
        }
        $query = preg_replace('/SELECT(.*)FROM/', 'SELECT ' . $summary . ' AS sum FROM', $this->sum);
        return intval($this->database->query($query, ...$this->arguments)->fetch()->sum);
    }
    
    private function getSetting(string $type): IRow {
        if(null == $row = $this->database->table($this->config['feeds'])
                        ->where('type', $type)
                        ->where('source', $this->presenter->getName() . ':' . $this->presenter->getAction())
                        ->fetch()) {
            return new EmptyRow();
        }
        return $row;
    }

    public function getTable(): string {
        return $this->table;
    }

    public function getQuery(): string {
        return $this->query;
    }
    
    public function group(array $groups): IBuilder {
        $this->groups = $groups;
        return $this;
    }

    public function having(string $having): IBuilder {
        $this->having = $having;
        return $this;
    }

    public function import(IProcess $import): IBuilder {
        $this->import = $import;
        return $this;
    }

    private function inject(string $annotation, string $column): void {
        $annotations = explode('@', $annotation);
        unset($annotations[0]);
        $this->annotations[$column] = isset($this->annotations[$column]) ? $this->annotations[$column] : [];
        foreach ($annotations as $annotationId) {
            if('enum' == $annotationId) {
            } else if ($this->presenter->getName() == $annotationId || $this->presenter->getName() . ':' . $this->presenter->getAction() == $annotationId) {
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
        if(!empty($this->getAnnotation($column, 'hidden'))) {
            unset($this->columns[$column]);
        } else {
            $this->columns[$column] = trim(preg_replace('/\@(.*)/', '', $annotation));
        }
        $this->column($column);
    }

    public function insert(IAdd $add): IBuilder {
        $this->add = $add;
        return $this;
    }

    public function innerJoin(string $innerJoin): IBuilder {
        $this->innerJoin[] = trim($innerJoin);
        return $this;
    }

    public function isButton(): bool {
        return $this->button instanceof IButton;
    }

    public function isChart(): bool {
        return $this->chart instanceof IChart;
    }

    public function isEdit(): bool {
        return $this->edit instanceof IEdit;
    }

    public function isExport(): bool {
        return $this->export instanceof IProcess;
    }

    public function isImport(): bool {
        return $this->import instanceof IProcess;
    }

    public function isListener(): bool {
        return $this->listener instanceof IListener;
    }

    public function isProcess(): bool {
        return $this->facade instanceof IProcess;
    }

    public function isRemove(): bool {
        return $this->remove instanceof IRemove || true == $this->remove;
    }

    public function join(string $join): IBuilder {
        $this->join[] = trim($join);
        return $this;
    }

    public function leftJoin(string $leftJoin): IBuilder {
        $this->leftJoin[] = trim($leftJoin);
        return $this;
    }

    public function limit(int $limit): IBuilder {
        $this->limit = $limit;
        return $this;
    }

    public function listen(IListener $listener): IBuilder {
        $this->listener = $listener;
        return $this;
    }

    private function logQuery(string $key): void {
        if (null == $this->database->table($this->config['spice'])
                        ->where('key', $key)
                        ->fetch()
        ) {
            $this->database->table($this->config['spice'])
                            ->insert(['key' => $key,
                                'source' => $this->presenter->getName() . ':' . $this->presenter->getAction(),
                                'query' => $this->query,
                                'arguments' => json_encode($this->arguments)]);
        }
    }

    public function log(string $handle): void {
        if (isset($this->config['log'])) {
            $this->database->table($this->config['log'])->insert(['users_id' => $this->identity->getId(),
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

    public function row($id, array $row): IRowFormFactory {
        foreach($row as $column => $status) {
            $value = $this->getPost('add') ? null : $status;
            $label = ucfirst($this->translatorModel->translate($this->table . '.' . $column));
            $attributes =  ['className' => 'form-control', 'name' => intval($id), 'value' => is_null($value) ? '' : $value];
            $this->getAnnotation($column, 'disable') ? $attributes['readonly'] = 'readonly' : null;
            $this->getAnnotation($column, 'onchange') ? $attributes['onChange'] = 'submit' : null;
            if ($this->getAnnotation($column, 'pri') || $this->getAnnotation($column, ['unedit'])) {
                $this->row->addHidden($column, $column, ['value' => $value]);
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
                $attributes['format'] = $this->config['format']['time']['build'];
                $attributes['locale'] = preg_replace('/(\_.*)/', '', $this->translatorModel->getLocale());
                $attributes['value'] = empty($value) ? null : date($this->config['format']['time']['build'], strtotime($value));
                $this->row->addDateTime($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, ['date'])) {
                $attributes['format'] = $this->config['format']['date']['build'];
                $attributes['locale'] = preg_replace('/(\_.*)/', '', $this->translatorModel->getLocale());
                $attributes['value'] = empty($value) ? null : date($this->config['format']['date']['build'], strtotime($value));
                $this->row->addDateTime($column, $label . ':', $attributes, []);
            } elseif ($this->getAnnotation($column, 'tinyint') && 1 == $value) {
                $attributes['checked'] = 'checked';
                $this->row->addCheckbox($column, $label, $attributes, []);
            } elseif ($this->getAnnotation($column, 'tinyint')) {
                $attributes['checked'] = null;
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
                $this->row->addUpload($column, $label);
            } elseif ($this->getAnnotation($column, 'multiupload')) {
                $attributes['max'] = $this->config['upload'];
                $this->row->addMultiUpload($column, $label, $attributes, []);
            } else {
                $this->row->addText($column, $label . ':', $attributes, []);
            }
        }
        $this->row->addMessage('_message', $this->translatorModel->translate('Changes were saved.'), ['className' => 'alert alert-success']);
        $this->row->addSubmit('_submit', ucfirst($this->translatorModel->translate('save')),
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

    private function setKeys(): void {
        if(is_array($keys = $this->database->table($this->table)->getPrimary())) {
            foreach($keys as $key) {
                $this->keys[$this->table . '.'  . $key] = $key;
            }
        } else {
            $this->keys = [$this->table . '.'  . $keys => $keys];
        }
    }

    public function submit(bool $submit): array {
        $row = $this->getRow();
        $updates = [$this->table => []];
        foreach($row as $column => $value) {
            $updates[preg_replace('/\.(.*)/', '', $this->columns[$column])][$column] = $value;
        }
        $resource = $this->database->table($this->table);
        if(empty($this->primary) && $this->add instanceof IAdd) {
            return $this->add->insert($row);
        } else if(empty($this->primary)) {
            return $this->database->table($this->table)->insert($row)->toArray();
        }
        foreach($this->primary as $column => $value) {
            $resource->where($column, $value);
        }
        $resource->limit(1)->update($updates[$this->table]);
        if(true == $submit && $this->edit instanceof IEdit) {
            $new = $this->edit->submit($this->primary, $this->getPost('Row'));
        } else if(false == $submit && $this->update instanceof IUpdate) {
            $new = $this->update->update($this->getPost('Key'), $this->getPost('Row'));
        }
        foreach($this->where as $column => $value) {
            is_numeric($column) ? $resource->where($value) : $resource->where($column, $value);
        }
        if(isset($new)) {
            return $new;
        } else if(null == $resource->fetch()) {
            return [];
        } else {
            return $this->getPost('Row');
        }
    }

    public function table(string $table): IBuilder {
        $this->table = $table;
        return $this;
    }

    public function update(IUpdate $update): IBuilder {
        $this->update = $update;
        return $this;
    }
    
    public function validate(): array {
        $validators = [];
        foreach($row = $this->getRow() as $column => $value) {
            if($this->getAnnotation($column, 'unedit')) {
            } else if($this->getAnnotation($column, 'required') && empty($value) && false == $this->getAnnotation($column, 'tinyint')) {
                $validators[$column] = ucfirst($this->translatorModel->translate($column)) . ' ' . $this->translatorModel->translate('is required.');
            } else if($this->getAnnotation($column, 'uni')) {
                $resource = $this->database->table($this->table);
                foreach($this->primary as $primary => $id) {
                    $resource->where($primary . ' !=', $id);
                }
                if($resource->where($column, $value)->fetch() instanceof ActiveRow) {
                    $validators[$column] =  ucfirst($this->translatorModel->translate('unique item'))  . ' ' . $this->translatorModel->translate($column) . ' ' . $this->translatorModel->translate('already defined in source table.');
                }
            } else if($this->getAnnotation($column, 'email') && Validators::isEmail($value)) {
                $validators[$column] = $this->translatorModel->translate($column) . ' ' . $this->translatorModel->translate('is not valid email.');
            } else if($this->getAnnotation($column, ['int', 'decimal', 'double', 'float']) && !is_numeric($value)) {
                $validators[$column] = $this->translatorModel->translate($column) . ' ' . $this->translatorModel->translate('is not valid number.');
            }
        }
        return $validators;
    }

    public function where($key, $column = null, $condition = null): IBuilder {
        if((is_bool($condition) && false == $condition) || empty($key)) {
        } elseif ('?' == $column and isset($this->where[$key])) {
            $this->arguments[$key] = $this->where[$key];
        } elseif (is_string($condition)) {
            $this->where[preg_replace('/(\s+\?.*|\?.*)/', '', $key)] = $column . $condition;
        } elseif (is_bool($condition) and true == $condition and is_array($column)) {
            $this->where[$key] = $column;
            $this->annotations[preg_replace('/(.*)\./', '', $key)]['enum'] = array_combine($column, $column);
        } elseif (is_callable($column) and false != $value = $column()) {
            $this->where[$key] = $value;
        } elseif (is_array($column) && isset($this->columns[preg_replace('/(.*)\./', '', $key)])) {
            $this->where[$key] = $column;
            $this->annotations[preg_replace('/(.*)\./', '', $key)]['enum'] = array_combine($column, $column);
        } elseif (is_string($column) || is_numeric($column) || is_array($column)) {
            $this->where[$key] = $column;
        } elseif (null === $column) {
            $this->where[] = $key;
        } elseif (null === $condition) {
            $this->where[] = $key;
        }
        return $this;
    }
    
    public function order(array $order): IBuilder {
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

    public function setRow(array $primary, array $data): IBuilder {
        $this->database->table($this->table)
                ->wherePrimary($primary)
                ->update($data);
        return $this;
    }

    public function prepare(): IBuilder {
        if(null == $filters = $this->getPost('Filters')) {
            $filters = [];
        }
        if(isset($filters['groups'])) {
            $this->group = rtrim($filters['groups'], '_');
            unset($filters['groups']);
        } else if(!empty($this->groups)) {
            $this->group = 0;
        }
        if(empty($sort = $this->getPost('Sort')) && null == $this->order) {
            foreach($this->columns as $name => $column) {
                if(empty($this->getAnnotation($name, 'unrender'))) {
                    $sort = [$name => 'DESC'];
                    break;
                }
            }
        } else if(null == $sort) {
            $sort = $this->order;
        }
        if(empty($sort)) {
            foreach($this->keys as $primary => $value) {
                $this->sort .= $primary . ' ASC, ';
            }
        }
        $this->sort = '';
        foreach($sort as $order => $sorted) {
            $this->sort .= ' ' . $order . ' ' . strtoupper($sorted) . ', ';
        }
        if(!is_numeric($offset = $this->getPost('Offset'))) {
            $offset = 0;
        } else if($offset > 0 && empty($this->getPost('Status'))) {
            $offset = $offset - 1; 
        }
        foreach ($filters as $column => $value) {
            $key = preg_replace('/\s(.*)/', '', $column);
            if(is_array($value) && [""] != $value && !empty($value) && !empty($this->columns[$key])) {
                foreach($value as $underscoreId => $underscore) {
                    $value[$underscoreId] = ltrim($value[$underscoreId], '_');
                }
                $this->where[$this->columns[$key]] = $value;
                continue;
            } else if([""] == $value || empty($value) || empty($this->columns[$key])) {
                continue;
            }
            $value = ltrim(preg_replace('/\;/', '', htmlspecialchars($value)), '_');
            if(!empty($subfilters = $this->getAnnotation($column, 'where'))) {
                foreach ($subfilters as $filter) {
                    $this->where[$filter . ' LIKE'] = '%' . $value . '%';
                }
            } elseif (preg_match('/\s\>\=/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->where[$this->columns[$key] . ' >='] = date($this->config['format']['date']['query'], $converted);
            } elseif (preg_match('/\s\>\=/', $column)) {
                $this->where[$this->columns[$key] . ' >='] = $value;
            } elseif (preg_match('/\s\<\=/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*\./', $value)) {
                $this->where[$this->columns[$key] . ' <='] = date($this->config['format']['date']['query'], $converted);
            } elseif (preg_match('/\s\<\=/', $column)) {
                $this->where[$this->columns[$key] . ' <='] = $value;
            } elseif (preg_match('/\s\>/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->where[$this->columns[$key] . ' >'] = date($this->config['format']['date']['query'], $converted);
            } elseif (preg_match('/\s\>/', $column)) {
                $this->where[$this->columns[$key] . ' >'] = $value;
            } elseif (preg_match('/\s\</', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->where[$this->columns[$key] . ' <'] = date($this->config['format']['date']['query'], $converted);
            } elseif (preg_match('/\s\</', $column)) {
                $this->where[$this->columns[$key] . ' <'] = $value;
            } elseif (preg_match('/\(/', $this->columns[$column]) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value))) {
                $this->having .= $column . ' = "' . $value . '" AND ';
            } elseif (preg_match('/\(/', $this->columns[$column]) && is_numeric($value)) {
                $this->having .= $column . ' = ' . $value . ' AND ';
            } elseif (preg_match('/\(/', $this->columns[$column])) {
                $this->having .= $column . ' LIKE "%' . $value . '%" AND ';
            } elseif ((bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->where[$this->columns[$column]] = date($this->config['format']['date']['query'], $converted);
            } elseif (is_numeric($value)) {
                $this->where[$this->columns[$column]] = $value;
            } else if(!empty($this->columns[$column])) {
                $this->where[$this->columns[$column] . ' LIKE'] = '%' . $value . '%';
            }
        }
        if($this->filter instanceof IFilter) {
            $this->where = $this->filter->filter($this->where);
        }
        /** where and having */
        $where = (!empty($this->where)) ? ' WHERE ' : '';
        foreach ($this->where as $column => $value) {
            $column = (preg_match('/\./', $column) || is_numeric($column)) ? $column : '`' . $column . '`';
            if (is_numeric($column)) {
                $where .= ' ' . $value . ' AND ';
            } elseif (is_array($value) and preg_match('/\sIN|\sNOT/', strtoupper($column))) {
                $where .= ' ' . $column . ' (?) AND ';
                $this->arguments[] = $value;
            } elseif (!is_array($value) and preg_match('/(>|<|=|\sLIKE|\sIN|\sIS|\sNOT|\sNULL|\sNULL)/', strtoupper($column))) {
                $where .= ' ' . str_replace('`', '', $column) . ' ? AND ';
                $this->arguments[] = $value;
            } elseif (is_array($value) && empty($value)) {
                $where .= ' ' . $column . ' IS NULL AND ';
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
            $this->sum .= ' GROUP BY ' . $this->groups[$this->group] . ' ';
        }
        if(!empty($this->having)) {
            $this->query .= ' HAVING ' . $this->having . ' ';
            $this->sum .= ' HAVING ' . $this->having . ' ';
        }
        /** offset */
        if(empty($status = $this->getPost('Status'))) {
            $this->offset = $offset * $this->config['pagination'];
            $this->limit = $this->config['pagination'];
        } else {
            $this->offset = $offset;
            if(in_array($status, ['excel', 'export'])) {
                $this->limit = $this->export->speed($this->config['speed']);
            } else if('import' == $status) {
                $this->limit = $this->import->speed($this->config['speed']);
            } else {
                $this->limit = $this->facade->speed($this->config['speed']);
            }
        }
        /** sort */
        $this->sort = rtrim($this->sort, ', ');
        if(!empty($this->sort)) {
            $this->query .= ' ORDER BY ' . $this->sort . ' ';
        }
        return $this;
    }

    public function process(IProcess $facade): IBuilder {
        $this->facade = $facade;
        return $this;
    }

    private function sanitize(string $column): bool {
        return 1  == sizeof($joined = explode('.', (string) $column)) ||
            preg_match('/\(|\)/', $column) ||
            (empty($joins = $this->join + $this->leftJoin + $this->innerJoin)) ||
            (substr_count(implode('', $joins), $joined[0]) > 0);
    }

    public function translate(string $name, string $annotation): string {
        if ($this->presenter->getName() . ':' . $this->presenter->getAction() . ':' . $name != $label = $this->translatorModel->translate($this->presenter->getName() . ':' . $this->presenter->getAction() . ':' . $name)) {
        } elseif ($this->presenter->getName() . ':' . $name != $label = $this->translatorModel->translate($this->presenter->getName() . ':' . $name)) {
        } elseif ($annotation != $label = $this->translatorModel->translate($annotation)) {
        } elseif ($label = $this->translatorModel->translate($name)) {
        }
        return $label;
    }

}
