<?php

namespace Masala;

use Nette\Database\Table\ActiveRow,
    Nette\Http\IRequest;

final class ExportService implements IProcessService {

    /** @var string */
    private $link;

    /** IRequest */
    private $request;

    /** @var ActiveRow */
    private $setting;

    /** @var string */
    private $tempDir;

    public function __construct($tempDir, IRequest $request) {
        $this->tempDir = $tempDir;
        $this->request = $request;
        $url = $this->request->getUrl();
        $this->link = $url->scheme . '://' . $url->host . $url->scriptPath;
    }

    /** @return void */
    public function attached(IReactFormFactory $form) {
    }

    /** @return string */
    public function getFile() {
        return $this->tempDir;
    }

    /** @return ActiveRow */
    public function getSetting() {
        return $this->setting;
    }

    /** @return IProcessService */
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
        return ['link' => $this->link . 'temp/' . $response['file']];
    }

}
