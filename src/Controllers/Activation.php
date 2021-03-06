<?php 
    namespace VirtualLab\Controllers;

    use VirtualLab\Helpers\JWT;
    use VirtualLab\Models\Wallet as WalletModel;
    use VirtualLab\Models\Pin as PinModel;
    use VirtualLab\Models\Model;
    use VirtualLab\Helpers\Helper;
    use VirtualLab\Helpers\KeyGen;
    
    class Activation extends Controller {
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

        public function activateByPin($req) {
            $this->validateActivateByPin($req);
            $this->dbConnection->open();
            $this->checkActivation($req->body->product_key);
            $productCode = $req->body->product_key[8] . $req->body->product_key[9];

            $product = Model::findOne($this->dbConnection, array('code' => $productCode), 'products');
            if (!$product) {
                $this->jsonResponse(array('success' => false, 'message' => 'Product not found'), 404);
            }
            $productPrice = $product['price'];

            $pin = PinModel::findOne($this->dbConnection, array('pin' => $req->body->pin));
            if (!$pin) {
                $this->jsonResponse(array('success' => false, 'message' => 'Invalid pin. Pin does not exists'), 404);
            }

            if ($pin['balance'] < $productPrice) {
                $this->jsonResponse(array('success' => false, 'message' => 'Insufficient balance in pin'), 400);
            }
            $newBalance = $pin['balance'] - $productPrice;

            $updateParam = array('balance' => $newBalance);
            $whereParam = array('id' => $pin['id']);

            if (PinModel::update($this->dbConnection, $updateParam, $whereParam)) {
                if(!Model::create($this->dbConnection, array('transaction_date' => gmdate("Y-m-d\ H:i:s"), 
                    'pin_id' => $pin['id'], 'amount' => $productPrice, 'pin_user' => $req->body->pin_user), 'pin_history')) {   
                        $this->jsonResponse(array('success' => false, 'message' => 'Server error from history'), 500);
                }
                
                $activationKey = $this->generateActivationKey($req->body->product_key);
                $activateData = array(
                    'product_key' => $req->body->product_key,
                    'activation_key' => $activationKey,
                    'user_identifier' => $req->body->pin_user,
                    'date_generated' => gmdate("Y-m-d\ H:i:s")
                );

                Model::create($this->dbConnection, $activateData, 'activations');
                $this->jsonResponse(array('success' => true, 'activation_key' => $activationKey), 200);
            }
            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }

        public function validateActivateByPin($req) {
            $regEx = "/^[a-zA-Z0-9]*$/";
            if (!preg_match($regEx, $req->body->product_key) || strlen($req->body->product_key) != \VirtualLab::PRODUCT_KEY_SIZE) {
                $this->jsonResponse(array('success' => false, 'message' => 'Invalid product key'), 400);
            }
            $isValidEmail = Helper::isValidEmail($req->body->pin_user);

            if ($isValidEmail['isValid'] || is_numeric($req->body->pin_user)) {
                return true;
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Invalid pin user phone number or email expected'), 400);
        }

        public function activate($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }

            // Validate product id
            $regEx = "/^[a-zA-Z0-9]*$/";
            if (!preg_match($regEx, $req->body->product_key) || strlen($req->body->product_key) != \VirtualLab::PRODUCT_KEY_SIZE) {
                $this->jsonResponse(array('success' => false, 'message' => 'Invalid product key'), 400);
            }

            $tokenPayload = json_decode($tokenPayload->payload);
            $productCode = $req->body->product_key[8] . $req->body->product_key[9];
            $product = Model::findOne($this->dbConnection, array('code' => $productCode), 'products');
            if (!$product) {
                $this->jsonResponse(array('success' => false, 'message' => 'Product not found'), 404);
            }

            $productPrice = $product['price'];
            $activationKey = $this->generateActivationKey($req->body->product_key);
            $this->updateWallet($productPrice, $tokenPayload->id);
            $activateData = array(
                'product_key' => $req->body->product_key,
                'activation_key' => $activationKey,
                'user_identifier' => $tokenPayload->email,
                'date_generated' => gmdate("Y-m-d\ H:i:s")
            );
            if (Model::create($this->dbConnection, $activateData, 'activations')) {
                $this->jsonResponse(array('success' => true, 'activation_key' => $activationKey), 200);
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }

        /**
         * It updates the user
         * @id the User id
         * @amount The amount we want to subtract from the user wallet
         */
        public function updateWallet($amount, $id) {
            $userWallet = WalletModel::findOne($this->dbConnection, array('user_id' => $id));
            if (!$userWallet || $amount > $userWallet['amount']) {
                $this->jsonResponse(array('success' => false, 'message' => 'Insuffcient balance'), 400);
            }

            //Deduct amount from user wallet
            $updateParam = array('amount' => $userWallet['amount'] - $amount);
            $whereParam = array('user_id' => $userWallet['user_id']);
            if (!WalletModel::update($this->dbConnection, $updateParam, $whereParam)) {
                $this->jsonResponse(array('success' => true, 'message' => 'Server error occured'), 500);
            }
        }

        public function get($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->query->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);

            $activations = Model::find($this->dbConnection, array('user_identifier' => $tokenPayload->email), 'activations');
            if (is_array($activations)) {
                $this->jsonResponse(array('success' => true, 'data' => $activations, 'message' => 'Activations retrieved successfully'), 200);
            }
            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }

        public function generateActivationKey($productKey) {
            return KeyGen::HashKey($productKey.'PL', 'PhysicsLab', 10);
        }

        public function getActivationKey($req) {
            // var_dump($req->body->product_key); exit;
            $this->jsonResponse(array('success' => false, 'product_key' => $req->body->product_key, 'key' => $this->generateActivationKey($req->body->product_key)));
        }
    }

?>