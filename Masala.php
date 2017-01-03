<?php

namespace Masala;

use Joseki\Application\Responses\InvalidStateException;
use Nette\Application\Responses\JsonResponse,
    Nette\Application\Responses\TextResponse,
    Nette\Application\UI\Control,
    Nette\Application\UI\Presenter,
    Nette\Localization\ITranslator,
    Nette\Http\IRequest,
    Nette\Utils\Paginator;

/** @author Lubomir Andrisek */
final class Masala extends Control implements IMasalaFactory {

    /** @var ITranslator */
    private $translatorModel;

    /** @var IImportFormFactory */
    private $importFormFactory;

    /** @var IFilterFormFactory */
    protected $filterFormFactory;

    /** @var IGridFormFactory */
    private $gridFormFactory;

    /** @var Paginator */
    private $paginator;

    /** @var IBuilder */
    private $grid;

    /** @var string */
    private $action;

    /** @var int */
    private $stop;

    /** @var Array */
    private $columns = [];

    /** @var Array */
    private $header;

    /** @var Array */
    private $parameters;

    /** @var Array */
    public $queries;

    /** @var string */
    private $status;

    /** @var string */
    private $port;

    /** @var string */
    private $root;

    public function __construct(Array $masala, ITranslator $translatorModel, IImportFormFactory $importFormFactory, IFilterFormFactory $filterFormFactory, IGridFormFactory $gridFormFactory, Paginator $paginator, IRequest $request) {
        parent::__construct(null, null);
        $this->translatorModel = $translatorModel;
        $this->importFormFactory = $importFormFactory;
        $this->filterFormFactory = $filterFormFactory;
        $this->gridFormFactory = $gridFormFactory;
        $this->paginator = $paginator->setItemsPerPage($masala['pagination']);
        $cookies = $request->getCookies();
        $this->queries = $request->getUrl()->getQueryParameters();
        $this->port = isset($masala['port']) ? $request->getUrl()->getHost() . ':' . $masala['port'] : false;
        $keys = preg_grep('/' . lcfirst(substr(__CLASS__, strrpos(__CLASS__, '\\') + 1)) . '\-(.*)/', array_keys($cookies));
        foreach ($keys as $key) {
            $this->queries[$key] = $cookies[$key];
        }
        $this->root = WWW_DIR . $masala['root'];
    }

    /** setters */
    public function setGrid(IBuilder $grid) {
        $this->grid = $grid;
        return $this;
    }

    public function setColumn($name, $label) {
        return $this->columns[] = new Column($name, $label, $this->grid);
    }

    public function setColumns() {
        $presenter = $this->getPresenter();
        foreach ($this->grid->getColumns() as $column => $subquery) {
            if ($presenter->name . ':' . $presenter->action . ':' . $column != $label = $this->translatorModel->translate($presenter->name . ':' . $presenter->action . ':' . $column)) {
                
            } elseif ($presenter->name . ':' . $column != $label = $this->translatorModel->translate($presenter->name . ':' . $column)) {
                
            } else {
                $label = $this->translatorModel->translate($column);
            }
            $this->setColumn($column, ucfirst($label));
        }
    }

    /** getters */
    public function getGrid() {
        return $this->grid;
    }

    private function getHeader($row, $header) {
        foreach ($row as $key => $column) {
            mb_detect_encoding($column, 'UTF-8', true) == false ? $column = trim(iconv('windows-1250', 'utf-8', $column)) : $column = trim($column);
            if (isset($header->$column)) {
                foreach ($header->$column as $feedColumn => $feedValue) {
                    if (!isset($header->$feedColumn) and is_numeric($feedValue)) {
                        $this->header[$feedColumn] = [$feedValue => $key];
                    } elseif (!isset($header->$feedColumn) and is_bool($feedColumn)) {
                        $this->header[$feedColumn] = $key;
                    } elseif (!isset($header->$feedColumn) and 'break' == $feedValue and ! isset($this->header[$feedColumn])) {
                        $this->header[$feedColumn] = $key;
                    } elseif (!isset($header->$feedColumn) and 'break' == $feedValue and isset($this->header[$feedColumn])) {
                        
                    } elseif (is_array($header->$feedColumn)) {
                        is_numeric($feedValue) ? $this->header[$feedColumn][$feedValue] = $key : $this->header[$feedColumn][] = $key;
                    } elseif (is_numeric($feedValue)) {
                        $this->header[$feedColumn] = [0 => $key, $feedValue => $header->$feedColumn];
                    }
                }
            }
        }
        if (!empty($this->header)) {
            foreach (json_decode($this->grid->getImport()->getSetting()->validator) as $validator => $value) {
                if (!isset($this->header[$validator])) {
                    return $this->header = $this->translatorModel->translate('Header does not contains validator') . ' ' . $this->translatorModel->translate($validator) . '.';
                }
            }
        }
    }

    public function getQuery($id) {
        if (isset($this->queries[$this->getName() . '-' . $id])) {
            return preg_replace('/\+/', ' ', $this->queries[$this->getName() . '-' . $id]);
        } elseif ('page' == $id) {
            return 1;
        } elseif ('filter' == $id) {
            return [];
        } elseif ('do' == $id and isset($this->queries[$id])) {
            return preg_replace('/\-(.*)/', '', preg_replace('/' . lcfirst($this->getName()) . '\-/', '', $this->queries[$id]));
        }
    }

    /** @return IMasalaFactory */
    public function create() {
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof Presenter and null != $this->grid->getTable()) {
            /** paginator */
            $this->paginator->setPage($this->getQuery('page'));
            /** IBuilder */
            $this->action = $presenter->action;
            $this->grid->attached($this);
            /** columns */
            $this->setColumns($presenter);
        }
    }

    /** signal methods */
    public function handleCsv($file, $divider) {
        $this->grid->log('csv');
        $this->status = 'import';
        $setting = $this->grid->getImport()->getSetting();
        $folder = $this->presenter->getContext()->parameters['tempDir'] . '/' . $this->getName() . '/' . str_replace(':', '', $this->getPresenter()->getName() . $this->getPresenter()->getAction());
        $path = $folder . '/' . $file . '.csv';
        $handle = fopen($path, 'r');
        $this->header = [];
        $header = json_decode($setting->mapper);
        $i = 0;
        $key = $this->grid->getId($this->status);
        while (false !== ($row = fgets($handle, 10000))) {
            /** sanitize */
            preg_match_all('/\"(.*?)\"/', $row, $matches);
            $matches[1] = (isset($matches[1])) ? $matches[1] : [];
            foreach ($matches[1] as $match) {
                $row = str_replace('<?php', '', (str_replace($match, str_replace(',', '.', $match), $row)));
            }
            $row = explode($divider, str_replace('"', '', $row));
            if (is_string($this->header)) {
                $this->presenter->flashMessage($this->header);
                $this->presenter->redirect('this');
            } elseif (empty($this->header)) {
                $this->getHeader($row, $header);
            } elseif (!empty($this->header)) {
                $this->grid->update($key . ':' . $i, $row);
                $i++;
            }
        }
        fclose($handle);
        $this->stop = $i;
        $this->grid->getImport()->prepare($this);
    }

    public function handleDone() {
        $this->grid->log('done');
        $rows = !is_array($this->presenter->request->getPost('rows')) ? [] : $this->presenter->request->getPost('rows');
        $row = $this->presenter->request->getPost('row');
        $service = 'get' . ucfirst($row['status']);
        $this->grid->$service()->done($rows, $this->presenter);
        $response = new TextResponse($this->presenter->link('this', ['do'=>$this->getName() . '-message', $this->getName() . '-status'=>$row['status']]));
        return $this->presenter->sendResponse($response);
    }

    public function handleExport() {
        $this->status = 'export';
        $this->stop = $this->grid->filter()->getExport()->prepare($this);;
        $response = new JsonResponse(['run' => 0, 'stop' => $this->stop, 'status' => 'export']);
        return $this->presenter->sendResponse($response);
    }

    public function handleFilter() {
        $rows = $this->grid->filter()->getOffsets();
        $response = new TextResponse($rows);
        return $this->presenter->sendResponse($response);
    }

    public function handleImport($stop) {
        $response = new JsonResponse(['run' => 0,
            'stop' => intval($stop),
            'status' => 'import',
            'key' => $key = str_replace(':', '', $this->getPresenter()->getName()) . ':' . $this->getPresenter()->getAction() . ':' . $this->getName() . ':handleCsv:' . $this->presenter->getUser()->getId()]);
        return $this->presenter->sendResponse($response);
    }

    public function handleMessage($status) {
        $service = 'get' . ucfirst($status);
        $message = $this->grid->$service()->message($this);
        $this->presenter->flashMessage($this->translatorModel->translate(empty($message) ? 'Lengthy process was completed.' : $message));
        $this->presenter->redrawControl('flashes');
    }

    public function handlePaginate() {
        $post = $this->presenter->request->getPost('filters');
        $filters = is_array($post) ? $post : [];
        $order = $this->presenter->request->getPost('order');
        $sort = $this->presenter->request->getPost('sort');
        $sum = $this->grid->filter($filters, $order, $sort)->getSum();
        $total = ($sum > $this->paginator->getItemsPerPage()) ? intval(round($sum / $this->paginator->getItemsPerPage())) : 1;
        $response = new TextResponse($total);
        return $this->presenter->sendResponse($response);
    }

    public function handlePostpone() {
        return $this->status = null;
    }

    public function handlePrepare() {
        $this->grid->log('prepare');
        $this->status = (null == $this->status) ? 'service' : $this->status;
        $this->stop = $this->grid->filter()->getService()->prepare($this);
        $response = new JsonResponse(['run' => 0, 'stop' => $this->stop, 'status' => 'service']);
        return $this->presenter->sendResponse($response);
    }

    public function handleRedraw() {
        $primary = $this->presenter->request->getPost('primary');
        $row = $this->grid->where('id', $this->presenter->request->getPost('id'))
                ->filter()
                ->flush($primary);
        $response = new TextResponse($row);
        return $this->presenter->sendResponse($response);
    }

    public function handleRun() {
        $upload = $this->presenter->request->getPost('row');
        $headers = $this->presenter->request->getPost('header');
        $headers = is_array($headers) ? $headers : [];
        $offset = (array) $this->grid->filter()->getOffset($upload['run'], $upload['status']);
        $rows = !is_array($this->presenter->request->getPost('rows')) ? [] : $this->presenter->request->getPost('rows');
        if (intval(str_replace('M', '', ini_get('post_max_size')) * 1024 * 1024) <
                (mb_strlen(serialize($rows), '8bit') + mb_strlen(serialize($headers), '8bit') + mb_strlen(serialize($upload), '8bit'))
        ) {
            throw new InvalidStateException('Uploaded data in post is too large according to post_max_size');
        }
        $row = empty($headers) ? $offset : [];
        foreach ($headers as $headerId => $header) {
            if (is_array($header)) {
                foreach ($header as $valueId => $value) {
                    $row[$headerId][$valueId] = $offset[$value];
                }
            } else {
                $row[$headerId] = $offset[$header];
            }
        }
        $service = 'get' . ucfirst($this->status = $upload['status']);
        $setting = $this->grid->$service()->getSetting();
        $callbacks = is_object($setting) ? json_decode($setting->callback) : [];
        foreach ($callbacks as $callbackId => $callback) {
            $sanitize = preg_replace('/print|echo|exec|call|eval|mysql/', '', $callback);
            eval('function call($row) {' . $sanitize . '}');
            $row = call($row);
        }
        $this->grid->$service()->run($row, $rows, $this);
        /** $response = new TextResponse($this->presenter->payload->rows = json_encode($result)); */
        $upload['run'] = $upload['run'] + 1;
        $upload['offset'] = $offset;
        $response = new JsonResponse($upload);
        return $this->presenter->sendResponse($response);
    }

    public function handleStorage() {
        $response = new JsonResponse([]);
        return $this->presenter->sendResponse($response);
    }

    public function handleSubmit() {
        $values = [];
        foreach ($this->presenter->request->getPost() as $componentId => $component) {
            $values[$componentId] = $component;
        }
        $this->grid->getDialog()->submit($values);
        $this->grid->flush($values['primary']);
        $response = new JsonResponse($values);
        return $this->presenter->sendResponse($response);
    }

    /** render methods */
    public function render() {
        $this->template->settings = json_decode($this->presenter->getUser()->getIdentity()->__get('settings'));
        $this->template->columns = $this->columns;
        $this->template->grid = $this->grid;
        $this->template->port = $this->port;
        $this->template->stop = $this->stop;
        $this->template->trigger = 'handle' . ucFirst($this->getQuery('do')) . '()';
        $this->template->header = $this->header;
        $this->template->status = $this->status;
        $this->template->setTranslator($this->translatorModel);
        $this->template->setFile(__DIR__ . '/templates/@layout.latte');
        $this->template->render();
    }

    public function renderChart(Array $data) {
        $items = [];
        foreach ($data as $x => $y) {
            $items[] = ['x' => $x, 'y' => $y];
        }
        $this->template->options = empty($data) ? ['start' => 0, 'stop' => 0] : ['start' => key($data), 'stop' => key(array_reverse($data))];
        $this->template->items = $items;
        $this->template->setFile(__DIR__ . '/templates/chart.latte');
        $this->template->render();
    }

    /** components methods */
    protected function createComponentImportForm() {
        return $this->importFormFactory->create()
                        ->setService($this->grid->getImport());
    }

    protected function createComponentFilterForm() {
        return $this->filterFormFactory->create()
                        ->setGrid($this->grid);
    }

    protected function createComponentGridForm() {
        return $this->gridFormFactory->create()
                        ->setGrid($this->grid);
    }

}

interface IMasalaFactory {

    /** @return Masala */
    function create();
}
