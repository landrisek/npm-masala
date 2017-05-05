<?php

namespace Masala;

use Nette\Caching\Cache,
    Nette\Caching\IStorage,
    Nette\Database\Context,
    Nette\Database\Table\ActiveRow,
    Nette\Database\Table\Selection,
    Nette\InvalidStateException;

/** @author Lubomir Andrisek */
final class RowBuilder implements IRowBuilder {

    /** @var Array */
    private $table;

    /** @var Array */
    private $columns = [];

    /** @var Array */
    private $defaults = [];

    /** @var Array */
    private $parameters = [];

    /** @var Array */
    private $config;

    /** @var string */
    private $action = 'default';

    /** @var string */
    private $title = 'edit item';

    /** @var string */
    private $spice;

    /** @var ActiveRow */
    private $data;

    /** @var Selection */
    private $resource;

    /** @var IEditFormService */
    private $service;

    /** @var Context */
    private $database;

    /** @var Cache */
    private $cache;

    public function __construct(Array $config, Context $database, IStorage $storage) {
        $this->config = $config;
        $this->database = $database;
        $this->cache = new Cache($storage);
    }

    /** getters */
    public function getAction() {
        return $this->action;
    }

    public function getConfig($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        return [];
    }

    public function getColumns() {
        return $this->columns;
    }

    public function getData() {
        return $this->data;
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

    private function getKey($method, $parameters) {
        return str_replace('\\', ':', get_class($this)) . ':' . $method . ':' . $parameters;
    }

    /** @return string */
    public function getTable() {
        return $this->table;
    }

    /** @return string */
    public function getTitle() {
        return $this->title;
    }

    public function getService() {
        return $this->service;
    }

    public function getParameters() {
        return $this->parameters;
    }

    public function getResource() {
        return $this->resource;
    }

    /** @return string */
    public function getSpice() {
        return $this->spice;
    }

    /** @return IBuilder */
    public function table($table) {
        $this->table = (string) $table;
        $this->resource = $this->database->table($table);
        return $this;
    }

    public function title($title) {
        $this->check();
        if (isset($this->$title)) {
            $this->title = $this->$title;
        }
        return $this;
    }

    public function redirect(Array $hidden) {
        foreach ($hidden as $key => $parameter) {
            if ('action' == $key) {
                $this->action = $parameter;
            } else {
                $this->parameters[$key] = $parameter;
            }
        }
        return $this;
    }

    public function where($key, $column, $condition = null) {
        if (is_bool($condition) and true == $condition) {
            $this->resource->where($key, $column);
        } elseif (null == $column and false != $column) {
            $this->resource->where($key);
        } elseif (is_bool($column) and true == $column) {
            $this->resource->where($key);
        } elseif (is_callable($column) and false != $value = $column()) {
            $this->resource->where($key, $value);
        } else {
            $this->resource->where($key, $column);
        }
        $this->defaults[$key] = $column;
        return $this;
    }

    public function process(IEditFormService $service) {
        $this->service = $service;
        return $this;
    }

    /** @return ActiveRow */
    public function check() {
        if (null == $this->data) {
            $this->data = $this->resource->fetch();
            /** select */
            foreach ($this->getDrivers() as $column) {
                if(isset($this->columns[$column['name']]) and is_string($this->columns[$column['name']]) and preg_match('/\sAS\s/', $this->columns[$column['name']])) {
                    throw new InvalidStateException('Use intented alias as key in column ' . $column . '.');
                } elseif (isset($this->columns[$column['name']]) and is_string($this->columns[$column['name']])) {
                    $column['vendor']['Comment'] .= '@' . trim(preg_replace('/(.*)\@/', '', $this->columns[$column['name']]));
                }
                $this->columns[$column['name']] = $column;
            }
            if (false != $this->data) {
                foreach ($this->data as $key => $row) {
                    if (property_exists($this, $key)) {
                        throw new InvalidStateException('Table has key "' . $key . '" already assigned as private property in ' . __CLASS__ . '.');
                    }
                    $this->$key = $row;
                }
            }
        }
        return $this->data;
    }

    public function setConfig($key, $method, $parameter) {
        $this->config[$key][$method] = $parameter;
    }

    public function setSpice($spice) {
        $this->spice = $spice;
        return $this;
    }

    public function setParameter($key, $parameter) {
        $this->parameters[$key] = (isset($this->parameters[$key])) ? $this->parameters[$key] : $parameter;
    }

    public function formSucceed(EditForm $form) {
        if (is_object($this->service)) {
            return $this->service->formSucceed($form);
        }
        return $form->getValues();
    }

    public function afterAttached(EditForm $form) {
        if (is_object($this->service)) {
            $this->service->afterAttached($form);
        }
    }

    public function beforeAttached(EditForm $form) {
        if (null !== $this->resource and false == $this->data = $this->check()) {
            foreach ($this->columns as $row) {
                if (is_array($row)) {
                    $rowName = $row['name'];
                    $this->$rowName = isset($this->defaults[$row['name']]) ? $this->defaults[$row['name']] : $row['default'];
                }
            }
        }
        if (is_object($this->service)) {
            $this->service->beforeAttached($form);
        }
    }

    public function afterSucceeded(EditForm $form) {
        if (is_object($this->service)) {
            return $this->service->afterSucceeded($form);
        }
    }

    public function beforeSucceeded(EditForm $form) {
        if (is_object($this->service)) {
            return $this->service->beforeSucceeded($form);
        } else {
            return $form->getValues();
        }
    }

    /** update */
    public function flush() {
        $hash = strtolower(__NAMESPACE__) . ':' . $this->spice;
        $this->cache->clean([Cache::ALL => [$hash]]);
        return $this;
    }
    
    public function update(Array $data) {
        return $this->database->table($this->table)
            ->where($this->resource->getPrimary(), $this->data->getPrimary())
            ->update($data);
    }

    /** insert */
    public function add(Array $data) {
        $id = $this->resource->insert($data);
        $this->parameters['id'] = (isset($this->parameters['id'])) ? $this->parameters['id'] : $id;
        return $id;
    }

    /** delete */
    public function delete() {
        return $this->resource->where($this->resource->getPrimary(), $this->data->getPrimary())
                        ->delete();
    }

}
