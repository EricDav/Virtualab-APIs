<?php
    namespace VirtualLab\Models;

    use VirtualLab\Models;

    class Wallet {
        const TABLE_NAME = 'wallets';
        /**
         * Create a new  school entry into the users database
         * @ return true if successfull
         * return false if the query fails for any reasons
         */
        public static function create($dbConnection, $params) {
            return Model::create($dbConnection, $params, self::TABLE_NAME);
        }

        /**
         * find a user either by id or email
         * @param $param {array} the prameters to find either an id or email
         */
        public static function findOne($dbConnection, $params) {
            return Model::findOne($dbConnection, $params, self::TABLE_NAME);
        }

        /**
         * Updates wallet
         */
        public static function update($dbConnection, $updateParams, $whereParams) {
            return Model::update($dbConnection,  $updateParams, $whereParams, self::TABLE_NAME);
        }
    }
    