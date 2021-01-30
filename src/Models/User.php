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
        public static function create($dbConnection, $name, $email, $password, $phoneNumber) {
            try {
                $sql = 'INSERT INTO users (email, name, password, phone_number) VALUES(?,?,?,?)';
                $stmt= $dbConnection->pdo->prepare($sql);
                $stmt->execute([$email, $name, $password, $role]);
                return $dbConnection->pdo->lastInsertId();
            } catch(Exception $e) {
                return false;
            }
        }

        /**
         * find a user either by id or email
         * @param $param {array} the prameters to find either an id or email
         */
        public static function findOne($dbConnection, $params, $offset=null, $limit=null, $order=null, $orderBy=null) {
            return Model::findOne($dbConnection, $params, self::TABLE_NAME, $offset, $limit, $order, $orderBy);
        }

        /**
         * find a user either by id or email
         * @param $param {array} the prameters to find either an id or email
         */
        public static function find($dbConnection, $params, $offset=null, $limit=null, $order=null, $orderBy=null) {
            return Model::find($dbConnection, $params, self::TABLE_NAME, $offset, $limit, $order, $orderByl);
        }


        /**
         * Updates user
         */
        public static function update($dbConnection, $updateParams, $whereParams) {
            return Model::update($dbConnection,  $updateParams, $whereParams, self::TABLE_NAME);
        }

        public static function getDetails($dbConnection, $userId, $email) {
            $pinsCountSql = 'SELECT COUNT(*) AS count  FROM pins WHERE user_id=' . $userId;
            $activationsCountSql = 'SELECT COUNT(*) AS count  FROM activations WHERE user_identifier=' . '"' . $email . '"';
            try {
                $pinsCount = $dbConnection->pdo->query($pinsCountSql)->fetch();
                $activationsCount = $dbConnection->pdo->query($activationsCountSql)->fetch();
    
                return array (
                    'pin_count' => $pinsCount,
                    'activation_count' => $activationsCount,

                );
            } catch(Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }

        public static function getTeachers($dbConnection, $userId) {
            $sql = 'SELECT users.name, users.email, teachers.teacher_id FROM teachers INNER JOIN users ON users.id = teachers.teacher_id WHERE teachers.user_id=' . $userId;
            try {
                return $dbConnection->pdo->query($sql)->fetchAll();
            } catch(Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }
    }
    