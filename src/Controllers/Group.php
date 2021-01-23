<?php 
    namespace VirtualLab\Controllers;

    use VirtualLab\Models\Model;
    use VirtualLab\Helpers\Helper;
    use VirtualLab\Models\Group as GroupModel;

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
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages));
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
                    array('date_created' => date("Y-m-d\ H:i:s"), 'name' => $name, 'user_id' => $user['id'], 'mode' => $modeCode, 
                        'product_code' => ($productKey[9] . $productKey[10]), 
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
                    array('user_id' => $user['id'], 'group_id' => $groupCode, 'approved' => 1, 'is_owner' => 1, 'can_share' => 1, '`exit`' => 1),
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
            
            if ($user) {
                $userGroup = Model::findOne(
                    $this->dbConnection,
                    array('group_id' => $groupId, 'user_id' => $user['id']),
                    'user_groups'
                );

                // Checks if user is already a member
                if ($userGroup) {
                    $this->jsonResponse(array(
                        'success' => false,
                        'message' => 'User already a member',
                    ));
                }

                $group = Model::findOne(
                    $this->dbConnection,
                    array('group_code' => $groupId),
                    'groups'
                );

                if ($group) {
                    Model::create(
                        $this->dbConnection,
                        array('user_id' => $user['id'], 'group_id' => $groupId, 'approved' => $group['mode'], 'is_owner' => 0),
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
                        array('user_id' => $user['id'], 'group_id' => $groupId, 'approved' => $group['mode'], 'is_owner' => 0),
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
                    'message' => 'User deleted from group',
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
                    'message' => 'User deleted from group',
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

            $userGroups = Model::findOne(
                $this->dbConnection,
                array('user_id' => $user['id'], 'group_id' => $groupId),
                'user_groups'
            );

            // var_dump($userGroups); exit;

            if (!$userGroups['can_share']) {
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
                    'deadline' => $deadline,
                    'date_created' => gmdate('Y-m-d H:i:s')
                ),
                'group_tasks'
            );


            if ($groupTask !== false) {
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

        public function addTaskResult($request) {
            $hash = $request->body->user_hash;
            $data = $request->body->product_key;
            $groupId = $request->body->group_id;
            $aggregateScore = $request->body->aggregate_score;
            $taskCode = $request->body->task_code;

            $this->validate(null, $groupId);
            $this->dbConnection->open();

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

            // checks if user is a memeber of the group
            $userGroup =  Model::findOne(
                $this->dbConnection,
                array('group_id' => $groupId, 'user_id' => $user['id']),
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
                array('user_id' => $user['id'], 'group_id' => $groupId, 'data' => $data, 'task_code' => $taskCode, 'aggregate_score' => $aggregateScore),
                'task_results'
            ) !== false) {
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

        public function fetch($request) {
            $hash = $request->body->user_hash;
            $data = $request->body->data;
            $groupId = $request->body->group_id;
            $dataToken = $request->body->data_token;
            $resultCodes = $request->body->result_codes;

            if (!$this->jsonvalidator($resultCodes)) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Invalid json data for result codes',
                ));
            }

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

            $groups = GroupModel::getGroups(
                $this->dbConnection,
                $user['id']
            );
            

            // var_dump($groups); exit;
            $groupIds = self::getGroupIds($groups);

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
                $groupObj['username'] = self::getUsername($group['admin_id'], $allUsers);
                $mode = $group['mode'] == self::PRIVATE_MODE ? 'private' : 'public';
                $groupObj['mode'] = $mode;
                $groupObj['entry_date'] = $group['date_created'];
                $groupObj['participant'] = self::getParticipant($allUsers, $group['group_code']);
                $groupObj['tasks'] = self::getGroupTasks($tasks, $group['group_code'], $allUsers, $results, $resultCodes);

                array_push($rData['groups'], $groupObj);
            }

            $newDataToken = md5($rData);
            if (!$dataToken || $dataToken == $newDataToken) {
                $this->jsonResponse(array(
                    'success' => true,
                    'message' => 'Successfull',
                    'data' => []
                ));
            }

            $this->jsonResponse(array(
                'success' => true,
                'message' => 'Successfull',
                'data' => $rData,
                'data_token' => $newDataToken
            ));
        }

        public static function editParticipant($request) {
            $hash = $request->body->user_hash;
            $data = $request->body->data;
            $groupId =  $request->body->groupId;
            $dataObj = json_decode($data);
            $username = $dataObj->username;
            $canShare = $dataObj->can_share ? 1 : 0;
            $exit = $dataObj->exit ? 1 : 0;
            $approved = $dataObj->approved ? 1 : 0;


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
                    'message' => 'User admin not found',
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

            $particpant = Model::findOne(
                $this->dbConnection,
                array('username' => $username),
                'app_users'
            );

            if (!$particpant) {
                $this->jsonResponse(array(
                    'success' => false,
                    'message' => 'Participant not found',
                ));
            }

            $updateParam = $group['user_id'] == $user['id'] ? array('can_share' => $canShare, 'approved' => $approved, 'exit' => $exit) : array('exit' => $exit);
            $result = Model::update(
                $this->dbConnection,
                $updateParam,
                array('group_id' => $groupId, 'user_id' => $particpant['id'])
            );

            if ($result) {
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

        public static function getUsername($id, $users) {
            for($i = 0; $i < sizeof($users); $i++) {
                if ($users[$i]['id'] == $id) {
                    return $users[$i]['username'];
                }
            }
        }

        public static function getParticipant($users, $groupId) {
            $particpant = array();

            foreach($users as $user) {
                if ($user['group_id'] == $groupId) {
                    $p =  array();
                    $p['username'] = $user['username'];
                    $p['can_share'] = $user['can_share'];
                    $p['approved'] = $user['approved'];
                    $p['entry_date'] = $user['date_created'];
                    array_push($particpant, $p);
                }
            }

            return $particpant;
        }

        public static function getGroupTasks($tasks, $groupId, $users, $results, $resultCodes) {
            $groupTasks = array();

            foreach($tasks as $task) {
                $t = array();

                $t['code'] = $task['task_code'];
                $t['data'] = $task['data'];
                $t['deadline'] = $task['deadline'];
                $t['entry_date'] = $task['date_created'];
                $t['shared_by'] = self::getUsername($task['user_id'], $users);
                $t['results'] = self::getResults($results, $groupId, $resultCodes);
                array_push($groupTasks, $t);
            }

            return $groupTasks;
        }

        public static function getResults($results, $groupId, $resultCodes) {
            $results = array();
            foreach($results as $result) {
               if ($result['group_id'] == $groupId) {
                   $r = array();

                   $r['aggregate_score'] = $result['aggregate_score'];
                   $r['code'] = $result['task_code'];
                   $r['username'] = $result['username'];
                   $r['data'] = $result['data'];
                   $r['entry_date'] = $result['date_created'];

                   if (!in_array($r, $resultCodes)) {
                       array_push($results, $r);
                   }
               }
            }

            return $results;
        }

        public static function getGroupIds($groups) {
            $groupIds = array();
            foreach($groups as $group) {
                array_push($groupIds, $group['group_id']);
            }

            return $groupIds;
        }
    }

?>