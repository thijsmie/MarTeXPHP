<?php
namespace MarTeX;

require_once (__DIR__."/module.php");

class Math extends MarTeXmodule{
    
    public function registerSpecialEnvironments() {
        return array("equation", "eqarray");
    }
    
    public function registerEnvironments() {
        return array("posteqarray");
    }
    
    public function handleSpecialEnvironment($env, $content) {
        // We are going to urlencode this bullshit and send it off with js to codecogs.
        // If it is an eqarray we would like to align things like a tabular
        // Problem is, we cannot produce html here yet, the syntax check will complain
        // So we make a different env that we reprocess later.
        switch($env) {
            case 'equation':
                return "$$".preg_replace('/\\\\/m', '&#92;', htmlentities($content))."$$";
            case 'eqarray':
                $lines = explode('\\\\', $content);
                foreach ($lines as $lkey => $line) {
                    $bits = explode('&', $line);
                    foreach($bits as $bkey => $bit) {
                        $bits[$bkey] = preg_replace('/\\\\/m', '&#92;', htmlentities($bit));
                    }
                    $lines[$lkey] = implode(' @@ ', $bits);
                }
                $lines = implode('\/newline ', $lines);
                return "\\begin{posteqarray}".$lines."\\end{posteqarray}";
        }
    }
    
    public function handleEnvironment($env, $options, $content) {
        switch($env) {
            case 'posteqarray':
                $lines = explode('\/newline', $content);
                foreach ($lines as $lkey => $line) {
                    $bits = explode(' @@ ', $line);
                    $lines[$lkey] = implode("$$</td><td>$$", $bits);
                }
                $lines = implode("$$</td></tr><tr><td>$$", $lines);
                return "<table><tr><td>$$".$lines."$$</td></tr></table><br>";
        }
    }    
}

?>
