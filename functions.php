<?Php
class nrkripper
{
	private $ch;
	public $config;
	public $debug=false;
	public $useragent='Mozilla/5.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B176 Safari/7534.48.3';
	public function __construct()
	{
		$this->ch=curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch,CURLOPT_USERAGENT,$this->useragent);
		curl_setopt($ch, CURLOPT_COOKIEFILE,'cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEJAR,'cookies.txt');
		include 'config.php';
		$this->config=$config;
		
	}
	public function nrkrip($url,$utmappe)
	{
		if(substr($utmappe,-1,1)!='/')
			$utmappe.='/';
		$data=$this->get($url); //Hent informasjon fra NRK
		if(!$segmentlist=$this->segmentlist($data)); //Hent segmentliste
			die("Feil ved henting av segmentliste\n");
		$id=$this->getid($url); //Finn id
		$tittel=$this->tittel($id); //Hent tittel
		$filnavn=$this->filnavn($tittel); //Formater tittel for filnavn
		$utfil=$utmappe.$filnavn; //Sett sammen utmappe og filnavn til utfil
		$this->downloadts($segmentlist,$utfil); //Last ned ts
		$this->mkvmerge($utfil);
		$this->subtitle($id,$utfil);
	}
	public function get($url)
	{
		curl_setopt($this->ch,CURLOPT_URL,$url);
		return curl_exec($this->ch);
	}
	private function varighet($episodedata)
	{
		preg_match('^Varighet.+\<dd\>(.+)\</dd\>^',$episodedata,$varighet); //Hent varighet fra beskrivelsen
		return $varighet[1];
	}

	public function tittel($id)
	{
		$tip=$this->get($url='http://tv.nrk.no/programtooltip/'.$id);
		if(preg_match('^\<h1\>.*\</h1\>^',$tip,$tipresult))
		{
			$name=strip_tags($tipresult[0]);
			return html_entity_decode($name);
		}
		else
			return false;
	}
	private function filnavn($tittel)
	{
		$filnavn=html_entity_decode($tittel);
		$filnavn=str_replace(':','-',$filnavn);
		return $filnavn;
	}
private function getid($url)
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
	private function segmentlist($data)
	{
		preg_match('^="(.*)master.m3u8.*"^U',$data,$result); //Finn basisurl
		if(!isset($result[1]))
			return false;
			
		$return=$this->get($result[1].'index_4_av.m3u8?null='); //Hent liste over segmenter
			
		if(!preg_match_all('^.+segment.+^',$segmentlist,$segments)); //Finn alle segmentene
			die("Ugylig segmentliste\n");
		return $segments[0];		
	}
	private function downloadts($segmentlist,$utfil,$showinfo=true)
	{

		$count=count($segments);
		
		$file=fopen($utfil.'.tmp','x'); //Åpne utfil for skriving
		if(!$file)
			return false;
		foreach($segments as $key=>$segment)
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
		rename($utfil.'.tmp',$utfil.'.ts');	//Lag riktig filtype
		return $utfil.'.ts';
	}
public function mkvmerge($filnavn)
{
	echo "Lager mkv\n";
	//$mkvfil=substr($tsfil,0,-2).'mkv'; //Fjern ts og legg til mkv
	$mkvfil=$filnavn.'.mkv';
	$tsfil=$filnavn.'.ts';
	echo shell_exec("mkvmerge -o '$mkvfil' '$tsfil' 2>&1");
}

public function subtitle($id,$filnavn) //$filnavn skal være fullstendig bane uten extension
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
public function episodelist($url)
{
	if(substr($url,0,4)=='http')
		$data=$this->get($url);
	else
		die("Ugyldig url: $url\n");
		
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
}
?>