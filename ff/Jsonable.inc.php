<?php

/**
 * JSONable object
 */
class Jsonable
{
    /**
     * Get JSON representation of object
     * Note: only the following types are recognized: "string", "int" and "array"
     * @return string
     */
    function toJson() {
        $out = '';
        foreach ($this as $k => $v) {
            $s = is_array($v)?
                (count($v)?
                    '["'. implode('","', array_map(array(self, 'esc'), $v)). '"]' :
                    '[]'
                )
                :
                (is_int($v)? $v : '"'. self::esc($v). '"');
            $out .= ',"'. $k. '":'. $s;
        }
        return '{'. ($out? substr($out, 1) : ''). '}';
    }
    
    function __get($name) {
        return isset($this->$name)? $this->$name : null;
    }

    /**
     * Escape string
     * @param string $s - input string
     * @return string
     */
    static function esc($s) {
        return str_replace('"', '\\"', str_replace('\\', '', $s));
    }
}
