<?php 
    namespace VirtualLab\Controllers;
    use VirtualLab\Models\Model;
    use VirtualLab\Utilities\Paystack;
    use VirtualLab\Helpers\KeyGen;

    class Payment extends Controller {
        const NAIRA = 'NGN';
        const DOLLAR = 'USD';
        const TRANSACTION_ID_SIZE = 16;
        const PAYMENT_VERIFIED = 1;
        const PAYMENT_UNVERIFIED = 0;
        const AUTHORIZATION_URL = "https://checkout.paystack.com/" ;

        public function __construct() {
            parent::__construct();
        }

        public function checkActivation($productKey) {
            if (!ctype_alnum($productKey) || strlen($productKey) != \VirtualLab::PRODUCT_KEY_SIZE) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid product key',
                    'data' => array()
                ));
            }
        }

        public function initialize($request) {
            $email = $request->body->buyer_email;
            $productKey = $request->body->product_key;
            $name = $request->body->name;
            $productCode = $productKey[8] . $productKey[9];
            $country = $request->body->country;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid email address',
                    'data' => array()
                ));
            }

            $this->dbConnection->open();
            $product = Model::findOne(
                $this->dbConnection,
                array('code' => $productCode),
                'products'
            );
            if(!$product) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid product key. Product can not be deduced from product key.',
                    'data' => array()
                ));
            }
            $amount = $product['price'];
            $this->checkActivation($productKey);

            $device = Model::findOne(
                $this->dbConnection,
                array('product_key' => $productKey),
                'devices'
            );

            if (!$device) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Decive with this ' . $productKey . ' product key not found',
                    'data' => array()
                ));
            }

            // Checks if a transaction has been initiated for this
            // product key
            $transaction = Model::findOne(
                $this->dbConnection,
                array('device_id' => $device['id']),
                'app_transactions'
            );

            // If yes return the transaction details
            if ($transaction) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'You are about to pay via paystack',
                    'data' => array(
                        'url' => self::AUTHORIZATION_URL . $transaction['access_code'],
                        'processor' => 'paystack or google pay',
                        'transaction_id' =>  $transaction['transaction_id']
                    )
                ));
            }

            $currency = strtolower($country) == 'nigeria' ? self::NAIRA : self::DOLLAR;
            $salt = (string)rand(100000000000,999999999999); // Generate a random id for the salt. For this given user
            $transactionId = $this->generateUniqueTransactionId($email.$name, $salt, $productCode);
            if (!$transactionId) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Unable to generate a unique Id',
                    'data' => array()
                ));
            }

            $result = json_decode(Paystack::initializeTransaction($email, $amount.'00', $currency, $transactionId));
            if (!$result->status) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => $result->message,
                    'data' => array()
                ));
            }


            Model::create(
                $this->dbConnection,
                array(
                    'transaction_id' => $transactionId,
                    'product_code' => $productCode,
                    'device_id' => $device['id'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'email' => $email,
                    'name' => $name,
                    'date_created' =>  gmdate("Y-m-d H:i:s"),
                    'is_payment_verified' => self::PAYMENT_UNVERIFIED,
                    'access_code' => $result->data->access_code
                ),
                'app_transactions'
            );
            $this->jsonResponse(array(
                'success' => true,
                'message' => 'You are about to pay via paystack',
                'data' => array(
                    'url' => $result->data->authorization_url,
                    'processor' => 'paystack or google pay',
                    'transaction_id' =>  $transactionId
                )
            ));
        }

        public function verify($request) {
            $transactionId = $request->body->transaction_id;
            $productKey = $request->body->product_key;
            $this->dbConnection->open();
            $this->checkActivation($productKey);
            $this->dbConnection->open();
            $device = Model::findOne(
                $this->dbConnection,
                array('product_key' => $productKey),
                'devices'
            );

            if (!$device || $device['product_key'] != $productKey) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid product key',
                    'data' => array()
                ));
            }
            $transaction = Model::findOne(
                $this->dbConnection,
                array('transaction_id' => $transactionId, 'device_id' => $device['id']),
                'app_transactions'
            );

            if (!$transaction) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Transaction with the product key not found',
                    'data' => array()
                ));
            }


            $result = json_decode(Paystack::verifyTransaction($transactionId));
            // var_dump($result); exit;
            if (!$result->status) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Verification failed',
                    'data' => array()
                ));
            }
            if (strtolower($result->data->status) != 'success') {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User has not made payment.',
                    'data' => array()
                ));
            }

            $amount = $result->data->amount;
            $amount = substr($amount, 0, strlen($amount) - 2);
            $currency = $result->data->currency;

            if ($amount != $transaction['amount'] || $currency != $transaction['currency']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Incorrect amount',
                    'data' => array()
                ));
            }

            $result = Model::update(
                $this->dbConnection,
                array(
                    'is_payment_verified' => self::PAYMENT_VERIFIED,
                    'date_verified' => gmdate("Y-m-d H:i:s"),
                ),
                array(
                    'transaction_id' => $transactionId
                ),
                'app_transactions'
            );

            $activationKey = $this->generateActivationKey($productKey);
            Model::create(
                $this->dbConnection,
                array(
                    'product_key' => $productKey,
                    'activation_key' => $activationKey,
                    'user_identifier' => $transaction['email'],
                    'name' => $transaction['name'],
                    'date_generated' => gmdate("Y-m-d H:i:s"),
                    'method' => 'paystack-app'
                ),
                'activations'
            );
            $this->jsonResponse(array(
                'success' => true,
                'message' => 'Successful',
                'activation_key' => $activationKey
            ));
        }

        public function generateActivationKey($productKey) {
            return KeyGen::HashKey($productKey.'PL', 'PhysicsLab', 10);
        }


        public function generateUniqueTransactionId($id, $salt, $productCode) {
            for ($i = 0; $i < 5; $i++) {
                $transactionId = KeyGen::HashKey($id, $salt, self::TRANSACTION_ID_SIZE - 2) . $productCode;
                $tran = Model::findOne(
                    $this->dbConnection,
                    array('transaction_id' => $transactionId),
                    'app_transactions'
                );

                if (!$tran) {
                    return $transactionId;
                }
            }

            return null;
        }


    }

?>