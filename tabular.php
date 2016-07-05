<?php
namespace MarTeX;

require_once (__DIR__."/module.php");

class Tabular extends MarTeXmodule{
    public function reset() {
        return true;
    }
    
    public function registerCommands() {
        return array("tabular/hline", "tabular/newline", "tabular/linewidth");
    }
    
    public function handleCommand($command, $argument) {
        switch($command) {
            case "tabular/hline":
                // We must indicate a hline here, but it shouldn't be parsed again next time around
                return " \\/hline ";
            case "tabular/newline":
                // Again here
                return " \\/newline ";
            case "tabular/linewidth":
                // Linewidth is applied to whole table, may parse as variable
                $argument = $this->valisaniArgument($argument, 1, "Int");
                return ModuleTools::setVar("linewidth", $argument);
        }
    }
    
    public function registerEnvironments() {
        return array("tabular");
    }
    
    public function handleEnvironment($env, $options, $content) {
        $colspec = $this->parsecols($options[1]);
        
        $vars = ModuleTools::getVars($content);
        $text = $this->str_replace_all("\n", "", ModuleTools::getText($content));
        
        if (array_key_exists("linewidth", $vars)) {
            $linewidth = intval($vars["linewidth"]);
        }
        else {
            $linewidth = 1;
        }
        
        $output = "<table cellpadding='4'; style='margin:6px;border-collapse:collapse;border-style:solid;border-width:0px;'>\n";
        
        $lines = explode("\/newline", $text);
        for( $i = 0; $i < count($lines); $i++ ) {
            // Do our prep for the line. Count the hlines, and from there figure out the row style;
            $hlinenum = -1;
            $line = $lines[$i];
            do {
                $oline = $line;
                $hlinenum++;
                $line = $this->str_replace_first("\/hline","", $oline);
            }
            while($oline != $line);
            $curlineborderstyle = 0;
            switch($hlinenum) {
                case 0:
                    break;
                case 1:
                    $curlineborderstyle = 1;
                break;
                default:
                    $this->raiseError("Only 2 hlines allowed per row, ommitting extra.");
                case 2:
                    $curlineborderstyle = 16;
                break;
            }
            
            if ($i == count($lines) - 2) {
                // Maybe this is the last line with real content, maybe we need to add extra lines...
                if (count(explode(' & ', $lines[$i+1])) == 1) {
                    //Yup, lets see if we need any bottom borderlines
                    $hlinenum = -1;
                    $nline = $lines[$i+1];
                    do {
                        $oline = $nline;
                        $hlinenum++;
                        $nline = $this->str_replace_first("\/hline","", $oline);
                    }
                    while($oline != $nline);
                    switch($hlinenum) {
                        case 0:
                            break;
                        case 1:
                            $curlineborderstyle += 2;
                        break;
                        default:
                            $this->raiseError("Only 2 hlines allowed per row, ommitting extra.");
                        case 2:
                            $curlineborderstyle = 32;
                        break;
                    }
                }  
            }
            
            $output.= "\t<tr>\n\t\t";
            $cells = explode(' & ',$line);
            if (count($cells) == 1) {
                continue;
            }
            
            if (count($cells) > count($colspec)) {
                $this->raiseError("Too many elements in row ".intval($i+1).".");
            }
            else if (count($cells) < count($colspec)) {
                $this->raiseWarning("Not enough elements in row ".intval($i+1).".");
                while (count($cells) < count($colspec)) {
                    $cells[] = "";
                }
            }
            
            
            for ($j = 0; $j < count($cells); $j++) {
                if ($j < count($colspec)) {
                    // We have markup for this thing
                    $output .= "<td style='min-width:20px;min-height:12px;".$this->tdcss($colspec[$j]+$curlineborderstyle, $linewidth)."'>".$cells[$j]."</td>";
                }
                else {
                    // Meh, out of bounds
                    $output .= "<td>".$cells[$j]."</td>";
                }
            }
            $output.= "\t</tr>\n";
        }
        $output.= "</table>\n";
        return $output;
    }
    
    public function tdcss($attr, $linewidth) {
        $style = "";
        if ($attr & 1 && !($attr & 16)) {
            $style .= "border-top: ".$linewidth."px solid black;";
        }
        if ($attr & 2 && !($attr & 32)) {
            $style .= "border-bottom: ".$linewidth."px solid black;";
        }
        if ($attr & 4 && !($attr & 64)) {
            $style .= "border-left: ".$linewidth."px solid black;";
        }
        if ($attr & 8 && !($attr & 128)) {
            $style .= "border-right: ".$linewidth."px solid black;";
        }
        if ($attr & 16) {
            $style .= "border-top: ".($linewidth*3)."px double black;";
        }
        if ($attr & 32) {
            $style .= "border-bottom: ".($linewidth*3)."px double black;";
        }
        if ($attr & 64) {
            $style .= "border-left: ".($linewidth*3)."px double black;";
        }
        if ($attr & 128) {
            $style .= "border-right: ".($linewidth*3)."px double black;";
        }
        if ($attr & 256 && $attr & 512) {
            $style .= "text-align: center;";
        }
        else if ($attr & 256) {
            $style .= "text-align: left;";
        }
        else if ($attr & 512) {
            $style .= "text-align: right;";
        }
        return $style;
    }
    
    public function parsecols($cols) {
        // This is the latex column spec parser, they look something like: |l c || r r |
        $colspec = array();
        $numb = 0;
        
        $strlen = strlen( $cols );
        for( $i = 0; $i <= $strlen; $i++ ) {
            $char = substr( $cols, $i, 1 );
            switch($char) {
                case " ": //Do nothing
                    break;
                case "|":
                    $numb += 1;
                    break;
                case "c":
                    switch ($numb) {
                        case 0:
                            $colspec[] = 256+512;
                        break;
                        case 1:
                            $colspec[] = 4+256+512;
                        break;
                        default:
                            $this->raiseWarning("Too many |'s in column specification '".$cols."', ignoring extra.");
                        case 2:
                            $colspec[] = 64+256+512;
                        break;
                    }
                    $numb = 0;
                break;
                case "l":
                    switch ($numb) {
                        case 0:
                            $colspec[] = 256;
                        break;
                        case 1:
                            $colspec[] = 4+256;
                        break;
                        default:
                            $this->raiseWarning("Too many |'s in column specification '".$cols."', ignoring extra.");
                        case 2:
                            $colspec[] = 64+256;
                        break;
                    }
                    $numb = 0;
                break;
                case "r":
                    switch ($numb) {
                        case 0:
                            $colspec[] = 512;
                        break;
                        case 1:
                            $colspec[] = 4+512;
                        break;
                        default:
                            $this->raiseWarning("Too many |'s in column specification '".$cols."', ignoring extra.");
                        case 2:
                            $colspec[] = 64+512;
                        break;
                    }
                    $numb = 0;
                break;  
            }
        }
        // Does the table need a right border? If the numb value is nonzero, there are borders left to add:
        switch ($numb) {
            case 0:
                // Done, no need to add anything
            break;
            case 1:
                $colspec[count($colspec)-1] += 8;
            break;
            default:
                $this->raiseWarning("Too many |'s in column specification '".$cols."', ignoring extra.");
            case 2:
                $colspec[count($colspec)-1] += 128;
            break;
        }
        return $colspec;        
    }    
}

?>
