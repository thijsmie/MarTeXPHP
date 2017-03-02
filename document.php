<?php
namespace MarTeX;

require_once (__DIR__."/module.php");

class Document extends MarTeXmodule {
    public function registerCommands() {
        return array("section", "subsection", "subsubsection", "textbf", "textit", "underline", "hline", "smallcaps", "ref", "refpass", "define", "newline", "paragraph", "title", "href"); 
    }
    
    public function handleCommand($command, $argument) {

        switch($command) {
            case "section":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return "<h2>".$argument."</h2>";
            case "subsection":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return "<h3>".$argument."</h3>";
            case "subsubsection":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return "<h4>".$argument."</h4>";
            case "textbf":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return "<b>".$argument."</b>";
            case "textit":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return "<i>".$argument."</i>";
            case "underline":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return "<u>".$argument."</u>";
            case "smallcaps":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return "<span style='font-variant: small-caps;'>".$argument."</span>";
            case "paragraph":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return "<p>".$argument."</p>";
            case "title":
                $argument = $this->valisaniArgument($argument, 1, "String");
                return "<h1>".$argument."</h1>";
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
                    return "\\refpass{".$argument."}";
                }
                return $reference;
            case "refpass":
                $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");    
                $reference = $this->MarTeX->getGlobalVar("label:".$argument);
                if ($reference === false) {
                    // This label is not defined on second pass, throw warning.
                    // Note, this means backward declaration of dynamic labels is not allowed, but forwards is!
                    $this->raiseError("Reference label '".$argument."' was not declared.");
                    return "?";
                }
                return $reference;
            case "define":
                $argument = $this->valisaniArgument($argument, 2, array("String", "String"));
                $this->MarTeX->setGlobalVar($argument[0], $argument[1]);
                return "";
            case "newline": 
                return "<br>"; 
            case "href":
                $argument = $this->valisaniArgument($argument, 2, array("String", "String"));
                return "<a href='".$argument[0]."'>".$argument[1]."</a>";              
        }
    }
    
    public function registerEnvironments() {
        return array("document", "paragraph", "code");
    }
    
    public function handleEnvironment($env, $options, $text) {
        switch($env) {
            case "document":
                return $text;
            case "paragraph":
                return "<p>".$text."</p>";
            case "code":
                if (is_array($options)) {
                    return '<pre><code class="language-'.$options[1].'">'.$this->str_replace_first("\n","",$text)."</code></pre>";
                }
                return "<pre>".$text."</pre>";
        }
    }
    
    public function reset() {
        return true;
    }
}

?>
