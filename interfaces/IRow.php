<?php

namespace Masala;

interface IRow {

    /** @return int */
    function add(array $data);

    /** @return int */
    function delete();

    /** @return array */
    function getColumns();

    /** @return array */
    function getConfig($key);
    
    /** @return IRow */
    function table($table);
    
    /** @return int */
    function update(array $data);

    /** @return IRow */
    function where($column, $value, $condition = null);

}
