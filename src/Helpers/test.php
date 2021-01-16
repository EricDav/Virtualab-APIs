<?php
namespace VirtualLab\Helpers;

require_once '../../Virtualab.Class.php';
require_once '../../vendor/autoload.php';

var_dump(KeyGen::HashKey('1', 'PIN', 8));
?>