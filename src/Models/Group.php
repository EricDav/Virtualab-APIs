<?php
    namespace VirtualLab\Models;

    class Group {
        const TABLE_NAME = 'groups';

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

        public static function getGroupIdStr($groupIds) {
            $groupIdStr = '(';
            for ($i = 0; $i < sizeof($groupIds); $i++) {
                if ($i == 0) {
                    $groupIdStr.="'". $groupIds[$i] . "'";
                } else  {
                    $groupIdStr.="," . "'". $groupIds[$i] . "'";
                }
            }

            $groupIdStr.=')';

            return $groupIdStr;
        }

        /**
         * Fetch all the users in all the groups a user belongs to
         */
        public static function fetchAllUsers($dbConnection, $groupIds) {
            try {
                $groupIdStr = self::getGroupIdStr($groupIds);
                $sql = 'SELECT app_users.id, app_users.first_name, app_users.last_name, app_users.country, app_users.username, user_groups.date_created, user_groups.group_id, user_groups.is_deleted AS blocked, user_groups.exit, user_groups.can_share, user_groups.approved FROM app_users INNER JOIN user_groups ON  app_users.id=user_groups.user_id  WHERE user_groups.is_deleted = 0 AND user_groups.group_id IN ' . $groupIdStr;
                // echo $sql; exit;
                return $dbConnection->pdo->query($sql)->fetchAll();
                
            } catch (Exception $e) {
                var_dump($e->getMessage());
                return false;
            }
        }

        /**
         * Fetch all the users in all the groups a user belongs to
         */
        public static function fetchUsers($dbConnection, $groupIds) {
            try {
                $groupIdStr = self::getGroupIdStr($groupIds);
                $sql = 'SELECT app_users.first_name, app_users.last_name, app_users.country, app_users.username FROM app_users INNER JOIN user_groups ON  app_users.id=user_groups.user_id  WHERE user_groups.is_deleted = 0 AND user_groups.group_id IN ' . $groupIdStr . ' GROUP BY app_users.id';
                return $dbConnection->pdo->query($sql)->fetchAll();
                
            } catch (Exception $e) {
                var_dump($e->getMessage());
                return false;
            }
        }

        public static function getGroups($dbConnection, $userId, $groupId) {
            try {

                $sql = 'SELECT user_groups.user_id, user_groups.group_id, user_groups.approved, user_groups.is_owner, groups.date_created, groups.name, groups.mode, groups.user_id AS admin_id FROM user_groups INNER JOIN groups ON user_groups.group_id = groups.group_code  WHERE user_groups.user_id=' . $userId . ' AND user_groups.is_deleted=0 AND user_groups.`exit`=0' . ($groupId ? ' AND groups.group_code=' . "'" . $groupId . "'" : '');
                // var_dump($sql); exit;
                return $dbConnection->pdo->query($sql)->fetchAll();
            } catch (Exception $e) {
                var_dump($e->getMessage());
                $dbConnection->pdo->rollBack();
                return false;
            }
        }

        public static function fetchTasks($dbConnection, $groupIds) {
            try {
                $groupIdStr = self::getGroupIdStr($groupIds);
                $sql = 'SELECT group_tasks.group_id, group_tasks.title, app_users.username, group_tasks.user_id, group_tasks.deadline, group_tasks.data, group_tasks.task_code, group_tasks.date_created  FROM group_tasks  INNER JOIN app_users ON group_tasks.user_id=app_users.id  WHERE group_tasks.group_id IN ' .  $groupIdStr;
                return $dbConnection->pdo->query($sql)->fetchAll();

            } catch (Exception $e) {
                var_dump($e->getMessage());
                $dbConnection->pdo->rollBack();
                return false;
            }
        }

        public static function getResults($dbConnection, $groupIds) {
            try {
                $groupIdStr = self::getGroupIdStr($groupIds);
                $sql = 'SELECT task_results.group_id, app_users.username, task_results.task_code, task_results.aggregate_score, task_results.data, task_results.date_created FROM task_results  INNER JOIN app_users ON task_results.user_id=app_users.id  WHERE task_results.group_id IN'  . $groupIdStr;
                return $dbConnection->pdo->query($sql)->fetchAll();

            } catch (Exception $e) {
                var_dump($e->getMessage());
                $dbConnection->pdo->rollBack();
                return false;
            }
        }
    }