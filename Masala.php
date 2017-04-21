<?php

namespace Masala;

use Nette\Application\Responses\JsonResponse,
    Nette\Application\Responses\TextResponse,
    Nette\Application\UI\Control,
    Nette\Application\UI\Presenter,
    Nette\InvalidStateException,
    Nette\Localization\ITranslator,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class Masala extends Control implements IMasalaFactory {

    /** @var ITranslator */
    private $translatorModel;

    /** @var IHelp */
    private $helpModel;

    /** @var IEditFormFactory */
    private $editFormFactory;

    /** @var IImportFormFactory */
    private $importFormFactory;

    /** @var IFilterFormFactory */
    protected $filterFormFactory;

    /** @var IBuilder */
    private $grid;

    /** @var IRowBuilder */
    private $row;

    /** @var IRequest */
    private $request;

    /** @var string */
    private $action;

    /** @var int */
    private $stop;

    /** @var Array */
    private $csv = ['header' => [], 'divider' => ',', 'file' => null];

    /** @var Array */
    private $columns = [];

    /** @var Array */
    private $parameters;

    /** @var Array */
    private $config;

    /** @var string */
    private $status;

    public function __construct(Array $config, IHelp $helpModel, ITranslator $translatorModel, IEditFormFactory $editFormFactory, IFilterFormFactory $filterFormFactory, IImportFormFactory $importFormFactory, IRequest $request, IRowBuilder $row) {
        parent::__construct(null, null);
        $this->config = $config;
        $this->helpModel = $helpModel;
        $this->translatorModel = $translatorModel;
        $this->editFormFactory = $editFormFactory;
        $this->filterFormFactory = $filterFormFactory;
        $this->importFormFactory = $importFormFactory;
        $this->request = $request;
        $this->row = $row;
        $this->config['port'] = isset($config['port']) ? $request->getUrl()->getHost() . ':' . $config['port'] : false;
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
                
            } elseif ($subquery != $label = $this->translatorModel->translate($subquery)) {
                
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
                    if (!isset($this->csv['header'][$feedColumn]) and is_numeric($feedValue)) {
                        $this->csv['header'][$feedColumn] = [$feedValue => $key];
                    } elseif (!isset($this->csv['header'][$feedColumn]) and is_bool($feedColumn)) {
                        $this->csv['header'][$feedColumn] = $key;
                    } elseif ('break' == $feedValue and ! isset($this->csv['header'][$feedColumn])) {
                        $this->csv['header'][$feedColumn] = $key;
                    } elseif ('break' == $feedValue and isset($this->csv['header'][$feedColumn])) {
                        
                    } elseif (is_array($header->$feedColumn)) {
                        is_numeric($feedValue) ? $this->csv['header'][$feedColumn][$feedValue] = $key : $this->csv['header'][$feedColumn][] = $key;
                    } elseif (is_numeric($feedValue)) {
                        $this->csv['header'][$feedColumn] = [0 => $key, $feedValue => $header->$feedColumn];
                    }
                }
            }
        }
        if (!empty($this->csv['header'])) {
            foreach (json_decode($this->grid->getImport()->getSetting()->validator) as $validator => $value) {
                if (!isset($this->csv['header'][$validator])) {
                    return $this->csv['header'] = $this->translatorModel->translate('Header does not contains validator') . ' ' . $this->translatorModel->translate($validator) . '.';
                }
            }
        }
    }

    public function getUrl($id) {
        $url = $this->request->getUrl()->getQueryParameters();
        if (isset($url[$this->getName() . '-' . $id])) {
            return preg_replace('/\+/', ' ', $url[$this->getName() . '-' . $id]);
        } elseif ('do' == $id and isset($url[$id])) {
            return preg_replace('/\-(.*)/', '', preg_replace('/' . lcfirst($this->getName()) . '\-/', '', $url[$id]));
        }
    }

    /** @return IMasalaFactory */
    public function create() {
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof Presenter and null != $this->grid->getTable()) {
            /** IBuilder */
            $this->action = $presenter->action;
            $this->grid->attached($this);
            /** columns */
            $this->setColumns($presenter);
        }
    }

    /** @return Masala */
    public function clones() {
        return new Masala($this->config, $this->translatorModel, $this->importFormFactory, $this->filterFormFactory, $this->request);
    }

    /** signal methods */
    public function handleCsv($file, $divider) {
        $this->grid->log('csv');
        $setting = $this->grid->getImport()->getSetting();
        $folder = $this->presenter->getContext()->parameters['tempDir'] . '/' . $this->getName() . '/' . str_replace(':', '', $this->getPresenter()->getName() . $this->getPresenter()->getAction());
        $path = $folder . '/' . $file . '.csv';
        $this->csv['file'] = $file;
        $this->csv['divider'] = $divider;
        $this->status = 'import';
        $this->stop = filesize($path);
        $header = json_decode($setting->mapper);
        $handle = fopen($path, 'r');
        while (false !== ($row = fgets($handle, 10000))) {
            $row = $this->sanitize($row, $divider);
            if (is_string($this->csv['header'])) {
                $this->presenter->flashMessage($this->csv['header']);
                $this->presenter->redirect('this');
            } elseif (empty($this->csv['header'])) {
                $this->getHeader($row, $header);
            } elseif (!empty($this->csv['header'])) {
                break;
            }
        }
        fclose($handle);
    }

    private function sanitize($row, $divider) {
        preg_match_all('/\"(.*?)\"/', $row, $matches);
        $matches[1] = (isset($matches[1])) ? $matches[1] : [];
        foreach ($matches[1] as $match) {
            $row = str_replace('<?php', '', (str_replace($match, str_replace(',', '.', $match), $row)));
        }
        return explode($divider, str_replace('"', '', $row));
    }

    public function handleDone() {
        $this->grid->log('done');
        $rows = !is_array($this->presenter->request->getPost('rows')) ? [] : $this->presenter->request->getPost('rows');
        $row = $this->presenter->request->getPost('row');
        $service = 'get' . ucfirst($row['status']);
        $this->grid->$service()->done($rows, $this->presenter);
        $response = new TextResponse($this->presenter->link('this', ['do' => $this->getName() . '-message', $this->getName() . '-status' => $row['status']]));
        return $this->presenter->sendResponse($response);
    }

    public function handleEdit() {
        $this->row->table($this->grid->getTable());
        foreach ($this->grid->filter()->getOffset($this->presenter->request->getPost('offset')) as $column => $value) {
            $this->row->where($column, $value);
        }
        $masalaFormFactory = $this->editFormFactory->create()
                ->setRow($this->row->setSpice($this->presenter->request->getPost('spice')))
                ->signalled('attached')
                ->attached($this);
        $response = new TextResponse($masalaFormFactory->render());
        return $this->presenter->sendResponse($response);
    }

    public function handleExport() {
        $this->status = 'export';
        $this->stop = $this->grid->filter()->getExport()->prepare($this);
        $response = new JsonResponse(['run' => 0, 'stop' => $this->stop, 'status' => 'export']);
        return $this->presenter->sendResponse($response);
    }

    public function handleFilter() {
        $rows = $this->grid->filter()->getOffsets();
        $response = new JsonResponse(['rows' => $rows,
            'url' => !empty($this->grid->getSpice()) ? strtolower($this->getName()) . '-spice=' . $this->grid->getSpice() : '']);
        return $this->presenter->sendResponse($response);
    }

    public function handleImport($stop) {
        $response = new JsonResponse(['run' => 0,
            'stop' => intval($stop),
            'status' => 'import',
            'rows' => $this->grid->filter()->getImport()->prepare($this),
            'key' => $key = str_replace(':', '', $this->getPresenter()->getName()) . ':' . $this->getPresenter()->getAction() . ':' . $this->getName() . ':handleCsv:' . $this->presenter->getUser()->getId()]);
        return $this->presenter->sendResponse($response);
    }

    public function handleMessage($status) {
        $service = 'get' . ucfirst($status);
        $message = $this->grid->$service()->message($this);
        $this->presenter->flashMessage($this->translatorModel->translate(empty($message) ? 'Lengthy process was completed.' : $message));
    }

    public function handleMigrate() {
        $stop = $this->grid->filter()->getMigration()->prepare($this);
        $response = new JsonResponse(['run' => 0, 'stop' => $stop, 'status' => 'migration', 'rows' => false]);
        return $this->presenter->sendResponse($response);
    }

    public function handlePaginate() {
        $sum = $this->grid->filter()->getSum();
        $total = ($sum > $this->grid->getPagination()) ? intval(round($sum / $this->grid->getPagination())) : 1;
        $response = new TextResponse($total);
        return $this->presenter->sendResponse($response);
    }

    public function handlePostpone() {
        return $this->status = null;
    }

    public function handlePrepare() {
        $this->grid->log('prepare');
        $this->status = (null == $this->status) ? 'service' : $this->status;
        $prepared = $this->grid->filter()->getService()->prepare($this);
        $this->stop = $prepared['stop'];
        $response = new JsonResponse(['run' => 0, 'stop' => $this->stop, 'status' => 'service', 'rows' => $prepared['rows']]);
        return $this->presenter->sendResponse($response);
    }

    public function handleRedraw() {
        $offset = $this->presenter->request->getPost('offset');
        $row = $this->grid
                ->filter()
                ->redrawOffset($offset)
                ->flushOffset($offset)
                ->loadOffset($offset);
        $response = new TextResponse($row);
        return $this->presenter->sendResponse($response);
    }

    public function handleRun() {
        $upload = $this->presenter->request->getPost('row');
        $csv = $this->presenter->request->getPost('csv');
        $skip = false;
        if (!empty($csv['header'])) {
            /** skip header */
            $skip = (0 == $upload['run']);
            if (intval(str_replace('M', '', ini_get('post_max_size')) * 1024 * 1024) <
                    (mb_strlen(serialize($csv['header']), '8bit') + mb_strlen(serialize($upload), '8bit'))
            ) {
                throw new InvalidStateException('Uploaded data in post is too large according to post_max_size');
            }
            $folder = $this->presenter->getContext()->parameters['tempDir'] . '/' . $this->getName() . '/' . str_replace(':', '', $this->getPresenter()->getName() . $this->getPresenter()->getAction());
            $path = $folder . '/' . $csv['file'] . '.csv';
            $handle = fopen($path, 'r');
            fseek($handle, $upload['run']);
            $offset = fgets($handle);
            $upload['run'] = ftell($handle);
            $row = [];
            $offset = $this->sanitize($offset, $csv['divider']);
            foreach ($csv['header'] as $headerId => $header) {
                if (is_array($header)) {
                    foreach ($header as $valueId => $value) {
                        $row[$headerId][$valueId] = $offset[$value];
                    }
                } else {
                    $row[$headerId] = $offset[$header];
                }
            }
            $upload['offset'] = $row;
        } else {
            $row = $this->grid->filter()->getOffset($upload['run']);
            $upload['run'] = $upload['run'] + 1;
            $upload['offset'] = $row;
        }
        $service = 'get' . ucfirst($this->status = $upload['status']);
        $setting = $this->grid->$service()->getSetting();
        $callbacks = is_object($setting) ? json_decode($setting->callback) : [];
        foreach ($callbacks as $callbackId => $callback) {
            $sanitize = preg_replace('/print|echo|exec|call|eval|mysql/', '', $callback);
            eval('function call($row) {' . $sanitize . '}');
            $row = call($row);
        }
        if (!isset($upload['rows']) and false == $skip) {
            $this->grid->$service()->run($row, [], $this);
        } elseif (false == $skip) {
            $upload['rows'] = $this->grid->$service()->run($row, $upload['rows'], $this);
        }
        /** $response = new TextResponse($this->presenter->payload->rows = json_encode($result)); */
        $response = new JsonResponse($upload);
        return $this->presenter->sendResponse($response);
    }

    public function handleStorage() {
        $response = [];
        $storage = $this->presenter->request->getPost('storage');
        foreach ($storage as $id => $value) {
            $response[$this->getName() . ':' . $id] = true;
            $primary = preg_replace('/(.*)\_/', '', $id);
            $column = preg_replace('/\_' . $primary . '/', '', $id);
            $this->grid->setRow($primary, [$column => $value]);
        }
        $cache = $this->presenter->request->getPost('cache');
        foreach ($cache as $key => $row) {
            $response[$key] = true;
            $this->grid->flush($key);
            $this->grid->update($key, $row);
        }
        $json = new JsonResponse($response);
        return $this->presenter->sendResponse($json);
    }

    public function handleSubmit($signal, $spice) {
        $values = [];
        foreach ($this->presenter->request->getPost('form') as $componentId => $component) {
            $values[$componentId] = $component;
        }
        $offset = preg_replace('/(.*)\:/', '', $spice);
        if (!is_numeric($offset)) {
            throw new InvalidStateException('Offset extracted with regex from spice is not number.');
        }
        $this->row->table($this->grid->getTable());
        foreach ($this->grid->filter()->getOffset($offset) as $column => $value) {
            $this->row->where($column, $value);
        }
        $masalaFormFactory = $this->editFormFactory->create()
                ->setRow($this->row->setSpice($spice))
                ->signalled($signal)
                ->attached($this)
                ->setValues($values);
        foreach ($masalaFormFactory->getComponents() as $name => $component) {
            if (false == $component->getRules()->validate()) {
                $errors = $component->getErrors();
                $response = new JsonResponse(['message' => array_shift($errors)]);
                return $this->presenter->sendResponse($response);
            }
        }
        $response = new JsonResponse(['message' => $masalaFormFactory->succeeded($masalaFormFactory),
            'offset' => preg_replace('/(.*)\:/', '', $spice),
            'row' => json_encode($this->row->getData()->toArray())]);
        return $this->presenter->sendResponse($response);
    }

    public function handleUpdate() {
        $this->grid->filter()->flush($this->grid->getHash());
        $response = new JsonResponse(['message' => ucfirst($this->translatorModel->translate('Grid data were updated.'))]);
        return $this->presenter->sendResponse($response);
    }

    /** render methods */
    public function render() {
        $this->template->bower = $this->config['bower'] . '/' . strtolower(__NAMESPACE__);
        $this->template->columns = $this->columns;
        $this->template->grid = $this->grid;
        $this->template->port = $this->config['port'];
        $this->template->settings = json_decode($this->presenter->getUser()->getIdentity()->__get('settings'));
        $this->template->help = $this->helpModel->getHelp($this->presenter->getName(), $this->presenter->getAction(), $this->request->getUrl()->getQuery());
        $this->template->stop = $this->stop;
        $this->template->trigger = 'handle' . ucfirst($this->getUrl('do')) . '()';
        $this->template->csv = $this->csv;
        $this->template->status = $this->status;
        $this->template->setTranslator($this->translatorModel);
        $this->template->setFile(__DIR__ . '/templates/@layout.latte');
        $this->template->render();
    }

    public function rendered($type) {
        $post = $this->request->getPost();
        if (isset($post[$this->getName() . ':rendered:' . $type])) {
            return true;
        } else {
            $this->request->getUrl()->setQueryParameter($this->getName() . ':rendered:' . $type, true);
            return false;
        }
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

}

interface IMasalaFactory {

    /** @return Masala */
    function create();
}
