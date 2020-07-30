<?php
    namespace VirtualLab\Controllers;

    use VirtualLab\Helpers\JWT;
    use VirtualLab\Models\School as SchoolModel;
    use VirtualLab\Models\ClassRoom;

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
                'user_id' => $request->body->user_id
            );

            $result = SchoolModel::create($this->dbConnection, $params);

            if ($result) {
                $this->jsonResponse(array('success' => true, 'message' => 'School created successfully'), 200);
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
            if (!is_numeric($req->body->school_id)) {
                $this->jsonResponse(array('success' => false, 'message' => array('school_id' => 'Invalid school_id')), 400);
            }
            $this->dbConnection->open();

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

            /**
             * It checks if the with the passed name already
             * exist in the school. 
             */
            $classRoom = ClassRoom::findOne($this->dbConnection, array('id' => $req->body->school_id));
            if ($classRoom === 'Server error') {
                $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
            }
            if ($classRoom) {
                $this->jsonResponse(array('success' => false, 'message' => 'Classroom name already exist in the school with the id ' . $req->body->school_id), 400);
            }


            if (ClassRoom::create($this->dbConnection, array('school_id' => $req->body->school_id, 'name' => $req->body->name))) {
                $this->jsonResponse(array('success' => true, 'message' => 'classroom for  school_id ' . $req->body->school_id . ' created successfully'), 200);
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), 500);
        }
    }
?>
