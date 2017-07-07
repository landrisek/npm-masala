<?php

namespace Masala;

use Nette\Application\Responses\JsonResponse,
    Nette\Application\Responses\TextResponse,
    Nette\Application\UI\Control,
    Nette\Application\IPresenter,
    Nette\Localization\ITranslator,
    Nette\Http\IRequest,
    PHPExcel;

/** @author Lubomir Andrisek */
final class Masala extends Control implements IMasalaFactory {

    /** @var array */
    private $config;

    /** @var IHelp */
    private $helpModel;

    /** @var IBuilder */
    private $grid;

    /** @var IGridFactory */
    protected $gridFactory;

    /** @var array */
    private $header;

    /** @var IImportFormFactory */
    private $importFormFactory;

    /** @var IProcessFormFactory */
    private $processFormFactory;

    /** @var IRequest */
    private $request;

    /** @var ITranslator */
    private $translatorModel;

    public function __construct(array $config, IGridFactory $gridFactory, IHelp $helpModel, IImportFormFactory $importFormFactory,  IProcessFormFactory $processFormFactory, IRequest $request, ITranslator $translatorModel) {
        parent::__construct(null, null);
        $this->config = $config;
        $this->gridFactory = $gridFactory;
        $this->helpModel = $helpModel;
        $this->importFormFactory = $importFormFactory;
        $this->processFormFactory = $processFormFactory;
        $this->request = $request;
        $this->translatorModel = $translatorModel;
    }

    /** @return IMasalaFactory */
    public function setGrid(IBuilder $grid) {
        $this->grid = $grid;
        return $this;
    }

    /** @return IBuilder */
    public function getGrid() {
        return $this->grid;
    }

    private function getHeader($row, $header) {
        foreach ($row as $key => $column) {
            mb_detect_encoding($column, 'UTF-8', true) == false ? $column = trim(iconv('windows-1250', 'utf-8', $column)) : $column = trim($column);
            if (isset($header->$column)) {
                foreach ($header->$column as $feedColumn => $feedValue) {
                    if (!isset($this->header[$feedColumn]) and is_numeric($feedValue)) {
                        $this->header[$feedColumn] = [$feedValue => $key];
                    } elseif (!isset($this->header[$feedColumn]) and is_bool($feedColumn)) {
                        $this->header[$feedColumn] = $key;
                    } elseif ('break' == $feedValue and ! isset($this->header[$feedColumn])) {
                        $this->header[$feedColumn] = $key;
                    } elseif ('break' == $feedValue and isset($this->header[$feedColumn])) {
                        
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

    /** @return IMasalaFactory */
    public function create() {
        return $this;
    }

    /** @return void */
    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter and null != $this->grid->getTable()) {
            $this->grid->attached($this);
        }
    }

    private function getDivider($file) {
        $handle = fopen($file, 'r');
        $dividers = [];
        foreach ([',', ';', '"'] as $divider) {
            $line = fgetcsv($handle, 10000, $divider);
            $dividers[count($line)] = $divider;
        }
        fclose($handle);
        ksort($dividers);
        $divider = array_reverse($dividers);
        return array_shift($divider);
    }

    /** @return array */
    private function getResponse() {
        $response = ['file' => $this->request->getPost('file'),
            'data' => $this->request->getPost('data'),
            'divider' => $this->request->getPost('divider'),
            'header' => $this->request->getPost('header'),
            'filters' => $this->request->getPost('filters'),
            'offset' => $this->request->getPost('offset'),
            'status' => $this->request->getPost('status'),
            'stop'=>$this->request->getPost('stop'),
        ];
        if(null == $response['data'] = $this->presenter->request->getPost('data')) {
            $response['data'] = [];
        }
        return $response;
    }

    /** @return JsonResponse */
    public function handleDone() {
        $this->grid->log('done');
        $service = 'get' . ucfirst($this->presenter->request->getPost('status'));
        return $this->presenter->sendResponse(new JsonResponse($this->grid->$service()->done($this->getResponse(), $this)));
    }

    /** @return JsonResponse */
    public function handleExport() {
        $folder = $this->grid->getExport()->getFile();
        !file_exists($folder) ? mkdir($folder, 0755, true) : null;
        $header = '';
        foreach(array_keys($this->grid->prepare()->getOffset(1)) as $column) {
            $header .= $this->translatorModel->translate($column) . ';';
        }
        $file = $this->grid->getId('export') . '.csv';
        file_put_contents($folder . '/' . $file, $header);
        $response = new JsonResponse($this->grid->getExport()->prepare([
                'file' => $file,
                'filters' => $this->request->getPost('filters'),
                'offset' => 0,
                'sort' => $this->request->getPost('sort'),
                'status' => 'export',
                'stop' => $this->grid->getSum()], $this));
        return $this->presenter->sendResponse($response);
    }

    public function handleExcel() {
        $excel = new PHPExcel();
        $folder = $this->grid->getExport()->getFile();
        $title = $this->request->getPost('title');
        $properties = $excel->getProperties();
        $properties->setTitle($title);
        $properties->setSubject($title);
        $properties->setDescription($title);
        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));
        $this->header = [];
        $id = 'a';
        $this->header = [];
        foreach($this->request->getPost('row') as $column => $value) {
            $this->header[$id] = [ucfirst($this->translatorModel->translate($column)), \PHPExcel_Style_Alignment::HORIZONTAL_LEFT];
            ++$id;
        }
        foreach ($this->header as $letter => $header) {
            $sheet->setCellValue($letter . '1', $header[0]);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
            $sheet->getStyle($letter . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        $file = $this->grid->getId('export') . '.xls';
        file_put_contents($folder . '/' . $file, $header);
        $response = new JsonResponse($this->grid->getExport()->prepare([
            'file' => $file,
            'filters' => $this->request->getPost('filters'),
            'offset' => 0,
            'sort' => $this->request->getPost('sort'),
            'status' => 'export',
            'stop' => $this->grid->getSum()], $this));
        return $this->presenter->sendResponse($response);
    }


    public function handleImport() {
        $path = $this->grid->getImport()->getFile();
        $setting = $this->grid->getImport()->getSetting();
        $header = json_decode($setting->mapper);
        $divider = $this->getDivider($path);
        $handle = fopen($path, 'r');
        while (false !== ($row = fgets($handle, 10000))) {
            $row = $this->sanitize($row, $divider);
            if (empty($this->header)) {
                $this->getHeader($row, $header);
            } elseif (!empty($this->header)) {
                break;
            }
        }
        $response = new JsonResponse($this->grid->getImport()->prepare(['divider'=>$divider,
                                    'header'=>$this->header,
                                    'file'=> $this->request->getPost('file'),
                                    'link'=>$this->link('run'),
                                    'offset'=> 0,
                                    'status'=>'import',
                                    'stop' => filesize($path)], $this));
        return $this->presenter->sendResponse($response);
    }

    /** @return TextResponse */
    public function handleSave($file) {
        $this->getGrid()->getImport()->save($id = $this->grid->getId($file), $this->request->getPost('file'));
        $response = new TextResponse($id);
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handlePrepare() {
        $this->grid->log('prepare');
        $data = ['offset' => 0, 'stop' => $this->grid->prepare()->getSum(), 'status' => 'service'];
        $response = new JsonResponse($this->grid->getService()->prepare($data, $this));
        return $this->presenter->sendResponse($response);
    }

    /** @return JsonResponse */
    public function handleRun() {
        $response = $this->getResponse();
        if ('import' == $response['status']) {
            $path = $this->grid->getImport()->getFile();
            $handle = fopen($path, 'r');
            fseek($handle, $response['offset']);
            $offset = fgets($handle);
            $response['offset'] = ftell($handle);
            $response['row'] = [];
            $offset = $this->sanitize($offset, $response['divider']);
            foreach ($response['header'] as $headerId => $header) {
                if (is_array($header)) {
                    foreach ($header as $valueId => $value) {
                        $response['row'][$headerId][$valueId] = $offset[$value];
                    }
                } else {
                    $response['row'][$headerId] = $offset[$header];
                }
            }
            $service = $this->grid->getImport();
            $response = $service->run($response, $this);
        /** export */
        } elseif('export' == $response['status']) {
            $service = $this->grid->getExport();
            $path = $service->getFile() . '/' . $response['file'];
            $response['limit'] = $this->config['exportSpeed'];
            $response['row'] = $this->grid->prepare()->getOffsets();
            $response = $service->run($response, $this);
            $handle = fopen('nette.safe://' . $path, 'a');
            foreach($response['row'] as $row) {
                fputs($handle, PHP_EOL . implode(';', $row) . ';');
            }
            fclose($handle);
            $response['offset'] = $response['offset'] + $this->config['exportSpeed'];
        /** process */
        } else {
            $service = $this->grid->getService();
            $response['offset'] = $response['offset'] + 1;
            if(!empty($response['row'] = $this->grid->prepare()->getOffset($response['offset']))) {
                $response = $service->run($response, $this);
            }
        }
        $setting = $service->getSetting();
        $callbacks = is_object($setting) ? json_decode($setting->callback) : [];
        foreach ($callbacks as $callbackId => $callback) {
            $sanitize = preg_replace('/print|echo|exec|call|eval|mysql/', '', $callback);
            eval('function call($row) {' . $sanitize . '}');
            $row = call($row);
        }
        return $this->presenter->sendResponse(new JsonResponse($response));
    }

    public function render() {
        $this->template->assets = $this->config['assets'];
        $this->template->npm = $this->config['npm'];
        $this->template->locale = preg_replace('/(\_.*)/', '', $this->translatorModel->getLocale());
        $this->template->dialogs = ['edit', 'help', 'import', 'process'];
        $this->template->help = $this->helpModel->getHelp($this->presenter->getName(), $this->presenter->getAction(), $this->request->getUrl()->getQuery());
        $this->template->grid = $this->grid;
        $columns = $this->grid->getColumns();
        $this->template->order = reset($columns);
        $this->template->setFile(__DIR__ . '/templates/@layout.latte');
        $this->template->setTranslator($this->translatorModel);
        $this->template->settings = json_decode($this->presenter->getUser()->getIdentity()->__get('settings'));
        $this->template->render();
    }

    private function sanitize($row, $divider) {
        preg_match_all('/\"(.*?)\"/', $row, $matches);
        $matches[1] = (isset($matches[1])) ? $matches[1] : [];
        foreach ($matches[1] as $match) {
            $row = str_replace('<?php', '', (str_replace($match, str_replace(',', '.', $match), $row)));
        }
        return explode($divider, str_replace('"', '', $row));
    }

    /** @return IGridFactory */
    protected function createComponentGrid() {
        return $this->gridFactory->create()
            ->setGrid($this->grid);
    }

    /** @return IImportFormFactory */
    protected function createComponentImportForm() {
        return $this->importFormFactory->create()
            ->setService($this->grid->getImport());
    }

    /** @return IProcessFormFactory */
    protected function createComponentProcessForm() {
        return $this->processFormFactory->create()
            ->setService($this->grid->getService());
    }

}

interface IMasalaFactory {

    /** @return Masala */
    function create();

}
