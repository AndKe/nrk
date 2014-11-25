<?Php
chdir(dirname(realpath(__FILE__))); //Bytt til mappen scriptet ligger i så relative filbaner blir riktige
include 'functions.php';
$nrk=new nrkripper;

$options = getopt("",array('subsonly'));
$serier=array_slice($argv,count($options)+1); //Hent liste over serier fra kommandolinjen

if(empty($serier))
	$serier=explode("\n",trim(file_get_contents('serier.txt'))); //Hent liste over serier fra fil

if(file_exists('sesonger unntak.txt'))
	$unntak=explode("\n",str_replace("\r","",file_get_contents('sesonger unntak.txt'))); //Finn sesonger som ikke skal hentes

foreach($serier as $url)
{
	$url=trim($url); //sesonger.txt deles etter \n, hvis det er brukt \r\n fjernes \r her

	$serieinfo=$nrk->serieinfo($url); //Hent sesongene og episodene i serien
	if($serieinfo===false)
		continue;
	echo $serieinfo['serietittel'].$nrk->br;
	foreach($serieinfo['sesonger'] as $sesong) //Gå gjennom sesongene
	{
		if(!empty($sesong['tittel'])) //Sjekk om sesongen har et navn
			$sesongnavn=$serieinfo['serietittel'].' '.$sesong['tittel'];
		else
			$sesongnavn=$serieinfo['serietittel'];
		echo $sesongnavn."\n";

		$outpath=$nrk->config['outpath'].$nrk->filnavn($sesongnavn).'/'; //Lag mappenavn for sesongen
		if(isset($unntak) && array_search($sesongnavn,$unntak)!==false) //Sjekk om denne sesongen ikke skal rippes
			continue;
		if(!file_exists($outpath)) //Lag mappe
			mkdir($outpath,0777,true);	
		foreach ($sesong['episoder'] as $episodekey=>$episode) //Gå gjennom episodene i sesongen
		{
			if(($eptext=$nrk->sesongepisode($episode['description'])) || ($eptext=$nrk->sesongepisode($episode['title'],true,$sesong['nummer'])))
				$nrk->tittel=$serieinfo['serietittel'].' '.$eptext;
			else
				$nrk->tittel=$episode['title'];
			if(isset($options['subsonly']))
				$status=$nrk->subtitle($episode['id'],$outpath.$nrk->filnavn($nrk->tittel));
			else
				$status=$nrk->nrkrip($serieinfo['baseurl'].'/'.$episode['id'],$outpath);

			if($status!==false)
				var_dump($status);
			else
			{
				echo $nrk->error;
				$nrk->error='';
			}
			//echo $nrk->sjekk->error;
		}
		if(count(scandir($outpath))==2) //Hvis mappen er tom, fjern den
			rmdir($outpath);
	}	
}
?>
