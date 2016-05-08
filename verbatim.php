<?php
namespace MarTeX;

require_once (__DIR__."/module.php");

class Verbatim extends MarTeXmodule {
    
    public function registerCommands() {
        return array("command");
    }
    
    public function handleCommand($command, $argument) {
        if (!is_array($argument))
            return "&#92;".$argument;
        $arg = "";
        for( $i = 1; $i < count($argument); $i++)
            $arg .= "&#123;".$argument[$i]."&#125;";
        return "&#92;".$argument[0].$arg."";
    }
    
    public function registerSpecialEnvironments() {
        return array("verbatim");
    }
    
    public function handleSpecialEnvironment($env, $text) {
        // Just do some replacin and it'll be fiiiiineeee....
        $replace = array("/\{/", "/\}/", '/\\\\/');
        $to = array("&#123;", "&#125;", "&#92;");
        return trim(preg_replace($replace, $to, $text));
    }
}
?>