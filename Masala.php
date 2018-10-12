<?php

namespace Masala;

use Nette\Application\Responses\JsonResponse,
    Nette\Application\Responses\TextResponse,
    Nette\Application\UI\Control,
    Nette\Application\IPresenter,
    Nette\ComponentModel\IComponent,
    Nette\Database\Table\ActiveRow,
    Nette\Http\IRequest,
    Nette\Utils\DateTime,
    Nette\Localization\ITranslator,
    PHPExcel,
    PHPExcel_Writer_Excel2007,
    PHPExcel_IOFactory,
    stdClass;

/** @author Lubomir Andrisek */
final class Masala extends Control implements IMasalaFactory {

    /** @var array */
    private $config;

    /** @var IBuilder */
    private $grid;

    /** @var IGridFactory */
    protected $gridFactory;

    /** @var array */
    private $header;

    /** @var IHelp */
    private $helpRepository;

    /** @var IIdentity */
    public $identity;

    /** @var IImportFormFactory */
    private $importFormFactory;

    /** @var IRequest */
    private $request;

    /** @var IBuilder */
    private $row;

    /** @var ITranslator */
    private $translatorRepository;

    public function __construct(array $config, IGridFactory $gridFactory, IHelp $helpRepository, IImportFormFactory $importFormFactory, IRequest $request, 
        ITranslator $translatorRepository) {
        parent::__construct(null, null);
        $this->config = $config;
        $this->gridFactory = $gridFactory;
        $this->helpRepository = $helpRepository;
        $this->importFormFactory = $importFormFactory;
        $this->request = $request;
        $this->translatorRepository = $translatorRepository;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            if(!empty($this->grid->getTable())) {
                $this->grid->attached($this);
            }
            if(!empty($this->row->getTable())) {
                $this->row->attached($this);
            }
        }
    }

    public function create(): Masala {
        return $this;
    }

    protected function createComponentGrid(): IGridFactory {
        return $this->gridFactory->create()
                    ->setGrid($this->grid);
    }

    protected function createComponentRowForm(): IRowFormFactory {
        $offsets = $this->row->limit(1)->prepare()->getOffsets();
        $form = $this->row->row(0, reset($offsets));
        if($this->row->isEdit()) {
            $this->row->getEdit()->after($form);
        }
        return $form;
    }

    protected function createComponentImportForm(): IImportFormFactory {
        return $this->importFormFactory->create()
                    ->setService($this->grid->getImport());
    }

    public function getGrid(): IBuilder {
        return $this->grid;
    }

    private function getHeader(array $row, stdClass $header): void {
        foreach ($row as $key => $column) {
            mb_detect_encoding($column, 'UTF-8', true) == false ? $column = trim(iconv('windows-1250', 'utf-8', $column)) : $column = trim($column);
            if (isset($header->$column)) {
                foreach ($header->$column as $feed => $value) {
                    if (!isset($this->header[$feed]) and is_numeric($value)) {
                        $this->header[$feed] = [$value => $key];
                    } elseif (!isset($this->header[$feed]) and is_bool($feed)) {
                        $this->header[$feed] = $key;
                    } elseif ('break' == $value and ! isset($this->header[$feed])) {
                        $this->header[$feed] = $key;
                    } elseif ('break' == $value and isset($this->header[$feed])) {
                        
                    } elseif (is_array($header->$feed)) {
                        is_numeric($value) ? $this->header[$feed][$value] = $key : $this->header[$feedColumn][] = $key;
                    } elseif (is_numeric($value)) {
                        $this->header[$feed] = [0 => $key, $value => $header->$feed];
                    }
                }
            }
        }
        if (!empty($this->header)) {
            foreach (json_decode($this->grid->getImport()->getSetting()->validator) as $validator => $value) {
                if (!isset($this->header[$validator])) {
                    $this->header = $this->translatorRepository->translate('Header does not contains validator') . ' ' . $this->translatorRepository->translate($validator) . '.';
                }
            }
        }
    }

    private function getDivider(string $file): string {
        $dividers = [];
        foreach ([',', ';', '"'] as $divider) {
            $handle = fopen($file, 'r');
            $line = fgetcsv($handle, 10000, $divider);
            fclose($handle);
            $dividers[count($line)] = $divider;
        }
        ksort($dividers);
        $divider = array_reverse($dividers);
        return array_shift($divider);
    }

    private function getResponse(): array {
        $response = ['_file' => $this->grid->getPost('_file'),
            'Data' => $this->grid->getPost('Data'),
            'divider' => $this->grid->getPost('divider'),
            'header' => $this->grid->getPost('header'),
            'Filters' => $this->grid->getPost('Filters'),
            'Offset' => $this->grid->getPost('Offset'),
            'Sort' => $this->grid->getPost('Sort'),
            'Status' => $this->grid->getPost('Status'),
            'Stop' => intval($this->grid->getPost('Stop')),
        ];
        if(null == $response['Data'] = $this->grid->getPost('Data')) {
            $response['Data'] = [];
        }
        return $response;
    }

    public function handleDone(): void {
        $this->grid->log('done');
        $service = 'get' . ucfirst($this->grid->getPost('Status'));
        $this->presenter->sendResponse(new JsonResponse($this->grid->$service()->done($this->getResponse(), $this)));
    }
    
    public function handleExport(): void {
        $folder = $this->grid->getExport()->getFile();
        !file_exists($folder) ? mkdir($folder, 0755, true) : null;
        $header = '';
        foreach($this->grid->prepare()->getOffset(0) as $column => $value) {
            if(!$value instanceof DateTime && empty($this->grid->getAnnotation($column, ['unrender', 'hidden']))) {
                $header .= $this->grid->translate($column, $this->grid->getTable() . '.' .  $column) . ';';
            }
        }
        $file = $this->grid->getId('export') . '.csv';
        file_put_contents($folder . '/' . $file, $header);
        $response = new JsonResponse($this->grid->getExport()->prepare([
                '_file' => $file,
                'Filters' => $this->grid->getPost('Filters'),
                'Offset' => 0,
                'Sort' => $this->grid->getPost('Sort'),
                'Status' => 'export',
                'Stop' => $this->grid->getSum()], $this));
        $this->presenter->sendResponse($response);
    }
    
    public function handleExcel(): void {
        $excel = new PHPExcel();
        $folder = $this->grid->getExport()->getFile();
        !file_exists($folder) ? mkdir($folder, 0755, true) : null;
        $title = 'export';
        $properties = $excel->getProperties();
        $properties->setTitle($title);
        $properties->setSubject($title);
        $properties->setDescription($title);
        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));
        $letter = 'a';
        foreach($this->grid->prepare()->getOffset(0) as $column => $value) {
            if(!$value instanceof DateTime && empty($this->grid->getAnnotation($column, ['unrender', 'hidden', 'unexport']))) {
                $sheet->setCellValue($letter . '1', ucfirst($this->translatorRepository->translate($column)));
                $sheet->getColumnDimension($letter)->setAutoSize(true);
                $sheet->getStyle($letter . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $letter++;
            }
        }
        $file = $this->grid->getId('excel') . '.xls';
        $writer = new PHPExcel_Writer_Excel2007($excel);
        $writer->save($folder . '/' .$file);
        $response = new JsonResponse($this->grid->getExport()->prepare([
            '_file' => $file,
            'Filters' => $this->grid->getPost('Filters'),
            'Offset' => 0,
            'Sort' => $this->grid->getPost('Sort'),
            'Status' => 'excel',
            'Stop' => $this->grid->getSum()], $this));
        $this->presenter->sendResponse($response);
    }
    
    public function handleImport(): void {
        $path = $this->grid->getImport()->getFile() . $this->grid->getPost('_name');
        $setting = $this->grid->getImport()->getSetting();
        $header = json_decode($setting->mapper);
        $divider = $this->getDivider($path);
        $handle = fopen($path, 'r');
        while (false !== ($row = fgets($handle, 10000))) {
            $before = $row;
            $row = $this->sanitize($row, $divider);
            if (empty($this->header)) {
                $offset = strlen($before);
                $this->getHeader($row, $header);
            } elseif (!empty($this->header)) {
                break;
            }
        }
        $response = new JsonResponse($this->grid->getImport()->prepare(['divider'=>$divider,
                                    'header'=>$this->header,
                                    '_file'=> $this->grid->getPost('_name'),
                                    'link'=> $this->link('run'),
                                    'Offset'=> $offset,
                                    'Status'=>'import',
                                    'Stop' => filesize($path)], $this));
        $this->presenter->sendResponse($response);
    }
    
    public function handlePrepare(): void {
        $this->grid->log('prepare');
        $data = ['Filters' => $this->grid->getPost('Filters'),
            'Offset' => 0,
            'Sort' => $this->grid->getPost('Sort'),
            'Status' => 'service',
            'Stop' => $this->grid->prepare()->getSum()];
        $response = new JsonResponse($this->grid->getService()->prepare($data, $this));
        $this->presenter->sendResponse($response);
    }
    
    public function handleRun(): void {
        $response = $this->getResponse();
        if ('import' == $response['Status']) {
            $service = $this->grid->getImport();
            $path = $this->grid->getImport()->getFile() . $this->grid->getPost('_file');
            $handle = fopen($path, 'r');
            for($i = 0; $i < $speed = $service->speed($this->config['speed']); $i++) {
                fseek($handle, $response['Offset']);
                $offset = fgets($handle);
                if($response['Stop'] == $response['Offset'] = ftell($handle)) {
                    $i = $speed;
                }
                $response['Row'] = [];
                $offset = $this->sanitize($offset, $response['divider']);
                foreach ($response['header'] as $headerId => $header) {
                    if (is_array($header)) {
                        foreach ($header as $valueId => $value) {
                            $response['Row'][$headerId][$valueId] = $offset[$value];
                        }
                    } else {
                        $response['Row'][$headerId] = $offset[$header];
                    }
                }
                $response = $service->run($response, $this);
            }
        /** export */
        } elseif(in_array($response['Status'], ['export', 'excel'])) {
            $service = $this->grid->getExport();
            $path = $service->getFile() . '/' . $response['_file'];
            $response['limit'] = $service->speed($this->config['speed']);
            $response['Row'] = $this->grid->prepare()->getOffsets();
            if(isset($response['Row'])) { unset($response['Row'][-1]); }
            $response['Sort'] = $this->grid->getPost('Sort');
            $response = $service->run($response, $this);
            if('export' == $response['Status']) {
                $handle = fopen('nette.safe://' . $path, 'a');
            } else {
                $excel = PHPExcel_IOFactory::load($path);
                $excel->setActiveSheetIndex(0);
                $last = $excel->getActiveSheet()->getHighestRow();
            }
            foreach($response['Row'] as $rowId => $cells) {
                foreach($cells as $cellId => $cell) {
                    if($cell instanceof DateTime) {
                        $response['Row'][$rowId][$cellId] = $cell->__toString();
                    } else if(empty($this->grid->getAnnotation($cellId, ['unrender', 'hidden', 'unexport'])) && isset($cell['Attributes']) && isset($cell['Attributes']['value'])) {
                        $response['Row'][$rowId][$cellId] = $cell['Attributes']['value'];
                    } else if(empty($this->grid->getAnnotation($cellId, ['unrender', 'hidden', 'unexport'])) && !isset($cell['Attributes'])) {
                        $response['Row'][$rowId][$cellId] = $cell;
                    } else {
                        unset($response['Row'][$rowId][$cellId]);
                    }
                }
                if('export' == $response['Status']) {
                    fputs($handle, PHP_EOL . implode(';', $response['Row'][$rowId]));
                } else {
                    $last++;
                    $letter = 'a';
                    foreach ($response['Row'][$rowId] as $cell) {
                        $excel->getActiveSheet()->SetCellValue($letter++ . $last, $cell);
                    }
                }
            }
            if('export' == $response['Status']) {
                fclose($handle);
            } else {
                $writer = new PHPExcel_Writer_Excel2007($excel);
                $writer->save($path);
            }
            $response['Offset'] = $response['Offset'] + $service->speed($this->config['speed']);
        /** process */
        } else {
            $service = $this->grid->getService();
            if(!empty($response['Row'] = $this->grid->prepare()->getOffsets())) {
                $response = $service->run($response, $this);
            }
            $response['Offset'] = $response['Offset'] + $service->speed($this->config['speed']);
        }
        if(($setting = $service->getSetting()) instanceof ActiveRow) {
            foreach (json_decode($setting->callback) as $callbackId => $callback) {
                $sanitize = preg_replace('/print|echo|exec|call|eval|mysql/', '', $callback);
                eval('function call($response["row"]) {' . $sanitize . '}');
                $response['Row'] = call($response['Row']);
            }
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }
    
    public function handleSave(): void {
        if($this->grid->isImport())  {
            $this->grid->getImport()->save($this->grid->getPost(''));
        } else {
            $this->row->getImport()->save($this->row->getPost(''));
        }
        $this->presenter->sendResponse(new TextResponse($this->row->getPost('_name')));
    }
    
    public function handleSubmit(): void {
        $this->presenter->sendResponse(new JsonResponse($this->row->submit(true)));
    }
    
    public function handleValidate(): void {
        $this->presenter->sendResponse(new JsonResponse($this->row->validate()));
    }
    
    public function render(): void {
        $this->template->assets = $this->config['assets'];
        $this->template->npm = $this->config['npm'];
        $this->template->locale = preg_replace('/(\_.*)/', '', $this->translatorRepository->getLocale());
        $this->template->dialogs = ['help', 'import', 'message'];
        $this->template->grid = $this->grid;
        $this->template->help = $this->helpRepository->getHelp($this->presenter->getName(), $this->presenter->getAction(), $this->request->getUrl()->getQuery());
        $columns = $this->grid->getColumns();
        $this->template->order = reset($columns);
        $this->template->setFile(__DIR__ . '/templates/@layout.latte');
        $this->template->setTranslator($this->translatorRepository);
        $this->template->settings = json_decode($this->presenter->getUser()->getIdentity()->__get('settings'));
        $this->template->render();
    }
    
    public function renderRow(): void {
        $this->template->dialogs = ['help' => 1102.0, 'import' => 1101.0, ];
        $this->template->grid = $this->row;
        $this->template->help = $this->helpRepository->getHelp($this->presenter->getName(), $this->presenter->getAction(), $this->request->getUrl()->getQuery());
        $this->template->npm = false;
        if(empty($this->grid->getTable())) {
            $this->template->npm = $this->config['npm'];
        }
        $this->template->setFile(__DIR__ . '/templates/row.latte');
        $this->template->setTranslator($this->translatorRepository);
        $this->template->render();
    }

    private function sanitize(string $row, string $divider): array {
        return explode($divider, preg_replace('/\<\?php|\"/', '', $row));
    }

    public function setConfig(string $key, $value): IMasalaFactory {
        $this->config[$key] = $value;
        return $this;
    }

    public function setGrid(IBuilder $grid): IMasalaFactory {
        $this->grid = $grid;
        return $this;
    }
    
    public function setRow(IBuilder $row): IMasalaFactory {
        $this->row = $row;
        return $this;
    }

}

interface IMasalaFactory {

    public function create(): Masala;

}
