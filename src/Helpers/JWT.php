<?php 
    namespace VirtualLab\Helpers;
    class JWT {
        const SECRET = '149b1d4a6d08ca8759329dbac00917c85ff981cdede63ab8b25ff0f5dc11b875';
        public static function generateJWT($payload) {
            $header = json_encode([
                'typ' => 'JWT',
                'alg' => 'HS256'
            ]);

            $secret = self::SECRET;

            // Encode Header
            $base64UrlHeader = self::base64UrlEncode($header);

            // Encode Payload
            $base64UrlPayload = self::base64UrlEncode($payload);

            // Create Signature Hash
            $signature = \hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

            // Encode Signature to Base64Url String
            $base64UrlSignature = self::base64UrlEncode($signature);

            // Create JWT
            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            return $jwt;
        }

        public static function base64UrlEncode($text) {
            return str_replace(
                ['+', '/', '='],
                ['-', '_', ''],
                \base64_encode($text)
            );
        }
    }

?>