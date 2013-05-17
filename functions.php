<?Php
class nrkripper
{
	private $ch;
	public $useragent='Mozilla/5.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B176 Safari/7534.48.3';
	public function __construct()
	{
		$this->ch=curl_init();	
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch,CURLOPT_USERAGENT,$this->useragent);
		curl_setopt($ch, CURLOPT_COOKIEFILE,'cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEJAR,'cookies.txt');

	}
	public function get($url)
	{
		curl_setopt($this->ch,CURLOPT_URL,$url);
		return curl_exec($this->ch);
	}


function title($id,$filename=true)
{
	$tip=$this->get($url='http://tv.nrk.no/programtooltip/'.$id);
	if(preg_match('^\<h1\>.*\</h1\>^',$tip,$tipresult))
	{
		$name=strip_tags($tipresult[0]);
		$name=html_entity_decode($name);
		if($filename)
			$name=str_replace(':','-',$name);
	}
	else
		$name=false;
	
	return $name;
}
function filnavn($tittel)
{
	$filnavn=html_entity_decode($tittel);
	$filnavn=str_replace(':','-',$filnavn);
	return $filnavn;
}
function getid($url)
{
	preg_match('^/([a-z]+[0-9]+/*)^',$url,$result);
	if(!isset($result[1]))
	{
		echo "Finner ikke id i url: $url\n";
		$result[1]=false;
		die();
	}
	
	return $result[1];
}
function segmentlist($data)
{
	preg_match('^="(.*)master.m3u8.*"^U',$data,$result); //Finn basisurl
	if(!isset($result[1]))
		$return=false;
	else
		$return=$this->get($result[1].'index_4_av.m3u8?null='); //Hent liste over segmenter
	return $return;
	
}
function download($segmentlist,$outfile,$showinfo=true) //$outfile skal være fullstendig bane med filnavn uten extension
{
	if(substr($segmentlist,0,4)=='http')
		die("Baseurl skal ikke brukes");
	
	$tsfil=$outfile.'.ts';


	preg_match_all('^.+segment.+^',$segmentlist,$segments); //Finn alle segmentene
	$count=count($segments[0]);
	//$out=$outpath.$outfile;
	if(file_exists($tsfil)) 
	{
		if(filesize($tsfil)==0) //Sjekk om filen er tom
			unlink($tsfil);
		else
			die("$tsfil eksisterer\n");
			
	}
	
	$file=fopen($tsfil.'.tmp','x'); //Åpne utfil for skriving
	
	if($file!==false) //Sjekk at filen lot seg åpne
	{
		//$info="Laster ned til $outfile\n";
	
		foreach($segments[0] as $key=>$segment)
		{
			if($showinfo)
			{
				$num=$key+1;
				echo "\rLaster ned segment $num av $count til $tsfil";
			}
			curl_setopt($this->ch, CURLOPT_URL,$segment);


			$tries=0;
			while($tries<3)
			{
				$data=curl_exec($this->ch);

				if (strlen($data)==curl_getinfo($this->ch,CURLINFO_CONTENT_LENGTH_DOWNLOAD))
					break;
	
				$tries++;
				echo "\nFeil ved nedlasting av segment $num. Prøver på nytt for $tries. gang";
			}
			if($tries==3)
				die("\nNedlasting feilet etter $tries forsøk\n");
			fwrite($file,$data);
			
		
		}
		echo "\n";
		fclose($file); //Lukk utfilen
		rename($tsfil.'.tmp',$tsfil);
		$mkvfile=$outfile.'.mkv';
		if(!file_exists($mkvfile))
		{
			if($showinfo)
			echo "Lager mkv\n";
			echo shell_exec("mkvmerge -o '$mkvfile' '$outfile.ts' 2>&1");
		}
			$return=$mkvfile;
		
	}
	else
		$return=false;
	//echo $info;
	curl_close($ch);
	return $return;
}
function subtitle($id,$filnavn) //$filnavn skal være fullstendig bane uten extension
{
	require_once 'subconvert.php'; //Verktøy for å konvertere undertekster
	$subconvert=new subconvert;
	if(!file_exists($filnavn.".srt"))
	{
		$xml=file_get_contents('http://tv.nrk.no/programsubtitles/'.$id);
		if(trim($xml)!='') //Sjekk at xml filen ikke er blank
		{
			file_put_contents($filnavn.".xml",$xml); //Lagre underteksten i originalt xml format
			$srt=$subconvert->xmltosrt($xml); //Konverter til srt
			file_put_contents($filnavn.".srt",$srt); //Lagre srt fil
			$return=$filnavn.".srt";
		}
		
	}
	else
		$return=false;
	return $return;
	
}
function episodelist($data)
{
	if(substr($data,0,4)=='http')
		$data=$this->get($data);
		
	preg_match_all('^(/program/Episodes.*)" title="(.*)"^U',$data,$sesongliste);
	
	$episoder=array(array(),array(),array(),array());
	foreach (array_unique($sesongliste[1]) as $seasonkey=>$seasonurl) //Gå gjennom url til sesongene
	{
		
		$sesong=$this->get($url='http://tv.nrk.no'.$seasonurl); //Hent liste over episodene i sesongen
		preg_match_all('^"(http://tv\.nrk\.no.*([a-z]{4}[0-9]{8}).*)"\>(.*)\<^U',$sesong,$sesongdata); //Finn alle episodene i sesongen
		//print_r($episodertemp);
	
		$sesonger[$seasonkey]['url']=$sesongdata[1];
		$sesonger[$seasonkey]['id']=$sesongdata[2];
		$sesonger[$seasonkey]['titler']=$sesongdata[3];
		$sesonger[$seasonkey]['sesongtittel']=str_replace('Vis programmer fra ','',array_unique($sesongliste[2])[$seasonkey]); //Denne måten å håndtere den returnerte verdien er gyldig kode fra PHP 5.4
		
		//die();
	/*	foreach ($episoder as $key=>$value)
		{
			//$episoder[$key]=array_merge($episoder[$key],$episodertemp[$key]); //Merge arrayet for gjeldende sesong med resten av sesongene
			$episoder[$seasonkey][$key]=array_merge($episoder[$key],$episodertemp[$key]); //Merge arrayet for gjeldende sesong med resten av sesongene
	
		}*/
	}
	//$episoder[4]=array_unique($sesongliste[2]);
	
	return $sesonger;
		
}
function varighetsjekk($episodedata,$fil)
{
	preg_match('^Varighet.+\<dd\>(.+)\</dd\>^',$episodedata,$varighet); //Hent varighet fra NRK

	$dur=str_replace(array(' minutter',' minutt',' timer',' time',','),array('mn','minute','h','h',''),$varighet[1]); //Gjør om tidsangivelsen fra NRK til å være lik mediainfo
	$mediainfo=trim(shell_exec($cmd="mediainfo --Inform=\"Video;%Duration/String%\" '$fil' 2>&1")); //Hent varighet fra mediainfo
	$mediainfo=preg_replace('^(mn).*^','$1',$mediainfo);
	echo "$dur==$mediainfo\n";
	return $dur==$mediainfo;
}
}
?>