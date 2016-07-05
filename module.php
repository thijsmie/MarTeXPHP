<?php
namespace MarTeX;

abstract class MarTeXmodule {
    // Parent variable, so you can access the envvars
    public $MarTeX;
    
    public function raiseWarning($warning) {
        $this->MarTeX->parseModuleWarning(get_class($this), $warning);
    }
    
    public function raiseError($error) {
        $this->MarTeX->parseModuleError(get_class($this), $error);
    }

    public function valisaniArgument($argument, $number, $valisani) {
        if ($number > 1 && !is_array($valisani)) {
            $valisani = array_fill(0, $number, $valisani);
        }
        
        if($number == 1 && is_array($argument)) {
            $this->raiseWarning("Too many arguments supplied to command.");
            return $this->sanitize($argument[0], $valisani);
        }
        if (($number > 1 && !is_array($argument) ) || ( $number > 1 && count($argument) < $number)) {
            $this->raiseError("Not enough arguments supplied to command.");
            return $this->sanitize(array_fill(0, $number, ""), $valisani); 
        }
        if($number > 1 && count($argument) > $number) {
            $this->raiseWarning("Too many arguments supplied to command.");
            return $this->sanitize(array_slice($argument, 0, $number), $valisani);
        }
        return $this->sanitize($argument, $valisani); 
    }
    
    public function sanitize($var, $type) {
        if (is_array($var)) {
            for($i = 0; $i < count($var); $i++) {
                $var[$i] = $this->sanitize($var[$i], $type[$i]);
            }
            return $var;
        }
        else {
            $types = explode('/', $type);
            if (count($types) > 1) {
                for($i = 0; $i < count($types); $i++) {
                    $var = $this->sanitize($var, $types[$i]);
                }
                return $var;
            }
            else {
                switch($type) {
                    case "String":
                        return $var;
                    break;
                    case "Integer":
                        if (is_numeric($var)) {
                            return intval($var);
                        }
                        else {
                            $this->raiseWarning("Argument ".$var." could not be parsed to integer.");
                            return 0;
                        }
                    break;
                    case "nowhitespace":
                        return preg_replace('/\s+/', '', $var);
                    break;
                    case "":
                        return $var;
                    break;
                }
            }
        }
    }
    
    public static function str_replace_all($from, $to, $subject)
    {
        $from = '/'.preg_quote($from, '/').'/';

        return preg_replace($from, $to, $subject);
    }
    
    public static function str_replace_first($from, $to, $subject)
    {
        $from = '/'.preg_quote($from, '/').'/';

        return preg_replace($from, $to, $subject, 1);
    }
    
    public function reset() {
        return true;
    }
    
    public function registerCommands() {
        return array();
    }
    
    public function handleCommand($command, $argument) {
        return "";
    }
    
    public function registerEnvironments() {
        return array();
    }
    
    public function handleEnvironment($env, $opt, $txt) {
        return "";
    }
    
    public function registerSpecialEnvironments() {
        return array();
    }
    
    public function handleSpecialEnvironment($env, $txt) {
        return "";
    }
}

class ModuleTools {
    // Using some weird ascii symbols to represent datafields.
    // The Syntaxvalidity check in the main class should make
    // Sure they are never used by the user
    private static $_Sep = "¬";
    private static $_Mid = "ƒ";
    
    public static function setVar($var, $value) {
        return self::$_Sep.$var.self::$_Mid.$value.self::$_Sep;
    }
    
    public static function getVars($text) {
        $vars = array();
        $ts = explode(self::$_Sep,$text);
        
        for($i = 0; $i < count($ts); $i+=1) {
            $kv = explode(self::$_Mid, $ts[$i]);
            if (count($kv) == 2) {
                $vars[$kv[0]] = $kv[1];
            }
        }
        return $vars;
    }
    
    public static function getText($text) {
        $txt = "";
        $ts = explode(self::$_Sep, $text);
        for($i = 0; $i < count($ts); $i+=1) {
            $kv = explode(self::$_Mid, $ts[$i]);
            if ($kv[0] == $ts[$i]) {
                $txt .= $kv[0];
            }
        }
        return $txt;
    }
}
?>
