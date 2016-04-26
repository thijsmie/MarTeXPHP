<?php
namespace MarTeX;

class MarTeX {
    public function __construct() {
        $char = fopen('char.txt','r');
        $code = array();
        $replace = array();
        if ( $char === false) {
            throw new Exception("MarTeX is missing a char replace array file. Please provide char.txt in the MarTeX directory.");
        }
        
        while (($line = fgets($char)) !== false) {
            $kv = explode("\t", $line);
            $code[] = '/'.preg_quote($kv[0]).'/';
            $replace[] = $kv[1];
        }

        fclose($char);
        $this->_simple_replace_array = array($code, $replace);
    }
    
    public function setGlobalVar($key, $value) {
        $this->_GlobalVars[$key] = $value;
    }
    
    public function getGlobalVar($key) {
        if (array_key_exists($key, $this->_GlobalVars)) {
            return $this->_GlobalVars[$key];
        }
        return false;
    }
    
    public function setEnvVar($key, $value) {
        $key = implode("/", $this->_EnvStack)."/".$key;
        $this->_EnvVars[$key] = $value;
    }
    
    public function getEnvVar($key) {
        $key = implode("/", $this->_EnvStack)."/".$key;
        if (array_key_exists($key, $this->_EnvVars)) {
            return $this->_EnvVars[$key];
        }
        return false;
    }
    
    private function simpleReplacePass($text) {
        return preg_replace($this->_simple_replace_array[0], $this->_simple_replace_array[1], $text);
    }
    
    private function handleCommand($command, $argument) {
        if ($command == "begin") {
            $this->_EnvStack[] = $argument;
            return "\\begin{".$argument."}";
        }
        else if ($command == "end") {
            $toEnd = array_pop($this->_EnvStack);
            if ($argument != $toEnd) {
                $this->_EnvStack[] = $toEnd;
                $this->parseError("(MarTeX) Error: environment end ".$argument." has no matching begin declaration.");
            }
            return "\\end{".$argument."}";
        }
        else {
            end($this->_EnvStack);
            do {
                $totalcommand = current($this->_EnvStack)."/".$command;
                
                if (array_key_exists($totalcommand, $this->_Funcs)) {
                    return $this->_Funcs[$totalcommand]->handleCommand($totalcommand, $argument);
                }
            }
            while (prev($this->_EnvStack) !== false);
            
            if (!array_key_exists($command, $this->_Funcs)) {
                $this->parseError("(MarTex) Error: unknown command ".$command.".");
                return "";
            }
            return $this->_Funcs[$command]->handleCommand($command, $argument);
        }
    }
    
    private function complexReplacePass($text) {
        // El Monstro:
        $regex = "/\\\\([\\w\\>]*)(?:(?:\\s*{\\s*([^{}]*)\\s*})|(?:\\s|\\\\|$))(?:((?:\\s*{(?:[^{}]*)})+)|)/im";
        //        Match \command       Argument           make optional     Maybe more arguments    make optional
	    preg_match_all($regex, $text, $matches);
	    
	    for($i = 0; $i < count($matches[0]); $i+=1) {
	        if ($matches[3][$i] != "") {
	            $step1 = str_replace_first("{","",$matches[3][$i]);
	            $step2 = str_replace_all("}","",$step1);
	            $step3 = explode("{", $step2);
	            
	            $arg = array_merge(array($matches[2][$i]),$step3);
	        }
	        else {
	            $arg = $matches[2][$i];
	        }
		    $ntext = $this->handleCommand($matches[1][$i], $arg);
		    if ($this->_newError) {
		        $this->_Error .= "\n\tCaused by command:".$matches[0][$i];
		        $this->_newError = false;
		    }
		    $text = str_replace_first($matches[0][$i], $ntext, $text);
	    }
	    return $text;
    }
    
    private function handleEnvironment($env, $options, $content) {
        if (!array_key_exists($env, $this->_Envs)) {
            $this->parseError("(MarTex) Error: unknown environment ".$env.".");
            return "";
        }
        return $this->_Envs[$env]->handleEnvironment($env, $options, $content);
    }
    
    private function environmentReplacePass($text) {
        $regex = "/\\\\begin\\s*{([^{}]*)}(?:\\[(\\s*\\w\\s*)\\]|)((?:.|\\n)*)\\\\end\\s*{\\s*\\1\\s*}/imU";
        preg_match_all($regex, $text, $matches);
        
        for($i = 0; $i < count($matches[0]); $i+=1) {
		    $ntext = $this->handleEnvironment($matches[1][$i], $matches[2][$i], $matches[3][$i]);
		    $text = str_replace_first($matches[0][$i], $ntext, $text);
	    }
	    
	    return $text;
    }

    public function parse($text) {
        // Reset all variables
        $this->_EnvVars = array();
        $this->_GlobalVars = array();
        $this->_EnvStack = array();
        $this->_hasError = false;
        $this->_newError = false;
        $this->_Error = "";
        $this->_Result = "";
        
        // Reset all modules
        for($i = 0; $i < count($this->_modObjs); $i++) {
            $this->_modObjs[$i]->reset();
        }
        
        // Parse
        do {
            $oldtext = $text;
            $text = $this->simpleReplacePass($oldtext);
        }
        while ($oldtext != $text);
        
        do {
            $oldtext = $text;
            $text = $this->complexReplacePass($oldtext);
        }
        while ($oldtext != $text);
        
        do {
            $oldtext = $text;
            $text = $this->environmentReplacePass($oldtext);
        }
        while ($oldtext != $text);
        
        $this->_Result = $text;
        
        if (count($this->_EnvStack) > 0) {
            $this->parseError("(MarTeX) Error: unclosed environment(s) detected: ".implode(', ', $this->_EnvStack).".");
        }
        return $this->_hasError;
    }
    
    public function registerModule($modObject) {
        $commands = $modObject->registerCommands();
        $environments = $modObject->registerEnvironments();
        
        if(count($commands) > 0) {
            $this->_Funcs = array_merge(            // Merge an array
                $this->_Funcs,                      // To our existing functions
                array_combine( $commands,           // That points registered commands
                    array_fill(0, count($commands), $modObject))); // To the module object
        }
        
        if(count($environments) > 0) {        
            $this->_Envs = array_merge(            // Merge an array
                $this->_Envs,                      // To our existing environments
                array_combine( $environments,           // That points registered environments
                    array_fill(0, count($environments), $modObject))); // To the module object
        }
        
        $this->_modObjs[] = $modObject; // Add module to modlist
        $modObject->MarTeX = $this; // And give the module object access to our environment variables.
    }
    
    public function parseError($error) {
        $this->_newError = true;
        if (!$this->_Error) {
            $this->_hasError = true;
            $this->_Error = $error;
        }
        else {
            $this->_Error .= "\n".$error;
        }
    }
    
    public function getError() {
        return $this->_Error;
    }
    
    public function getResult() {
        return $this->_Result;
    }
    
    public function documentation() {
        $output = file_get_contents("doc/MarTeX.html");
        for($i = 0; $i < count($this->_modObjs); $i++) {
            $output .= $this->_modObjs[$i]->docstring();
        }
        return $output;
    }
    
    private $_simple_replace_array;
    private $_EnvVars = array();
    private $_GlobalVars = array();
    private $_modObjs = array();
    private $_Funcs   = array();
    private $_EnvStack = array();
    private $_Envs = array();
    private $_hasError = false;
    private $_newError = false;
    private $_Error;
    private $_Result;
}

function str_replace_first($from, $to, $subject)
{
    $from = '/'.preg_quote($from, '/').'/';

    return preg_replace($from, $to, $subject, 1);
}

function str_replace_all($from, $to, $subject)
{
    $from = '/'.preg_quote($from, '/').'/';

    return preg_replace($from, $to, $subject);
}

?>
