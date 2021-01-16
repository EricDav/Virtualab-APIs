<?php
    namespace VirtualLab\Controllers;

    use VirtualLab\Helpers\Helper;
    use VirtualLab\Helpers\JWT;
    use VirtualLab\Models\User as UserModel;
    use VirtualLab\Models\Model;
    use VirtualLab\Models\Wallet;
    use VirtualLab\SendMail;

    class User extends Controller {
        const EXP_IN_SEC = 18000;
        const DEFAUL_USERNAME_SUFFIX = '123awer>$#';

        public function __construct() {
            parent::__construct();
        }

        public function create($request) {
            $this->dbConnection->open();
            $errorMessages = $this->validateCreate($request);

            if (sizeof($errorMessages) == 0) {
                $passwordHash = password_hash($request->body->password, PASSWORD_DEFAULT);
                $userId = UserModel::create($this->dbConnection, $request->body->name, $request->body->email, $passwordHash, $request->body->phone_number);
                if ($userId) {
                    $jwt = JWT::generateJWT(json_encode(['email' => $request->body->email, 'name' => $request->body->name, 'id' => $userId, 'exp' => (time()) + User::EXP_IN_SEC]));
                    $this->jsonResponse(array('success' => true, 'message' => 'User created successfully', 'token' => $jwt, 'user' => $user, 'exp' => User::EXP_IN_SEC), Controller::HTTP_OKAY_CODE);
                }
                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
            } else {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages), Controller::HTTP_BAD_REQUEST_CODE);
            }
        }

        public function validateCreate($request) {

            // Start validations of request inputs
            $errorMessages = array();
            $nameArr = explode(' ', $request->body->name);

            $firstName = $nameArr[0];
            $lastName = $nameArr[1];

            $isValidFirstName = Helper::isValidName($firstName);
            $isValidLastName = Helper::isValidName($lastName);
            $isValidEmail = Helper::isValidEmail($request->body->email);
            
            if (!is_numeric($request->body->phone_number) || strlen($request->body->phone_number) != 11) {
                $errorMessages['phoneNumber'] = 'Invalid phone number';
            }

            if (!$isValidEmail['isValid']) {
                $errorMessages['email'] = $isValidEmail['message'];
            }

            if (!$isValidFirstName['isValid']) {
                $errorMessages['firstName'] = $isValidFirstName['message'];
            }

            if (!$isValidLastName['isValid']) {
                $errorMessages['lastName'] = $isValidLastName['message'];
            }

            if (!trim($request->body->password)) {
                $errorMessages['password'] = 'Password is required';
            }
            // Validation ends

            return $errorMessages;
        }

        public function registerAppUser($request) {
            $errorMessages = array();

            $firstName = $request->body->first_name;
            $lastName = $request->body->last_name;
            $email = $request->body->email;
            $country = $request->body->country;
            $productKey = $request->body->product_key;

            $isValidFirstName = Helper::isValidName($firstName);
            $isValidLastName = Helper::isValidName($lastName);
            $isValidCountry = Helper::isValidName($country);
            $isValidEmail = Helper::isValidEmail($request->body->email);

            if (!$isValidEmail['isValid']) {
                $errorMessages['email'] = $isValidEmail['message'];
            }

            if (!$isValidFirstName['isValid']) {
                $errorMessages['first_name'] = $isValidFirstName['message'];
            }

            if (!$isValidLastName['isValid']) {
                $errorMessages['last_name'] = $isValidLastName['message'];
            }

            if (!is_numeric($productKey) || strlen($productKey) != \VirtualLab::PRODUCT_KEY_SIZE) {
                $errorMessages['product_key'] = 'Invalid product key';
            }

            // Check for error messages
            if (sizeof($errorMessages) == 0) {
                $this->dbConnection->open();
                // Checks if user device has been registered
                $device = Model::findOne($this->dbConnection, array('product_key' => $productKey), 'devices');

                
                // If device not found  return failure 
                if (!$device) {
                    $this->jsonResponse(array('success' => false, 'message' => 'Device not registered'));
                }

                $yourCode = Helper::generatePin();
                $userDetails = array(
                    'first_name' => $firstName,
                    'last_name' => $lastName, 
                    'email' => $email,
                    'device_id' => $device['id'],
                    'is_verified' => 0,
                    'token' => $yourCode
                );
                
                $user = Model::findOne($this->dbConnection, array('email' => $request->body->email), 'app_users');
                if (!$user) {            
                    // Register app user 
                    $userId =  Model::create(
                        $this->dbConnection,
                        $userDetails,
                        'app_users'
                    );
            
                    // checks if user was created with the id
                    if (!$userId) {
                        $this->jsonResponse(array('success' => false, 'message' => 'Server error'));
                    }
            
                    // Create user devices
                    Model::create(
                        $this->dbConnection,
                        array('device_id' => $device['id'], 'app_user_id' => $userId),
                        'user_devices'
                    );

                    $message = "<h3>Your verification code: " . $yourCode . "</div>";
                    $mail = new SendMail($email, "Account Verification", $message, true);
                    $mail->send();
                    $this->jsonResponse(array('success' => true, 'message' => 'Check your email address for the verification code and enter it below'));
                } else {
                    //If the user email exists, treat it as if the user formatted the system or
                    // switched to a new device or is trying to re-verify for whatsoever reason.
                    $result =  Model::update(
                        $this->dbConnection,
                        $userDetails,
                        array('id' => $user['id']),
                        'app_users'
                    );

                    if ($result) {
                        $userDevice = Model::findOne(
                            $this->dbConnection,
                            array('app_user_id' => $user['id'], 'device_id' => $device['id']),
                            'user_devices'
                        );

                        if (!$userDevice) {
                            Model::create(
                                $this->dbConnection,
                                array('device_id' => $device['id'], 'app_user_id' => $userId),
                                'user_devices'
                            );
                        }
                        
                        $message = "<h3>Your verification code: " . $yourCode . "</div>";
                        $mail = new SendMail($email, "Account Verification", $message, true);
                        $mail->send();
                        $this->jsonResponse(array('success' => true, 'message' => 'Check your email address for the verification code and enter it below'));
                    }

                }


            } else {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages));
            }
        }

        public function verifyAppUsers($request) {
            $email = $request->body->email;
            $token = $request->body->token;
            $this->dbConnection->open();

            $user = Model::findOne(
                $this->dbConnection,
                array('email' => $email),
                'app_users'
            );

            if ($user['token'] && $user['token'] == $token) {
                $username = $user['first_name'] . $user['last_name'] . $user['id'];
                $hash = $hash = password_hash($username . User:: DEFAUL_USERNAME_SUFFIX, PASSWORD_DEFAULT);
                Model::update(
                    $this->dbConnection,
                    array('username' => $username, 'is_verified' => 1, 'hash' => $hash),
                    array('email' => $email),
                    'app_users'
                );

                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successfully verified',
                    'data' => array('username' => $username, 'hash' => $hash)
                ));
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Token is incorrect',
                'data' => array()
            ));
        }

        public function login($request) {
            $this->dbConnection->open();
            $errorMessages = $this->validateLogin($request->body->email, $request->body->password);

            if (sizeof($errorMessages) > 0) {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages), Controller::HTTP_BAD_REQUEST_CODE);
            }

            $user = UserModel::findOne($this->dbConnection, array('email' => $request->body->email));
            if ($user === 'Server error') {
                $this->jsonResponse(array('success' => false, 'message' => ['Server error']), Controller::HTTP_SERVER_ERROR_CODE);
            }

            if (is_array($user) && password_verify($request->body->password, $user['password'])) {
                $user['password'] = '';
                $jwt = JWT::generateJWT(json_encode(['email' => $user['email'], 'name' => $user['name'], 'id' => $user['id'], 'exp' => (time()) + User::EXP_IN_SEC]));

                $this->jsonResponse(array('success' => true, 'user' => $user, 'message' => 'logged in successfully', 'token' => $jwt, 'exp' =>  User::EXP_IN_SEC),
                Controller::HTTP_OKAY_CODE);
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Invalid username or password'), Controller::HTTP_UNAUTHORIZED_CODE);
        }

        public function validateLogin($email, $password) {
            $errorMessages = array();
            $isValidEmail = Helper::isValidEmail($email);
            // var_dump($isValidEmail); exit;

            if (!$isValidEmail['isValid']) {
                array_push($errorMessages, $isValidEmail['message']);
            }

            if (empty(trim($password))) { 
                array_push($errorMessages, 'Password is required');
            }

            return $errorMessages;
        }

        public function transactions($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->query->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);

            $transactions = Model::find($this->dbConnection, array(
                'user_id' => $tokenPayload->id
            ), 'users_transactions', null, null, 'date_created', 'desc');

            if (is_array($transactions)) {
                $this->jsonResponse(array('success' => true, 'data' => $transactions), 200);
            }
            $this->jsonResponse(array('success' => false, 'message' => 'Internal server error'), 500);
        }

        public function transfer($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);
            if ($req->body->email == $tokenPayload->email) {
                $this->jsonResponse(array('success' => false, 'message' => 'You can transfer to yourself'), Controller::HTTP_BAD_REQUEST_CODE);
            }
            $user = UserModel::findOne($this->dbConnection, array(
                'email' => $req->body->email
            ));
            $this->validateTransfer($req, $user);
            $withdrawBalance = $this->withdraw($tokenPayload->id, $req->body->amount);
            $depositBalance = $this->deposit($user['id'], $req->body->amount);
            $transactions = array(
                array(
                    'user_id' => $tokenPayload->id,
                    'amount' => $req->body->amount,
                    'transaction_type' => 0,
                    'date_created' => gmdate("Y-m-d\ H:i:s"),
                    'transaction' => 'Transfer to ' . $user['email'],
                    'balance' => $withdrawBalance
                ),
                array(
                    'user_id' => $user['id'],
                    'amount' => $req->body->amount,
                    'transaction_type' => 1,
                    'date_created' => gmdate("Y-m-d\ H:i:s"),
                    'transaction' => 'Transfer from ' . $tokenPayload->email,
                    'balance' =>  $depositBalance
                )
            );
            Model::createAll($this->dbConnection, $transactions, 'users_transactions');
            $this->jsonResponse(array('success' => true, 'message' => 'Transfarred successfully'), 200);
        }

        public function validateTransfer($req, $user) {
            if (!$user) {
                $this->jsonResponse(array('success' => false, 'message' => 'User not found'), Controller::HTTP_NOT_FOUND);
            }

            // Checks if user is an admin
            if ($user['role'] != 3) {
                 $this->jsonResponse(array('success' => false, 'message' => 'User not an admin'), Controller::HTTP_BAD_REQUEST_CODE);
            }
        }

        public function verifyPayment($req) {
            $this->dbConnection->open();
            if (!is_numeric($req->body->user_id)) {
                $this->jsonResponse(array('success' => false, 'message' => 'Invalid user id'), Controller::HTTP_BAD_REQUEST_CODE);
            }

            if(!UserModel::findOne($this->dbConnection, array(
                'id' => $req->body->user_id,
                'role' => 3
                ))) {
                $this->jsonResponse(array('success' => false, 'message' => 'User not an admin'), Controller::HTTP_BAD_REQUEST_CODE); 
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $req->body->ref,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer sk_test_65ac9388bcd60415b66744729a2a522877c95980",
                "Cache-Control: no-cache",
                ),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if ($err) {
                echo "cURL Error #:" . $err; exit;
            } else {
                $resObj = json_decode($response);
                if (!$resObj->status) {
                    $this->jsonResponse(array('success' => false, 'message' => 'Something went wrong while verifying'), Controller::HTTP_BAD_REQUEST_CODE);
                    //log error here
                }

                $amount = $resObj->data->amount;
                $amount = substr($amount, 0, strlen($amount) - 2);
                $balance = $this->deposit($req->body->user_id, $amount);
                // Add to the trasanction history of user
                Model::create($this->dbConnection, array(
                    'user_id' => $req->body->user_id,
                    'amount' => $amount,
                    'transaction_type' => 1,
                    'date_created' => gmdate("Y-m-d\ H:i:s"),
                    'transaction' => 'Deposit with paystack',
                    'balance' => $balance
                ), 'users_transactions');
                $this->jsonResponse(array('success' => true, 'message' => 'Deposited successfully'), 200);
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
            $users = UserModel::find($this->dbConnection, array('role' => $req->query->role));
            if (is_array($users)) {
                $this->jsonResponse(array('success' => true, 'data' => $users), 200);
            }
            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
        }

        /**
         * Fetches the user summary like activations count, pins count
         * and also the last ten transactions
         */
        public function userDetails($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->query->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);
            $lastTenTransactions = Model::find($this->dbConnection, array(
                'user_id' => $tokenPayload->id
            ), 'users_transactions', null, 10, 'date_created', 'desc');

            if (!is_array($lastTenTransactions)) {
                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
            }

            $wallet = Wallet::findOne($this->dbConnection, array('user_id' => $tokenPayload->id));
            $details = UserModel::getDetails($this->dbConnection, $tokenPayload->id, $tokenPayload->email);
            if (!is_array($details)) {
                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
            }
            $details['transactions'] = $lastTenTransactions;
            $details['balance'] = $wallet ? $wallet : ['amount' => 0];
            if (is_array($details)) {
                $this->jsonResponse(array('success' => true, 'data' => $details), 200);
            }
            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
        }

        public function getTeachers($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->query->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);
            if (!is_numeric($tokenPayload->id)) {
                $this->jsonResponse(array('success' => false, 'message' => 'Invalid user id'), Controller::HTTP_BAD_REQUEST_CODE);
            }
            $teachers = UserModel::getTeachers($this->dbConnection, $tokenPayload->id);

            if (is_array($teachers)) {
                $this->jsonResponse(array('success' => true, 'data' => $teachers), 200);
            }
            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
        }

        public function validateUpdate($request, $userId) {
            $errorMessages = array();
            $nameArr = explode(' ', $request->body->name);

            $firstName = $nameArr[0];
            $lastName = $nameArr[1];

            $isValidFirstName = Helper::isValidName($firstName);
            $isValidLastName = Helper::isValidName($lastName);
            $isValidEmail = Helper::isValidEmail($request->body->email);
            // var_dump($isValidLastName); exit;

            if (!$isValidEmail['isValid']) {
                $errorMessages['email'] = $isValidEmail['message'];
            }

            if (!$isValidFirstName['isValid']) {
                $errorMessages['firstName'] = $isValidFirstName['message'];
            }

            if (!$isValidLastName['isValid']) {
                $errorMessages['lastName'] = $isValidLastName['message'];
            }

            if (sizeof($errorMessages) == 0) {
                $user = UserModel::findOne($this->dbConnection, array('email' => $request->body->email));

                if ($user && $user['id'] != $userId) {
                    $errorMessages['email'] = 'User with this email already exist';
                }
            }

            return $errorMessages;
        }

        public function update($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);

            $errorMessages = $this->validateUpdate($req, $tokenPayload->id);

            if (sizeof($errorMessages) > 0) {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages), 400);
            }

            $phoneNumber = $req->body->phone_number;
            if (!is_numeric($phoneNumber)) {
                $phoneNumber = '';
            }

            if (UserModel::update($this->dbConnection, [
                'email' => $req->body->email,
                'name' => $req->body->name,
                'phone_number' => $phoneNumber
            ], ['id' => $tokenPayload->id])) {
                $this->jsonResponse(array('success' => true, 'message' => 'Updated success'), 200);
            } else {
                $this->jsonResponse(array('success' => false, 'message' => 'Internal server error'), 500);
            }
        }

        public function updatePassword($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);

            $user = UserModel::findOne($this->dbConnection, ['id' => $tokenPayload->id]);
            if (!$user) {
                $this->jsonResponse(array('success' => false, 'message' => 'User not found'), 404);
            }

            if (password_verify($req->body->old_password, $user['password'])) {
                $passwordHash = password_hash($req->body->new_password, PASSWORD_DEFAULT);

                if (UserModel::update($this->dbConnection, [
                    'password' => $passwordHash,
                ], ['id' => $tokenPayload->id])) {
                    $this->jsonResponse(array('success' => true, 'data' => $teachers), 200);
                } else {
                    $this->jsonResponse(array('success' => false, 'message' => 'Internal server error'), 500);
                }
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Old password not correct'), 400);

        }
    }
?>
