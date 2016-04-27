<?php

include '../martex.php';
include '../docu.php';

$Tex = new MarTeX\MarTeX();
$Docu = new MarTeX\Docu();

$Tex->registerModule($Docu);


$input = file_get_contents("MarTeX.tex");
$Tex->parse($input);
echo $Tex->getError();
file_put_contents("documentation.html",$Tex->getResult());

?>
