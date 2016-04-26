<?php
namespace MarTeX;

require_once "module.php";

class Itemize extends MarTeXmodule implements IMarTeXmodule {
    public function reset() {
    
    }
    
    public function registerCommands() {
        return array("itemize/item", "enumerate/item");
    }
    
    public function registerEnvironments() {
        return array("itemize", "enumerate");
    }
}

?>
