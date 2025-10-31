<?php
    class Router {
        private static $routes = [];
        private static $middleware;
        private static $group = null;
        private static $groups = [];
        private $currentGroup;
        public static $currentRouteKey;
        public static $translations = [];
        public static $canonicals = [];
        public static $language;
        public static $country;


        private static function buildRouteArray(string $name, callable|array $func, mixed $content_type = null, mixed $id = null) : array {
            $trace = debug_backtrace();
            return  [
                "func" => $func,
                "content-types" =>  is_string($content_type) ? [$content_type] : $content_type,
                "group" => (@$trace[4]['function'] == "cond" || @$trace[5]['function'] == "cond") ? self::$group : null,
                "id" => $id
            ];
        }

        public static function buildRoutes(string $method, string|array $names, callable|array $func, mixed $content_type = null) :self {
            

            if(is_array($names)) {
                if(empty($names[Language::defaultLang])) {
                    throw new Exception("No route on default language for ");
                } else foreach(Language::getLanguages() as $lang=>$ldata) {
                    $name = $names[$lang];
                    //if(Language::getLanguage() == $lang) $default = $names[$lang]; 
                    //$default = $name;
                    self::$routes[$method]["$lang.$name"] = self::buildRouteArray($name, $func, $content_type);
                    self::$translations[$lang][$names[Language::defaultLang]] = $name;
                }
            } else self::$routes[$method][Language::getLanguage().".$names"] = self::buildRouteArray($names, $func, $content_type);
            return new self();
        }

        public static function get(string|array $names, callable|array $func, mixed $content_type = null) :self {
            return self::buildRoutes("GET", $names, $func, $content_type);
        }

        public static function head(string|array $names, callable|array $func, mixed $content_type = null) :self {
            return self::buildRoutes("HEAD", $names, $func, $content_type);
        }

        public static function post(string|array $names, callable|array $func, mixed $content_type = null) : self {
            return self::buildRoutes("POST", $names, $func, $content_type);
        }

        public static function put(string|array $names, callable|array $func, mixed $content_type = null) : self {
            return self::buildRoutes("PUT", $names, $func, $content_type);
        }

        public static function delete(string|array $names, callable|array $func, mixed $content_type = null) : self {
            return self::buildRoutes("DELETE", $names, $func, $content_type);
        }

        public static function route(string|array $names, callable|array $func, mixed $content_type = null) : self {
            return self::get($names, $func, $content_type)->head($names, $func, $content_type)->post($names, $func, $content_type)->put($names, $func, $content_type)->delete($names, $func, $content_type);
        }

        public static function sendResponse($code, string|Alert $return = null) : void {
            http_response_code($code);
            if($return instanceof Alert) {
                header("Content-Type: application/json");
                die($return->asJson());
            } else {
                die($return);
            }
            die();
        }

        public static function redirectTo($to) : void {
            header("Location: $to");
            die();
        }

        public static function cond(callable|string $type, callable $routes) : self {
            self::$group = count(self::$groups);
            $routes();
            self::$groups[] = $type;
            return (new self())->setCurrentGroup(self::$group);
        }

        public static function translate($name, $to = null, $country = null) {
            $to = $to ?? Language::getLanguage();
            $country = ($country ?? Language::getCountry()) ;
            $path = $to. (!empty($country) ? "-$country" : "");
            return "/$path/".(self::$translations[$to][$name] ?? $name);
        }

        public static function getTranslations() {
            $tmp = null;
            foreach(self::$translations[Language::getLanguage()] as $defaultLangRoute=>$translatedRoute) {
                if(self::$currentRouteKey == $translatedRoute) {
                    foreach(self::$translations as $language=>$routes) {
                        $tmp[$language] = $routes[$defaultLangRoute];
                    }
                    break;
                }
            }
            return $tmp;
        }

        public static function routeReq($req = null) : void {
            try {
                $req = explode("/", filter_var(@$_GET["routeurl"], FILTER_SANITIZE_URL));
                if(Language::urlHasLanguage()) {
                    $req = array_slice($req, 1);
                    self::setLanguage(Language::getLanguage());
                    self::setCountry(Language::getLanguage());
                }
                for($i=0;$i<count($req);$i++) {
                    $hasToBeKey = implode("/", array_slice($req, 0, count($req)-$i));
                    if(null !== @$route = self::$routes[$_SERVER["REQUEST_METHOD"]][Language::getLanguage().".".$hasToBeKey]
                    ) break;
                }
                if(empty($route)) throw new Exception("Route not found");
                else {
                    self::$currentRouteKey = $hasToBeKey;
                    if(($route["group"]!==null && true !== $testCond = self::$groups[$route["group"]]())) {
                        self::sendResponse(403);
                        die();
                    }else if((is_array($route["content-types"]) && !in_array(@getallheaders()["Content-Type"], $route["content-types"]))) {
                        self::sendResponse(403);
                        die();
                    } else {
                        if(is_array($route["func"])) $route["func"] = [new $route["func"][0](), $route["func"][1]];
                        $view = call_user_func_array($route["func"], array_slice($req, substr_count(self::$currentRouteKey, "/")+1));
                        if($view instanceof Alert) {
                            header("Content-Type: application/json");
                            die($view->asJson());
                        } else die($view);
                    }
                }
            } catch(Exception $e) {
                if(env("DEBUG") == "true") die($e); 
                self::sendResponse(404);
            }
        }

        public function getLanguages() {
            foreach(Languages::getLanguages() as $language) {
                //
            }
        }

        public static function loader($className) : bool {
            $filenames = [
                MODELS."/" . str_replace("\\", '/', $className) . ".php",
                MODELS."/default/" . str_replace("\\", '/', $className) . ".php",
                MODELS."/services/" . str_replace("\\", '/', $className) . ".php",
                CONTROLLERS."/" . str_replace("\\", '/', $className) . ".php"
            ];
            foreach($filenames as $filename) {
                if (file_exists($filename)) {
                    include($filename);
                    if (class_exists($className)) {
                        return TRUE;
                    }
                }
            }
            return FALSE;
        }

        /**
         * Get the value of currentGroup
         */
        protected function getCurrentGroup()
        {
                return $this->currentGroup;
        }

        /**
         * Set the value of currentGroup
         */
        protected function setCurrentGroup($currentGroup): self
        {
                $this->currentGroup = $currentGroup;

                return $this;
        }

        public static function getRoutes(): array
        {
                return self::$routes;
        }

        public static function setRoutes(array $routes)
        {
                self::$routes = $routes;
        }

        public static function addRoutes(array $routes)
        {
                self::$routes = $routes;
        }

        /**
         * Get the value of language
         *
         * @return $language
         */
        public static function getLanguage()
        {
                return self::$language;
        }

        /**
         * Set the value of language
         *
         * @param $language $language
         */
        public static function setLanguage($language)
        {
                self::$language = $language;
        }

        /**
         * Get the value of country
         *
         * @return $country
         */
        public static function getCountry()
        {
                return self::$country;
        }

        /**
         * Set the value of country
         *
         * @param $country $country
         */
        public static function setCountry($country)
        {
                self::$country = $country;
        }

        /**
         * Get the value of groups
         *
         * @return $groups
         */
        public static function getGroups()
        {
                return self::$groups;
        }

    }
?>