<?php

namespace Masala;

use Nette\Application\Responses\JsonResponse,
    Nette\Application\UI\Control,
    Nette\Application\IPresenter,
    Nette\ComponentModel\IComponent,
    Nette\Http\IRequest,
    Nette\Utils\DateTime,
    Nette\Localization\ITranslator,
    PHPExcel_Writer_Excel2007,
    PHPExcel_IOFactory;

/** @author Lubomir Andrisek */
final class Console extends Control implements IConsoleFactory {

    /** @var array */
    private $config;

    /** @var IBuilder */
    private $grid;

    /** @var IRequest */
    private $request;

    /** @var ITranslator */
    private $translatorRepository;

    public function __construct(array $config, IRequest $request, ITranslator $translatorRepository) {
        parent::__construct(null, null);
        $this->config = $config;
        $this->request = $request;
        $this->translatorRepository = $translatorRepository;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            if(!empty($this->grid->getTable())) {
                $this->grid->attached($this);
            }
        }
    }

    public function create(): Console {
        return $this;
    }

    public function getGrid(): IBuilder {
        return $this->grid;
    }

    private function getResponse(): array {
        $response = ['_file' => $this->grid->getPost('_file'),
            'data' => $this->grid->getPost('data'),
            'divider' => $this->grid->getPost('divider'),
            'header' => $this->grid->getPost('header'),
            'filters' => $this->grid->getPost('filters'),
            'offset' => $this->grid->getPost('offset'),
            'status' => $this->grid->getPost('status'),
            'stop'=>$this->grid->getPost('stop'),
        ];
        if(null == $response['data'] = $this->grid->getPost('data')) {
            $response['data'] = [];
        }
        return $response;
    }

    public function handleDone(): void {
        $this->grid->log('done');
        $service = 'get' . ucfirst($this->grid->getPost('status'));
        $this->presenter->sendResponse(new JsonResponse($this->grid->$service()->done($this->getResponse(), $this)));
    }

    public function handlePrepare(): void {
        $this->grid->log('prepare');
        $data = ['filters' => $this->grid->getPost('filters'),
            'offset' => 0,
            'sort' => $this->grid->getPost('sort'),
            'status' => 'service',
            'stop' => $this->grid->prepare()->getSum()];
        $response = new JsonResponse($this->grid->getService()->prepare($data, $this));
        $this->presenter->sendResponse($response);
    }

    public function handleRun(): void {
        $response = $this->getResponse();
        if ('import' == $response['status']) {
            $service = $this->grid->getImport();
            $path = $this->grid->getImport()->getFile() . $this->grid->getPost('_file');
            $handle = fopen($path, 'r');
            for($i = 0; $i < $speed = $service->speed($this->config['speed']); $i++) {
                fseek($handle, $response['offset']);
                $offset = fgets($handle);
                if($response['stop'] == $response['offset'] = ftell($handle)) {
                    $i = $speed;
                }
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
                $response = $service->run($response, $this);
            }
        /** export */
        } elseif(in_array($response['status'], ['export', 'excel'])) {
            $service = $this->grid->getExport();
            $path = $service->getFile() . '/' . $response['_file'];
            $response['limit'] = $service->speed($this->config['speed']);
            $response['row'] = $this->grid->prepare()->getOffsets();
            $response['sort'] = $this->grid->getPost('sort');
            $response = $service->run($response, $this);
            if('export' == $response['status']) {
                $handle = fopen('nette.safe://' . $path, 'a');
            } else {
                $excel = PHPExcel_IOFactory::load($path);
                $excel->setActiveSheetIndex(0);
                $last = $excel->getActiveSheet()->getHighestRow();
            }
            foreach($response['row'] as $rowId => $cells) {
                foreach($cells as $cellId => $cell) {
                    if($cell instanceof DateTime) {
                        $response['row'][$rowId][$cellId] = $cell->__toString();
                    } else if(empty($this->grid->getAnnotation($cellId, ['unrender', 'hidden', 'unexport'])) && isset($cell['Attributes']) && isset($cell['Attributes']['value'])) {
                        $response['row'][$rowId][$cellId] = $cell['Attributes']['value'];
                    } else if(empty($this->grid->getAnnotation($cellId, ['unrender', 'hidden', 'unexport'])) && !isset($cell['Attributes'])) {
                        $response['row'][$rowId][$cellId] = $cell;
                    } else {
                        unset($response['row'][$rowId][$cellId]);
                    }
                }
                if('export' == $response['status']) {
                    fputs($handle, PHP_EOL . implode(';', $response['row'][$rowId]));
                } else {
                    $last++;
                    $letter = 'a';
                    foreach ($response['row'][$rowId] as $cell) {
                        $excel->getActiveSheet()->SetCellValue($letter++ . $last, $cell);
                    }
                }
            }
            if('export' == $response['status']) {
                fclose($handle);
            } else {
                $writer = new PHPExcel_Writer_Excel2007($excel);
                $writer->save($path);
            }
            $response['offset'] = $response['offset'] + $service->speed($this->config['speed']);
        /** process */
        } else {
            $service = $this->grid->getService();
            if(!empty($response['row'] = $this->grid->prepare()->getOffsets())) {
                $response = $service->run($response, $this);
            }
            $response['offset'] = $response['offset'] + $service->speed($this->config['speed']);
        }
        $setting = $service->getSetting();
        $callbacks = is_object($setting) ? json_decode($setting->callback) : [];
        foreach ($callbacks as $callbackId => $callback) {
            $sanitize = preg_replace('/print|echo|exec|call|eval|mysql/', '', $callback);
            eval('function call($response["row"]) {' . $sanitize . '}');
            $response['row'] = call($response['row']);
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }

    public function render(): void {
        $this->template->setFile(__DIR__ . '/../templates/payload.latte');
        $this->template->render();
    }

    public function run(): IConsoleFactory {
        return $this;
    }

    public function setGrid(IBuilder $grid): IConsoleFactory {
        $this->grid = $grid;
        return $this;
    }


}

interface IConsoleFactory {

    public function create(): Console;

}
