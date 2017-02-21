<?php

namespace Masala;

use Nette\Database\Table\ActiveRow,
    Nette\Localization\ITranslator,
    Nette\Application\UI\Presenter,
    Nette\Http\IRequest;

final class ExportService implements IProcessService {

    /** @var ITranslator */
    private $translatorModel;

    /** IRequest */
    private $request;

    /** @var ActiveRow */
    private $setting;

    /** @var string */
    private $directory;

    /** @var string */
    private $link;
    
    public function __construct($tempDir, ITranslator $translatorModel, IRequest $request) {
        $this->translatorModel = $translatorModel;
        $this->directory = $tempDir;
        $this->request = $request;
        $url = $this->request->getUrl();
        $this->link = $url->scheme . '://' . $url->host . '/' . $url->scriptPath;
    }

    /** getters */
    public function getSetting() {
        return $this->setting;
    }

    /** setters */
    public function setSetting(ActiveRow $setting) {
        $this->setting = $setting;
        return $this;
    }

    /** process methods */
    public function prepare(IMasalaFactory $masala) {
        $sum = $masala->getGrid()->getSum();
        $folder = $this->directory . '/' . $masala->getName() . '/export';
        !file_exists($folder) ? mkdir($folder, 0755, true) : null;
        $file = $folder . '/' . md5($masala->presenter->getName() . $masala->presenter->getAction() . $masala->presenter->getUser()->getIdentity()->getId()) . '.csv';
        file_put_contents($file, implode(',', $this->request->getPost('header')));
        return $sum;
    }

    public function run(Array $row, Array $rows, IMasalaFactory $masala) {
        $folder = $this->directory . '/' . $masala->getName() . '/export';
        $file = $folder . '/' . md5($masala->presenter->getName() . $masala->presenter->getAction() . $masala->presenter->getUser()->getIdentity()->getId()) . '.csv';
        $handle = fopen($file, 'a');
        fputs($handle, PHP_EOL . implode(',', $row) . ',');
        fclose($handle);
        $rows['status'] = 'export';
        return $rows;
    }

    public function done(Array $rows, Presenter $presenter) {
        return ['status'=>'export'];
    }

    public function message(IMasalaFactory $masala) {
        $link = $this->link . 'temp/' . $masala->getName() . '/export/' . md5($masala->presenter->getName() . $masala->presenter->getAction() . $masala->presenter->getUser()->getIdentity()->getId()) . '.csv';
        return '<a href="' . $link . '">' . $this->translatorModel->translate('Click here to download your file.') . '<a/>';
    }

}
