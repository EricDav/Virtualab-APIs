<?php
    namespace VirtualLab\Controllers;

    use VirtualLab\Helpers\JWT;
    use VirtualLab\Models\School as SchoolModel;
    use VirtualLab\Models\ClassRoom;

    class Wallet extends Controller {
        public function __construct() {
            parent::__construct();
        }

        public function deposit($userId, $amount) { 
            $userWallet = WalletModel::findOne($this->dbConnection, array('user_id' => $tokenPayload->id));
            if (!$userWallet) {
                return WalletModel::create($this->dbConnection, array('user_id' => $userId, 'amount' => $amount));
            }
            $updateParam = array('amount' => $userWallet['amount'] + $amountNeeded);
            $whereParam = array('user_id' => $userWallet['user_id']);
            if (!WalletModel::update($this->dbConnection, $updateParam, $whereParam)) {
                $this->jsonResponse(array('success' => true, 'message' => 'Server error occured'), 500);
            }
            return true;
        }

        public function withdraw($userId, $amount) {
            $userWallet = WalletModel::findOne($this->dbConnection, array('user_id' => $userId));
            if ($userWallet['amount'] < $amount) {
                $this->jsonResponse(array('success' => false, 'message' => 'Insufficient balance'), 400);
            }

            $updateParam = array('amount' => $userWallet['amount'] - $amountNeeded);
            $whereParam = array('user_id' => $userWallet['user_id']);
            if (!WalletModel::update($this->dbConnection, $updateParam, $whereParam)) {
                $this->jsonResponse(array('success' => true, 'message' => 'Server error occured'), 500);
            }
            return true;
        }
    }
?>
