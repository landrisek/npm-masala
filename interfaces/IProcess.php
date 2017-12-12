<?php

namespace Masala;

use Nette\Database\Table\IRow;

interface IProcess {

    /** @return void */
    function attached(IReactFormFactory $form);

    /** @return array */
    function done(array $data, IMasalaFactory $masala);

    /** @return string */
    function getFile();

    /** @return IRow */
    function getSetting();

    /** @return array */
    function prepare(array $response, IMasalaFactory $masala);

    /** @return array */
    function run(array $response, IMasalaFactory $masala);

    /** @return IProcess */
    function setSetting(IRow $setting);

    /** @return int */
    function speed($speed);

}