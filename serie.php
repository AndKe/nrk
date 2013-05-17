<?Php
include 'functions.php';
depend('mkvmerge');

$serier=array('http://tv.nrk.no/serie/saann-er-jeg-og-saann-er-det/msue11005111/22-12-2012');
if(file_exists('sesonger unntak.txt'))
	$unntak=explode("\r\n",file_get_contents('sesonger unntak.txt'));
else
	$unntak=array();

foreach($serier as $url)
{
	$sesonger=episodelist($url);
	//echo implode("\n",$episoder[3])."\n"; //Vis liste over episoder
	//print_r($sesonger);
	//die();
	
	foreach($sesonger as $sesongkey=>$sesong) //Gå gjennom sesongene
	{

		preg_match('^(.+) .+^',$sesonger[0]['titler'][0],$serietittel);
		$serietittel=html_entity_decode($serietittel[1]);
		$outpath=$config['outpath'].filnavn($serietittel.' '.$sesong['sesongtittel']).'/';
		if(array_search($serietittel.' '.$sesong['sesongtittel'],$unntak)!==false) //Sjekk om denne sesongen ikke skal rippes
			continue;
		foreach ($sesong['url'] as $episodekey=>$url) //Gå gjennom episodene i sesongen
		{
			$filnavn=filnavn($sesong['titler'][$episodekey]);
			$tsfil=$outpath.$filnavn.'.ts';
			$mkvfil=$outpath.$filnavn.'.mkv';
			$episodedata=get($sesong['url'][$episodekey],false,false,$agent);
			//echo $mkvfil."\n";
			//echo var_dump(file_exists($mkvfil));
			if(file_exists($outpath.$filnavn.'.mkv')) //Sjekk om episoden allerede er lastet ned
			{
				//echo "eksisterer\n";
				if(varighetsjekk($episodedata,$mkvfil))
				{
					echo "".html_entity_decode($sesong['titler'][$episodekey])." er allerede lastet ned\n";
					continue;
				}
				else
				{
					//die();
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
			}
			}
		
	}
	
	unset($mkvfile,$episoder);
}



//print_r($episoder);

?>
