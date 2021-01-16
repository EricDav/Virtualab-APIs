<?php 
    namespace VirtualLab\Controllers;

    use VirtualLab\Models\Model;
    use VirtualLab\Helpers\Helper;

    class Group extends Controller {
        const __ID = '883974';
        const APPROVED = 1;
        const UN_APPROVED = 0;
        public function __construct() {
            parent::__construct();
        }

        public function validate($email, $name=null, $id=null) {
            $errorMessages = array();
            $isValidName = Helper::isValidName($name);
            $isValidEmail = Helper::isValidEmail($email);

            if (!$isValidEmail['isValid']) {
                $errorMessages['email'] = $isValidEmail['message'];
            }

            if ($name && !$isValidName['isValid']) {
                $errorMessages['name'] = $isValidName['message'];
            }

            if ($id && !is_numeric($id)) {
                $errorMessages['group_id'] = 'Invalid group id';
            }

            if (sizeof($errorMessages) > 0) {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages));
            }
        }

        public function create($request) {
            $name = $request->body->group_name;
            $email = $request->body->email;
            $productKey = $request->body->product_key;
            $mode = $request->body->private_mode ? 1 : 0;
            $this->validate($email, $name);

            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('email' => $email),
                'app_users'
            );

            if ($user) {
                $groupId = Model::create(
                    $this->dbConnection,
                    array('date_created' => date("Y-m-d\ H:i:s"), 'name' => $name, 'user_id' => $user['id'], 'mode' => $mode, 'product_key' => $productKey),
                    'groups'
                );

                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Group sucessfully created',
                    'data' => array('group_id' => $groupId)
                ));
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'User with the email not found',
                'data' => array()
            ));
        }

        public function join($request) {
            $emeil = $request->body->email;
            $productKey = $request->body->product_key;
            $groupId = $request->body->group_id;

            $this->validate($email, null, $groupId);
            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('email' => $email),
                'app_users'
            );
            
            if ($user) {
                $group = Model::findOne(
                    $this->dbConnection,
                    array('id' => $groupId),
                    'groups'
                );

                if ($group) {
                    Model::create(
                        $this->dbConnection,
                        array('user_id' => $user['id'], 'group_id' => $group['id'], 'approved' => $group['mode']),
                        'user_groups'
                    );

                    $this->jsonResponse(array(
                        'success' => true,
                        'message' => 'User added successfully',
                    ));
                }
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'User with the email not found',
                'data' => array()
            ));
        }

        public function exit($request) {
            $emeil = $request->body->group_name;
            $productKey = $request->body->product_key;
            $groupId = $request->body->group_id;

            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('email' => $email),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User with the email not found',
                ));
            }

            if (Model::delete($this->dbConnection, array('user_id' => $user['id'], 'group_id' => $groupId), 'user_groups')) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'User deleted from group',
                ));
            } 

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Server error',
            ));
        }

        public function edit($request) {
            $emeil = $request->body->group_name;
            $productKey = $request->body->product_key;
            $groupId = $request->body->group_id;

            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('email' => $email),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User with the email not found',
                ));
            }

            $group = Model::findOne(
                $this->dbConnection,
                array('id' => $groupId),
                'groups'
            );

            if (!$group) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Group with the id not found',
                ));
            }

            if ($user['id'] != $group['user_id']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not the creator of the group',
                ));
            }

            $result =  Model::update(
                $this->dbConnection,
                array('name' => $request->body->name, 'mode' => $request->body->private_mode),
                array('id' => $groupId),
                'groups'
            );

            if ($result) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'User deleted from group',
                )); 
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Server error'
            ));
        }
    }

?>