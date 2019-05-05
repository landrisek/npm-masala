<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IBuilderFactory {

    public function clone(): IBuilderFactory;

    public function handleExport(): void;

    public function handlePage(): void;

    public function handleState(): void;

    public function props(): array;

}
