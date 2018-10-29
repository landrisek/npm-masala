<?php 

namespace Masala;

use Exception;

final class Types {

    public static function float($value) {
        if(!is_float($value)) {
            throw new Exception($value . ' is not float.');
        }
        return $value;
    }

    public static function int($value) {
        if(!is_int($value)) {
            throw new Exception($value . ' is not integer.');
        }
        return $value;
    }

    public static function string($value) {
        if(!is_string($value)) {
            throw new Exception($value . ' is not string.');
        }
        return $value;
    }

}