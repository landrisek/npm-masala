<?php

namespace Masala;

use Nette\Database\Table\ActiveRow,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

final class ExportService implements IProcess {

    /** @var string */
    private $link;

    /** IRequest */
    private $request;

    /** @var ActiveRow */
    private $setting;

    /** @var string */
    private $tempDir;

    /** @var ITranslator */
    private $translatorModel;

    public function __construct($tempDir, IRequest $request, ITranslator $translatorModel) {
        $this->tempDir = $tempDir;
        $this->request = $request;
        $url = $this->request->getUrl();
        $this->link = $url->scheme . '://' . $url->host . $url->scriptPath;
        $this->translatorModel = $translatorModel;
    }

    /** @return void */
    public function attached(IReactFormFactory $form) { }

    /** @return string */
    public function getFile() {
        return $this->tempDir;
    }

    /** @return ActiveRow */
    public function getSetting() {
        return $this->setting;
    }

    /** @return IProcess */
    public function setSetting(ActiveRow $setting) {
        $this->setting = $setting;
        return $this;
    }

    /** @return array */
    public function prepare(array $response, IMasalaFactory $masala) {
        return $response;
    }

    /** @return array */
    public function run(array $response, IMasalaFactory $masala) {
        return $response;
    }

    /** @return array */
    public function done(array $response, IMasalaFactory $masala) {
        return ['label' => $this->translatorModel->translate('Click here to download your file.'),'href' => $this->link . 'temp/' . $response['file']];
    }

}
