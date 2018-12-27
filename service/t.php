<?php 

namespace service;

use lib\api\fuiou;


function mian(){
    echo 111;
    $fuiouTask = new fuiou();
    echo 222;
    $i = $fuiouTask->freezeToFreeze('13578900987','15678900987','20000');
    echo 333;
    return $i;
}



$i = mian();




print_r($i);
exit;

?>