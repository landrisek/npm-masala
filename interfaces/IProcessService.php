<?php

namespace Masala;

use Nette\Database\Table\ActiveRow;

interface IProcessService {

    /** @return void */
    function attached(IReactFormFactory $form);

    /** @return ActiveRow */
    function getSetting();

    /** @return IProcessService */
    function setSetting(ActiveRow $setting);

    /** @return string */
    function getFile();

    /** @return array */
    function prepare(array $response, IMasalaFactory $masala);

    /** @return array */
    function run(array $response, IMasalaFactory $masala);

    /** @return array */
    function done(array $data, IMasalaFactory $masala);

}
