<?php

namespace MarTeX;

class MarTeX {
    /**
     * Class: MarTeX 
     * Function: Constructor ( )
     * Opens the char file with the simple replace array for the special characters.
     * Returns: None
     **/
    public function __construct() {
        $char = fopen(__DIR__ . '/char.txt', 'r');
        $code = array();
        $replace = array();
        if($char === false) {
            throw new Exception("MarTeX is missing a char replace array file. Please provide char.txt in the MarTeX directory.");
        }

        while(($line = fgets($char))!== false) {
            $line = rtrim($line);
            $kv = explode("\t", $line);
            $code[] = '/' . preg_quote($kv[0]). '/m';
            $replace[] = $kv[1];
        }
        fclose($char);
        $this->_simple_replace_array = array($code, $replace);
    }

    /**
     * Function: setGlobalVar ( key of variable, value to set )
     * Sets a global variable to a value. For module use only.
     * Global variables get wiped on a reset().
     * Returns: None
     **/
    public function setGlobalVar($key, $value) {
        $this->_GlobalVars[$key] = $value;
    }

    /**
     * Function: getGlobalVar ( key of variable )
     * Gets a global variable to a value. For module use only.
     * Global variables get wiped on a reset().
     * Returns: value of variable or 'false' when the key does not exist
     **/
    public function getGlobalVar($key) {
        if(array_key_exists($key, $this->_GlobalVars)) {
            return $this->_GlobalVars[$key];
        }
        return false;
    }

    /**
     * Function: setEnvVar ( key of variable, value to set )
     * Set environment variable, for module use only.
     * Variable belongs to the type of environment it was created in.
     * Returns: None
     **/
    public function setEnvVar($key, $value) {
        $key = implode("/", $this->_EnvStack). "/" . $key;
        $this->_EnvVars[$key] = $value;
    }

    /**
     * Function: getEnvVar ( key of variable )
     * Get environment variable, for module use only.
     * Variable belongs to the type of environment it was created in.
     * Returns: value of variable or 'false' when the key does not exist
     **/
    public function getEnvVar($key) {
        $key = implode("/", $this->_EnvStack). "/" . $key;
        if(array_key_exists($key, $this->_EnvVars)) {
            return $this->_EnvVars[$key];
        }
        return false;
    }

    /**
     * Function: simpleReplacePass ( text to work on )
     * Do all simple replaces in text. These are mostly
     * special characters.
     * Returns: adapted text
     **/
    public function simpleReplacePass($text) {
        return preg_replace($this->_simple_replace_array[0], $this->_simple_replace_array[1], $text);
    }
    
    /**
     * Function: syntaxValidity ( text )
     * Check wether the syntax of a certain text is valid
     * This means all braces must match and no
     * html tags are in the source
     **/
    public function syntaxValidity($text) {
        $num_leftbraces = 0;
        preg_match_all('/[^\\\\](\\{+)/mi', $text, $matches);
        for($i = 0; $i < count($matches); $i++) {
            $num_leftbraces += strlen($matches[$i][1]);            
        }
        
        $num_rightbraces = 0;
        preg_match_all('/[^\\\\](\\}+)/mi', $text, $matches);
        for($i = 0; $i < count($matches); $i++) {
            $num_rightbraces += strlen($matches[$i][1]);            
        }
        
        if ($num_leftbraces != $num_rightbraces) {
            $this->parseError(
                    "(MarTeX/Syntax) Fatal: Unmatched braces in text. Make sure braces that"
                   ." are not part of a command are escaped.".strval($num_leftbraces)." ".strval($num_rightbraces)); 
            return false;
        }
        
        $num_forbidden_special = preg_match_all('/[^\\\\](?:\\>|\\<|¦|ƒ|¬)/', $text, $some3);
        if ($num_forbidden_special > 0) {
            $this->parseError(
                    "(MarTeX/Syntax) Fatal: Unescaped special characters in text."); 
            return false;            
        }
        return true;
    }

    /**
     * Function: handleCommand ( commandword, argument(s))
     * Gets called by the complexReplacePass function. 
     * Argument can either be a single string or an array of strings
     * Returns: text after command is handled
     **/
    private function handleCommand($command, $argument) {
        if($command == "begin") {
            // Handle special command "begin".
            $test = $argument;
            if(is_array($argument)) {
                // If the argument is an array do some implode voodoo
                $test = $argument[0];
                $argument = implode('}{', $argument);
            }
            // Append to environment stack.
            $this->_EnvStack[] = $test;
            // Return the command as if nothing happened. It will be deleted in
            // the environment replacepass.
            return "\\begin{" . $argument . "}";
        } 
        else if($command == "end") {
            // Handle special command "end"
            $toEnd = array_pop($this->_EnvStack);
            if($argument != $toEnd) {
                // The environment on the end stack does not match the
                // command. The writer of the TeX code has made an error.
                $this->parseError("(MarTeX) Error: environment end " . 
                                    $toEnd . 
                                    " has no matching begin declaration.");
            }
            // Return command unedited.
            return "\\end{" . $argument . "}";
        } 
        else {
            // Handle a normal command.
            // First check wether some command is defined in one of 
            // the environments we are in.
            end($this->_EnvStack);
            do {
                $totalcommand = current($this->_EnvStack). "/" . $command;
                if(array_key_exists($totalcommand, $this->_Funcs)) {
                    return $this->_Funcs[$totalcommand]->handleCommand($totalcommand, $argument);
                }
            }
            while(prev($this->_EnvStack)!== false);
            
            // If not, check if the raw command exists.
            if(!array_key_exists($command, $this->_Funcs)) {
                // If not, return error and empty string.
                $this->parseError("(MarTex) Error: unknown command " . 
                                    $command . 
                                    ".");
                return "";
            }
            
            // Handle the command with the module.
            // If the $argument is invalid, the module is expected to call
            // parseError to report that to the user.
            return $this->_Funcs[$command]->handleCommand($command, $argument);
        }
    }
    
    /**
     * Function: complexPreProcess ( text to process )
     * Using a regex it detects multi-argument commands.
     * It appends these arguments with a special separator.
     * This makes it possible to force recursive command processing.
     * Returns: preprocessed text.
     **/
    private function complexPreProcess($text) {
        // Regex to match } *whitespace* {
        $regex = "/}\\s*{/im";
        
        preg_match_all($regex, $text, $matches);
        
        for($i = 0; $i < count($matches[0]); $i += 1) {
            $text = str_replace_first($matches[0][$i], "¦", $text);
        }
        return $text;
    }

    /**
     * Function: complexReplacePass ( text to work on )
     * Regex search for strings \command and \command{ar}{gu}{ments}
     * Does this recursively, so \second{\first{}}
     * Returns: text with all inner commands processed.
     **/
    private function complexReplacePass($text) {
        
        // Regex, split for readability:
        
        $reg_commandword = '\\\\((?:[a-z]|[A-Z])+)';
        //Match backslash, then commandword
        
        $reg_withargument = '(?:\s*\{([^\{\}\\\\]*)\})';
        //Match pair of braces with no braces or backslash contained
        
        $reg_withoutargument = '(?:\s+(?!\s|{))';
        //Match possible whitespace and make sure no brace follows
        
        $regex = "/$reg_commandword(?:$reg_withargument|$reg_withoutargument)/im";
        //Construct full regex
        
        preg_match_all($regex, $text, $matches);
        
        for($i = 0;$i < count ($matches[0]);$i += 1 ) {
            $arg = explode("¦", $matches[2][$i]);
            if(count($arg)== 1)
                $arg = $arg[0];
            $ntext = $this->handleCommand($matches[1][$i], $arg);
            if($this->_newError) {
                $this->_Error .= "\n\tCaused by command:" . $matches[0][$i];
                $this->_newError = false;
            }
            $text = str_replace_first($matches[0][$i], $ntext, $text);
        }
        return $text;
    }

    /**
     * Function: handleEnvironment ( Environment name, environment options, environment inner text )
     * Process environments
     * Returns: processed environment.
     **/
    private function handleEnvironment($env, $options, $content) {
        if(!array_key_exists($env, $this->_Envs)) {
            $this->parseError("(MarTex) Error: unknown environment " . 
                                $env . 
                                ".");
            return "";
        }
        return $this->_Envs[$env]->handleEnvironment($env, $options, $content);
    }

    /**
     * Function: environmentReplacePass ( text to work on )
     * Regex search for \begin{env} ... \end{env}
     * Raw big regex, subject to being changed in the future.
     * Returns: text with replaced environments
     **/
    private function environmentReplacePass($text) {
        $regex = "/\\\\begin\\s*{([^{}]*)}(?:\\[(\\s*\\w\\s*)\\]|)(?:((?:\\s*{(?:[^{}]*)})+)|)((?:.|\\n)*)\\\\end\\s*{\\s*\\1\\s*}/imU";
        preg_match_all($regex, $text, $matches);
        for($i = 0; $i < count($matches[0]); $i += 1) {
            if($matches[3][$i] != "") {
                $step1 = str_replace_first("{", "", $matches[3][$i]);
                $step2 = str_replace_all("}", "", $step1);
                $step3 = explode("{", $step2);
                $arg = array_merge(array($matches[2][$i]), $step3);
            } else {
                $arg = $matches[2][$i];
            }
            $ntext = $this->handleEnvironment($matches[1][$i], $arg, $matches[4][$i]);
            $text = str_replace_first($matches[0][$i], $ntext, $text);
        }
        return $text;
    }
    
    /**
     * Function: specialEnvReplacePass ( text to work on )
     * Regex search for \begin{env} ... \end{env}
     * Raw big regex, subject to being changed in the future.
     * This is for special environments, that want an unprocessed
     * body. Examples are verbatim, python etc.
     * Note that this runs even before the syntax check.
     * Returns: text with replaced environments
     **/
    public function specialEnvReplacePass($text) {
        foreach($this->_SpecialEnvs as $key => $value) {
            $regex = "/\\\\begin\\s*\\{\\s*".
                        $key."\\s*}((?:.|\\n)*)\\\\end\\s*{\\s*".
                        $key."\\s*}/imU";
            preg_match_all($regex, $text, $matches);
            for($i = 0; $i < count($matches[0]); $i++) {
                $ntext = $value->handleSpecialEnvironment($key, $matches[1][$i]);
                if ($ntext !== false)
                    $text = str_replace_first($matches[0][$i], $ntext, $text);
            }
        }
        return $text;
    }

    /**
     * Function: parse ( text )
     * Function to call with the text you want MarTeX to work on.
     * Does all replace passes in sequence and until all commands
     * are handled. 
     * Returns: true when no error was encountered.
     **/
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
        for($i = 0; $i < count($this->_modObjs); $i ++ ) {
            $this->_modObjs[$i]->reset();
        }
        
        // Parse the special environments
        $text = $this->specialEnvReplacePass($text);
        
        // Check syntax
        if (!$this->syntaxValidity($text)) {
            $this->_Result = "Fatal error: no output produced.";
            return false;
        }
        
        // Parse with simplereplace (No need for recursion)
        $text = $this->simpleReplacePass($text);
        
        // Parse with complexreplace
        do {
            $oldtext = $text;
            $text = $this->complexPreProcess($text);
            $text = $this->complexReplacePass($text);
        }
        while($oldtext != $text);
        
        // Parse with environmentreplace
        do {
            $oldtext = $text;
            $text = $this->environmentReplacePass($oldtext);
        }
        while($oldtext != $text);
        
        // Set result;
        $this->_Result = $text;
        
        // Detect unclosed environments.
        if(count($this->_EnvStack)> 0) {
            $this->parseError("(MarTeX) Error: unclosed environment(s) detected: " . 
                                                    implode(', ', $this->_EnvStack). 
                                                        ".");
        }
        return !$this->_hasError;
    }

    /**
     * Function: registerModule ( module object )
     * Register a module.
     * Please note this can be order-dependent.
     * Commands can be overwritten by later added modules.
     * Environments cannot be overwritten.
     * This is by design.
     * Returns: None
     **/
    public function registerModule($modObject) {
        // Please note that commands can be overwritten, but environments cannot.
        $commands = $modObject->registerCommands();
        $environments = $modObject->registerEnvironments();
        $specialenvironments = $modObject->registerSpecialEnvironments();
        
        if(count($commands)> 0) {
            $this->_Funcs = array_merge(// Merge an array
            $this->_Funcs, // To our existing functions
            array_combine($commands, // That points registered commands
            array_fill(0, count($commands), $modObject)));
            // To the module object
        }
        
        if(count($environments)> 0) {
            $this->_Envs = array_merge(// Merge an array   
            array_combine($environments, // That points registered environments
            array_fill(0, count($environments), $modObject)), // To the module object
            $this->_Envs);
            // To our existing environments            
        }
        
        if(count($specialenvironments)> 0) {
            $this->_SpecialEnvs = array_merge(// Merge an array   
            array_combine($specialenvironments, // That points registered environments
            array_fill(0, count($specialenvironments), $modObject)), // To the module object
            $this->_SpecialEnvs);
            // To our existing environments            
        }
        
        $this->_modObjs[] = $modObject;
        // Add module to modlist
        $modObject->MarTeX = $this;
        // And give the module object access to our environment variables.
    }

    /**
     * Function: parseError ( error string )
     * Raise a parse error, to be seen by the user.
     * Note that this does not stop the parsing execution.
     * Returns: None
     **/
    public function parseError($error) {
        $this->_newError = true;
        if(!$this->_Error) {
            $this->_hasError = true;
            $this->_Error = $error;
        } else {
            $this->_Error .= "\n" . $error;
        }
    }

    /**
     * Function: hasError ( )
     * Check if we have encountered an error while parsing.
     * Returns: boolean hasError
     **/
    public function hasError() {
        return $this->_hasError;
    }

    /**
     * Function: getError ( )
     * Returns: String with all errors
     **/
    public function getError() {
        return $this->_Error;
    }

    /**
     * Function: getResult ( )
     * Returns: Result of last parse() call
     **/
    public function getResult() {
        return $this->_Result;
    }
    
    private $_simple_replace_array;
    private $_EnvVars = array();
    private $_GlobalVars = array();
    private $_modObjs = array();
    private $_Funcs = array();
    private $_EnvStack = array();
    private $_Envs = array();
    private $_SpecialEnvs = array();
    private $_hasError = false;
    private $_newError = false;
    private $_Error;
    private $_Result;
}

function str_replace_first($from, $to, $subject) {
    $from = '/' . preg_quote($from, '/'). '/';
    return preg_replace($from, $to, $subject, 1);
}

function str_replace_all($from, $to, $subject) {
    $from = '/' . preg_quote($from, '/'). '/';
    return preg_replace($from, $to, $subject);
}

?>
