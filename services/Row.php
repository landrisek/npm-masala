<?php

namespace Masala;

use Nette\Caching\Cache,
    Nette\Caching\IStorage,
    Nette\Database\Context,
    Nette\Database\Table\ActiveRow,
    Nette\Database\Table\Selection,
    Nette\InvalidStateException;

/** @author Lubomir Andrisek */
final class Row implements IRow {

    /** @var string */
    private $columns;

    /** @var Cache */
    private $rowCache;

    /** @var array */
    private $rowConfig;

    /** @var array */
    private $rowColumns = [];

    /** @var ActiveRow */
    private $rowData;

    /** @var Context */
    private $rowDatabase;

    /** @var array */
    private $rowDefaults = [];

    /** @var string */
    private $rowTitle = 'edit item';

    /** @var Selection */
    private $rowResource;

    /** @var IEdit */
    private $rowService;

    /** @var string */
    private $rowTable;

    public function __construct(array $config, Context $database, IStorage $storage) {
        $this->rowConfig = $config;
        $this->rowDatabase = $database;
        $this->rowCache = new Cache($storage);
    }

    /** @return void */
    public function after(IReactFormFactory $form) {
        if($this->rowService instanceof IEdit) {
            $this->rowService->after($form, $this);
        }
    }

    /** @return void */
    public function before(IReactFormFactory $form) {
        if (null !== $this->rowResource and false == $this->rowData = $this->check()) {
            foreach ($this->rowColumns as $row) {
                if (is_array($row)) {
                    $rowName = $row['name'];
                    $this->$rowName = isset($this->rowDefaults[$row['name']]) ? $this->rowDefaults[$row['name']] : $row['default'];
                }
            }
        }
    }

    /** @return ActiveRow */
    public function check() {
        if (null == $this->rowData) {
            if(!empty($this->columns)) {
                $this->rowData = $this->rowResource->select($this->columns)->fetch();
            } else {
                $this->rowData = $this->rowResource->fetch();
            }
            /** select */
            foreach ($this->getDrivers() as $column) {
                if(isset($this->rowColumns[$column['name']]) and is_string($this->rowColumns[$column['name']]) and preg_match('/\sAS\s/', $this->rowColumns[$column['name']])) {
                    throw new InvalidStateException('Use intented alias as key in column ' . $column . '.');
                } elseif (isset($this->rowColumns[$column['name']]) and is_string($this->rowColumns[$column['name']])) {
                    $column['vendor']['Comment'] .= '@' . trim(preg_replace('/(.*)\@/', '', $this->rowColumns[$column['name']]));
                }
                $this->rowColumns[$column['name']] = $column;
            }
            if (false != $this->rowData) {
                foreach ($this->rowData as $key => $row) {
                    if (property_exists($this, $key)) {
                        throw new InvalidStateException('Table ' . $this->rowTable . ' has key "' . $key . '" already assigned as private property in ' . __CLASS__ . '.');
                    }
                    $this->$key = $row;
                }
            }
        }
        return $this->rowData;
    }

    /** @return array */
    public function getConfig($key) {
        if (isset($this->rowConfig[$key])) {
            return $this->rowConfig[$key];
        }
        return [];
    }

    /** @return array */
    public function getColumns() {
        return $this->rowColumns;
    }

    /** @return array */
    public function getData() {
        return $this->rowData;
    }

    /** @return array */
    public function getDefaults() {
        return $this->rowDefaults;
    }

    /** @return array */
    public function getDrivers() {
        $driverId = $this->getKey('attached', $this->rowTable);
        if (null == $drivers = $this->rowCache->load($driverId)) {
            $this->rowCache->save($driverId, $drivers = $this->rowDatabase->getConnection()
                ->getSupplementalDriver()
                ->getColumns($this->rowTable));
        }
        return $drivers;
    }

    /** @return string */
    private function getKey($method, $parameters) {
        return str_replace('\\', ':', get_class($this)) . ':' . $method . ':' . $parameters;
    }

    /** @return string */
    public function getTable() {
        return $this->rowTable;
    }

    /** @return string */
    public function getTitle() {
        return $this->rowTitle;
    }

    public function getService() {
        return $this->rowService;
    }

    public function getResource() {
        return $this->rowResource;
    }

    /** @return IRow */
    public function table($table) {
        $this->rowTable = (string) $table;
        $this->rowResource = $this->rowDatabase->table($table);
        return $this;
    }

    /** @return IRow */
    public function title($title) {
        $this->check();
        if (isset($this->$title)) {
            $this->rowTitle = $this->$title;
        }
        return $this;
    }

    /** @return IRow */
    public function where($key, $column, $condition = null) {
        if(is_bool($condition) and false == $condition) {
        } elseif (null == $column and false != $column) {
            $this->rowResource->where($key);
        } elseif (is_bool($column) and true == $column) {
            $this->rowResource->where($key);
        } elseif (is_callable($column) and false != $value = $column()) {
            $this->rowResource->where($key, $value);
        } else {
            $this->rowResource->where($key, $column);
        }
        $this->rowDefaults[$key] = $column;
        return $this;
    }

    /** @return IRow */
    public function process(IEdit $service) {
        $this->rowService = $service;
        return $this;
    }

    /** @return IRow */
    public function select($columns) {
        $this->columns = $columns;
        return $this;
    }

    /** @return int */
    public function update(array $data) {
        if($this->rowService instanceof IEdit) {
            $data = $this->rowService->submit($data);
        }
        $primary = $data['primary'];
        unset($data['primary']);
        foreach($primary as $column => $value) {
            /** keep given order in javascript */
            if(is_numeric($trim = preg_replace('/(\_)/', '', $value))) {
                $this->rowResource->where($column, $trim);
            } else {
                $this->rowResource->where($column, $value);         
            }
        }
        return $this->rowResource->update($data);
    }

    /** @return ActiveRow */
    public function add(array $data) {
        return $this->rowResource->insert($data);
    }

    /** @return int */
    public function delete() {
        return $this->rowResource->where($this->rowResource->getPrimary(), $this->rowData->getPrimary())
                        ->delete();
    }

}
