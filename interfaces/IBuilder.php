<?php

namespace Masala;

interface IBuilder {

    /** @return IBuilder */
    public function getOffsets();

    /** @return IBuilder */
    public function getSum();

    /** @return IBuilder */
    public function prepare();

}
