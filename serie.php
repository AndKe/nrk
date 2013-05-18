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
		$outpath=$nrk->config['outpath'].$nrk->filnavn($serietittel.' '.$sesong['sesongtittel']).'/';
		if(isset($unntak) && array_search($serietittel.' '.$sesong['sesongtittel'],$unntak)!==false) //Sjekk om denne sesongen ikke skal rippes
			continue;
		if(!file_exists($outpath))
			mkdir($outpath,0777,true);	
		foreach ($sesong['url'] as $episodekey=>$url) //Gå gjennom episodene i sesongen
		{
			var_dump($url);
			var_dump($outpath);
			var_dump($nrk->nrkrip($url,$outpath));
			echo $nrk->error;
			echo $nrk->sjekk->error;

			/*die($url."\n");
			$filnavn=$nrk->filnavn($sesong['titler'][$episodekey]);
			$tsfil=$outpath.$filnavn.'.ts';
			$mkvfil=$outpath.$filnavn.'.mkv';
			$episodedata=$nrk->get($sesong['url'][$episodekey]);
			if(file_exists($outpath.$filnavn.'.mkv')) //Sjekk om episoden allerede er lastet ned
			{
				//echo "eksisterer\n";
				$nrk->sjekkfil($mkvfil,$nrk->varighet($episodedata));
				if($nrk->varighetsjekk($episodedata,$mkvfil))
				{
					echo html_entity_decode($sesong['titler'][$episodekey])." er allerede lastet ned\n";
					continue;
				}
				else
				{
					rename($tsfil,$tsfil.'.old');
					rename($mkvfil,$mkvfil.'.old');
				}
			}
			elseif(file_exists($tsfil))
				rename($tsfil,$tsfil.'.old');
			
			

			
			if($segmentlist=segmentlist($episodedata)) //Hvis segmentliste er funnet, last ned
			{
				
				if(!file_exists($outpath))
					mkdir($outpath);
				file_put_contents($outpath.$filnavn.'.htm',$episodedata);
				//echo "Laster ned {$episoder[1][$key]}\n"; //Vis hvilken episode som lastes ned
				//echo $outfile."\n";
				//echo $baseurl."\n";
				//$segmentlist=get($baseurl.'index_4_av.m3u8?e=6303f0e8a89ec6bd&id=',false,$url,$agent); //Hent liste over segmenter
				
				download($segmentlist,$outpath.$filnavn); //Last ned episoden
				subtitle($sesong['id'][$episodekey],$outpath.$filnavn); //Hent undertekst
			}*/
			}
		
	}
	
	unset($mkvfile,$episoder);
}


//print_r($episoder);

?>
