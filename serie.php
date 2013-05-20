<?Php
include 'functions.php';
$nrk=new nrkripper;
$serier=explode("\n",trim(file_get_contents('serier.txt'))); //Hent liste over serier
if(file_exists('sesonger unntak.txt'))
	$unntak=explode("\n",str_replace("\r","",file_get_contents('sesonger unntak.txt'))); //Finn sesonger som ikke skal hentes

foreach($serier as $url)
{
	$url=trim($url); //sesonger.txt deles etter \n, hvis det er brukt \r\n fjernes \r her
	$sesonger=$nrk->episodelist($url);
	
	foreach($sesonger as $sesongkey=>$sesong) //Gå gjennom sesongene
	{
		preg_match('^(.+) .+^',$sesonger[0]['titler'][0],$serietittel);
		$serietittel=html_entity_decode($serietittel[1]);
		echo $serietittel.' '.$sesong['sesongtittel']."\n";
		$outpath=$nrk->config['outpath'].$nrk->filnavn($serietittel.' '.$sesong['sesongtittel']).'/'; //Lag mappenavn for sesongen
		if(isset($unntak) && array_search($serietittel.' '.$sesong['sesongtittel'],$unntak)!==false) //Sjekk om denne sesongen ikke skal rippes
			continue;
		if(!file_exists($outpath)) //Lag mappe
			mkdir($outpath,0777,true);	
		foreach ($sesong['url'] as $episodekey=>$url) //Gå gjennom episodene i sesongen
		{
			
			$status=$nrk->nrkrip($url,$outpath);
			if($status!==false)
				var_dump($status);
			else
				echo $nrk->error;
			//echo $nrk->sjekk->error;
		}
	}	
}
?>
