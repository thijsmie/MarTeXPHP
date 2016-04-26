<?php
namespace MarTeX;

require_once "module.php";

class Document extends MarTeXmodule implements IMarTeXmodule {
    public function registerCommands() {
        return array("section", "subsection", "subsubsection", "textbf", "textit", "hline", "ref", "ref>", "define"); 
    }
    
    public function handleCommand($command, $argument) {

        switch($command) {
            case "section":
                $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");
                return "<h3>".$argument."</h3>";
            case "subsection":
                $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");
                return "<h4>".$argument."</h4>";
            case "subsubsection":
                $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");
                return "<h5>".$argument."</h5>";
            case "textbf":
                $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");
                return "<b>".$argument."</b>";
            case "textit":
                $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");
                return "<i>".$argument."</i>";
            case "hline":
                $argument = $this->valisaniArgument($argument, 0, "");
                return "<hr>";           
            case "ref":
                $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");    
                $reference = $this->MarTeX->getGlobalVar("label:".$argument);
                if ($reference === false) {
                    // This label is not defined, but may be defined below this call.
                    // Therefore, change the functioncall to something that is impossible to call as user
                    // So we can try again on the next iteration
                    return "\\ref>{".$argument."}";
                }
                return $reference;
            case "ref>":
                $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");    
                $reference = $this->MarTeX->getGlobalVar("label:".$argument);
                if ($reference === false) {
                    // This label is not defined on second pass, throw warning.
                    // Note, this means backward declaration of dynamic labels is not allowed, but forwards is!
                    $this->MarTeX->parseError("(MarTeX/document) Error: reference label '".$argument."' was not declared.");
                    return "?";
                }
                return $reference;
            case "define":
                $argument = $this->valisaniArgument($argument, 2, array("String", "String"));
                $this->MarTeX->setGlobalVar($argument[0], $argument[1]);
        }
    }
    
    public function registerEnvironments() {
        return array("document");
    }
    
    public function handleEnvironment($env, $options, $text) {
        // Just passthrough
        return $text;
    }
    
    public function reset() {
        return true;
    }
}

?>
