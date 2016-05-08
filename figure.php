<?php
namespace MarTeX;

require_once (__DIR__."/module.php");

class Figure extends MarTeXmodule {
    public function reset() {
        $this->_labelNumber = 0;
    }
    
    public function registerCommands() {
        return array("figure/caption", "figure/includegraphics", "figure/width", "figure/height", "figure/alttext", "figure/label"); 
    }
    
    public function handleCommand($command, $argument) {
        $argument = $this->valisaniArgument($argument, 1, "String/nowhitespace");
        switch($command) {
            case "figure/caption":
                return ModuleTools::setVar("caption", $argument);
            case "figure/includegraphics":
                return ModuleTools::setVar("image", $argument);
            case "figure/width":
                return ModuleTools::setVar("width", $argument);
            case "figure/height":
                return ModuleTools::setVar("height", $argument); 
            case "figure/alttext":
                return ModuleTools::setVar("alt", $argument);
            case "figure/label":  
                $this->_labelNumber += 1; 
                if ($this->MarTeX->getGlobalVar("figureheader") === false) {
                    $this->MarTeX->setGlobalVar("figureheader", "Figure");
                }
                if ($this->MarTeX->getGlobalVar("label:".$argument) !== false) {
                    $this->MarTeX->parseError("(MarTeX/Figure) Warning: label ".$argument." was already defined.");
                }
                $this->MarTeX->setGlobalVar("label:".$argument, $this->MarTeX->getGlobalVar("figureheader")." ".$this->_labelNumber);     
                return ModuleTools::setVar("label", ucfirst($this->MarTeX->getGlobalVar("figureheader"))." ".$this->_labelNumber);     
        }
    }
    
    public function registerEnvironments() {
        return array("figure");
    }
    
    public function handleEnvironment($env, $options, $text) {
        $vars = ModuleTools::getVars($text);        
        $output = "<figure>\n";
        
        // Image
        if (array_key_exists("image", $vars)) {
            $output .= "<img src='".$vars["image"]."' ";
        }
        else {
            $this->MarTeX->parseError("(MarTeX/Figure) Warning: Environment 'figure' did not contain an image.");
            $output .= "<img src='NOOOOH.png' ";
        }
        
        // Optional stuff
        if (array_key_exists("alt", $vars)) {
            $output.= "alt='".$vars["alt"]."' ";
        }
        
        if (array_key_exists("width", $vars)) {
            $output.= "width='".$vars["width"]."' ";
        }
        
        if (array_key_exists("height", $vars)) {
            $output.= "height='".$vars["height"]."' ";
        }
        
        $output.= ">\n";
        
        // Caption
        if (array_key_exists("caption", $vars)) {
            $output.= "<figcaption>";
            if (array_key_exists("label", $vars)) {
                $output .= $vars["label"].": ";
            }
            $output .= $vars["caption"]."</figcaption>\n";
        }
        else if (array_key_exists("label", $vars)) {
            $output .= "<figcaption>".$vars["label"]."</figcaption>\n";
        }
        
        $output.="</figure>";
        return $output;        
    }
    
    public $_labelNumber;
}

?>
