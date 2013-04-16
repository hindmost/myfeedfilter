<?php

/**
 * Fix javascript file
 * @param string $sFile - filename
 * @param array $aVars - variables to fix - array of 'name'=>'value' pairs
 */
function fixJsFile($sFile, $aVars) {
    if (!is_readable($sFile) || !($cont = file_get_contents($sFile)))
        return;
    foreach ($aVars as $name => $val) {
        if ($s = fixJsVar($cont, $name, $val)) $cont = $s;
    }
    file_put_contents($sFile, $cont);
}

/**
 * Fix javascript variable
 * @param string $sText - javascript code
 * @param string $sName - name of variable
 * @param mixed $val - value of variable
 * @return string
 */
function fixJsVar($sText, $sName, $val) {
    if (!preg_match("/(\n[ \t]*var *".$sName." *= *)[^\r\n]+/u", $sText, $a_m))
        return false;
    $s0 = $a_m[0]; $s1 = $a_m[1];
    $k = stripos($sText, $s0);
    $s_v = is_array($val)?
        ("['". implode("', '", $val). "']") :
        (is_bool($val)? ($val? "true":"false") : (is_int($val)? $val : "'$val'"));
    return
        substr($sText, 0, $k+ strlen($s1)).
        $s_v. ";".
        substr($sText, $k+ strlen($s0));
}
