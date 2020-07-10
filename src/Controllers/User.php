<?php
    namespace VirtualLab\Controllers;

    use VirtualLab\Helpers\Helper;
    use VirtualLab\Models\User as UserModel;

    class User extends Controller {
        public function __construct() {
            parent::__construct();
        }

        public function create($request) {
            $this->dbConnection->open();
            $errorMessages = $this->validateCreate($request);
            if (sizeof($errorMessages) == 0) {
                $response = UserModel::create($this->dbConnection, $request->body->name, $request->body->email, $request->body->password);
                if ($response) {
                    $this->jsonResponse(array('success' => true, 'message' => 'User created successfully'), Controller::HTTP_OKAY_CODE);
                }

                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
            } else {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages), Controller::HTTP_BAD_REQUEST_CODE);
            }
        }

        public function validateCreate($request) {
            $errorMessages = array();
            $isValidEmail = Helper::isValidName($request->body->name);
            $isValidName = Helper::isValidEmail($request->body->email);

            if (!$isValidEmail['isValid']) {
                array_push($errorMessages, $isValidEmail['message']);
            }

            if (!$isValidName['isValid']) {
                array_push($errorMessages, $isValidName['message']);
            }

            if (!$request->body->password) {
                array_push($errorMessages, 'Password is required');
            }

            if (sizeof($errorMessages) == 0) {
                $user = UserModel::findOne($this->dbConnection, $request->body->email, 'email');

                if ($user) {
                    array_push($errorMessages, 'User with the email already exist');
                }
            }

            return $errorMessages;

        }
    }

?>