<?php

namespace Masala;

use Nette\Database\Table\IRow;

interface IProcess {

    /** @return void */
    public function attached(IReactFormFactory $form);

    /** @return array */
    public function done(array $data, IMasalaFactory $masala);

    /** @return string */
    public function getFile();

    /** @return IRow */
    public function getSetting();

    /** @return array */
    public function prepare(array $response, IMasalaFactory $masala);

    /** @return array */
    public function run(array $response, IMasalaFactory $masala);

    /** @return IProcess */
    public function setSetting(IRow $setting);

    /** @return int */
    public function speed($speed);

}