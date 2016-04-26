<?php
namespace MarTeX;

require_once "module.php";

class Tabular extends MarTeXmodule implements IMarTeXmodule {
    public function reset() {
        return true;
    }
    
    public function registerCommands() {
        return array("tabular/hline", "tabular/cline");
    }
    
    public function handleEnvironment($env, $options, $content) {
    
    }
}
