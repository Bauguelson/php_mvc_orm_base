<?php
    class AppController {
        
        public function home() {
            return view("home", [
                "some" => ["data"=>true]
            ]);
        }

        public function dashboard() {
            return view("app.dashboard", [
                "some" => ["data"=>true]
            ]);
        }
    }
?>