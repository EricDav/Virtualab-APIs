<?php
    namespace VirtualLab\Models;

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
            try {

                if (!is_array($params)) {
                    throw new \Exception('Invalid paramters parameter should be an array');
                }
                // Generate where clause

                $whereClause = self::generateWhereClause(self::TABLE_NAME, $params);                
                $sql = 'SELECT * FROM users WHERE' . $whereClause['clause'];
                $stmt= $dbConnection->pdo->prepare($sql);
                $stmt->execute($whereClause['params']);
                
                return $stmt->fetch();

            } catch(Exception $e) {
                echo $e->getMessage();
                return 'Server error';
            }
        }

        /**
         * It generates a where claus for the find function 
         * 
         * @param $tableName the name of the table we want to find rows e.g users
         * @param $findBy the attributes we want to filter from e.g id, email
         */
        public static function generateWhereClause($tableName, $findBy) {
            $whereClause = '';
            $paramsVal = [];
            $counter = 0;

            foreach($findBy as $prop => $val) {
                if ($counter == 0) {
                    $whereClause .=' ' . $tableName . '.' . $prop . '=?';
                } else {
                    $whereClause.= ' AND ' . $tableName . '.' . $prop . '=?';
                }

                array_push($paramsVal, $val);
                $counter+=1;
            }

            return array(
                'clause' => $whereClause,
                'params' => $paramsVal
            );
        }
    }