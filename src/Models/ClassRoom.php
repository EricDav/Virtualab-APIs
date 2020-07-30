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

        public static function findOne($dbConnection, $params) {
            return Model::findOne($dbConnection, $params, self::TABLE_NAME);
        }

    }

?>