<?php

namespace Masala;

final class Validators {

    public const EMAIL = 'isEmail';
    public static function isEmail(string $value): bool {
	$atom = "[-a-z0-9!#$%&'*+/=?^_`{|}~]"; // RFC 5322 unquoted characters in local-part
	$alpha = "a-z\x80-\xFF"; // superset of IDN
	return (bool) preg_match("(^
			(\"([ !#-[\\]-~]*|\\\\[ -~])+\"|$atom+(\\.$atom+)*)  # quoted or unquoted
			@
			([0-9$alpha]([-0-9$alpha]{0,61}[0-9$alpha])?\\.)+    # domain - RFC 1034
			[$alpha]([-0-9$alpha]{0,17}[$alpha])?                # top domain
		\\z)ix", $value);
    }
    
    public const INTEGER = 'isInteger';
    public static function isInteger($value): bool {
        return is_int($value) || is_string($value) && preg_match('#^-?[0-9]+\z#', $value);
    }
    
    public const NUMBER = 'isNumber';
    public static function isNumber($value): bool {
        return is_float($value) || is_int($value) || is_string($value) && preg_match('#^-?[0-9]*[.]?[0-9]+\z#', $value);
    }

    public const NONE = 'isNone';
    public static function isNone($value): bool {
        return $value == null;
    }

    public const LIST = 'isList';
    public static function isList($value): bool {
        return is_array($value) && (!$value || array_keys($value) === range(0, count($value) - 1));
    }

    public const RANGE = 'isInRange';
    public static function isInRange($value, array $range): bool {
        if ($value === null || !(isset($range[0]) || isset($range[1]))) {
            return false;
	}
	$limit = $range[0] ?? $range[1];
	if (is_string($limit)) {
            $value = (string) $value;
        } elseif ($limit instanceof \DateTimeInterface) {
            if (!$value instanceof \DateTimeInterface) {
                return false;
            }
	} elseif (is_numeric($value)) {
            $value *= 1;
	} else {
            return false;
	}
        return (!isset($range[0]) || ($value >= $range[0])) && (!isset($range[1]) || ($value <= $range[1]));
    }
    
    public const PHONENUMBER = 'isPhoneNumber';
    public static function isPhoneNumber(string $value) {
        preg_match('/[0-9]{9}|' . /** 773139113 */
                    '[0-9]{3}\s[0-9]{3}\s[0-9]{3}\s|' . /** 773 139 113 */ 
                    '\+[0-9]{12}|' . /** +420773139113 */
                    '\+[0-9]{3}\s[0-9]{3}\s[0-9]{3}\s[0-9]{3}\s/', /** +420 773 139 113  */
                $value);
    }
        
    public const UNICODE = 'isUnicode';
    public static function isUnicode($value): bool {
        return is_string($value) && preg_match('##u', $value);
    }
    
    public const URI = 'isUri';
    public static function isUri(string $value): bool {
        return (bool) preg_match('#^[a-z\d+\.-]+:\S+\z#i', $value);
    }
    
    public const URL = 'isUrl';
    public static function isUrl(string $value): bool {
        $alpha = "a-z\x80-\xFF";
	return (bool) preg_match("(^
			https?://(
				(([-_0-9$alpha]+\\.)*                       # subdomain
					[0-9$alpha]([-0-9$alpha]{0,61}[0-9$alpha])?\\.)?  # domain
					[$alpha]([-0-9$alpha]{0,17}[$alpha])?   # top domain
				|\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}  # IPv4
				|\[[0-9a-f:]{3,39}\]                        # IPv6
			)(:\\d{1,5})?                                   # port
			(/\\S*)?                                        # path
		\\z)ix", $value);
    }

    public const TYPE = 'isType';
    public static function isType(string $type): bool {
        return class_exists($type) || interface_exists($type) || trait_exists($type);
    }

}
