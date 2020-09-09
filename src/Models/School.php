<?php
    namespace VirtualLab\Models;

    class School {
        const TABLE_NAME = 'schools';

        /**
         * Create a new  school entry into the users database
         * @ return true if successfull
         * return false if the query fails for any reasons
         */
        public static function create($dbConnection, $params) {
            return Model::create($dbConnection, $params, self::TABLE_NAME);
        }

        /**
         * find a school either by its attributes
         * @param $param {array} the prameters to find either an id or email
         */
        public static function findOne($dbConnection, $params, $offset=null, $limit=null, $order=null, $orderBy=null) {
            return Model::findOne($dbConnection, $params, self::TABLE_NAME, $offset, $limit, $order, $orderBy);
        }

        /**
         * find a school either by its attributes
         * @param $param {array} the prameters to find either an id or email
         */
        public static function find($dbConnection, $params, $offset=null, $limit=null, $order=null, $orderBy=null) {
            return Model::find($dbConnection, $params, self::TABLE_NAME, $offset, $limit, $order, $orderBy);
        }
    }