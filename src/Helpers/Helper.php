<?php
    namespace VirtualLab\Helpers;

    class Helper {
        public static function isValidName($name) {
            if (empty(trim($name))) {
                return Array("isValid" => false, "message" => 'Name is required');
            } else if (!preg_match("/^[a-zA-Z0-9 ]*$/",$name)) {
                return Array("isValid" => false, "message" => 'Name must contain only letters, numbers and white space');
            } else if (strlen($name) > 50) {
                return Array("isValid" => false, "message" => "Name can not be more than 50 characters");
            } else {
                return Array("isValid" => true, "message" => "");
            }
        }

        public static function isValidResident($resident) {
            if (empty(trim($resident))) {
                return Array("isValid" => false, "message" => 'Resident is required');
            } else if (!preg_match("/^[a-zA-Z0-9 ]*$/",$resident)) {
                return Array("isValid" => false, "message" => 'Resident must contain only letters, numbers and white space');
            } else if (strlen($resident) > 125) {
                return Array("isValid" => false, "message" => "Resident can not be more than 125 characters");
            } else {
                return Array("isValid" => true, "message" => "");
            }
        }

        public static function isValidEmail($email){
            if (filter_var($email, FILTER_VALIDATE_EMAIL)){
                return Array("isValid" => true, "message" => "");
            } else {
                return Array("isValid" => false, "message" => 'Email address is invalid');
            }
        }

        public static function generatePin() {
            $pins = '';
            $tmp = mt_rand(1,9);
            $j = 0;
            do {
                $tmp .= mt_rand(0, 9);
            } while(++$j < 5);
                
            return $tmp;
        }
    }
?>
