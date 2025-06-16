<?php
/*
  Virtualine Request Library

    https://virtualine.net
*/

namespace Virtualine\Http\Helpers;

class Utils {

    //starts with
    public static function startsWith($haystack, $needle) {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
}