<?php

namespace Masala;

use Nette\Application\Responses\JsonResponse;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\InvalidStateException;
use Nette\Application\UI\Presenter;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Http\IRequest;
use Nette\Localization\ITranslator;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Writer_Excel2007;
use stdClass;

/** @author Lubomir Andrisek */
class SqlController extends Presenter implements IController {

    /** @var array */
    protected $arguments = [];

    /** @var Cache */
    private $cache;

    /** @var array */
    private $columns = [];

    /** @var Context */
    protected $database;

    /** @var string */
    private $dir;

    /** @var string */
    protected const DATE = 'Y-m-d';

    /** @var array */
    private $groups = [];

    /** @var string */
    protected $folder = '';

    /** @var string */
    private $having = '';

    /** @var string */
    protected $id;

    /** @var array */
    private $innerJoin = [];

    /** @var array */
    private $join = [];

    /** @var array */
    private $leftJoin = [];

    /** @var int */
    protected $limit = 20;

    /** @var string */
    private $link;

    /** @var array */
    private $order = [];

    /** @var string */
    protected $path  = '';

    /** @var array */
    protected $props = [];

    /** @var string */
    protected const PORT = '8081';

    /** @var string */
    protected const PUBLIC = '/assets/src/';

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

    /** @var ITranslator @inject */
    public $translatorRepository;

    /** @var array */
    private $where = [];

    /** @var string */
    protected $update;

    /** @var string */
    protected $query;

    public function __construct(Container $container, Context $database, IRequest $request, IStorage $storage) {
        $this->cache = new Cache($storage);
        $this->database = $database;
        $this->dir = $container->parameters['wwwDir'] . self::PUBLIC;
        $this->link = rtrim($request->getUrl()->getBaseUrl(), '/') . ':' . self::PORT;
    }

    public function actionExport(): void {
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
        $this->sendResponse(new JsonResponse($this->state));
    }

    public function actionPage(string $key): void {
        if(empty($this->where)) {
            $sum = $this->database->query('SHOW TABLE STATUS WHERE Name = "' . $this->table . '"')->fetch()->Rows;
        } if(empty($this->groups)) {
            $sum = intval($this->database->query(preg_replace('/SELECT(.*)FROM/', 'SELECT COUNT(*) AS sum FROM', $this->sum['query']), ...$this->sum['arguments'])->fetch()->sum);
        } else {
            $sum = $this->database->query(preg_replace('/SELECT(.*)FROM/', 'SELECT COUNT(*) AS sum FROM', $this->sum['query']), ...$this->sum['arguments'])->getRowCount();
        }
        $this->state->Paginator->Last = ceil($sum / $this->limit);
        $this->state->Paginator->Sum = $sum;
        $this->sendResponse(new JsonResponse($this->state));
    }

    public function actionState(): void {
        $this->state->Rows = (object) $this->database->query($this->query, ...$this->arguments)->fetchAll();
        $this->sendResponse(new JsonResponse($this->state));
    }

    protected function beforeRender(): void {
        parent::beforeRender();
        $this->template->id = $this->id;
        $this->template->link = $this->link . '/' . strtolower(preg_replace('/\:/', '/', $this->getName()));
        if(null == $src = $this->cache->load($key = get_class($this) . ':' . __FUNCTION__)) {
            foreach(scandir($this->dir) as $file) {
                $src[preg_replace('/\.(.*)/', '', $file)] = $file;
            }
            $this->cache->save($key, $src);
        }
        if(isset($src[$this->template->id])) {
            $this->template->src = self::PUBLIC . $src[$this->template->id];
        }
    }

    protected function column(string $column, string $label, string $link = null): IController {
        $this->props[$column] = ['id' => $column, 'label' =>  $this->translatorRepository->translate($label), 'link' => empty($link) ? $this->link('state') : $link];
        return $this;
    }

    protected function group(array $groups): IController {
        $this->groups = $groups;
        return $this;
    }

    protected function having(string $having): IController {
        $this->having = $having;
        return $this;
    }

    protected function innerJoin(string $innerJoin): IController {
        $this->innerJoin[] = trim($innerJoin);
        return $this;
    }

    protected function join(string $join): IController {
        $this->join[] = trim($join);
        return $this;
    }

    protected function leftJoin(string $leftJoin): IController {
        $this->leftJoin[] = trim($leftJoin);
        $this->leftJoin[] = trim($leftJoin);
        return $this;
    }

    protected function limit(int $limit): IController {
        $this->limit = $limit;
        return $this;
    }

    protected function mount(string $label, string $link): IController {
        $this->props['componentDidMount'][$link] = $this->link($link);
        $this->props[$link] = ['label' => $this->translatorRepository->translate($label),
              'Paginator' => ['link' => $this->link('state', $this->id),
                              'next' => $this->translatorRepository->translate('next'),
                              'page' => ucfirst($this->translatorRepository->translate('page')),
                              'previous' => $this->translatorRepository->translate('previous'),
                              'sum' => $this->translatorRepository->translate('total')]];
        return $this;
    }

    protected function order(array $order): IController {
        $this->order = $order;
        return $this;
    }

    protected function prop(string $key, array $value): IController {
        $this->props[$key] = $value;
        $this->props[$key]['id'] = $key;
        return $this;
    }

    protected function row(stdClass $row): array {
        return (array) $row;
    }

    protected function startup(): void {
        parent::startup();
        $this->id = preg_replace('/(.*)\:/', '', $this->getName()) . 'Presenter';
        $this->props['download'] = ['label' => $this->translatorRepository->translate('Click here to download your file.')];
        $this->props['export'] = ['label' => $this->translatorRepository->translate('export'), 'link' => $this->link('export')];
        $this->props['Paginator'] = ['link' => $this->link('state'),
            'next' => $this->translatorRepository->translate('next'),
            'page' => ucfirst($this->translatorRepository->translate('page')),
            'previous' => $this->translatorRepository->translate('previous'),
            'sum' => $this->translatorRepository->translate('total')];
        $this->props['page'] = $this->link('page', $this->id);
        $this->props['submit'] = ['id' => $this->id, 'label' => $this->translatorRepository->translate('filter data'), 'link' => $this->link('state')];
    }

    protected function select(string $column, string $alias = null): IController {
        if(preg_match('/\sAS\s/', $alias)) {
            throw new InvalidStateException('Use intented alias as key in column ' . $column . '.');
        }
        $this->columns[!empty($alias) ? $alias : $column] = $column;
        return $this;
    }

    protected function set(string $set): IController {
        $this->update = str_replace(self::SET, $set, $this->update);
        $arguments = array_slice($this->arguments, 0, sizeof($this->arguments) - 2);
        $this->database->query($this->update, ...$arguments);
        return $this;
    }

    protected function state(): IController {
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

    protected function table(string $table): IController {
        $this->table = $table;
        return $this;
    }

    protected function where(string $key, $column = null, bool $condition = null): IController {
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

    protected function unmount(string $link): IController {
        unset($this->props['componentDidMount'][$link]);
        return $this;

    }

}
