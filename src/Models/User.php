<?php
    namespace VirtualLab\Models;

    class User {
        /**
         * Create a new user entry into the users database
         * @ return true if successfull
         * return false if the query for any reasons
         */
        public static function create($dbConnection, $name, $email, $password) {
            try {
                $sql = 'INSERT INTO users (email, name, password) VALUES(?,?,?)';
                $stmt= $dbConnection->pdo->prepare($sql);
                $stmt->execute([$email, $name, $password]);
                return true;
            } catch(Exception $e) {
                return false;
            }
        }

        /**
         * find a user either by id or email
         * @param $param the prameters to find either an id or email
         */
        public static function findOne($dbConnection, $param, $findBy='id') {
            try {

                // echo $findBy; exit;
                if ($findBy != 'id' && $findBy != 'email') {
                    throw new \Exception('Invalid paramter for findBy');
                }
                
                $sql = 'SELECT * FROM users WHERE users.' . $findBy . '=?';
                $stmt= $dbConnection->pdo->prepare($sql);
                $stmt->execute([$param]);
                return $stmt->fetch();

            } catch(Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }
    }