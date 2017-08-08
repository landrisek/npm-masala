<?php

namespace Masala;

use Models\TranslatorModel,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class AddForm extends EditForm {

    public function __construct($jsDir, $upload, IRequest $request, MockService $mockService, TranslatorModel $translatorModel) {
        parent::__construct($jsDir, $request, $translatorModel);
    }
}
