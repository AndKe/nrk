<?Php
unset($argv[0]);
include 'functions.php';
$nrk=new nrkripper;
foreach ($argv as $url)
{
	if(!$nrk->nrkrip($url,$nrk->config['outpath']))
		echo "Kunne ikke laste ned fra $url\n";
}
?>
