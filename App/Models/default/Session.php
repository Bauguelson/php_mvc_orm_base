<?php
     abstract class Session {
        private static $name = "app_session";
        private const CSRF_INPUT_NAME = "_csrf_token";

        public static function start($name = "app_session") : void {
            Session::$name = $name;
            if(@session_name() != $name) {
                session_write_close();
                session_name($name);
            }
            @session_start();
            if(empty(Self::getCsrf())) Self::setCsrf();
            if(in_array(@$_SERVER["REQUEST_METHOD"], ["POST", "PUT", "DELETE"])) {
                if((@$_POST[Self::CSRF_INPUT_NAME] != Self::get("csrf") && @getallheaders()["csrf"] != Self::get("csrf") && @getallheaders()["Csrf"] != Self::get("csrf"))) {
                    http_response_code(403);
                    die((new Alert(false, Alert::TYPE_ERROR,"We couldn't approve your request. Try reloading your page or contact an admin if the problem persist."))->asJson());
                }
            }
            return;
        }

        public static function getAdmin() : ?Admin
        {
            return (@$_SESSION["admin"] ?? null);
        }

        public static function setAdmin(?Admin $admin): void
        {
            $_SESSION["admin"] = $admin;
        }

        public static function getContact() : ?Contact
        {
            return (@$_SESSION["contact"] ?? null);
        }

        public static function setContact(?Contact $contact): void
        {
            $_SESSION["contact"] = $contact;
        }

        public static function getAccount() : ?Account
        {
            return (@$_SESSION["account"] ?? null);
        }

        public static function setAccount(?Account $account): void
        {
            $_SESSION["account"] = $account;
        }

        public static function csrfProtected() {
        }

        public static function setCsrf() {
            Self::set("csrf", Random::string(64));
            return static::class;
        }

        public static function getCsrfInput() {
            return '<input type="hidden" name="'.Self::CSRF_INPUT_NAME.'" value="'.Self::get("csrf").'" />';
        }

        public static function getCsrf() {
            return Self::get("csrf");
        }

        public static function isLoggedIn() : bool {
            if(isset($_COOKIE[Self::$name])) {
                // if(session_status() !== PHP_SESSION_ACTIVE) Self::start();
                try {
                    if(Self::getAccount() !== null) {
                        $account = Account::getById(Self::getAccount()->getId());
                        if($account instanceof Account) {
                            $account->with(["contacts_id"]);
                            if($account->getPassword() == Self::getAccount()->getPassword() && $account->getEmail() == Self::getAccount()->getEmail()) {
                                Self::setAccount($account);
                                return true;
                            }
                        }
                    }
                } catch(Exception $e) {
                }
            }
            return false;
        }
        public static function logout() {
            session_unset(); // unset($_SESSION);
            session_destroy();
            setcookie(Self::$name, "", time()-10, "/");
        }

        public static function requireLogin($redir = "/login") : bool {
            if(!Self::isLoggedIn()) {
                header("Location: $redir");
                die();
            }
            return true;
        }

        public function requireNoLogin($redir = "/dashboard") : void {
            if($this->isLoggedIn()) {
                header("Location: $redir");
                die();
            }
        }

        public static function get($key) {
            return @$_SESSION["userdata"][$key] ?? null;
        }

        public static function set($key, $value) : void {
            if(empty($_SESSION["userdata"])) $_SESSION["userdata"] = [];
            $_SESSION["userdata"][$key] = $value;
        }
    }
?>