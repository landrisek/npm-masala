<?php

namespace Masala;

interface IRowBuilder {

    /** @return int */
    function add(array $data);

    /** @return int */
    function delete();
    
    /** @return int */
    function update(array $primary, array $data);
    
    /** @return array */
    function getColumns();

    /** @return array */
    function getConfig($key);

}
