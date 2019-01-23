<?php

namespace Masala;

use Nette\Application\Responses\JsonResponse,
    Nette\Application\UI\Control,
    Nette\Caching\IStorage,
    Nette\Caching\Cache,
    Nette\Database\Context,
    Nette\InvalidStateException,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
class SqlBuilder extends Control implements IBuilderFactory {

    /** @var Cache */
    private $cache;

    /** @var array */
    private $columns = [];

    /** @var Context */
    private $database;

    /** @var string */
    protected const DATE = 'Y-m-d';

    /** @var array */
    private $defaults;

    /** @var int */
    private $group;

    /** @var array */
    private $groups = [];

    /** @var string */
    private $having = '';

    /** @var array */
    private $innerJoin = [];

    /** @var array */
    private $join = [];

    /** @var array */
    private $leftJoin = [];

    /** @var int */
    private $limit;

    /** @var int */
    private $offset = 0;

    /** @var array */
    private $order;

    /** @var array */
    private $props = [];

    /** @var string */
    private $sort = '';

    /** @var string */
    private $sum;

    /** @var array */
    private $where = [];

    /** @var string */
    private $table = '';

    /** @var ITranslator */
    private $translatorRepository;

    /** @var string */
    private $query;

    public function __construct(Context $database, IStorage $storage, ITranslator $translatorRepository) {
        $this->database = $database;
        $this->cache = new Cache($storage);
        $this->translatorRepository = $translatorRepository;
    }

    protected function build(): array {
        if(!empty($state = json_decode(file_get_contents('php://input'), true))) {
            dump($state); exit;
            dump('@todo: filtering, groups, sort, offset, limit'); exit;
        }
        $values = [];
        if(!empty($this->where)) {
            $this->query .= 'WHERE ';
        }
        foreach ($this->where as $column => $value) {
            if(isset($this->defaults[$column]) && is_array($this->defaults[$column])) {
                $this->query .= '`' . $column . '` = ? AND ';
                $values[] = $value;
            } else if(is_array($value) && preg_match('/\sIN|\sNOT/', strtoupper($column))) {
                $this->query .= '`' .$column . '` = (?) AND ';
                $values[] = $value;
            } elseif (!is_array($value) && preg_match('/(>|<|=|\sLIKE|\sIN|\sIS|\sNOT|\sNULL|\sNULL)/', strtoupper($column))) {
                $this->query .= '`' .$column . '` ? AND ';
                $values[] = $value;
            } elseif (is_array($value) && empty($value)) {
                $this->query .= '`' .$column . '` IS NULL AND ';
            } elseif (is_array($value)) {
                $this->query .= '`' . $column . '` IN (?) AND';
                $values[] = $value;
            } else if (preg_match('/\s\>\=/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->query .= '`' . $column . '` >= ? AND ';
                $values[] = date(self::DATE, $converted);
            } elseif (preg_match('/\s\>\=/', $column)) {
                $this->query .= '`' .$column . '` >= ? AND';
                $values[] = $value;
            } elseif (preg_match('/\s\<\=/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*\./', $value)) {
                $this->query .= '`' . $column . '` <= ? AND'; 
                $values[] = date(self::DATE, $converted);
            } elseif (preg_match('/\s\<\=/', $column)) {
                $this->query .= '`' . $column . '` <= ? AND';
                $values[] = $value;
            } elseif (preg_match('/\s\>/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->query .= '`' . $column . '` > ?';
                $values[] = date(self::DATE, $converted);
            } elseif (preg_match('/\s\>/', $column)) {
                $this->query .= '`' . $column . '` > ?';
                $values[] = $value;
            } elseif (preg_match('/\s\</', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->query .= '`' . $column . '` < ?';
                $values[] = date(self::DATE, $converted);
            } elseif (preg_match('/\s\</', $column)) {
                $this->query .= '`' . $column . '` < ?';
                $values[] = $value;
            } elseif (preg_match('/\(/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value))) {
                $this->having .= '`' . $column . '` = "' . $value . '" AND ';
            } elseif (preg_match('/\(/', $column) && is_numeric($value)) {
                $this->having .= '`' . $column . '` = ' . $value . ' AND ';
            } elseif (preg_match('/\(/', $column)) {
                $this->having .= '`' . $column . '` LIKE "%' . $value . '%" AND ';
            } elseif ((bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->query .= '`' . $column . '` = ? ';
                $values[] = date(self::DATE, $converted);
            } elseif (is_numeric($value)) {
                $this->query .= '`' .$column . '` = ? ';
                $values[] = $value;
            } else if(!empty($value)) {
                $this->query .= '`' . $column . '` LIKE = %' . $value . '%';
            }
        }
        $this->query = rtrim($this->query, 'AND ');
        if(isset($this->groups[$this->group])) {
            $this->query .= ' GROUP BY ' . $this->groups[$this->group] . ' ';
            $this->sum .= ' GROUP BY ' . $this->groups[$this->group] . ' ';
        }
        if(!empty($this->having = rtrim($this->having, 'AND '))) {
            $this->query .= ' HAVING ' . $this->having . ' ';
        }
        if(!empty($this->sort = rtrim($this->sort, ', '))) {
            $this->query .= ' ORDER BY ' . $this->sort . ' ';
        }
        $values[] = $this->limit;
        $values[] = $this->offset * $this->limit;
    }

    public function fetch(): IBuilderFactory {
        if(null == $this->defaults = $this->cache->load($key = get_class($this) . __FUNCTION__)) {
            $this->defaults = [];
            foreach($this->database->getConnection()->getSupplementalDriver()->getColumns($this->table) as $driver) {
                if(preg_match('/enum\(/', $driver['vendor']['Type'])) {
                    $this->defaults[$driver['name']] = explode(',', preg_replace('/(.*)\(|\)|\'/', '', $driver['vendor']['Type'])); 
                }
            }
            $this->cache->save($key, $this->defaults);
        }
        $this->query = 'SELECT ';
        if(empty($this->columns)) {
            $this->query .= '* ';
        }
        foreach ($this->columns as $column => $alias) {
            if(!preg_match('/\.|\s| |\(|\)/', trim($column))) {
                $this->query .= $this->table . '.' . $column . ', ';
            } else {
                $this->query .= $column . ' AS `' . $alias . '`, ';
            }
        }
        $this->query = rtrim($this->query, ', ') .  ' FROM ' . $this->table . ' ';
        foreach ($this->join as $join) {
            $this->query .= 'JOIN ' . $join . ' ';
        }
        foreach ($this->leftJoin as $join) {
            $this->query .= 'LEFT JOIN ' . $join . ' ';
        }
        foreach ($this->innerJoin as $join) {
            $this->query .= 'INNER JOIN ' . $join . ' ';
        }
        return $this;
    }

    public function handlePage(): void {
        $this->state();
        $state = json_decode(file_get_contents('php://input'), true);
        if(empty($this->where)) {
            $page = $this->database->query('SHOW TABLE STATUS WHERE Name = "' . $this->table . '"')->fetch()->Rows;
        } if(empty($this->groups)) {
            echo $this->query; exit;
            $page = intval($this->database->query($this->query, ...$arguments)->fetch()->sum);
        } else {
           echo $this->query; exit;
           $page = $this->database->query($this->query, ...$arguments)->getRowCount();
        }
        dump('todo'); exit;
        $this->presenter->sendResponse(new JsonResponse(['page' => $page]));
    }

    public function group(array $groups): IBuilderFactory {
        $this->groups = $groups;
        return $this;
    }

    public function having(string $having): IBuilderFactory {
        $this->having = $having;
        return $this;
    }

    public function handleState(): void {
        $this->build()->presenter->sendResponse(new JsonResponse(
                ['rows' => $this->database->query($this->query . ' LIMIT ? OFFSET ? ', ...$values)->fetchAll()]));
    }

    public function innerJoin(string $innerJoin): IBuilderFactory {
        $this->innerJoin[] = trim($innerJoin);
        return $this;
    }

    public function join(string $join): IBuilderFactory {
        $this->join[] = trim($join);
        return $this;
    }

    public function leftJoin(string $leftJoin): IBuilderFactory {
        $this->leftJoin[] = trim($leftJoin);
        return $this;
    }

    public function limit(int $limit): IBuilderFactory {
        $this->limit = $limit;
        return $this;
    }

    public function prop(string $key, $value): IBuilderFactory {
        $this->props[$key] = $value;
    }

    public function props(): array {
        $this->props['_state'] = $this->link('state');
        $this->props['_paginator'] = ['link' => $this->link('page'),
                                'next' => $this->translatorRepository->translate('next'),
                                'page' => ucfirst($this->translatorRepository->translate('page')),
                                'previous' => $this->translatorRepository->translate('previous')];
        return $this->props;
    }

    public function select(string $column, string $label, string $alias = null): IBuilderFactory {
        if(preg_match('/\sAS\s/', $alias)) {
            throw new InvalidStateException('Use intented alias as key in column ' . $column . '.');
        }
        $this->columns[$column] = empty($alias) ? $column : $alias;
        $this->props[$column] = ['label' => $label];
        return $this;
    }

    public function table(string $table): IBuilderFactory {
        $this->table = $table;
        return $this;
    }

    public function where(string $key, $column = null, bool $condition = null): IBuilderFactory {
        if((is_bool($condition) && false == $condition)) {
        } elseif (is_string($condition)) {
            $this->where[preg_replace('/(\s+\?.*|\?.*)/', '', $key)] = $column . $condition;
        } elseif (is_bool($condition) && true == $condition and is_array($column)) {
            $this->where[$key] = $column;
        } elseif (is_callable($column) && false != $value = $column()) {
            $this->where[$key] = $value;
        } elseif (is_array($column) && isset($this->columns[preg_replace('/(.*)\./', '', $key)])) {
            $this->where[$key] = $column;
        } elseif (is_string($column) || is_numeric($column) || is_array($column)) {
            $this->where[$key] = $column;
        } elseif (null === $column) {
            $this->where[] = $key;
        } elseif (null === $condition) {
            $this->where[] = $key;
        }
        return $this;
    }

    public function order(array $order): IBuilderFactory {
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

}