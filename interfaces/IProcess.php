<?php

namespace Masala;

/** @author Lubomir Andrisek */
use Nette\Database\Table\IRow;

interface IProcess {

    public function attached(IReactFormFactory $form): void;

    public function done(array $data, IMasalaFactory $masala): array;

    public function getFile(): string;

    public function getSetting(): IRow;

    public function prepare(array $response, IMasalaFactory $masala): array;

    public function run(array $response, IMasalaFactory $masala): array;

    public function setSetting(IRow $setting): IProcess;

    public function speed(int $speed): int;

}