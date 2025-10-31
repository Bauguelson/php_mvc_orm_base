<?php
    class Random {
        private $random;
        const INT = "1234567890";
        const STRING = "abcdefghijklmnopqrstuvwxyz";
        const SPECIAL = "!\"#$%&'()*+,-./:;<=>?@[\]^_`{|}~";

        public static function int(int $min = 1, int $max = 9999) : int {
            return rand($min, $max);
        }

        public static function fromCharacters(string $characters, int $minlen = 1, ?int $maxlen = 5) : string {
            $len = rand($minlen, ($maxlen == NULL || $maxlen < $minlen ? $minlen : $maxlen));
            $randomChain = "";
            for($i=0;$i<$len;$i++) $randomChain .= $characters[rand(0, strlen($characters)-1)];
            return $randomChain;
        }

        public static function string(int $minlen = 4, ?int $maxlen = NULL, bool $uppercase = true, bool $lowercase = true, bool $int = true, bool $special = false) : string {
            $characters = "";
            if($uppercase == true) $characters .= strtoupper(self::STRING);
            if($lowercase == true) $characters .= strtolower(self::STRING);
            if($int == true) $characters .= self::INT;
            if($special == true) $characters .= self::SPECIAL;
            return self::fromCharacters($characters, $minlen, $maxlen);
        }

    }
?>