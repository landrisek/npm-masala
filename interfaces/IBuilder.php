<?php

namespace Masala;

interface IBuilder {

    /** @return IBuilder */
    function getOffsets();

    /** @return IBuilder */
    function getSum();

    /** @return IBuilder */
    function prepare();

}
