<?php
    namespace VirtualLab\Models;

    use VirtualLab\Models;

    class Pin {
        const TABLE_NAME = 'pins';
        /**
         * Create a new  school entry into the users database
         * @ return true if successfull
         * return false if the query fails for any reasons
         */
        public static function createAll($dbConnection, $params) {
            return Model::createAll($dbConnection, $params, self::TABLE_NAME);
        }

        /**
         * find a user either by id or email
         * @param $param {array} the prameters to find either an id or email
         */
        public static function findOne($dbConnection, $params) {
            return Model::findOne($dbConnection, $params, self::TABLE_NAME);
        }

        public static function find($dbConnection, $params) {
            return Model::find($dbConnection, $params, self::TABLE_NAME);
        }

        /**
         * Updates a pin
         */
        public static function update($dbConnection, $updateParams, $whereParams) {
            return Model::update($dbConnection,  $updateParams, $whereParams, self::TABLE_NAME);
        }
    }
    