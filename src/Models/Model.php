<?php
    namespace VirtualLab\Models;

    define('FIND_ONE', 1);
    define('FIND', 2);
    class Model {
        public static function create($dbConnection, $params, $tableName) {
            try {
                $clauseData = self::generateInsertClause($params);

                $sql = 'INSERT INTO ' . $tableName . ' ' . $clauseData['attributes']  . ' VALUES' . $clauseData['values'];

                $stmt= $dbConnection->pdo->prepare($sql);
                $stmt->execute($clauseData['params']);
                return $dbConnection->pdo->lastInsertId();
            } catch(Exception $e) {
                return false;
            }
        }

        public static function generateInsertClause($params) {
            $paramsVal = [];
            $attributes = '(';
            $values= '(';
            $counter = 0;
            $sizeofParam = sizeof($params);

            foreach($params as $prop => $val) {
                if ($counter != $sizeofParam - 1) {
                    $attributes .= $prop . ', ';
                    $values .= '?,';
                } else {
                    $attributes .= $prop . ')';
                    $values .= '?)';
                }

                $counter +=1;

                array_push($paramsVal, $val);
            }

            return array(
                'attributes' => $attributes,
                'params' => $paramsVal,
                'values' => $values
            );
        }

        public static function findOne($dbConnection, $params, $tableName) {
            return self::__find($dbConnection, $params, $tableName, FIND_ONE);
        }

        public static function find($dbConnection, $params, $tableName) {
            return self::__find($dbConnection, $params, $tableName, FIND);
        }

        public static function __find($dbConnection, $params, $tableName, $callee) {
            try {

                if (!is_array($params)) {
                    throw new \Exception('Invalid paramters parameter should be an array');
                }
                // Generate where clause

                $whereClause = self::generateWhereClause($tableName, $params);                
                $sql = 'SELECT * FROM ' . $tableName . ' WHERE' . $whereClause['clause'];
                $stmt= $dbConnection->pdo->prepare($sql);
                $stmt->execute($whereClause['params']);
                
                if ($callee == FIND) {
                    return $stmt->fetchAll();
                }

               // var_dump($stmt->fetch()); exit;

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


?>