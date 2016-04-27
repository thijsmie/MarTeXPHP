<?php
namespace MarTeX;

require_once (__DIR__."/module.php");

class Docu extends MarTeXmodule {
    // NOTE: This module is unsafe! Only run trusted texfiles or on a separate server and copy the generated content afterwards!!
    public function registerCommands() {
        return array("command", "descriptor", "envdescriptor", "include", "usepackage");
    }
    
    public function handleCommand($command, $argument) {
        switch($command) {
            case "command":
                if (!is_array($argument))
                    return "&#92;".$argument;
                $arg = "";
                for( $i = 1; $i < count($argument); $i++)
                    $arg .= "&#123;".$argument[$i]."&#125;";
                return "&#92;".$argument[0].$arg;
            break;
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
                    $path = ".";
                }
                if (!file_exists($path.'/'.$argument.".tex")) {
                    $this->MarTeX->parseError("(MarTeX/Docu) Error: include file '".$argument."' does not exist.");
                    return "";
                }
                return $this->MarTeX->simpleReplacePass(file_get_contents($path.'/'.$argument.".tex"));
            break;
            case "usepackage":
                if (!file_exists(__DIR__.'/'.$argument.".php")) {
                    $this->MarTeX->parseError("(MarTeX/Docu) Error: module '".__DIR__.'/'.$argument.".php"."' does not exist.");
                    return "";
                }
                require_once (__DIR__.'/'.$argument.".php");
                $argument = "MarTeX\\".ucfirst($argument);
                $this->MarTeX->registerModule(new $argument());
                return "";
            break;       
        }
    }
    
    public function registerEnvironments() {
        return array("page");
    }
    
    public function handleEnvironment($env, $option, $text) {
        return "<html><body>".$text."</body></html>";
    }
}
