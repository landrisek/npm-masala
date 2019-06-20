<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IController {

    public function actionExport(): void;

    public function actionPage(string $key): void;

    public function actionState(): void;

}
