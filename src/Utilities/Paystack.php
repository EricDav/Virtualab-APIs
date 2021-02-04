<?php
    namespace VirtualLab\Utilities;

    class Paystack {
        const PAYSTACK_INITIALIZE_URL = "https://api.paystack.co/transaction/initialize";
        const PAYSTACK_VERIFY_URL = "https://api.paystack.co/transaction/verify/";
        const SECRET_TEST_KEY = 'sk_test_65ac9388bcd60415b66744729a2a522877c95980';

        /**
         * Initialize a transaction 
         * 
         * @return returns a a transaction url, which enables user to pay
         */
        public static function initializeTransaction($email, $amount, $currency, $transactionId) {
            $url = self::PAYSTACK_INITIALIZE_URL;
            $fields = [
              'email' => $email,
              'amount' => $amount,
              'currency' => $currency,
              'reference' => $transactionId
            ];

            $fields_string = http_build_query($fields);
            //open connection
            $ch = curl_init();
            
            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              "Authorization: Bearer " . self::SECRET_TEST_KEY,
              "Cache-Control: no-cache",
            ));
            
            //So that curl_exec returns the contents of the cURL; rather than echoing it
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
            
            //execute post
            $result = curl_exec($ch);

            return $result;
        }

        public static function verifyTransaction($transactionId) {
            $curl = curl_init();
  
            curl_setopt_array($curl, array(
              CURLOPT_URL =>  self::PAYSTACK_VERIFY_URL .  $transactionId,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "GET",
              CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . self::SECRET_TEST_KEY,
                "Cache-Control: no-cache",
              ),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if ($err) {
               return $err;
            } else {
              return $response;
            }
        }
    }

?>