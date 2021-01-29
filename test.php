<?php           $groupIds = ['123', '456'];

// echo strtotime('	
// 2021-01-29 00:00:00
// ');
// echo htmlentities("Hooke's");exit;

$a = filter_var("Hooke's", FILTER_SANITIZE_STRING);
echo utf8_decode($a);
// var_dump(date('Y-m-d H:i:s', 1611701211));

?>