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

    public function attached(IReactFormFactory $form): void { }

    public function done(array $response, IMasalaFactory $masala): array {
        return ['Label' => $this->translatorModel->translate('Click here to download your file.'), 'href' => $this->link . 'temp/' . $response['_file']];
    }
    
    public function getFile(): string {
        return $this->tempDir;
    }

    public function getSetting(): IRow {
        if(null == $this->setting) {
            return new EmptyRow();
        }
    }

    public function prepare(array $response, IMasalaFactory $masala): array {
        return $response;
    }

    public function run(array $response, IMasalaFactory $masala): array {
        return $response;
    }
    
    public function setSetting(IRow $setting): IProcess {
        $this->setting = $setting;
        return $this;
    }

    public function speed(int $speed): int {
        return $speed;
    }
}
