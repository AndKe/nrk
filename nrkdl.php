<?Php
unset($argv[0]);
include 'functions.php';
$nrk=new nrkripper;
foreach ($argv as $url)
{
	$nrk->nrkrip($url,$config['outpath']);	
}
?>
