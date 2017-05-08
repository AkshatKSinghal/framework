<?php

namespace DB;

/**
* Class to interface with MySQL
*/

class DB
{
    public static $singleton = array();
    public static $transaction;
    public static $defaultConfig = array(
        "hostname" => "localhost",
        "username" => "root",
        "password" => "archit@2905",
        "database" => "btpost"
        );

    public static function getInstance($config = null)
    {
        if (empty($config)) {
            $config = self::$defaultConfig;
        }
        $hash = $config['hostname'] . $config['username'] . $config ['database'];
        if (!isset(self::$singleton[$hash])) {
            $mysqli = new \mysqli($config["hostname"], $config["username"], $config["password"], $config["database"]);
            if (!$mysqli) {
                throw new Exception("Error Connecting to DB");
            }
            self::$singleton[$hash] = $mysqli;
        }
        return self::$singleton[$hash];
    }


    /**
     * Function to save model object into DB
     *
     * @param object $object Model Object to be saved
     * @param array $fields List of fields to be saved
     *
     * @throws MySqlException in case of duplicate record
     * @throws MySqlException in case of foreign key constraint failure
     * in child row
     * @throws MySqlException in case of foreign key constraint failure
     * in parent row
     *
     * @return string $id Primary ID of the record
     */
    public static function saveObject($object, $fields, $incrementValues = [])
    {
        #TODO Add validation to check if the fields are part of DB fields
        $tableName = $object->tableName();
        foreach ($fields as $field) {
            $key = $object->convertToDBField($field);
            $field = ucfirst($field);
            $data[$key] = $object->{"get$field"}();
        }
        $primaryKey = $object->primaryKeyName();
        $primaryKeyField = $object->convertToDBField($primaryKey);
        unset($data[$primaryKeyField]);

        if ($object->isNew()) {
            $keys = implode(", ", array_keys($data));
            $values = "'".implode("', '", array_values($data)) . "'";
            $query = "INSERT INTO $tableName ($keys) VALUES ($values)";
        } else {
            $id = $object->getPrimaryKey();
            $updateValues = [];
            foreach ($data as $field => $value) {
                if (isset($incrementValues[$field])) {
                    $updateValues[] = " `$field` = $field + " . $incrementValues[$field];
                } else {
                    $updateValues[] = " `$field` = '$value'";
                }
            }
            $updateQuery = implode(", ", $updateValues);
            $query = "UPDATE $tableName SET $updateQuery WHERE `$primaryKey` = '$id'";
        }
        self::executeQuery($query);
        if ($object->isNew()) {
            return self::getInstance()->insert_id;
        }
    }

    public static function updateValues($object, $fields)
    {
        $tableName = $object->tableName();
        $primaryKey = $object->primaryKeyName();
        $primaryKeyField = $object->convertToDBField($primaryKey);
        $id = $object->getPrimaryKey();

        foreach ($fields as $field => $value) {
            $field = $object->convertToDBField($field);
            $updateValues[] = " $field = $field + $value";
        }
        $updateQuery = implode(", ", $updateValues);
        $query = "UPDATE $tableName SET $updateQuery WHERE `$primaryKey` = '$id'";
        self::executeQuery($query);
    }

    /**
     * Function to get values from DB based on the search criteria
     *
     * @param string $tableName Name of the primary table to be queried on
     * @param mixed $queryParams Filter parameters
     * @param array $fieldList List of fields to be retrieved from the DB
     * @param int $limit Limit on the number of records to be fetched
     * [optional, default 100]
     * @param int $page Page number, for offset purpose [optional; default 1]
     *
     * @throws MySqlException in case of $queryParams or $fieldList contains
     * tables not directly or indirectly connected to the primary table
     * @throws MySqlException in case $page is less than 1
     *
     * @return array $result Array of results
     */
    public static function search($table, $queryParams, $fieldList = [], $limit = 100, $page = 1)
    {
        if (empty($queryParams)) {
            throw new Exception("Filter parameters mandatory");
        }
        if (empty($fieldList)) {
            $fieldList = "*";
        } else {
            $fieldList = implode(", ", $fieldList);
        }
        $filterQuery = array();
        foreach ($queryParams as $field => $value) {
            $filterQuery[] = " $field = '$value' ";
        }
        $filterQuery = implode(" AND ", $filterQuery);
        $offset = ($page - 1) * $limit;
        $query = "SELECT $fieldList FROM $table WHERE $filterQuery LIMIT $offset, $limit";
        return self::executeQuery($query);
    }


    /**
     * Function to search and return single value for given query
     * 
     * @uses search function to get the data
     * 
     * @return array $data First record for the given query
     */
    public static function searchOne($table, $queryParams, $fieldList = [])
    {
        $data = self::search($table, $queryParams, $fieldList, 1);
        return end($data);
    }


    /**
     * Function to get object from DB based on primary key
     *
     * @param string $model Name of the model
     * @param string $id Primary Key identifier
     * @param array $fields List of fields to be retrieved from the DB
     *
     * @throws MySqlException in case of $queryParams or $fieldList contains
     * tables not directly or indirectly connected to the primary table
     *
     * @return mixed $result
     */
    public static function get($model, $id, $fields = array())
    {
        $tableName = $model::tableName();
        $primaryKey = $model::primaryKeyName();
        $queryParams = array(
            $primaryKey => $id
            );
        return self::search($tableName, $queryParams, $fields, 1);
    }

    /**
     * Function to execute query and return the result
     *
     * @param string $query Query to be executed
     *
     * @throws MySqlException thrown by MySql
     *
     * @return mixed $result result of the query executed
     */
    public static function executeQuery($query)
    {
        return self::getInstance()->query($query);
    }

    public static function getDBSchema($tableName)
    {
        $query = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '". $tableName . "'";
        $response = self::executeQuery($query);
        $fieldsArray = [];
        while ($row = $response->fetch_assoc()) {
            $limit = '';
            switch ($row['DATA_TYPE']) {
                case 'varchar':
                case 'char':
                case 'date':
                case 'timestamp':
                case 'enum':
                    $limit = $row['CHARACTER_MAXIMUM_LENGTH'];
                    break;
                case 'int':
                    $limit = $row['NUMERIC_PRECISION'];
                    break;
                default:
                    $limit = '';
                    break;
            }
            $values = '';
            if ($row['DATA_TYPE'] == 'enum') {
                $columnType = $row['COLUMN_TYPE'];
                $matches = [];
                preg_match_all('/(?<=[(,])([^,)]+)(?=[,)])/', $columnType, $matches);
                $values = $matches[1];
            }
            $fieldsArray[$row['COLUMN_NAME']] = [
                'type' => $row['DATA_TYPE'],
                'limit' => $limit,
                'values' => $values,
                'null' => $row['IS_NULLABLE'],
            ];
        }
        return $fieldsArray;
    }
}
