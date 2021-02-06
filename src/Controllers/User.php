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
        const TOKEN_EXPIRATION_TIME = 60; //IN MINUTES
        const VERIFICATION_TOKEN_EXPIRATION_TIME = 1440; //IN MINUTES 
        const VERIFIED_CODE = 1;
        const UN_VERIFIED_CODE = 0;

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
                    $user = array(
                        'id' => $userId,
                        'name' => $request->body->name,
                        'email' => $request->body->email,
                        'phone_number' => $request->body->phone_number,
                        'role' => 1
                    );
                    $yourCode = Helper::generatePin();
                    $this->sendVerificationCode($request->body->email, $yourCode, $user['id']);
                    // $jwt = JWT::generateJWT(json_encode(['email' => $request->body->email, 'name' => $request->body->name, 'id' => $userId, 'exp' => (time()) + User::EXP_IN_SEC]));
                    $this->jsonResponse(array('success' => true, 'message' => 'User created successfully', 'user' => $user, 'exp' => User::EXP_IN_SEC), Controller::HTTP_OKAY_CODE);
                }
                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
            } else {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages), Controller::HTTP_BAD_REQUEST_CODE);
            }
        }

        public function validateCreate($request) {

            // Start validations of request inputs
            $errorMessages = array();
            $name = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $request->body->name);
            $name = rtrim($name);

            $nameArr = explode(' ', $name);

            $firstName = $nameArr[0];
            $lastName = $nameArr[1];

            $isValidFirstName = Helper::isValidName($firstName);
            $isValidLastName = Helper::isValidName($lastName);
            $isValidEmail = Helper::isValidEmail($request->body->email);
        

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

            if (sizeof($errorMessages) == 0) {
                $user = Model::findOne($this->dbConnection, array('email' => $request->body->email), 'users');
                if ($user) {
                    $errorMessages['email'] = 'Email address already exists';
                }
            }

            return $errorMessages;
        }

        public function registerAppUser($request) {
            $errorMessages = array();

            $firstName = $request->body->first_name;
            $lastName = $request->body->last_name;
            $email = strtolower($request->body->email);
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

            if (strlen($productKey) != \VirtualLab::PRODUCT_KEY_SIZE) {
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
                    'country' => $country,
                    'device_id' => $device['id'],
                    'is_verified' => 0,
                    'date_created' => gmdate('Y-m-d H:i:s'),
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

                    $message = "<h3>Your verification code: " . $yourCode . "</h3>";
                    $message .= '<p style="margin-left:5px;">Someone is trying to verify his/her profile on the PVL(Physics Virtual Laboratory) application. If you did not initiate this process ignore this mail and no action will be taken on your profile.</p>';
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
                                array('device_id' => $device['id'], 'app_user_id' => $user['id']),
                                'user_devices'
                            );
                        }

                        $message = '<h3 style="margin-left:5px;">Your verification code: ' . $yourCode . "</h3>";
                        $message .= '<p style="margin-left:5px;">Someone is trying to verify his/her profile on the PVL(Physics Virtual Laboratory) application. If you did not initiate this process ignore this mail and no action will be taken on your profile.</p>';
                        $mail = new SendMail($email, "Account Verification", $message);
                        $mail->send();
                        $this->jsonResponse(array('success' => true, 'message' => 'Check your email address for the verification code and enter it below'));
                    }

                }


            } else {
                $msg = $this->getMessage($errorMessages);
                $this->jsonResponse(array('success' => false, 'message' => $msg, 'data' => $errorMessages));
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

                $username = strtolower($user['first_name'] . $user['last_name']) . $user['id'];
                if ($user['username']) {
                    $hash = $user['hash'];
                    $updateData = array('is_verified' => 1);
                } else {
                    $hash = password_hash($username . User:: DEFAUL_USERNAME_SUFFIX, PASSWORD_DEFAULT);
                    $updateData = array('username' => $username, 'is_verified' => 1, 'hash' => $hash);
                }

                Model::update(
                    $this->dbConnection,
                    $updateData,
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

        public function updateAppUser($request) {
            $errorMessages = array();

            $firstName = $request->body->first_name;
            $lastName = $request->body->last_name;
            $country = $request->body->country;

            $isValidFirstName = Helper::isValidName($firstName);
            $isValidLastName = Helper::isValidName($lastName);
            $isValidCountry = Helper::isValidName($country);

            if (!$isValidCountry['isValid']) {
                $errorMessages['country'] = $isValidEmail['message'];
            }

            if (!$isValidFirstName['isValid']) {
                $errorMessages['first_name'] = $isValidFirstName['message'];
            }

            if (!$isValidLastName['isValid']) {
                $errorMessages['last_name'] = $isValidLastName['message'];
            }
            if (sizeof($errorMessages) == 0) {
                $this->dbConnection->open();
                $user = Model::findOne(
                    $this->dbConnection,
                    array('hash' => $request->body->user_hash),
                    'app_users'
                );

                if (!$user) {
                    $this->jsonResponse(array('success' => false, 'message' => 'User not found'));
                }

                $updateData = array(
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'country' => $country
                );

                if (
                    Model::update(
                        $this->dbConnection,
                        $updateData,
                        array('id' => $user['id']),
                        'app_users'
                    )
                ) {
                    $this->jsonResponse(array('success' => true, 'message' => 'Successfully Update User'));
                } else {
                    $this->jsonResponse(array('success' => false, 'message' => 'Server error'));
                }
            }

            $this->jsonResponse(array('success' => false, 'message' => $this->getMessage($errorMessages), 'data' => $errorMessages));
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
                $jwt = $user['is_verified'] ? JWT::generateJWT(json_encode(['email' => $user['email'], 'name' => $user['name'], 'id' => $user['id'], 'exp' => (time()) + User::EXP_IN_SEC])) : null;

                if (!$jwt) {
                    $yourCode = Helper::generatePin();
                    $this->sendVerificationCode($request->body->email, $yourCode, $user['id']);
                }
                $this->jsonResponse(array('success' => true, 'user' => $user, 'message' => 'logged in successfully', 'token' => $jwt, 'exp' =>  self::EXP_IN_SEC),
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

                $user = UserModel::findOne($this->dbConnection, array('phone_number' => $request->body->phone_number));

                if ($user && $user['id'] != $userId) {
                    $errorMessages['phone_number'] = 'User with this phone number already exists';
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

        public function feedback($request) {
            $name = $request->body->name;
            $email = $request->body->email;
            $message = $request->body->message;
            $productKey = $request->body->product_key;
            $this->dbConnection->open();
            $id = Model::create(
                $this->dbConnection,
                array(
                    'name' => $name,
                    'email' => $email,
                    'message' => $message,
                    'product_key' => $productKey
                ),
                'feedbacks'
            );

            if ($id) {
                $this->jsonResponse(array('success' => true, 'message' => 'Thanks for your feedback. '));
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Internal server error'), 500);
        }

        public function getMinutesDiffFromNow($dateStr){
            $startDate = new \DateTime($dateStr);
            $sinceStart = $startDate->diff(new \DateTime(gmdate("Y-m-d\ H:i:s")));
    
            $minutes = $sinceStart->days * 24 * 60;
            $minutes += $sinceStart->h * 60;
            $minutes += $sinceStart->i;
            
            return $minutes;
        }

        public function initiateForgotPassword($request) {
            $email = $request->body->email;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid email address',
                    'data' => array()
                ));
            }

            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('email' => $email),
                'users'
            );

            if(!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User with that email does not exist.',
                    'data' => array()
                ));
            }

            $yourCode = Helper::generatePin();

            Model::update(
                $this->dbConnection,
                array('token' => $yourCode, 'token_created_at' => gmdate("Y-m-d\ H:i:s")),
                array('id' => $user['id']),
                'users'
            );

            $this->sendToken($email, $yourCode);
            $this->jsonResponse(array(
                'success' => true,
                'message' => 'A 6 digit code has been sent to your email. Enter the code below and reset your password',
                'data' => array()
            ));
        }

        public function sendToken($email, $yourCode) {
            $message = "<h3>Your Forgot password code: " . $yourCode . "</h3>";
            $message .='<div><br>This token is only valid for 60 minutes.</div>';
            $message .= '<br><p style="margin-left:5px;">Someone is trying to change your password, if this not you ignore this email and no action will be taken.</p>';
            $mail = new SendMail($email, "Forgot Password", $message, true);
            $mail->send();
        }

        public function sendVerificationCode($email, $yourCode, $userId) {
            Model::update(
                $this->dbConnection,
                array('is_verified_token' => $yourCode, 'is_verified_token_created_at' => gmdate("Y-m-d\ H:i:s")),
                array('id' => $userId),
                'users'
            );
            $message = "<h3>Your verification code: " . $yourCode . "</h3>";
            $message .='<div><br>This token is only valid for 24 hours.</div>';
            // $message .= '<br><p style="margin-left:5px;">Someone is trying to change your password, if this not you ignore this email and no action will be taken.</p>';
            $mail = new SendMail($email, "Forgot Password", $message, true);
            $mail->send();
        }

        public function verifyForgotPassword($request) {
            $email = $request->body->email;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid email address',
                    'data' => array()
                ));
            }

            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('email' => $email),
                'users'
            );

            if(!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User with that email does not exist.',
                    'data' => array()
                ));
            }

            if ($user['token'] != $request->body->token) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid token.',
                    'data' => array()
                ));
            }

            $minutesSinceWhenTokenIsSent = $this->getMinutesDiffFromNow($user['token_created_at']);

            if ($minutesSinceWhenTokenIsSent > self::TOKEN_EXPIRATION_TIME) {
                Model::update(
                    $this->dbConnection,
                    array('token' => $yourCode),
                    array('id' => $user['id'], 'token_created_at' => gmdate("Y-m-d\ H:i:s")),
                    'users'
                );
                $this->sendToken($email, $yourCode);
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Token has expired. A new token has been sent to your email.',
                    'data' => array()
                ));
            }

            $passwordHash = password_hash($request->body->password, PASSWORD_DEFAULT);
            Model::update(
                $this->dbConnection,
                array('password' => $passwordHash),
                array('id' => $user['id']),
                'users'
            );
            $this->jsonResponse(array(
                'success' => true,
                'message' => 'Password updated successfully',
                'data' => array()
            ));
        }

        public function sendVerificationToken($request) {
            $email = $request->body->email;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid email address',
                    'data' => array()
                ));
            }

            $yourCode = Helper::generatePin();
            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('email' => $email),
                'users'
            );

            if(!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User with that email does not exist.',
                    'data' => array()
                ));
            }

            $this->sendVerificationCode($email, $yourCode, $user['id']);
            $this->jsonResponse(array(
                'success' => true,
                'message' => 'A code has been sent to your email.',
                'data' => array()
            ));
        }

        public function verifyUser($request) {
            $email = $request->body->email;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid email address',
                    'data' => array()
                ));
            }

            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('email' => $email),
                'users'
            );

            if(!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User with that email does not exist.',
                    'data' => array()
                ));
            }

            if ($user['is_verified_token'] != $request->body->token) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid verification code.',
                    'data' => array()
                ));
            }

            $minutesSinceWhenTokenIsSent = $this->getMinutesDiffFromNow($user['is_verified_token_created_at']);

            if ($minutesSinceWhenTokenIsSent > self::VERIFICATION_TOKEN_EXPIRATION_TIME) {
                $this->sendVerificationCode($email, $yourCode, $user['id']);
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Verification code has expired. A new code has been sent to your email.',
                    'data' => array()
                ));
            }

            Model::update(
                $this->dbConnection,
                array('is_verified' => self::VERIFIED_CODE),
                array('id' => $user['id']),
                'users'
            );
            $user['password'] = '';
            $user['is_verified_token'] = '';
            $jwt = JWT::generateJWT(json_encode(['email' => $user['email'], 'name' => $user['name'], 'id' => $user['id'], 'exp' => (time()) + self::EXP_IN_SEC]));
            $this->jsonResponse(array(
                'success' => true,
                'message' => 'Your account has been verified successfully',
                'user' => $user,
                'token' => $jwt,
                'exp' => self::EXP_IN_SEC
            ));
        }

        public function contact($request) {
            $email = $request->body->email;
            $subject = $request->body->subject;
            $message = $request->body->message;
            $name = isset($_POST['name']) ? $_POST['name'] : null;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'email' => $email,
                    'message' => 'Invalid email address',
                    'data' => array()
                ));
            }
            $this->dbConnection->open();
            Model::create(
                $this->dbConnection,
                array(
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message
                ),
                'contacts'
            );

            $this->jsonResponse(array(
                'success' => true,
                'message' => 'Message sent successfully'
            ));
        }
    }
?>
