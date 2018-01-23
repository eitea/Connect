<?php

if (!function_exists('stripSymbols')) {
    function stripSymbols($s) {
        $result = "";
        foreach (str_split($s) as $char) {
            if (ctype_alnum($char)) {
                $result = $result . $char;
            }
        }
        return $result;
    }
}


?>