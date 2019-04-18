<?php

namespace Masala;

/** @author Lubomir Andrisek */
final class Strings {

    public static function escape(string $tags): string {
        return trim(strip_tags(trim(html_entity_decode($tags,   ENT_QUOTES, 'UTF-8'), '\xc2\xa0')));
    }

    public static function trim(string $tags): string {
        return preg_replace('/\<p\>[\s+]/', '<p>', preg_replace('/\<p\>\<\/p\>/', '', $tags));
    }
}
