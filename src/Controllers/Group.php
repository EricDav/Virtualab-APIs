<?php 
    namespace VirtualLab\Controllers;

    use VirtualLab\Models\Model;
    use VirtualLab\Helpers\Helper;
    use VirtualLab\Models\Group as GroupModel;
    use VirtualLab\SendMail;

    class Group extends Controller {
        const __ID = '883974';
        const APPROVED = 1;
        const UN_APPROVED = 0;
        const PRIVATE_MODE = 0;
        const PUBLIC_MODE = 1;
        const MODE = array('private' => 0, 'public' => 1);
        public function __construct() {
            parent::__construct();
        }

        public function validate($name=null, $id=null) {
            $errorMessages = array();
            $isValidName = Helper::isValidName($name);

            if ($name && !$isValidName['isValid']) {
                $errorMessages['name'] = $isValidName['message'];
            }

            if ($id && !is_numeric($id)) {
                $errorMessages['group_id'] = 'Invalid group id';
            }

            if (sizeof($errorMessages) > 0) {
                $this->jsonResponse(array('success' => false,  'message' => $errorMessages));
            }
        }

        public function create($request) {
            $name = $request->body->group_name;
            $hash = $request->body->user_hash;
            $productKey = $request->body->product_key;

            $mode = $request->body->mode;
            $this->validate($name);

            if ($mode != 'private' && $mode != 'public') {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid mode',
                    'data' => array()
                ));
            }

            $modeCode = Group::MODE[$mode];

            $this->dbConnection->open();

            $user = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if ($user) {
                // Checks if user has created group with the name provided
                $group = Model::findOne(
                    $this->dbConnection,
                    array('user_id' => $user['id'], 'name' => $name),
                    'groups'
                );


                if ($group) {
                    $this->jsonResponse(array(
                        'success' => false,
                        'message' => 'You have already created a group with this name',
                        'data' => array()
                    ));
                }

                $groupCode = $this->generateRandomId();
                if (!$groupCode) {
                    $this->jsonResponse(array(
                        'success' => false,
                        'message' => 'Unexpected error occurred please try creating group again',
                        'data' => array()
                    ));
                }

                $groupId = Model::create(
                    $this->dbConnection,
                    array('date_created' => gmdate("Y-m-d\ H:i:s"), 'name' => $name, 'user_id' => $user['id'], 'mode' => $modeCode, 
                        'product_code' => ($productKey[8] . $productKey[9]), 
                        'group_code' => $groupCode
                    ),
                    'groups'
                );

                if (!$groupCode) {
                    $this->jsonResponse(array(
                        'success' => false,
                        'message' => 'An error occured while creating group',
                        'data' => array()
                    ));
                }

                $userGroup = Model::create(
                    $this->dbConnection,
                    array('user_id' => $user['id'], 'group_id' => $groupCode, 'approved' => 1, 'is_owner' => 1, 'can_share' => 1, '`exit`' => 0, 'date_created' => gmdate("Y-m-d H:i:s")),
                    'user_groups'
                );

                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Group sucessfully created',
                    'data' => array('group_id' => $groupCode)
                ));
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'User with the hash not found',
                'data' => array()
            ));
        }

        public function generateRandomId() {
            $randomId = (string)rand(1000000000,9999999999);

            // Try up to 5 times to check if id already created
            for ($i = 0; $i < 5; $i++) {
                $group = Model::findOne(
                    $this->dbConnection,
                    array('group_code' => $randomId),
                    'groups'
                );

                if (!$group) {
                    return $randomId;
                }
            }

            return null;
        }

        public function join($request) {
            $hash = $request->body->user_hash;
            $productKey = $request->body->product_key;
            $groupId = $request->body->group_id;

            $this->validate(null, $groupId);
            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            $group = Model::findOne(
                $this->dbConnection,
                array('group_code' => $groupId),
                'groups'
            );
            
            if ($user) {
                $userGroup = Model::findOne(
                    $this->dbConnection,
                    array('group_id' => $groupId, 'user_id' => $user['id']),
                    'user_groups'
                );

                // checks if user has been blocked
                if ($userGroup && $userGroup['is_deleted']) {
                    $this->jsonResponse(array(
                        'success' => false,
                        'message' => 'User has been blocked from this group',
                    ));
                }

                // 
                if ($userGroup && $userGroup['exit']) {
                    // checks 
                    if (
                        Model::update(
                            $this->dbConnection,
                            array('`exit`' => 0),
                            array('user_id' => $user['id'], 'group_id' => $groupId), 
                            'user_groups'
                        )
                    ) {
                        $this->jsonResponse(array(
                            'success' => true,
                            'message' => 'User added back to group',
                        ));
                    }
                }

                if ($group) {
                    if ($userGroup && !$userGroup['exit'] &&  !$userGroup['is_deleted']) {
                        $this->jsonResponse(array(
                            'success' => false,
                            'message' => 'User already a member',
                        ));
                    }

                    Model::create(
                        $this->dbConnection,
                        array('user_id' => $user['id'], 'group_id' => $groupId, 'approved' => $group['mode'], 'is_owner' => 0, 'date_created' => gmdate("Y-m-d H:i:s"), '`exit`' => 0),
                        'user_groups'
                    );

                    $this->jsonResponse(array(
                        'success' => true,
                        'message' => 'User added successfully',
                    ));
                } else {
                    $this->jsonResponse(array(
                        'success' => false,
                        'message' => 'Group not found',
                        'data' => array()
                    ));
                }
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'User not found',
                'data' => array()
            ));
        }

        public function add($request) {
            $hash = $request->body->user_hash;
            $productKey = $request->body->product_key;
            $groupId = $request->body->group_id;
            $username = $request->body->username;
            $this->validate($username, $groupId);

            $this->dbConnection->open();
            $user =  Model::findOne(
                $this->dbConnection,
                array('username' => $username),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User with the username not found',
                ));
            }

            $adminUser = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if ($adminUser) {
                $group = Model::findOne(
                    $this->dbConnection,
                    array('group_code' => $groupId),
                    'groups'
                );

                if ($group) {
                    if ($group['user_id'] != $adminUser['id']) {
                        $this->jsonResponse(array(
                            'success' => false,
                            'message' => 'Can not add user as you are not the owner of the group',
                        ));
                    }

                    Model::create(
                        $this->dbConnection,
                        array('user_id' => $user['id'], 'group_id' => $groupId, 'approved' => $group['mode'], 'date_created' => gmdate("Y-m-d H:i:s"), 'is_owner' => 0),
                        'user_groups'
                    );

                    $this->jsonResponse(array(
                        'success' => true,
                        'message' => 'User added successfully',
                    ));
                } else {
                    $this->jsonResponse(array(
                        'success' => false,
                        'message' => 'Group not found',
                        'data' => array()
                    ));
                }
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Admin user not found',
                'data' => array()
            ));
        }

        public function exit($request) {
            $hash = $request->body->user_hash;
            $productKey = $request->body->product_key;
            $groupId = $request->body->group_id;
            $username = $request->body->username;

            $this->validate(null, $groupId);
            $this->dbConnection->open();

            $user = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User  not found',
                ));
            }

            $group = Model::findOne(
                $this->dbConnection,
                array('group_code' => $groupId),
                'groups'
            );

            if (!$group) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Group  not found',
                ));
            }

            $userToExit = Model::findOne(
                $this->dbConnection,
                array('username' => $username),
                'app_users'
            );

            if (!$userToExit) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'You to exit not found',
                ));
            }

            // Checks if admin is exiting a user
            if ($userToExit['id'] != $user['id']) {

                // if so checks if the requester with the hash is the admin
                if ($user['id'] != $group['user_id']) {
                    $this->jsonResponse(array(
                        'success' => false,
                        'message' => 'You are not the admin of this group',
                    ));
                }
            }

            if ($group['user_id'] == $userToExit['id']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'You are the admin of this group, you can not exit yourself',
                ));
            }

            $userGroup = Model::findOne(
                $this->dbConnection,
                array('user_id' => $userToExit['id'], 'group_id' => $group['group_code']),
                'user_groups'
            );

            if (!$userGroup) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not a memeber of the group',
                ));
            }

            if ($userGroup['exit']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User already exited from the group',
                ));
            }

            if (
                Model::update(
                    $this->dbConnection,
                    array('`exit`' => 1, 'approved' =>  $group['mode']),
                    array('user_id' => $userToExit['id'], 'group_id' => $groupId), 
                    'user_groups'
                )
            ) {
                $particpant = array(
                    'group_id' => $groupId,
                    'username' => $username,
                    'can_share' => $userGroup['can_share'],
                    'approve' => $userGroup['approved'],
                    'exit' => 1,
                    'blocked' => $userGroup['is_deleted'],
                    'entry_date' => strtotime($userGroup['date_created'])
                );
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'User exited from group',
                    'participant' => $particpant
                ));
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Server error',
            ));
        }

        public function remove($request) {
            $hash = $request->body->user_hash;
            $productKey = $request->body->product_key;
            $groupId = $request->body->group_id;
            $username = $request->body->username;
            $this->validate($username, $groupId);

            $this->dbConnection->open();
            $user =  Model::findOne(
                $this->dbConnection,
                array('username' => $username),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User with the username not found',
                ));
            }

            $adminUser = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if (!$adminUser) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Admin user not found',
                ));
            }

            $group = Model::findOne(
                $this->dbConnection,
                array('group_code' => $groupId),
                'groups'
            );

            if (!$group) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Group  not found',
                ));
            }
            
            $userGroup = Model::findOne(
                $this->dbConnection,
                array('user_id' => $user['id'], 'group_id' => $group['group_code']),
                'user_groups'
            );

            if ($userGroup['is_deleted']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User already blocked from the group',
                ));
            }

            if (!$userGroup) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not a memeber of the group',
                ));
            }
            

            if ($group['user_id'] != $adminUser['id']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Can not remove user, you are not the group owner',
                ));
            }

            if (
                Model::update(
                    $this->dbConnection,
                    array('is_deleted' => 1),
                    array('user_id' => $user['id'], 'group_id' => $groupId), 
                    'user_groups'
                )
            ) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'User blocked from group',
                ));
            } 

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Server error',
            ));

        }

        public function edit($request) {
            $hash = $request->body->user_hash;
            $productKey = $request->body->product_key;
            $groupId = $request->body->group_id;
            $name = $request->body->group_name;

            $mode = strtolower($request->body->mode);
            $this->validate($name);

            if ($mode != 'private' && $mode != 'public') {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid mode',
                    'data' => array()
                ));
            }

            $modeCode = Group::MODE[$mode];


            $this->validate(null, $groupId);

            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not found',
                ));
            }

            $group = Model::findOne(
                $this->dbConnection,
                array('group_code' => $groupId),
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
                    'message' => 'Only admin can edit group',
                ));
            }

            if ($name != $group['name']) {
                // Checks if user has created group with the name provided
                $group = Model::findOne(
                    $this->dbConnection,
                    array('user_id' => $user['id'], 'name' => $name),
                    'groups'
                );

                if ($group) {
                    $this->jsonResponse(array(
                        'success' => false,
                        'message' => 'You have already created a group with this name',
                        'data' => array()
                    ));
                }
            }

            $result =  Model::update(
                $this->dbConnection,
                array('name' => $name, 'mode' => $modeCode),
                array('group_code' => $groupId),
                'groups'
            );

            if ($result) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Group edited successfully',
                )); 
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Server error'
            ));
        }
        public function generateRandomTaskICode() {
            $randomId = (string)rand(100000000000,999999999999);

            // Try up to 5 times to check if id already created
            for ($i = 0; $i < 5; $i++) {
                $groupTask = Model::findOne(
                    $this->dbConnection,
                    array('task_code' => $randomId),
                    'group_tasks'
                );

                if (!$groupTask) {
                    return $randomId;
                }
            }

            return null;
        }

        public function addTask($request) {
            $hash = $request->body->user_hash;
            $data = $request->body->data;
            $groupId = $request->body->group_id;
            $deadline = $request->body->deadline;
            $title = $request->body->title;

            if (!is_numeric($deadline)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid deadline',
                ));
            }
            $deadline = (int)$deadline;

            $this->validate(null, $groupId);
            $this->dbConnection->open();
            $taskCode = $this->generateRandomTaskICode();

            if (!$taskCode) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Server error',
                ));
            }

            $user = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not found',
                ));
            }

            // $task = Model::findOne(
            //     $this->dbConnection,
            //     array('title' => $title),
            //     'group_tasks'
            // );

            $userGroups = Model::findOne(
                $this->dbConnection,
                array('user_id' => $user['id'], 'group_id' => $groupId, '`exit`' => 0, 'is_deleted' => 0),
                'user_groups'
            );

            if (!$userGroups) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not a member of the group',
                ));  
            }

            if (!$userGroups['can_share'] || !$userGroups['approved']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not parmitted to share task on group',
                ));
            }

            $group = Model::findOne(
                $this->dbConnection,
                array('group_code' => $groupId),
                'groups'
            );
            $groupName = $group['name'];

            if (!$group) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Group with the id not found',
                ));
            }

            $groupTask = Model::create(
                $this->dbConnection,
                array('user_id' => $user['id'], 
                    'group_id' => $groupId, 
                    'data' => $data, 
                    'task_code' => $taskCode, 
                    'deadline' => gmdate('Y-m-d H:i:s', $deadline),
                    'date_created' => gmdate('Y-m-d H:i:s'),
                    'title' => $title
                ),
                'group_tasks'
            );

            if ($groupTask !== false) {
                $emails = array();
                $groupMembers = GroupModel::getGroupEmails($this->dbConnection, $groupId);
                foreach($groupMembers as $member) {
                    if ($member['email'] != $user['email']) {
                        array_push($emails, $member['email']);
                    }
                }
                $message = '<p style="margin-left:5px;">' . '<b>' . $user['first_name'] . '</b>' . ' just shared a task in' . '<strong>' . $groupName. '</strong>' .  '</p>';
                $mail = new SendMail($emails, $title . " task has been shared to one of your group", $message, true);
                $mail->send();
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successfull',
                ));
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Server error',
            ));
        }

        public function generateRandomResultId() {
            $randomId = (string)rand(1000000000,9999999999);

            // Try up to 5 times to check if id already created
            for ($i = 0; $i < 5; $i++) {
                $group = Model::findOne(
                    $this->dbConnection,
                    array('result_code' => $randomId),
                    'task_results'
                );

                if (!$group) {
                    return $randomId;
                }
            }

            return null;
        }

        public function addTaskResult($request) {
            $hash = $request->body->user_hash;
            $data = $request->body->data;
            $groupId = $request->body->group_id;
            $aggregateScore = $request->body->aggregate_score;
            $taskCode = $request->body->task_code;

            $this->validate(null, $groupId);
            $this->dbConnection->open();
            $resultCode = $this->generateRandomResultId();

            if (!$resultCode) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Server error',
                ));
            }

            if (!is_numeric($taskCode) || strlen($taskCode) != 12) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid task code',
                ));
            }

            $user = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not found',
                ));
            }

            $group = Model::findOne(
                $this->dbConnection,
                array('group_code' => $groupId),
                'groups'
            );

            if (!$group) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Group with the id not found',
                ));
            }

            $task = Model::findOne(
                $this->dbConnection,
                array('task_code' => $taskCode),
                'group_tasks'
            );

            if (!$task) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Task not found',
                ));
            }

            $userResult = Model::findOne(
                $this->dbConnection,
                array('user_id' => $user['id'], 'task_code' => $taskCode, 'group_id' => $groupId),
                'task_results'
            );

            // checks if user already dropped a result for a particular task in a particular group
            if ($userResult) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User already added a result fot this task',
                ));
            }

            // checks if user is a memeber of the group
            $userGroup =  Model::findOne(
                $this->dbConnection,
                array('group_id' => $groupId, 'user_id' => $user['id'], '`exit`' => 0, 'is_deleted' => 0),
                'user_groups'
            );

            if (!$userGroup) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not a member of the group',
                ));
            }

            if (Model::create(
                $this->dbConnection,
                array('user_id' => $user['id'], 'date_created' => gmdate('Y-m-d H:m:s'), 'group_id' => $groupId, 'data' => $data, 'task_code' => $taskCode, 'aggregate_score' => $aggregateScore, 'result_code' => $resultCode),
                'task_results'
            ) !== false) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successfull',
                    'code' => $resultCode
                ));
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Server error',
            ));
        }

        public function fetch($request) {
            date_default_timezone_set('UTC');
            $hash = $request->body->user_hash;
            $groupId = isset($_POST['group_id']) ? $_POST['group_id'] : null;
            $dataToken = isset($_POST['data_token']) ?  $_POST['data_token'] : null;
            $resultCodes = $request->body->result_codes;

            if (!$this->jsonvalidator($resultCodes)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid json data for result codes',
                ));
            }
            $resultCodes = json_decode($resultCodes);

            $this->dbConnection->open();

            $user = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not found',
                ));
            }

            $groups = GroupModel::getGroups(
                $this->dbConnection,
                $user['id'],
                $groupId
            );

            if (sizeof($groups) == 0) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successfull',
                    'data' => [],
                    'data_token' => $newDataToken
                ));
            }

            // var_dump($groups); exit;
            $groupIds = $groupId ? [$groupId] : self::getGroupIds($groups);

            $allUsers = GroupModel::fetchAllUsers(
                $this->dbConnection,
                $groupIds
            );

            $tasks = GroupModel::fetchTasks(
                $this->dbConnection,
                $groupIds
            );

            $results = GroupModel::getResults(
                $this->dbConnection,
                $groupIds
            );

            $users = GroupModel::fetchUsers(
                $this->dbConnection,
                $groupIds
            );

            $rData = array (
                'users' => $users,
                'groups' => array()
            );

            foreach($groups as $group) {
                $groupObj = array();
                $groupObj['group_id'] = $group['group_id'];
                $groupObj['name'] = $group['name'];
                $groupObj['admin'] = self::getUsername($group['admin_id'], $allUsers);
                $mode = $group['mode'] == self::PRIVATE_MODE ? 'private' : 'public';
                $groupObj['mode'] = $mode;
                $groupObj['entry_date'] = strtotime($group['date_created']);
                $groupObj['participants'] = self::getParticipant($allUsers, $group['group_id']);
                $groupObj['tasks'] = self::getGroupTasks($tasks, $group['group_id'], $allUsers, $results, $resultCodes);

                array_push($rData['groups'], $groupObj);
            }

            $newDataToken = md5(json_encode($rData));
            if ($dataToken == $newDataToken) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successfull',
                    'data' => [],
                    'data_token' => $newDataToken
                ));
            }

            $this->jsonResponse(array(
                'success' => true,
                'message' => 'Successfull',
                'data' => $rData,
                'data_token' => $newDataToken
            ));
        }

        public static function getUsername($id, $users) {
            // var_dump($users[1]['username']); var_dump($id); exit; 
            for($i = 0; $i < sizeof($users); $i++) {
                if ($users[$i]['id'] == $id) {
                    $username = $users[$i]['username'];
                    break;
                }
            }

            return $username;
        }

        public static function getParticipant($users, $groupId) {
            $particpant = array();

            foreach($users as $user) {
                if ($user['group_id'] == $groupId) {
                    $p =  array();
                    $p['username'] = $user['username'];
                    $p['can_share'] = $user['can_share'];
                    $p['approved'] = $user['approved'];
                    $p['exit'] = $user['exit'];
                    $p['blocked'] = $user['blocked'];
                    $p['entry_date'] = strtotime($user['date_created']);
                    array_push($particpant, $p);
                }
            }

            return $particpant;
        }

        public static function getGroupTasks($tasks, $groupId, $users, $results, $resultCodes) {
            $groupTasks = array();

            foreach($tasks as $task) {
                if ($task['group_id'] == $groupId) {
                    $t = array();

                    $t['code'] = $task['task_code'];
                    $t['data'] = $task['data'];
                    $t['deadline'] = strtotime($task['deadline']);
                    $t['entry_date'] = strtotime($task['date_created']);
                    $username = self::getUsername($task['user_id'], $users);
                    $t['shared_by'] = $username;
                    $t['results'] = self::getResults($results, $groupId, $resultCodes, $task['task_code']);
                    $t['title'] = $task['title'];
                    array_push($groupTasks, $t);
                }
            }

            return $groupTasks;
        }

        public static function getResults($results, $groupId, $resultCodes, $taskCode) {
            $fResults = array();
            foreach($results as $result) {
               if ($result['group_id'] == $groupId && $result['task_code'] == $taskCode) {
                   $r = array();

                   $r['aggregate_score'] = $result['aggregate_score'];
                   $r['code'] = $result['task_code'];
                   $r['username'] = $result['username'];
                   $r['data'] = $result['data'];
                   $r['entry_date'] = strtotime($result['date_created']);

                   if (!in_array($r, $resultCodes)) {
                       array_push($fResults, $r);
                   }
               }
            }

            return $fResults;
        }

        public static function getGroupIds($groups) {
            $groupIds = array();
            foreach($groups as $group) {
                array_push($groupIds, $group['group_id']);
            }

            return $groupIds;
        }

        public function getBlockedUsers() {

        }

        public function editTask($request) {
            $hash = $request->body->user_hash;
            $data = $request->body->data;
            $taskCode = $request->body->task_code;
            $deadline = $request->body->deadline;
            $title = $request->body->title;

            $this->validate($title, $taskCode);
            $this->dbConnection->open();
            if (!is_numeric($deadline)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid deadline',
                ));
            }
            $deadline = (int)$deadline;

            $user = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not found',
                ));
            }

            $task = Model::findOne(
                $this->dbConnection,
                array('task_code' => $taskCode),
                'group_tasks'
            );

            if (!$task) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Task not found',
                ));
            }

            if ($task['user_id'] != $user['id']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'You do not have the permission to edit this task',
                ));
            }

            $result = Model::update(
                $this->dbConnection,
                array('deadline' => gmdate('Y-m-d H:i:s', $deadline), 'data' => $data, 'title' => $title),
                array('task_code' => $taskCode),
                'group_tasks'
            );

            if ($result) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successful',
                ));
            }

            $this->jsonResponse(array(
                'success' => false,
                'message' => 'Server error',
            ));
        }

        public function approve($request) {
            $hash = $request->body->user_hash;
            $username = $request->body->username;
            $groupId = $request->body->group_id;

            $this->validate(null, $groupId);
            $this->dbConnection->open();

            $adminUser = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );

            if (!$adminUser) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Admin not found',
                ));
            }

            $user = Model::findOne(
                $this->dbConnection,
                 array('username' => $username),
                'app_users'
            );

            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not found',
                ));
            }

            $group = Model::findOne(
                $this->dbConnection,
                array('group_code' => $groupId),
                'groups'
            );

            // Checks if the suppossed admin user with the hash is the group admin
            if (!$group) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Group not found',
                ));
            }
            if ($group['user_id'] != $adminUser['id']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not an admin',
                ));
            }

            // Checks if user is a group member 
            $member =  Model::findOne(
                $this->dbConnection,
                array('group_id' => $groupId, 'user_id' => $user['id'], 'exit' => 0, 'is_deleted' => 0),
                'user_groups'
            );
            if (!$member) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not a member of group',
                ));
            }

           $result = Model::update(
                $this->dbConnection,
                 array('approved' => 1),
                 array('user_id' => $user['id'], 'group_id' => $groupId),
                'user_groups'
            );

           if ($result) {
               $particpant = array(
                    'group_id' => $groupId,
                    'username' => $username,
                    'can_share' => $member['can_share'],
                    'approve' => 1,
                    'exit' => $member['exit'],
                    'blocked' => $member['is_deleted'],
                    'entry_date' => strtotime($member['date_created'])
               );
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successful',
                    'participant' => $particpant
                ));
           }

           $this->jsonResponse(array(
                'success' => false,
                'message' => 'User not an admin',
            ));

        }

        public function contributor($request) {
            $hash = $request->body->user_hash;
            $username = $request->body->username;
            $groupId = $request->body->group_id;
            $canShare = $request->body->can_share;
            $this->validate(null, $groupId);
            $this->dbConnection->open();


            if (!is_numeric($canShare) || ($canShare != 1 && $canShare != 0)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid can share',
                ));
            }

            // Checks if admin ca be found
            $adminUser = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );
            if (!$adminUser) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Admin not found',
                ));
            }


            // check if user can be found
            $user = Model::findOne(
                $this->dbConnection,
                 array('username' => $username),
                'app_users'
            );
            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not found',
                ));
            }



            $group = Model::findOne(
                $this->dbConnection,
                array('group_code' => $groupId),
                'groups'
            );

            // Checks if group exists
            if (!$group) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Group not found',
                ));
            }

            // Checks if the suppossed admin user with the hash is the group admin
            if ($group['user_id'] != $adminUser['id']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not an admin',
                ));
            }

            // Checks if user is a group member 
            $member =  Model::findOne(
                $this->dbConnection,
                array('group_id' => $groupId, 'user_id' => $user['id'], 'exit' => 0, 'is_deleted' => 0),
                'user_groups'
            );
            if (!$member) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not a memeber of group',
                ));
            }

           $result = Model::update(
                $this->dbConnection,
                 array('can_share' => $canShare),
                 array('user_id' => $user['id'], 'group_id' => $groupId),
                'user_groups'
            );

           if ($result) {
                $particpant = array(
                    'group_id' => $groupId,
                    'username' => $username,
                    'can_share' => $canShare,
                    'approve' => $member['approved'],
                    'exit' => $member['exit'],
                    'blocked' => $member['is_deleted'],
                    'entry_date' => strtotime($member['date_created'])
                );
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successful',
                    'participant' => $particpant
                ));
           }

           $this->jsonResponse(array(
                'success' => false,
                'message' => 'User not an admin',
            ));
        }

        public function block($request) {
            $hash = $request->body->user_hash;
            $username = $request->body->username;
            $groupId = $request->body->group_id;
            $block = $request->body->blocked;

            $this->validate(null, $groupId);
            $this->dbConnection->open();


            if (!is_numeric($block) || ($block != 1 && $block != 0)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid blocked',
                ));
            }

            // Checks if admin ca be found
            $adminUser = Model::findOne(
                $this->dbConnection,
                array('hash' => $hash),
                'app_users'
            );
            if (!$adminUser) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Admin not found',
                ));
            }


            // check if user can be found
            $user = Model::findOne(
                $this->dbConnection,
                 array('username' => $username),
                'app_users'
            );
            if (!$user) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not found',
                ));
            }

            if ($user['id'] == $adminUser['id']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Admin can not block itself',
                ));
            }



            $group = Model::findOne(
                $this->dbConnection,
                array('group_code' => $groupId),
                'groups'
            );

            // Checks if group exists
            if (!$group) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Group not found',
                ));
            }

            // Checks if the suppossed admin user with the hash is the group admin
            if ($group['user_id'] != $adminUser['id']) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not an admin',
                ));
            }

            // Checks if user is a group member 
            $member =  Model::findOne(
                $this->dbConnection,
                array('group_id' => $groupId, 'user_id' => $user['id'], 'exit' => 0),
                'user_groups'
            );
            if (!$member) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'User not a memeber of group',
                ));
            }

           $result = Model::update(
                $this->dbConnection,
                 array('is_deleted' => $block),
                 array('user_id' => $user['id'], 'group_id' => $groupId),
                'user_groups'
            );

           if ($result) {
               $block = (int) $block;
                $particpant = array(
                    'group_id' => $groupId,
                    'username' => $username,
                    'can_share' => $member['can_share'],
                    'approve' => $member['approved'],
                    'exit' => $member['exit'],
                    'blocked' => $block,
                    'entry_date' => strtotime($member['date_created'])
                );
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successful',
                    'participant' => $particpant
                ));
           }

           $this->jsonResponse(array(
                'success' => false,
                'message' => 'User not an admin',
            ));
        }
    }
?>
