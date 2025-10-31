<?php
    function view(string $name, array $data = []) : string {
        global $_GLOBALS;
        $name = str_replace(".","/",$name);
        $fileName = VIEWS."/$name.phtml";
        if(file_exists($fileName)) {
            extract($data);
            ob_start();
            include($fileName);
            $result = ob_get_clean();
            return $result;
        } else throw new Exception("View not found.");
    }

    function config($cfg, $default = null) {
        $keys = explode(".", $cfg);
        if(file_exists(CONFIG."/{$keys[0]}.php")) {
            $config = include(CONFIG."/{$keys[0]}.php");
            if(count($keys) > 1) foreach(array_slice($keys, 1) as $key) {
                if (isset($config[$key])) {
                    $config = $config[$key];
                } else {
                    return $default;
                }
            }
        } else throw new Exception("Configuration file not found at ".CONFIG."/$cfg.php");
        return $config;
    }
    
    function env($key, $default = null) {
        $filePath = __ROOT__ . '/.env'
        ;
        if (!file_exists($filePath)) {
            return [];
        }
    
        $env = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
    
            list($name, $value) = array_map('trim', explode('=', $line, 2));
    
            if (!empty($name)) {
                $env[$name] = $value;
            }
        }
    
        return  isset($env[$key]) ? $env[$key] : $default;
    }

    function extractScripts(&$html, $page) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_use_internal_errors(false);
        $scriptTags = iterator_to_array($dom->getElementsByTagName('script'));
        $scripts = $head ='';
        $obfuscate = $buildFile = false;
        foreach($scriptTags as $scriptTag) {
            $sendTohead = false;
            $attributes = '';
            foreach ($scriptTag->attributes as $attr) {
                if($attr->name=="type" && $attr->value == "application/ld+json") $sendTohead = true;
                if($attr->name=="obfuscate" && $attr->value == "true") $obfuscate = true;
                elseif($attr->name=="build-file" && $attr->value == "true") $buildFile = true;
                else $attributes .= $attr->name.'="'. $attr->value.'" ';
            }
            $jsContent = ($obfuscate == true ? HunterObfuscator::Obfuscate($scriptTag->nodeValue) : $scriptTag->nodeValue);
            if(!empty($scriptTag->nodeValue) && $buildFile) {
                $file = "/assets/js/app/".substr(md5($_GET["routeurl"]), 0, 8)."h".substr(md5($scriptTag->nodeValue), 0, 18).($obfuscate ? 'o' : '').".js";
                if(!file_exists(__PUBLIC__.$file)) {
                    file_put_contents(__PUBLIC__.$file, $jsContent);
                }
                $attributes .= 'src="'.$file.'" ';
            }
            if($sendTohead == true) $head .= "<".trim("script ".$attributes).">".($buildFile == false ? $jsContent : '')."</script>\n";
            else $scripts .= "<".trim("script ".$attributes).">".($buildFile == false ? $jsContent : '')."</script>\n";
            
            $scriptTag->parentNode->removeChild($scriptTag);
        }
        //$scriptTag->parentNode->removeChild($scriptTag);
        $dom->saveHTML();
        $body = $dom->getElementsByTagName('body')->item(0);
        $html = str_replace(['<body>','</body>'], '', $dom->saveHTML($body));
        return [
            "scripts" => $scripts,
            "head" => $head
        ];
    }
    
    function obfuscateScripts(&$html, $id = null) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_use_internal_errors(false);
        $scriptTags = iterator_to_array($dom->getElementsByTagName('script'));
        $scripts = $head ='';
        $obfuscate = $buildFile = true;
        foreach($scriptTags as $scriptTag) {
            $sendTohead = false;
            $attributes = '';
            foreach ($scriptTag->attributes as $attr) {
                if($attr->name=="type" && $attr->value == "application/ld+json") $sendTohead = true;
                if($attr->name=="obfuscate" && $attr->value == "false") $obfuscate = false;
                elseif($attr->name=="build-file" && $attr->value == "false") $buildFile = false;
                else $attributes .= $attr->name.'="'. $attr->value.'" ';
            }
            $jsContent = ($obfuscate == true ? HunterObfuscator::Obfuscate($scriptTag->nodeValue) : $scriptTag->nodeValue);
            if(!empty($scriptTag->nodeValue) && $buildFile) {
                $file = "/assets/js/app/".substr(md5($scriptTag->nodeValue), 0, 18).($obfuscate ? 'o' : '').".js";
                if(!file_exists(__PUBLIC__."/".$file)) {
                    @file_put_contents(__PUBLIC__."/".$file, $jsContent);
                }
                $attributes .= 'src="'.$file.'" ';
            }
            if($sendTohead == true) $head .= "<".trim("script ".$attributes).">".($buildFile == false ? $jsContent : '')."</script>\n";
            else $scripts .= "<".trim("script ".$attributes).">".($buildFile == false ? $jsContent : '')."</script>\n";
            
            $scriptTag->parentNode->removeChild($scriptTag);
        }
        $html = $scripts;
        return $scripts;
    }
    
    function _t($string, ...$args) {
        //array_shift($args);
        $translation=json_decode(@file_get_contents(__DIR__."/../translations/".(Language::getLanguage() ?: Session::get("language") ?: env("APP_DEFAULT_LANGUAGE")).".json"), true);
        $translation = isset($translation[$string]) ? $translation[$string] : $string;
        return vsprintf($translation, $args);
    }

    function _e(string $string, array $values = [], ?string $language = null) {
        $translation=json_decode(file_get_contents(__DIR__."/../translations/".($language != null && Language::exists($language) ? $language : (Language::getLanguage() ?: env("APP_DEFAULT_LANGUAGE"))).".json"), true);
        $translation = isset($translation[$string]) ? $translation[$string] : $string;
        return vsprintf($translation, $values);
    }
    
    function _re(string $string, array $values, ?string $language = null) {
        $translation=json_decode(file_get_contents(__DIR__."/../translations/".(Language::getLanguage() ?: 'fr').".json"), true);
        $key= array_search($string, $translation);
        if($key) $translation = $key;
        else $translation = $string;
        return vsprintf($translation, $values);
    }
    
    function objectToArray($obj) {
        if (is_object($obj) || is_array($obj)) {
            $result = [];
            foreach ($obj as $key => $value) {
                $result[$key] = objectToArray($value);
            }
            return $result;
        }
        return $obj;
    }

    function strtodate(string $string) {
        return date("Y-m-d H:i:s", strtotime($string));
    }

    // STACKOVERFLOW CODE
    
    function guidv4($prettify = true) {
        $native = function_exists('random_bytes');

        $data = $native ? random_bytes(16) : openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        if ($prettify) {
            return guid_pretty($data);
        }
        return $data;
    }

    function guid_pretty($data) {
        return strlen($data) == 16 ?
            vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)) :
            false;
    }

    function guid_ugly($data) {
        $data = preg_replace('/[^[:xdigit:]]+/', '', $data);
        return strlen($data) == 32 ? hex2bin($data) : false;
    }
    // END STACKOVERFLOW CODE

    function pluralize($word) {
        // Words that are exceptions to the pluralization rules
        $irregularPlurals = array(
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
            'tooth' => 'teeth',
            'foot' => 'feet',
            'mouse' => 'mice',
            'goose' => 'geese',
            'ox' => 'oxen',
            'sheep' => 'sheep', // stays the same
            'fish' => 'fish',   // stays the same
        );
    
        // Check for irregular plurals
        if (array_key_exists(strtolower($word), $irregularPlurals)) {
            return $irregularPlurals[strtolower($word)];
        }
    
        // Check for uncountable words (same singular and plural form)
        $uncountableWords = array('deer', 'moose', 'series', 'species', 'equipment', 'information', 'rice', 'money');
        if (in_array(strtolower($word), $uncountableWords)) {
            return $word;
        }
    
        // Handle words ending in 'y' preceded by a consonant (city -> cities)
        if (preg_match('/[bcdfghjklmnpqrstvwxyz]y$/i', $word)) {
            return preg_replace('/y$/i', 'ies', $word);
        }
    
        // Handle words ending in 's', 'ss', 'sh', 'ch', 'x', or 'z' (bus -> buses, dish -> dishes)
        if (preg_match('/(s|ss|sh|ch|x|z)$/i', $word)) {
            return $word . 'es';
        }
    
        // Handle words ending in 'f' or 'fe' (wolf -> wolves, knife -> knives)
        if (preg_match('/(f|fe)$/i', $word)) {
            return preg_replace('/(f|fe)$/i', 'ves', $word);
        }
    
        // Handle general case (add 's' for most words)
        return $word . 's';
    }

    function unpluralize($word) {
        // Words that are exceptions to the pluralization rules
        $irregularSingulars = array(
            'men' => 'man',
            'women' => 'woman',
            'children' => 'child',
            'teeth' => 'tooth',
            'feet' => 'foot',
            'mice' => 'mouse',
            'geese' => 'goose',
            'oxen' => 'ox',
            'sheep' => 'sheep', // stays the same
            'fish' => 'fish',   // stays the same
        );
    
        // Check for irregular plurals
        if (array_key_exists(strtolower($word), $irregularSingulars)) {
            return $irregularSingulars[strtolower($word)];
        }
    
        // Check for uncountable words (same singular and plural form)
        $uncountableWords = array('deer', 'moose', 'series', 'species', 'equipment', 'information', 'rice', 'money');
        if (in_array(strtolower($word), $uncountableWords)) {
            return $word;
        }
    
        // Handle words ending in 'ies' (cities -> city)
        if (preg_match('/ies$/i', $word)) {
            return preg_replace('/ies$/i', 'y', $word);
        }
    
        // Handle words ending in 'es' for words ending in s, sh, ch, x, or z (buses -> bus, dishes -> dish)
        if (preg_match('/(s|sh|ch|x|z)es$/i', $word)) {
            return preg_replace('/es$/i', '', $word);
        }
    
        // Handle words ending in 'ves' (wolves -> wolf, knives -> knife)
        if (preg_match('/ves$/i', $word)) {
            return preg_replace('/ves$/i', 'f', $word);
        }
    
        // Handle regular plurals (boys -> boy)
        if (preg_match('/s$/i', $word) && !preg_match('/ss$/i', $word)) {
            return preg_replace('/s$/i', '', $word);
        }
        return $word;
    }


    function convertToHigherUnit($value, $unit = "bit") {
        $units = [
            'bit' => 1,
            'kbit' => 1000,
            'Mbit' => 1000 * 1000,
            'Gbit' => 1000 * 1000 * 1000,
            'Tbit' => 1000 * 1000 * 1000 * 1000,
            'Pbit' => 1000 * 1000 * 1000 * 1000 * 1000
        ];
        if (!isset($units[$unit])) {
            throw new InvalidArgumentException("Invalid unit specified.");
        }
        $bits = $value * $units[$unit];
        if ($bits >= $units['Tbit']) {
            $newValue = $bits / $units['Tbit'];
            $newUnit = 'Tbit';
        } elseif ($bits >= $units['Tbit']) {
            $newValue = $bits / $units['Tbit'];
            $newUnit = 'Tbit';
        } elseif ($bits >= $units['Gbit']) {
            $newValue = $bits / $units['Gbit'];
            $newUnit = 'Gbit';
        } elseif ($bits >= $units['Mbit']) {
            $newValue = $bits / $units['Mbit'];
            $newUnit = 'Mbit';
        } elseif ($bits >= $units['kbit']) {
            $newValue = $bits / $units['kbit'];
            $newUnit = 'kbit';
        } elseif ($bits < 1) {
            $newValue = "";
            $newUnit = 'Illimité';
        } else {
            $newValue = $bits;
            $newUnit = 'bit';
        }
    
        return "{$newValue} {$newUnit}";
    }
    
    function moneyFormat($amount) {
        if(Session::get("language") == "fr") {
            return number_format($amount, 2, ',', ' ');
        } else return number_format($amount, 2, '.', ',');
    }

    function digitCoderize($string, $len = null) {
        $code = 0;
        for($i=0;$i<($len==NULL?strlen($string) : $len)-1;$i++) {
            $code .= is_numeric($string[$i]) ? $string[$i] : array_search($string[$i], range("a", "z")) % 9;
        }
        return $code;
    }

    function frenchDepartment($zipCode) : string {
        if($zipCode == 97150) return config("countries.MF");
        if($zipCode == 97133) return config("countries.BL");
        $z = intval(substr($zipCode?:"", 0, 3));
        switch($z) {
            case 971:
            return config("countries.GP");
            case 972:
            return config("countries.MQ");
            case 973:
            return config("countries.GF");
            return "Guyane française";
            case 974:
            return config("countries.RE");
            case 975:
            return config("countries.PM");
            case 976:
            return config("countries.YT");
            default:
            return "France";
        }
    }

    function captureFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        $language = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'unknown';
        $host = gethostbyaddr($ipAddress);
        $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'unknown';
        $timeZone = date_default_timezone_get();
        $accept = !empty($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : 'unknown';
        $connection = !empty($_SERVER['HTTP_CONNECTION']) ? $_SERVER['HTTP_CONNECTION'] : 'unknown';
        $fingerprint = array(
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'language' => $language,
            'host' => $host,
            'referer' => $referer,
            'timezone' => $timeZone,
            'accept' => $accept,
            'connection' => $connection
        );
        return json_encode($fingerprint);
    }

?>