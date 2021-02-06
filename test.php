<?php
$charSet = 'fad     g';
$charSet = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $charSet);
$charSet = rtrim($charSet);
echo $charSet; exit;
?>