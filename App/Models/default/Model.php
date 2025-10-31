<?php
    abstract class Model {
        private static $db;
        protected static $_table = null;
        protected static $_pk = "id";
        public static $hasInstance = false;

        public function __construct() {
            self::$hasInstance = true;
        }
        
        private static function setDb() {
            self::$db = new PDO('mysql:host='.env("DB_HOST").';port='.env("DB_PORT").';dbname='.env("DB_NAME").';charset=utf8', env("DB_USER"),  env("DB_PASS"));
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

        }
        
        protected static function getDb() {
            if(self::$db == null) self::setDb();
            return self::$db;
        }

        public static function hasInstance() {
            return self::$hasInstance;
        }

        public static function _table() {
            if(self::$_table) return self::$_table;
            foreach(str_split(static::class) as $letter) @$_table .= (ctype_upper($letter) ? '_'.strtolower($letter) : $letter);
            $_table = pluralize(substr($_table, 1));
            return $_table;
        }

        public static function _pk() {
            return static::$_pk; 
        }

        public function _pk_getter() {
            $method = "get";
            foreach(explode("_", static::class::$_pk) as $val) $method .= ucfirst($val);
            return $this->$method();
        }
        
        public static function getAll() {
            $t = get_called_class();
            $var = [];
            $req = self::getDb()->prepare("SELECT * FROM `".static::class::_table()."`".(method_exists(static::class, static::class::$_pk) ? 'ORDER BY `'. static::class::$_pk .'` DESC' : ''));
            $req->execute();
            while($data = $req->fetch(PDO::FETCH_ASSOC)) {
                $var[] = new (static::class)($data, false);
            }
            $req->closeCursor();
            return $var;
        }

        public static function getById($id, $model = null) {
            $req = self::getDb()->prepare("SELECT * FROM `".static::class::_table()."` WHERE `".static::class::$_pk."` = ?");
            $req->execute([$id]);
            if($req->rowCount() < 1) return null;
            return ($model != null ? $model->hydrate($req->fetch(PDO::FETCH_ASSOC)) : new (static::class)($req->fetch(PDO::FETCH_ASSOC)));
        }

        public static function deleteById($id) : bool {
            $sql = self::getDb()->prepare("DELETE FROM `".static::class::_table()."` WHERE `".static::class::$_pk."` = :id");
            $sql->execute([":id"=>$id]);
            return (bool) $sql->rowCount();
        }

        private static function cleanUnwantedParams(array &$parameters) : Array {
            foreach($parameters as $name=>$value) {
                $parameters[$name] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
                $setParamaterName = "set"; foreach(explode("_", $name) as $namePart) $setParamaterName .= ucfirst($namePart);
                if(!method_exists(static::class, $setParamaterName)) {
                    unset($parameters[$name]);
                }
            }
            try {
                new (static::class)($parameters);
            } catch(Exception $e) {
                throw new Exception("Impossible de créer l'objet.");
            }
            return $parameters;
        }

        public static function updateById($id, array $parameters, array $options = ["removeNull" => false]) : bool {
            try {
                unset($parameters["id"]);
                $parameters = static::class::cleanUnwantedParams($parameters);
                $updates = [];
                if(property_exists(static::class, "updated_at")) $parameters["updated_at"] = date("Y-m-d H:i:s");
                $query = "UPDATE `".static::class::_table()."` SET ";
                foreach($parameters as $name=>$value) {
                    
                    if($value === null) {
                        if(@$options["removeNull"] !== true) $query .= "`$name` = null,";
                        continue;
                    }
                    $query .= "`$name` = :$name,";
                    $updates[$name] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
                }
                $query = substr($query, 0, strlen($query)-1)." WHERE `".static::class::$_pk."` = :id";
                $sql = self::getDb()->prepare($query);
                $sql->execute(array_merge(["id"=>$id], $updates));
            } catch(Exception $e) {
                return false;
            }
            return (bool) $sql->rowCount();
        }

        public static function updateBy(array $by,array $parameters, ?int $limit = 1) : bool {
            try {
                unset($parameters["id"]);
                $parameters = static::class::cleanUnwantedParams($parameters);
                $by = static::class::cleanUnwantedParams($by);
                $updates = [];
                if(property_exists(static::class, "updated_at")) $parameters["updated_at"] = date("Y-m-d H:i:s");
                $query = "UPDATE `".static::class::_table()."` SET ";
                foreach($parameters as $name=>$value) {
                    if($value === null) {
                        $query .= "`$name` = null,";
                        continue;
                    }
                    $query .= "`$name` = :$name,";
                    $updates[$name] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
                }
                $whereClause = " WHERE ";
                foreach($by as $name=>$value) {
                    if($value === null) {
                        $whereClause .= "`$name` = null,";
                        continue;
                    }
                    $count =0;
                    $tmpName = $name;
                    while(isset($updates[$tmpName])) {
                        $count++;
                        $tmpName = $name.$count;
                    }
                    $whereClause .= "`$name` = :$tmpName,";
                    $updates[$tmpName] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
                }
                $query = substr($query, 0, strlen($query)-1).(strlen($whereClause) > 7 ? substr($whereClause, 0, strlen($whereClause)-1) : '').($limit != null ? ' LIMIT '.$limit : '');
                $sql = self::getDb()->prepare($query);
                $sql->execute($updates);
            } catch(Exception $e) {
                return false;
            }
            return (bool) $sql->rowCount();
        }

        public static function insert(array $parameters, bool $getObjectById = TRUE) : null|int|self {
            try {
                $parameters = static::class::cleanUnwantedParams($parameters);
                $parametersForSql = [];
                if(property_exists(static::class, "created_at")) $parameters["created_at"] = date("Y-m-d H:i:s");
                if(property_exists(static::class, "updated_at")) $parameters["updated_at"] = date("Y-m-d H:i:s");
                $query = "INSERT INTO `".static::class::_table()."` ";
                $columns = $values = "(";
                foreach($parameters as $name=>$value) {
                    $columns .= "`".$name."`,";
                    if($value === null) $values .= "null,";
                    else {
                        $values .= ":".$name.",";
                        $parametersForSql[":$name"] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
                    }
                }
                $columns = substr($columns, 0, strlen($columns)-1).")";
                $values = substr($values, 0, strlen($values)-1).")";
                $query .= "$columns VALUES$values";
                $sql = self::getDb()->prepare($query);
                $sql->execute($parametersForSql);
            } catch(Exception $e) {
                return 0;
            }
            return ($getObjectById  ? self::getById(@$parametersForSql[":".static::class::$_pk] ?: self::getDb()->lastInsertId()) : $sql->rowCount());
        }

        public static function deleteBy(array $by, $limit = null, $page = null) : bool {
            $objects = [];
            $by = static::class::cleanUnwantedParams($by);
            $wheres = [];
            $query = "DELETE FROM `".static::class::_table()."` WHERE ";
            foreach($by as $name=>$value) {
                if($value === null) {
                    $query .= "`$name` is null AND ";
                    continue;
                }
                $query .= "`$name` = :$name AND ";
                $wheres[$name] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
            }
            $query = substr($query, 0, strlen($query)-strlen(" AND "));
            $sql = self::getDb()->prepare($query.($limit != null ? ' LIMIT '.$limit : '').($limit !=null && $page != null ? " OFFSET ".($limit*($page-1)) : ''));
            $sql->execute($wheres);
            return (bool) $sql->rowCount();
        }

        public static function getLike(array $like) : ?Self {
            return self::getAllLike($like, 1)[0] ?? null;
        }

        public static function getAllLike(array $like, $limit = null, $page = 1) : Array {
            $objects = [];
            try {
                $like = static::class::cleanUnwantedParams($like);
            } catch(Exception $e) {
                //
            }
            $wheres = [];
            $query = "SELECT * FROM `".static::class::_table()."` WHERE ";
            foreach($like as $name=>$value) {
                if($value === null) {
                    $query .= "`$name` is null AND ";
                    continue;
                }
                $value = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
                
                $parts = explode("%", $value);
                $query .= "`$name` LIKE CONCAT(";
                foreach($parts as $partkey=>$partvalue) {
                    $query .= ($partkey==0 && empty($partvalue) ? "'%'," : '' );
                    if(!empty($partvalue)) {
                        $wheres[$name."_$partkey"] = $partvalue;
                        $query .= ":".$name."_$partkey,";
                        $query .= (isset($parts[$partkey+1]) ? "'%'," : '' );
                    }
                }
                $query = substr($query, 0, strlen($query)-1).") AND ";
            }
            $query = substr($query, 0, strlen($query)-strlen(" AND "));
            $sql = self::getDb()->prepare($query.($limit != null ? ' LIMIT '.$limit : '').($limit !=null && $page != null ? " OFFSET ".($limit*($page-1)) : ''));
            $sql->execute($wheres);
            while($cols=$sql->fetch(PDO::FETCH_ASSOC)) {
                $objects[] = new (static::class)($cols);
            }
            return $objects;
        }

        public static function getBy(array $by) : ?Self {
            return self::getAllBy($by, 1)[0] ?? null;
        }
        // Options: limit, page, order_asc, order_desc
        public static function getAllBy(array $by, $limit = null, $page = null, array $options = ["limit"=>null,"page"=>null,"order_desc"=>["created_at"]]) : Array {
            $objects = [];
            $by = static::class::cleanUnwantedParams($by);
            $wheres = [];
            $query = "SELECT * FROM `".static::class::_table()."` WHERE ";
            foreach($by as $name=>$value) {
                if($value === null) {
                    $query .= "`$name` is null AND ";
                    continue;
                }
                $query .= "`$name` = :$name AND ";
                $wheres[$name] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
            }
            $query = substr($query, 0, strlen($query)-(count($by) > 0 ? strlen(" AND ") : strlen(" WHERE ") ));
            $sql = self::getDb()->prepare($query.(property_exists(static::class, "created_at") ? " ORDER BY `created_at` DESC " : "").($limit != null ? ' LIMIT '.$limit : '').($limit !=null && $page != null ? " OFFSET ".($limit*($page-1)) : ''));
            $sql->execute($wheres);
            while($cols=$sql->fetch(PDO::FETCH_ASSOC)) {
                $objects[] = new (static::class)($cols);
            }
            return $objects;
        }

        public static function get(array $where, $limit = null) : Array {
            $objects = [];
            $where = static::class::cleanUnwantedParams($where);
            $wheres = [];
            $query = "SELECT * FROM `".static::class::_table()."` WHERE ";
            foreach($where["by"] as $name=>$value) {
                if($value === null) {
                    $query .= "`$name` is null AND ";
                    continue;
                }
                $query .= "`$name` = :$name AND ";
                $wheres[$name] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
            }
            foreach($where["like"] as $name=>$value) {
                if($value === null) {
                    $query .= "`$name` is null AND ";
                    continue;
                }
                $query .= "`$name` LIKE CONCAT('%',:$name,'%') AND ";
                $wheres[$name] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
            }
            $query = substr($query, 0, strlen($query)-strlen(" AND "));
            $sql = self::getDb()->prepare($query.($limit != null ? ' LIMIT '.$limit : ''));
            $sql->execute($wheres);
            while($cols=$sql->fetch(PDO::FETCH_ASSOC)) {
                $objects[] = new (static::class)($cols);
            }
            return $objects;
        }

        public static function where_sql($sql, ?array $params = []) : Array {
            $objects = [];
            $where = static::class::cleanUnwantedParams($where);
            $wheres = [];
            $query = "SELECT * FROM `".static::class::_table()."` WHERE $sql";
            $sql = self::getDb()->prepare($query.($limit != null ? ' LIMIT '.$limit : ''));
            $sql->execute($params);
            while($cols=$sql->fetch(PDO::FETCH_ASSOC)) {
                $objects[] = new (static::class)($cols);
            }
            return $objects;
        }

        public static function count() : int {
            $objects = [];
            $sql = self::getDb()->prepare( "SELECT COUNT(*) FROM `".static::class::_table()."`");
            $sql->execute();
            return $sql->fetchColumn(0);
        }

        public static function countBy(array $by) : int {
            $objects = [];
            $by = static::class::cleanUnwantedParams($by);
            $wheres = [];
            $query = "SELECT COUNT(*) FROM `".static::class::_table()."` WHERE ";
            foreach($by as $name=>$value) {
                if($value === null) {
                    $query .= "`$name` is null AND ";
                    continue;
                }
                $query .= "`$name` = :$name AND ";
                $wheres[$name] = ($value instanceof ObjectModel ? $value->get_object_vars() : (is_array($value) || is_object($value) ? json_encode($value) : $value));
            }
            $query = substr($query, 0, strlen($query)-strlen(" AND "));
            $sql = self::getDb()->prepare($query);
            $sql->execute($wheres);
            return $sql->fetchColumn(0);
        }

        public static function query($query, $params = []) {
            $sql = self::getDb()->prepare($query);
            $sql->execute($params);
            return $sql;
        }


        public static function has($has) {
            $whereQuery = " WHERE ";
            $where = [];
            if($has::hasInstance()) {
                $name = $has::_table()."_".$has::_pk();
                $whereQuery .= (count($where) ? ' AND ' : '')." `$name` = :$name";
                $where[$name] = $has->_pk_getter();
            }
            if(self::hasInstance()) {
                $name = self::_table()."_".self::_pk();
                $whereQuery .= (count($where) ? ' AND ' : '')." `$name` = :$name";
                $where[$name] = self->_pk_getter();
            }
            $sql = self::query("SELECT * FROM `".self::_table()."_has_".$has::_table()."`".(count($where) ? $whereQuery:""), $where);
            $hasObject = $has::class;
            while($hydrate = $sql->fetch(PDO::FETCH_ASSOC)) {
                $objects[] = new $hasObject($hydrate, false);
            }
            $sql->closeCursor();
            return $has::hasInstance() ? $objects[0] : $objects;
        }

        public static function belongs($belongs) {
            $whereQuery = " WHERE ";
            $where = [];
            if($belongs::hasInstance()) {
                $name = $belongs::_table()."_".$belongs::_pk();
                $whereQuery .= (count($where) ? ' AND ' : '')." `$name` = :$name";
                $where[$name] = $belongs->_pk_getter();
            }
            if(self::hasInstance()) {
                $name = self::_table()."_".self::_pk();
                $whereQuery .= (count($where) ? ' AND ' : '')." `$name` = :$name";
                $where[$name] = self->_pk_getter();
            }
            $sql = self::query("SELECT * FROM `".$belongs::_table()."_has_".self::_table()."`".(count($where) ? $whereQuery:""), $where);
            $belongsObject = $belongs::class;
            while($hydrate = $sql->fetch(PDO::FETCH_ASSOC)) {
                $objects[] = new $belongsObject($hydrate, false);
            }
            $sql->closeCursor();
            return $belongs::hasInstance() ? $objects[0] : $objects;
        }
    }
?>