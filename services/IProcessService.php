<?php

namespace Masala;

use Nette\Database\Table\ActiveRow,
    Nette\Application\UI\Presenter;

interface IProcessService {

    /** @return ActiveRow */
    function getSetting();

    /** @return IProcessService */
    function setSetting(ActiveRow $setting);

    /** @return Array */
    function prepare(IMasalaFactory $masala);

    /** @return Array */
    function run(Array $row, Array $rows, IMasalaFactory $masala);

    /** @return Bool */
    function done(Array $step, Presenter $presenter);

    /** @return string */
    function message(IMasalaFactory $masala);
}
