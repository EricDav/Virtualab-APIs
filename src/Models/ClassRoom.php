<?php
    namespace VirtualLab\Models;

    use VirtualLab\Models;

    class ClassRoom {
        const TABLE_NAME = 'classrooms';
        /**
         * Create a class room.
         * It calls the create method of the Model class
         */
        public static function create($dbConnection, $params) {
            return Model::create($dbConnection, $params, self::TABLE_NAME);
        }

        public static function findOne($dbConnection, $params, $offset=null, $limit=null, $order=null, $orderBy=null) {
            return Model::findOne($dbConnection, $params, self::TABLE_NAME, $offset, $limit, $order, $orderBy);
        }

        public static function getClassRoomsDetails($dbConnection, $schoolId) {
            try {
                $sql = 'SELECT classrooms.id, classrooms.name,
                users.name as teacher, (SELECT COUNT(*) from students WHERE students.`classroom_id`=classrooms.id) AS num_students 
                FROM classrooms LEFT JOIN users ON classrooms.`user_id` =users.id WHERE classrooms.school_id=' . $schoolId;

                return $dbConnection->pdo->query($sql)->fetchAll();
            } catch(Exception $e) {
                return false;
            }
        }
        /**
         * Updates a classroom
         */
        public static function update($dbConnection, $updateParams, $whereParams) {
            return Model::update($dbConnection,  $updateParams, $whereParams, self::TABLE_NAME);
        }

    }
?>
