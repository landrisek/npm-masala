<?php

namespace Masala;

use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Control;
use Nette\Database\Context;
use Nette\InvalidStateException;
use Nette\Localization\ITranslator;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Writer_Excel2007;
use stdClass;

/** @author Lubomir Andrisek */
class SqlBuilder extends Control implements IBuilderFactory {

    /** @var array */
    protected $arguments = [];

    /** @var array */
    private $columns = [];

    /** @var Context */
    protected $database;

    /** @var string */
    protected const DATE = 'Y-m-d';

    /** @var int */
    private $group;

    /** @var array */
    private $groups = [];

    /** @var string */
    protected $folder = '';

    /** @var string */
    private $having = '';

    /** @var array */
    private $innerJoin = [];

    /** @var array */
    private $join = [];

    /** @var array */
    private $leftJoin = [];

    /** @var int */
    protected $limit = 20;

    /** @var array */
    private $order = [];

    /** @var string */
    protected $path  = '';

    /** @var array */
    private $props = [];

    /** @var string */
    private const SET = '<%SET%>';

    /** @var string */
    private $sort = '';

    /** @var array */
    protected $state;

    /** @var array */
    private $sum;

    /** @var string */
    private $table = '';

    /** @var ITranslator */
    protected $translatorRepository;

    /** @var array */
    private $where = [];

    /** @var string */
    protected $update;

    /** @var string */
    protected $query;

    public function __construct(Context $database, ITranslator $translatorRepository) {
        $this->database = $database;
        $this->translatorRepository = $translatorRepository;
    }

    public function clone(): IBuilderFactory {
        return clone $this;
    }

    protected function fetch(): IBuilderFactory {
        $this->query = 'SELECT ';
        if(empty($this->columns)) {
            $this->query .= '* ';
        }
        $this->update .= ' ';
        foreach($this->columns as $alias => $column) {
            if(preg_match('/\.|\s| |\(|\)/', trim($column))) {
                $this->query .= $column . ' AS `' . $alias . '`, ';
            } else {
                $this->query .= $this->table . '.' . $column . ', ';
            }
        }
        $this->query = rtrim($this->query, ', ') .  ' FROM ' . $this->table . ' ';
        $this->update = 'UPDATE ' . $this->table . ' SET ' . self::SET .' ';
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

    protected function group(array $groups): IBuilderFactory {
        $this->groups = $groups;
        return $this;
    }

    public function handleExport(): void {
        $this->state()->state->Rows = (object) $this->database->query($this->query, ...$this->arguments)->fetchAll();
        if(1 == $this->state->Paginator->Current) {
            $excel = new PHPExcel();
            $title = 'export';
            $properties = $excel->getProperties();
            $properties->setTitle($title);
            $properties->setSubject($title);
            $properties->setDescription($title);
            $excel->setActiveSheetIndex(0);
            $sheet = $excel->getActiveSheet();
            $sheet->setTitle(substr($title, 0, 31));
            $letter = 'a';
            foreach($this->row(reset($this->state->Rows)) as $column => $value) {
                $sheet->setCellValue($letter . '1', $column);
                $sheet->getColumnDimension($letter)->setAutoSize(true);
                $sheet->getStyle($letter . '1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $letter++;
            }
            $writer = new PHPExcel_Writer_Excel2007($excel);
            $this->state->file = $this->file();
            $writer->save($this->folder . $this->state->file);
            unset($this->state->download);
        } else {
            $excel = PHPExcel_IOFactory::load($this->folder . $this->state->file);
            $excel->setActiveSheetIndex(0);
        }
        $last = $excel->getActiveSheet()->getHighestRow();
        foreach($this->state->Rows as $key => $row) {
            $last++;
            $letter = 'a';
            foreach($this->row($row) as $cell) {
                $excel->getActiveSheet()->SetCellValue($letter++ . $last, $cell);
            }
        }
        $writer = new PHPExcel_Writer_Excel2007($excel);
        $writer->save($this->folder . $this->state->file);
        if($this->state->Paginator->Current == $this->state->Paginator->Last) {
            $this->state->download = $this->path . $this->state->file;
        }
        $this->state->Paginator->Current++;
        $this->presenter->sendResponse(new JsonResponse($this->state));
    }

    public function handlePage(): void {
        if(empty($this->state()->where)) {
            $sum = $this->database->query('SHOW TABLE STATUS WHERE Name = "' . $this->table . '"')->fetch()->Rows;
        } if(empty($this->groups)) {
            $sum = intval($this->database->query(preg_replace('/SELECT(.*)FROM/', 'SELECT COUNT(*) AS sum FROM', $this->sum['query']), ...$this->sum['arguments'])->fetch()->sum);
        } else {
           $sum = $this->database->query(preg_replace('/SELECT(.*)FROM/', 'SELECT COUNT(*) AS sum FROM', $this->sum['query']), ...$this->sum['arguments'])->getRowCount();
        }
        $this->state->Paginator->Last = ceil($sum / $this->limit);
        $this->state->Paginator->Sum = $sum;
        $this->presenter->sendResponse(new JsonResponse($this->state));
    }

    public function handleState(): void {
        $this->state()->state->Rows = (object) $this->database->query($this->query, ...$this->arguments)->fetchAll();
        $this->presenter->sendResponse(new JsonResponse($this->state));
    }

    protected function having(string $having): IBuilderFactory {
        $this->having = $having;
        return $this;
    }

    protected function innerJoin(string $innerJoin): IBuilderFactory {
        $this->innerJoin[] = trim($innerJoin);
        return $this;
    }

    protected function join(string $join): IBuilderFactory {
        $this->join[] = trim($join);
        return $this;
    }

    protected function leftJoin(string $leftJoin): IBuilderFactory {
        $this->leftJoin[] = trim($leftJoin);
        return $this;
    }

    protected function limit(int $limit): IBuilderFactory {
        $this->limit = $limit;
        return $this;
    }

    protected function order(array $order): IBuilderFactory {
        $this->order = $order;
        return $this;
    }

    protected function prop(string $key, $value): IBuilderFactory {
        $this->props[$key] = $value;
        return $this;
    }

    public function props(): array {
        $this->props['download'] = ['label' => $this->translatorRepository->translate('Click here to download your file.')];
        $this->props['export'] = ['label' => $this->translatorRepository->translate('export'),
                                 'link' => $this->link('export')];
        $this->props['Paginator'] = ['link' => $this->link('state'),
                                'next' => $this->translatorRepository->translate('next'),
                                'page' => ucfirst($this->translatorRepository->translate('page')),
                                'previous' => $this->translatorRepository->translate('previous'),
                                'sum' => $this->translatorRepository->translate('total')];
        $this->props['page'] = $this->link('page');
        $this->props['state'] = ['label' => $this->translatorRepository->translate('filter data'),
                                 'link' => $this->link('state')];
        foreach($this->columns as $column) {
            $this->props[$column]['link'] = $this->link('state');
        }
        return $this->props;
    }

    protected function row(stdClass $row): array {
        return (array) $row;
    }

    protected function select(string $column, string $label, string $alias = null): IBuilderFactory {
        if(preg_match('/\sAS\s/', $alias)) {
            throw new InvalidStateException('Use intented alias as key in column ' . $column . '.');
        }
        $this->columns[!empty($alias) ? $alias : $column] = $column;
        $this->props[!empty($alias) ? $alias : $column] = ['label' => $label];
        return $this;
    }

    protected function set(string $set): IBuilderFactory {
        $this->update = str_replace(self::SET, $set, $this->update);
        $arguments = array_slice($this->arguments, 0, sizeof($this->arguments) - 2);
        $this->database->query($this->update, ...$arguments);
        return $this;
    }

    protected function state(): IBuilderFactory {
        $this->state = json_decode(file_get_contents('php://input'), false);
        foreach($this->state->Order as $alias => $column) {
            if(isset($this->columns[$alias]) && !empty($column)) {
                $this->sort .= ' ' . $alias . ' ' . ltrim($column, '-') . ', ';
            }
        }
        foreach($this->state->Where as $alias => $value) {
            if(isset($this->columns[preg_replace('/(>|<|=|\s)/', '', $alias)])) {
                $this->where($alias, $value, !empty($value));
            }
        }
        if(empty($this->sort)) {
            foreach($this->order as $alias => $value) {
                if(!in_array($value, ['asc', 'desc', 'ASC', 'DESC'])) {
                    throw new InvalidStateException('Order value can be only DESC or ASC.');
                } else {
                    $this->sort .= ' ' . $alias . ' ' . $value . ', ';
                }
            }
        }
        if(!empty($this->where)) {
            $this->update .= 'WHERE ';
            $this->query .= 'WHERE ';
        }
        foreach($this->where as $column => $value) {
            if(is_array($value) && preg_match('/\sIN|\sNOT/', strtoupper($column))) {
                $this->update .= '`' .$column . '` = (?) AND ';
                $this->query .= '`' .$column . '` = (?) AND ';
                $this->arguments[] = $value;
            } else if (preg_match('/(>|<|=)/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->update .= $column . ' ? AND ';
                $this->query .=  $column . ' ? AND ';
                $this->arguments[] = date(self::DATE, $converted);
            } elseif (preg_match('/(>|<|=|\!=)/', $column)) {
                $this->update .= $column . ' ? AND ';
                $this->query .= $column . ' ? AND ';
                $this->arguments[] = $value;
            } elseif (preg_match('/\(/', $column) && (bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value))) {
                $this->having .=  $column . ' = "' . $value . '" AND ';
            } elseif (!is_array($value) && preg_match('/(>|<|=|\sLIKE|\sIN|\sIS|\sNOT|\sNULL|\sNULL)/', strtoupper($column))) {
                $this->update .= $column . ' ? AND ';
                $this->query .= $column . ' ? AND ';
                $this->arguments[] = $value;
            } elseif (is_array($value) && empty($value)) {
                $this->update .= '`' .$column . '` IS NULL AND ';
                $this->query .= '`' .$column . '` IS NULL AND ';
            } elseif (is_array($value)) {
                $this->update .= $column . ' IN (?) AND ';
                $this->query .= $column . ' IN (?) AND ';
                $this->arguments[] = $value;
            } elseif (preg_match('/\(/', $column) && is_numeric($value)) {
                $this->having .= $column . ' = ' . $value . ' AND ';
            } elseif (preg_match('/\(/', $column)) {
                $this->having .= $column . ' LIKE "%' . $value . '%" AND ';
            } elseif ((bool) strpbrk($value, 1234567890) && is_int($converted = strtotime($value)) && preg_match('/\-|.*\..*/', $value)) {
                $this->update .= $column . ' = ? AND ';
                $this->query .= $column . ' = ? AND ';
                $this->arguments[] = date(self::DATE, $converted);
            } elseif (is_numeric($value)) {
                $this->update .= '`' . $column . '` = ? AND ';
                $this->query .= '`' . $column . '` = ? AND ';
                $this->arguments[] = $value;
            } else if(is_numeric($column) && !empty($value)) {
                $this->update .= ' ' . $value . ' AND ';
                $this->query .= ' ' . $value . ' AND ';
            } else if(!isset($this->state->Where->$column)) {
                $this->update .= $column . ' = ? AND ';
                $this->query .= $column . ' = ? AND ';
                $this->arguments[] = $value;
            } else if(!empty($value)) {
                $this->update .= $column . ' LIKE ? AND ';
                $this->query .= $column . ' LIKE ? AND ';
                $this->arguments[] = '%' . $value . '%';
            }
        }
        $this->update = rtrim($this->update, 'AND ');
        $this->query = rtrim($this->query, 'AND ');
        if(isset($this->groups[$this->state->Group])) {
            $this->query .= ' GROUP BY ' . $this->groups[$this->state->Group] . ' ';
        }
        if(!empty($this->having = rtrim($this->having, 'AND '))) {
            $this->query .= ' HAVING ' . $this->having . ' ';
        }
        if(!empty($this->sort = rtrim($this->sort, ', '))) {
            $this->query .= ' ORDER BY ' . $this->sort . ' ';
        }
        $this->sum = ['arguments' => $this->arguments, 'query' => $this->query];
        $this->arguments[] = $this->limit;
        $this->arguments[] = ($this->state->Paginator->Current - 1) * $this->limit;
        $this->query .= ' LIMIT ? OFFSET ? ';
        return $this;
    }

    protected function table(string $table): IBuilderFactory {
        $this->table = $table;
        return $this;
    }

    protected function where(string $key, $column = null, bool $condition = null): IBuilderFactory {
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

}
