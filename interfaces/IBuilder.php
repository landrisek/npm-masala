<?php

namespace Masala;

interface IBuilder {

    /** @return IBuilder */
    function filter(Array $view = []);
    
}
