<?php

$_POST = array_map('trim', $_POST);
$path_bin = "/var/www/data_sanspapier/bin/socgen/bin/static/request";
$params = $_POST;
$params['pathfile'] = '/var/www/data_sanspapier/bin/socgen/param/pathfile';

$params_str =  "";

foreach($params as $key=>$value){
	
	$params_str .= " " . $key . "=" . $value;
}
$params_str = trim($params_str);
$params_str = escapeshellcmd($params_str);
$result = exec($path_bin . " ". $params_str);

//On separe les differents champs et on les met dans une variable tableau
$resarr = explode ("!", $result);
//récupération des paramètres
$code = $resarr[1];
$error = $resarr[2];
$message = $resarr[3];

if ($code != "0"){
	echo "error: " . $error;
}else{
	echo change_case_tags($message);	
}


function change_case_tags($string, $action = 'strtolower')
{
    $string = preg_replace('!<([^> ]+)!e', "$action('\\0')", $string);

    if(strpos($string, '=') !== false)
    {
        return $string = preg_replace('!(?:\s([^\s]+)=([\'"])?(.+)\\2)!Uie', "$action(' \\1').'='.str_replace('\\\\\\', '', '\\2').'\\3'.str_replace('\\\\\\', '', '\\2').''", $string);
    }

    return $string;
}


?>
