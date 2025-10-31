<?php
    abstract class ObjectModel extends Model {

        public function __construct(array $data)
        {
            parent::__construct();
            $this->hydrate($data);
        }

        public function hydrate($data) : self {
            $data = $this::cleanProperties($data);
            foreach ($data as $key => $value) {
                if (!property_exists($this, $key)) continue;
                $method = 'set';
                foreach(explode("_", $key) as $val) $method .= ucfirst($val);
                if (method_exists($this, $method)) $this->$method($value);
            }
            return $this;
        }

        private function toAllData() {
            $reflection = new ReflectionObject($this);
            $properties = $reflection->getProperties();
            $arr = [];
            foreach ($properties as $property) {
                if (!$property->isInitialized($this)) {
                    continue;
                }
                $method = "set";
                foreach(explode("_", $property->getName()) as $val) $method .= ucfirst($val);
                if(substr($property->getName(), 0, 1) == "_" || $property->getName()[0] == "\0" || !method_exists($this, $method)) continue;
                $property->setAccessible(true);
                $arr[$property->getName()] = $property->getValue($this);
            }
        
            return $arr;
        }
 
        public function withAll() {
            foreach((array)$this as $key=>$value) {
                if(substr($key, -3) === "_id" && $value != null) {
                    //if(substr($key, -6) == "ies_id") $tkey = substr($key, 0, strlen($key)-6)."y";
                    //else if(substr($key, -6) == "xes_id") $tkey = substr($key, 0, strlen($key)-5);
                    //else $tkey = substr($key, 0, strlen($key)-4);

                    $tkey = unplularize($key);
                    $keySetMethod = "set";
                    $object = "";
                    foreach(explode("_", $tkey) as $val) {
                        $keySetMethod .= ucfirst($val);
                        $object .= ucfirst($val);
                    }
                    if(method_exists($this, $keySetMethod) && method_exists($object, "getById")) {
                        $this->$keySetMethod($object::getById($value));
                    }
                }
            }
            return $this;
        } 

        public function with(array $attributes) {
            foreach($attributes as $key=>$asObject) {
                if(!class_exists($asObject)) {
                    $key = $asObject;
                    $asObject = null;
                }
                $tkey = unpluralize($key);
                $object = "";
                foreach(explode("_", $tkey) as $val) $object .= ucfirst($val);
                $getter = "get$object";
                if($this->$getter() != null && substr($key, -3) === "_id") {
                    //if(substr($key, -6) == "ies_id") $tkey = substr($key, 0, strlen($key)-6)."y";
                    //else if(substr($key, -6) == "xes_id") $tkey = substr($key, 0, strlen($key)-5);
                    //else
                    $tkey = substr($key, 0, strlen($key)-3);
                    $tkey = unpluralize($tkey);
                    $object = "";
                    foreach(explode("_", $tkey) as $val) $object .= ucfirst($val);
                    $keySetMethod = "set".$object;
                    $asObject = $asObject ?? $object;
                    if(method_exists($this, $keySetMethod) && method_exists($asObject, "getById")) {
                        $this->$keySetMethod($asObject::getById($this->$getter()));
                    }
                }
            }
            return $this;
        } 

        public function gwith(array $attributes) {
            foreach($attributes as $key=>$asKey) {
                foreach(explode("_", $key) as $val) $object .= ucfirst($val);
                $keyGetMethod = "get".$object;
                if(method_exists($this, $keyGetMethod)) {
                    $this->$keyGetMethod();
                }
            }
            return $this;
        } 

        public static function cleanProperties($properties) {
            foreach($properties as $name=>$value) {
                if(!property_exists(get_called_class(),$name)) unset($properties[$name]);
            }
            return $properties;
        }

        public static function checkProperties($properties) {
            $error = null;
            try {
                $class = get_called_class();
                foreach ($data as $key => $value) {
                    if (!property_exists(get_called_class(), $key)) continue;
                    $method = 'set' ;
                    foreach(explode("_", $key) as $pos=>$val) $method .= ucfirst($val);
                    if (method_exists($class, $method)) $class->$method($value);
                }
            } catch(Exception $e) {
                if($e->getCode() == 10) {
                    $error = $e->getMessage();
                } else throw new Exception("Unexpected exception: ".$e->getMessage());
            }
            return $error;
        }

        public function saveOrUpdate() : bool|self {
            if(!$this->exist()) return $this->save() instanceof self;
            else return $this->update();
        }

        public function save($getById=true, $resetGuid = false) : null|self {
            if($resetGuid) $this->setId(guidv4());
            $insert = self::insert($this->toAllData(), $getById);
            if(property_exists($this, $this::$_pk)) return $insert;
            return $this;
        }

        

        public function update($params = null) : bool {
            return self::updateById($this->_pk_getter(), $params ?: $this->toAllData(), ["removeNull"=>true]);
        }

        public function delete() : int {
            return self::deleteById($this->_pk_getter());
        }

        public static function new($data = [], $idMode="guidv4") : ?self {
            $class = static::class;
            if($idMode=="guidv4") $id = guidv4();
            else $id = null;
            return new $class(array_merge([$class::_pk() => $id],$data));
        }

        public function refresh() : ?self {
            return static::class::getById($this->_pk_getter(), $this);
        }

        public function exist() : bool {
            return $this->_pk_getter()===null ? false : (bool)self::countBy([static::class::$_pk=>$this->_pk_getter()]);
        }

        public function matchingSave() : bool {
            return $this->_pk_getter()===null ? false : (bool)self::countBy($this->toAllData());
        }

        public function asJson(array $with = []) {
            $fkeys = [];
            $json = json_encode($this);
            if(count($with) > 0) {
                foreach($with as $key=>$asKey) {
                    if(is_int($key)) $key = $asKey;
                    $fk = "";
                    foreach(explode("_", $key) as $val) $fk .= ucfirst($val);
                    $keyGetMethod = "get".$fk;
                    if(method_exists($this, $keyGetMethod)) {
                        $fkeys[$asKey] = $this->$keyGetMethod();
                    }
                }
                $json=json_encode(json_decode($json, true)+$fkeys);
            }
            return $json;
        }

        public function asArray(array $with = []) {
            return json_decode($this->asJson($with),true);
        }

        public function get_object_vars() {
            $objectReflection = new ReflectionClass($this);
            $parentReflection = new ReflectionClass(Model::class);

            // Get all properties of the object and parent
            $objectProperties = $objectReflection->getProperties();
            $parentProperties = $parentReflection->getProperties();

            // Create a list of parent property names
            $parentPropertyNames = array_map(function($prop) {
                return $prop->getName();
            }, $parentProperties);

            // Filter properties that are not in the parent class
            $childProperties = [];
            foreach ($objectProperties as $property) {
                $propertyName = $property->getName();
                if (!in_array($propertyName, $parentPropertyNames)) {
                    $property->setAccessible(true); // Access private/protected properties
                    $childProperties[$propertyName] = $property->getValue($this);
                }
            }

            return $childProperties;
            return (get_object_vars($this));
        }

    }
?>