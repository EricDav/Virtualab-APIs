<?php
    namespace VirtualLab\Controllers;

    use VirtualLab\Helpers\Helper;
    use VirtualLab\Helpers\JWT;
    use VirtualLab\Models\User as UserModel;

    class User extends Controller {
        public function __construct() {
            parent::__construct();
        }

        public function create($request) {
            $this->dbConnection->open();
            $errorMessages = $this->validateCreate($request);
            if (sizeof($errorMessages) == 0) {
                $passwordHash = password_hash($request->body->password, PASSWORD_DEFAULT);
                $userId = UserModel::create($this->dbConnection, $request->body->name, $request->body->email, $passwordHash);
                if ($userId) {
                    $jwt = JWT::generateJWT(json_encode(['email' => $request->body->email, 'name' => $request->body->name, 'id' => $userId]));
                    $this->jsonResponse(array('success' => true, 'message' => 'User created successfully', 'token' => $jwt), Controller::HTTP_OKAY_CODE);
                }

                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
            } else {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages), Controller::HTTP_BAD_REQUEST_CODE);
            }
        }

        public function validateCreate($request) {
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

            if (!trim($request->body->password)) {
                $errorMessages['password'] = 'Password is required';
            }

            if (sizeof($errorMessages) == 0) {
                $user = UserModel::findOne($this->dbConnection, array('email' => $request->body->email));

                if ($user) {
                    $errorMessages['email'] = 'User with this email already exist';
                }
            }

            return $errorMessages;
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
                $jwt = JWT::generateJWT(json_encode(['email' => $user['email'], 'name' => $user['name'], 'id' => $user['id'], 'exp' => (time()) + 360000]));

                $this->jsonResponse(array('success' => true, 'message' => 'logged in successfully', 'token' => $jwt),
                Controller::HTTP_OKAY_CODE);
            }

            $this->jsonResponse(array('success' => false, 'message' => ['Invalid username or password']), Controller::HTTP_UNAUTHORIZED_CODE);
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
    }

?>