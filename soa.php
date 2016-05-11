<?php
namespace MarTeX;

require_once (__DIR__."/module.php");

class Soa extends MarTeXmodule {
    // NOTE: This module is unsafe! Only run trusted texfiles or on a separate server and copy the generated content afterwards!!
    public function registerCommands() {
        return array("descriptor", "envdescriptor", "include", "usepackage", "page/link", "document/link", "page/script");
    }
    
    public function handleCommand($command, $argument) {
        switch($command) {
            case "descriptor":
                $argument = $this->valisaniArgument($argument, 3, array("String", "String", "String"));
                return "<i>".$argument[0].":</i> <b>".$argument[1]."</b><br><p>".$argument[2]."</p>";
            break;
            case "envdescriptor":
                $argument = $this->valisaniArgument($argument, 2, array("String", "String"));
                return "<i>".$argument[0].":</i> <br><p>".$argument[1]."</p>";
            break;
            case "include":
                //Why the .tex? We wouldn't want people including php script content now would we...
                
                $path = $this->MarTeX->getGlobalVar("path");
                if ($path === false) {
                    $path = __DIR__;
                }
                else {
                    $path = __DIR__."/".$path;
                }
                if (!file_exists($path.'/'.$argument.".tex")) {
                    $this->MarTeX->parseError("(MarTeX/Soa) Error: include file '".$path.'/'.$argument.".tex"."' does not exist.");
                    return "";
                }
                
                $text = $this->MarTeX->specialEnvReplacePass(file_get_contents($path.'/'.$argument.".tex"));
                // Check syntax
                if (!$this->MarTeX->syntaxValidity($text)) {
                    $this->MarTeX->parseError("(MarTeX/Soa) Error: include file '".$argument."' has invalid syntax.");
                    return "";
                }          
                
                return $this->MarTeX->simpleReplacePass($text);
            break;
            case "usepackage":
                if (!file_exists(__DIR__.'/'.$argument.".php")) {
                    $this->MarTeX->parseError("(MarTeX/Soa) Error: module '".__DIR__.'/'.$argument.".php"."' does not exist.");
                    return "";
                }
                require_once (__DIR__.'/'.$argument.".php");
                $argument = "MarTeX\\".ucfirst($argument);
                $this->MarTeX->registerModule(new $argument());
                return "";
            break; 
            case "page/link":
                return "<link href='".$argument[0]."' rel='".$argument[1]."' type='".$argument[2]."' />";
            
            case "document/link":
                $this->MarTeX->parseError("(MarTeX/Soa) Error: link statements should occur before document.");
                return "";
                   
            case "page/script":
                return "<script src='".$argument[0]."' type='".$argument[1]."'></script>";    
        }
    }
    
    public function registerEnvironments() {
        return array("page", "document");
    }
    
    public function handleEnvironment($env, $option, $text) {
        switch($env) {
            case "page": 
                return "<!DOCTYPE html><html><head>".$text."</html>";
            case "document":
                return "</head><body>".$text."</body>";
        }
    }
}
?>
