<?Php
unset($argv[0]);
chdir(dirname(realpath(__FILE__))); //Bytt til mappen scriptet ligger i så relative filbaner blir riktige
require 'functions.php';
$nrk=new nrkripper;
$nrk->dependcheck->depend(array('avconv'));
$data=$nrk->get($argv[1]);
$segments=$nrk->segmentlist($data);

//print_r($result);
//$segments=$nrk->get($result[1].'index_1_a.m3u8?null=');
$title=$nrk->finntittel($data);
$utfil=$nrk->config['outpath'].$nrk->filnavn($title);
$nrk->downloadts($segments,$utfil);
shell_exec("avconv -i \"$utfil.ts\" -bsf:a aac_adtstoasc -acodec copy \"$utfil.m4a\"");

echo $nrk->error;