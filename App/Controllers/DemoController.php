<?php
    class DemoController {
        public function test() {
            
            die((new Account([]))::hasInstance());
            // Chantier::has(Event:class);                              // Get all events, return Array of Event
            // $chantier::has(Event);                                   // Get all events that belongs to chantiers, return Array of Event
            // $chantier::has(new Event(["id" => "abc"]));              // Find event by specific ID matching the instancied Chantier object, return Event (known Event ID)
            // $chantier::has($event);                                  // Find event by instancied Event's (object) ID matching the instancied Chantier object, return Event
            // Event::belongs(Chantier::class);                         // Get all chantiers that has events, return Array of Chantier
            // $event::belongs(Chantier);                               // Get all chantiers that has event, return Array of Chantier
            // $event::belongs(new Chantier(["id" => "abc"]));          // return Chantier
            // $event::belongs($chantier);                              // return Chantier

            // Demo updating account email
            $account = Account::getById(11);
            $account->updateById(1, ["email"=>"bauguelson@gmail.com"]);
            $account->refresh(); // Reload current object from database, equivalent of redoing $account = User::getById(11);
            die(json_encode($account));


            # More demos
            // Get contact's data by id
            die(json_encode(Contact::getById(1)));
            // Get contact's data by id, retreiving also linked account's and company's data (Example without specifying classes name)
            die(json_encode(Contact::getById(1)->with(["accounts_id", "companies_id"])));
            // Get contact's data by id, retreiving also linked account's and company's data (Example specifying classes name)
            die(json_encode(Contact::getById(1)->with([
                "accounts_id" => Account::class,
                "companies_id" => Company::class,
                //"some_custom_names_id" => Company::class
            ])));
            // Update by id method 1 (return bool)
            Contact::getById(1)->setEmail("test@gmail.com")->update();
            // Update by id method 2 (return bool), unsafe way
            Contact::updateById(1, [
                "email" => "test@gmail.com"
            ]);
            // Retreive all data of first found (single) contact with email (return Contact)
            die(json_encode(Contact::getBy(["email"=>"test@gmail.com"])));
            // Retreive all data of contacts sharing same email (return array)
            die(json_encode(Contact::getAllBy(["email"=>"test@gmail.com"])));
            // Retreive all data of contacts owned by an account id (return array)
            die(json_encode(Contact::getAllBy(["accounts_id"=>1])));
            //
            die(json_encode(Contact::getById(1)));
        }
    }
?>