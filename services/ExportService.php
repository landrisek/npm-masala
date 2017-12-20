<?php

namespace Masala;

use Nette\Database\Table\IRow,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

final class ExportService implements IProcess {

    /** @var string */
    private $link;

    /** @var IRow */
    private $setting;

    /** @var string */
    private $tempDir;

    /** @var ITranslator */
    private $translatorModel;

    public function __construct($tempDir, IRequest $request, ITranslator $translatorModel) {
        $this->tempDir = $tempDir;
        $url = $request->getUrl();
        $this->link = $url->scheme . '://' . $url->host . $url->scriptPath;
        $this->translatorModel = $translatorModel;
    }

    /** @return void */
    public function attached(IReactFormFactory $form) { }

    /** @return array */
    public function done(array $response, IMasalaFactory $masala) {
        return ['label' => $this->translatorModel->translate('Click here to download your file.'), 'href' => $this->link . 'temp/' . $response['_file']];
    }

    /** @return string */
    public function getFile() {
        return $this->tempDir;
    }

    /** @return IRow */
    public function getSetting() {
        return $this->setting;
    }

    /** @return array */
    public function prepare(array $response, IMasalaFactory $masala) {
        return $response;
    }

    /** @return array */
    public function run(array $response, IMasalaFactory $masala) {
        return $response;
    }

    /** @return IProcess */
    public function setSetting(IRow $setting) {
        $this->setting = $setting;
        return $this;
    }

    /** @return int */
    public function speed($speed) {
        return $speed;
    }
}
