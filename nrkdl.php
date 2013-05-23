<?Php
unset($argv[0]);
chdir(dirname(realpath(__FILE__))); //Bytt til mappen scriptet ligger i så relative filbaner blir riktige
include 'functions.php';
$nrk=new nrkripper;
foreach ($argv as $url) //Hvert argument er et program som skal lastes ned
{
	if(!$nrk->nrkrip($url,$nrk->config['outpath']))
		echo "Kunne ikke laste ned fra $url\n";
}
?>
