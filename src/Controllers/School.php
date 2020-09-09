<?php
    namespace VirtualLab\Controllers;

    use VirtualLab\Helpers\JWT;
    use VirtualLab\Models\School as SchoolModel;
    use VirtualLab\Models\ClassRoom;
    use VirtualLab\Models\User;
    use VirtualLab\Models\Model;

    class School extends Controller {
        public function __construct() {
            parent::__construct();
        }

        /**
         * It creates a School entity into the database
         * it calls the model for creating schools.
         * 
         * @param $request the request object
         */
        public function create($request) {
            $tokenPayload = JWT::verifyToken($request->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }

            $this->dbConnection->open();
            $request->body->user_id = json_decode($tokenPayload->payload)->id;
            $this->validateSchoolData($this->dbConnection, $request);


            $params = array (
                'name' => $request->body->name,
                'phone_number' => $request->body->phone_number,
                'country' => $request->body->country,
                'city' => $request->body->city,
                'address' => $request->body->address,
                'user_id' => $request->body->user_id,
                'email' => $request->body->email,
                'date_created' =>  gmdate("Y-m-d\ H:i:s")
            );

            $result = SchoolModel::create($this->dbConnection, $params);

            if ($result) {
                $this->jsonResponse(array('success' => true, 'message' => 'School created successfully', 'id' => $result), 200);
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }

        public function validateSchoolData($dbConnection, $req) {
            if (!is_numeric($req->body->phone_number)) {
                $this->jsonResponse(array('success' => false, 'message' => array('phone_number' => 'Invlaid characters found in phone number')));
            }

            $school = SchoolModel::findOne($dbConnection, array('user_id' => $req->body->user_id, 'name' => $req->body->name));

            if ($school === 'Server error') {
                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
            }

            if ($school) {
                $this->jsonResponse(array('success' => false, 'message' => 'School name already exist'), 400);
            }
        }

        public function addClass($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);

            if (!is_numeric($req->body->school_id)) {
                $this->jsonResponse(array('success' => false, 'message' => array('school_id' => 'Invalid school_id')), 400);
            }

            /**
             * It checks if the school with the id passed exists 
             * in the database. 
             */
            $school = SchoolModel::findOne($this->dbConnection, array('id' => $req->body->school_id));

            if ($school === 'Server error') {
                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
            }
            if (!$school) {
                $this->jsonResponse(array('success' => false, 'message' => 'School with id ' . $req->body->school_id . ' does not exist'), 404);
            }

             //checks if user is the creator of the school
             if ($school['user_id'] != $tokenPayload->id) {
                $this->jsonResponse(array('success' => false, 'message' => 'You do not have permssion to add classromms to thos school'), 401);
             }

            /**
             * It checks if the with the passed name already
             * exist in the school. 
             */
            $classRoom = ClassRoom::findOne($this->dbConnection, array('school_id' => $req->body->school_id, 'name' => $req->body->name));
            if ($classRoom === 'Server error') {
                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
            }

            if ($classRoom) {
                $this->jsonResponse(array('success' => false, 'message' => 'Classroom name already exist in the school with the id ' . $req->body->school_id), 400);
            }

            $newClassroom = ClassRoom::create($this->dbConnection, array('school_id' => $req->body->school_id, 'name' => $req->body->name));
            if ($newClassroom) {
                $this->jsonResponse(array('success' => true, 'message' => 'classroom for  school_id ' . $req->body->school_id . ' created successfully', 'id' => $newClassroom), 200);
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
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
            $schools = SchoolModel::find($this->dbConnection, array('user_id' => $tokenPayload->id));

            if ($schools) {
                $this->jsonResponse(array('success' => true, 'data' => $schools, 'message' => 'Schools retrieved successfully'), 200);
            }
            if (is_array($schools)) {
                $this->jsonResponse(array('success' => true, 'data' => $schools, 'message' => 'School created successfully'), 200);
            }
            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }

        public function getClassrooms($req){
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->query->token);
            // var_dump($tokenPayload); exit;
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);

            if (!is_numeric($req->query->school_id)) {
                $this->jsonResponse(array('success' => false, 'message' => array('school_id' => 'Invalid school_id')), 400);
            }

            $school = SchoolModel::findOne($this->dbConnection, array('id' => $req->query->school_id));

            // Verify if user is the creator of the school
            if ($tokenPayload->id != $school['user_id']) {
                $this->jsonResponse(array('success' => false, 'message' => 'School not created by user'), 401);
            }

            $classrooms = ClassRoom::getClassRoomsDetails($this->dbConnection, $school['id']);
            $school['classrooms'] = $classrooms;

            if (is_array($school)) {
                $this->jsonResponse(array('success' => true, 'data' => $school, 'message' => 'Classrooms created successfully'), 200);
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }

        public function assignTeacher($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);
            $classroom = ClassRoom::findOne($this->dbConnection, array(
                'id' => $req->body->classroom_id
            ));

            if (!$classroom) {
                $this->jsonResponse(array('success' => false, 'message' => 'Classroom not found'), 400);
            }

            $school = SchoolModel::findOne($this->dbConnection, array(
                'id' => $classroom['school_id']
            ));

            // var_dump($school); exit;

            if (!$school) {
                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
            }

            // Checks if user created the school where the classroom is
            if ($school['user_id'] != $tokenPayload->id) {
                $this->jsonResponse(array('success' => false, 'message' => 'School where the classroom is does npt belong to user'), 400);
            }

            $teacher = Model::findOne($this->dbConnection, array(
                'teacher_id' => $req->body->teacher_id
            ), 'teachers');

            if (!$teacher) {
                $this->jsonResponse(array('success' => false, 'message' => 'Teacher not found'), 404);
            }

            if ($teacher['user_id'] != $tokenPayload->id) {
                $this->jsonResponse(array('success' => false, 'message' => 'User not the owner creator of the teacher'), 400);
            }

            if (ClassRoom::update($this->dbConnection, array('user_id' => $req->body->teacher_id), array('id' => $req->body->classroom_id))) {
                $this->jsonResponse(array('success' => true, 'message' => 'Teacher assigned successfully'), 200);
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }
    }
?>
