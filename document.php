<?php
namespace MarTeX;

require_once (__DIR__."/module.php");

class Document extends MarTeXmodule {
    public function registerCommands() {
        return array("section", "subsection", "subsubsection", "textbf", "textit", "underline", "hline", "smallcaps", "ref", "refpass", "define", "newline", "paragraph", "title", "href", "par","color", "colour"); 
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
            case "par":
                return "<br><br>";
            case "color":
            case "colour":
                if (!is_array($argument) or count($argument) < 2) {
                    $this->raiseError("Invalid color command.");
                    return "";
                }
                $coltype = "";
                $coldef = "";
                $txt = "";
                if (count($argument) == 2) {
                    $coldef = $argument[0];
                    $txt = $argument[1];
                    if ($argument[0][0] == '#') {
                        $coltype = "hex";
                    }
                    else if (substr_count($argument[0], ',') == 2) {
                        $coltype = "rgb";
                    }
                    else if (substr_count($argument[0], ',') == 3) {
                        $coltype = "rgba";
                    }
                    else {
                        $this->raiseError("Unable to guess colour type.");
                        return "";
                    }
                }
                else if (count($argument == 3)) {
                    $coltype = $argument[0];
                    $coldef = $argument[1];
                    $txt = $argument[2];
                }
                else {
                    $this->raiseError("Invalid number of arguments.");
                    return "";
                }
                
                switch($coltype) {
                    case "hex":
                        return "<span style='color:#" . 
                            preg_replace("/[^a-zA-Z0-9]+/", "", $coldef) . 
                             "'>".$txt."</span>";
                    case "rgb":
                        $bins = explode(',' , $coldef);
                        if (count($bins) != 3) {
                            $this->raiseError("Invalid rgb colour definition.");
                            return "";
                        }
                        return "<span style='color:rgb(" . 
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[0]) . "," .
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[1]) . "," .
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[2]) . 
                             ")'>".$txt."</span>";
                    case "rgba":
                        $bins = explode(',', $coldef);
                        if (count($bins) != 4) {
                            $this->raiseError("Invalid rgb colour definition.");
                            return "";
                        }
                        return "<span style='color:rgba(" . 
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[0]) . "," .
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[1]) . "," .
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[2]) . "," .
                            preg_replace("/[^a-zA-Z0-9.]+/", "", $bins[3]) .
                             ")'>".$txt."</span>";
                    case "hsl":
                        $bins = explode(',', $coldef);
                        if (count($bins) != 3) {
                            $this->raiseError("Invalid rgb colour definition.");
                            return "";
                        }
                        return "<span style='color:hsl(" . 
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[0]) . "," .
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[1]) . "%," .
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[2]) . "%" .
                             ")'>".$txt."</span>";
                    case "hsla":
                       $bins = explode(',', $coldef);
                        if (count($bins) != 4) {
                            $this->raiseError("Invalid hsla colour definition.");
                            return "";
                        }
                        return "<span style='color:hsla(" . 
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[0]) . "," .
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[1]) . "%," .
                            preg_replace("/[^a-zA-Z0-9]+/", "", $bins[2]) . "%," .
                            preg_replace("/[^a-zA-Z0-9.]+/", "", $bins[3]) . 
                             ")'>".$txt."</span>";
                }
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
