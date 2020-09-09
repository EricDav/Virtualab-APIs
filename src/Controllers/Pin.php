<?php 
    namespace VirtualLab\Controllers;

    use VirtualLab\Helpers\JWT;
    use VirtualLab\Models\Wallet as WalletModel;
    use VirtualLab\Models\Pin as PinModel;
    use VirtualLab\Models\Model;
    
    class Pin extends Controller {
        public function __construct() {
            parent::__construct();
        }

        public function create($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);
            
            if (is_numeric($req->body->num_pins) && is_numeric($req->body->amount)) {
                if ((int)$req->body->num_pins < 1) {
                    $this->jsonResponse(array('success' => false, 'message' => 'Pins quantity should be more than 0'), 400);
                }

                if ((int)$req->body->amount < 500) {
                    $this->jsonResponse(array('success' => false, 'message' => 'Amount can not be less than 500'), 400);
                }

                $userWallet = WalletModel::findOne($this->dbConnection, array('user_id' => $tokenPayload->id));
                $amountNeeded = (int)$req->body->amount * (int)$req->body->num_pins;
                if (!$userWallet || $amountNeeded > $userWallet['amount']) {
                    $this->jsonResponse(array('success' => false, 'message' => 'Insuffcient balance'), 400);
                }

                //Deduct amount from user wallet before generating pins
                $updateParam = array('amount' => $userWallet['amount'] - $amountNeeded);
                $whereParam = array('user_id' => $userWallet['user_id']);
                if (!WalletModel::update($this->dbConnection, $updateParam, $whereParam)) {
                    $this->jsonResponse(array('success' => true, 'message' => 'Server error occured'), 500);
                }

                // After deduction we go ahead to generate pins
                $pins = $this->generatePins($req->body->num_pins);
                $params = $this->generateParams($pins, $tokenPayload->id, $req->body->amount);
                if (PinModel::createAll($this->dbConnection, $params)) {
                    Model::create($this->dbConnection, array(
                        'user_id' => $tokenPayload->id,
                        'amount' => $amountNeeded,
                        'transaction_type' => 0,
                        'date_created' => gmdate("Y-m-d\ H:i:s"),
                        'transaction' => 'Generate pins',
                        'balance' => $userWallet['amount'] - $amountNeeded
                    ), 'users_transactions');
                    $this->jsonResponse(array('success' => true, 'message' => 'Pins created successfully'), 200);
                }

                $this->jsonResponse(array('success' => true, 'message' => 'Server error occured'), 500);
            }  else {
                $this->jsonResponse(array('success' => true, 'message' => 'Invalid parameters'), 500);
            }
        }

        public function get($request) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($request->query->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);
            $pins = PinModel::find($this->dbConnection, array('user_id' => $tokenPayload->id), null, null, 'date_created', 'desc');
            
            if (is_array($pins)) {
                $this->jsonResponse(array('success' => true, 'data' => $pins, 'message' => 'Pins retrieved successfully'), 200);
            }
            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }

        public function activate($req) {

        }

        public function generateParams($pins, $userId, $amount) {
            $dateCreated = gmdate("Y-m-d\ H:i:s");
            $params = array();

            foreach($pins as $pin) {
                array_push($params, array('pin' => $pin, 'user_id' => $userId,
                    'balance' => $amount, 'date_created' => $dateCreated));
            }

            return $params;

        }

        public function generatePins($numPins) {
            $pins = array();
            for ($i = 0; $i < $numPins; $i++) {
                $j = 0;
                $tmp = mt_rand(1,9);
                do {
                    $tmp .= mt_rand(0, 9);
                } while(++$j < 13);
                
                array_push($pins, $tmp);
            }

            return $pins;
        }

        public function history($req) {
            $this->dbConnection->open();
            if (!is_numeric($req->query->pin_id)) {
                $this->jsonResponse(array('success' => false, 'message' => 'Invalid pin id'), 400);
            }

            $pinHistory = Model::find($this->dbConnection, array('pin_id' => $req->query->pin_id), 'pin_history', null, null, 'transaction_date', 'desc');

            if (is_array($pinHistory)) {
                $this->jsonResponse(array('success' => true, 'data' => $pinHistory, 'message' => 'Pins retrieved successfully'), 200);
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }
    }
?>
