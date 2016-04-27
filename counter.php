<?php
namespace MarTeX;

require_once (__DIR__."/module.php");

class Counter extends MarTeXmodule implements IMarTeXmodule {
    public function registerCommands() {
        return array("newcounter", "addtocounter", "setcounter", "stepcounter", "arabic", "value");
    }
    
    public function handleCommand($command, $argument) {
        switch($command) {
            case "newcounter":
                $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");
                $this->MarTeX->setGlobalVar("counter_".$argument, 1);
                return "";
            case "addtocounter":
                $argument = $this->valisaniArgument($argument, 2, array("String", "Int"));
                $this->MarTeX->setGlobalVar("counter_".$argument[0], 
                    $this->MarTeX->getGlobalVar("counter_".$argument[0])
                    + intval($argument[1]));
                return "";
            case "setcounter":
                $argument = $this->valisaniArgument($argument, 2, array("String", "Int"));
                $this->MarTeX->setGlobalVar("counter_".$argument[0], 
                    intval($argument[1]));
                return "";
            case "stepcounter":
                $argument = $this->valisaniArgument($argument, 1, "String");
                $this->MarTeX->setGlobalVar("counter_".$argument, 
                    $this->MarTeX->getGlobalVar("counter_".$argument)
                    + 1);
                return "";
            case "arabic":
            case "value":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return strval($this->MarTeX->getGlobalVar("counter_".$argument));
        }
    }
    
    public function registerEnvironments() {
        return array();
    }
    
    public function handleEnvironment($env, $options, $text) {
        return $text;
    }
    
    public function reset() {
        // Depends on global variabels, not internal, so the main module has removed all counters already.
        return true;
    }
}
