<?Php
unset($argv[0]);
chdir(dirname(realpath(__FILE__))); //Bytt til mappen scriptet ligger i så relative filbaner blir riktige
require 'functions.php';
$nrk=new nrkripper;
$data=$nrk->get($argv[1]);
$segments=$nrk->segmentlist($data);

//print_r($result);
//$segments=$nrk->get($result[1].'index_1_a.m3u8?null=');
$nrk->downloadts($segments,'radio');

echo $nrk->error;