<?php

include __DIR__.'/../martex.php';
include __DIR__.'/../soa.php';
include __DIR__.'/../verbatim.php';

$Tex = new MarTeX\MarTeX();
$Soa = new MarTeX\Soa();

//Cannot use usepackage on verbatim since it does preprocessing;
$Verbatim = new MarTeX\Verbatim();

$Tex->registerModule($Soa);
$Tex->registerModule($Verbatim);


$input = file_get_contents("MarTeX.tex");
$Tex->parse($input);
echo $Tex->getError()."\n";
file_put_contents("documentation.html",$Tex->getResult());

?>
