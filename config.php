<?php
// ReactOS Source directory
$ROSDir = 'H:\ReactOS\\';

// Translations folder
$langDir = 'lang';

// We uses this to search origin language files
$originLang = 'US|En';

// Filename extension
$fileExt = 'rc';

// Ignore string list
$ignoreString = array('OK', 'Ok', '&OK', '[OK]\n', 'ReactOS', 'MS Shell Dlg', 'Arial', 'Static', 'STATIC', 'popup');

if (!file_exists($ROSDir))
{
    echo "ReactOS source path <b>$ROSDir</b> does not exist!";
	exit;
}
?>