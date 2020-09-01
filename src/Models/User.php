<?php
    namespace VirtualLab\Models;

    use VirtualLab\Models;

    class User {
        const TABLE_NAME = 'users';
        /**
         * Create a new user entry into the users database
         * @ return true if successfull
         * return false if the query fails for any reasons
         */
        public static function create($dbConnection, $name, $email, $password) {
            try {
                $sql = 'INSERT INTO users (email, name, password) VALUES(?,?,?)';
                $stmt= $dbConnection->pdo->prepare($sql);
                $stmt->execute([$email, $name, $password]);
                return $dbConnection->pdo->lastInsertId();
            } catch(Exception $e) {
                return false;
            }
        }

        /**
         * find a user either by id or email
         * @param $param {array} the prameters to find either an id or email
         */
        public static function findOne($dbConnection, $params) {
            return Model::findOne($dbConnection, $params, self::TABLE_NAME);
        }
    }
    