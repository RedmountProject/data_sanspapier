<?php
$_POST = array_map('trim', $_POST);
$path_bin = "/var/www/data_sanspapier/bin/socgen/bin/static/response";
$enc_str = $_POST['ENC'];
$params = array();
$params['pathfile'] = '/var/www/data_sanspapier/bin/socgen/param/pathfile';
$params['message'] = escapeshellcmd($enc_str); 

$params_str =  "";

foreach($params as $key=>$value){
        
        $params_str .= " " . $key . "=" . $value;
}
$params_str = trim($params_str);

$result = exec($path_bin . " " . $params_str);
$resarr = explode ("!", $result);
$code = $resarr[1];
$error = $resarr[2];

if ($code != "0"){
        echo "error: " . $error;
}else{
	echo implode("|",$resarr);                
}


?>
