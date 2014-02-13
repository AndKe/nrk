<?Php
require 'tools/dependcheck.php';
require 'filsjekk.php';
class nrkripper
{
	private $ch;
	public $config;
	public $silent=false;
	public $sjekk;
	public $error;
	public $tittel;
	public $dependcheck;
	public $mode;
	public $br;
	public function __construct()
	{
		if(php_sapi_name() == 'cli') //Sjekk om scriptet kjøres på kommandolinje eller i browser for å avgjøre linjeskift
			$this->br="\n";
		else
			$this->br="<br />\n";

		$this->ch=curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch,CURLOPT_USERAGENT,'Mozilla/5.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B176 Safari/7534.48.3'); //useragent må være en iOS enhet for at NRK skal tilby formatet som kan rippes
		curl_setopt($this->ch, CURLOPT_COOKIEFILE,'cookies.txt');
		curl_setopt($this->ch, CURLOPT_COOKIEJAR,'cookies.txt');
		require 'config.php';
		if(substr($config['outpath'],-1,1)!='/') //Outpath må slutte med /
			$config['outpath'].='/';
		$this->config=$config;
		$this->sjekk=new filsjekk;	
		$this->dependcheck=new dependcheck;
	}
	public function nrkrip($url,$utmappe) //Dette er funksjonen som kalles for å rippe fra NRK
	{
		if(substr($utmappe,-1,1)!='/')
			$utmappe.='/';
		$data=$this->get($url); //Hent informasjon fra NRK
		$id=$this->getid($url); //Finn id
		if(!isset($this->tittel))
			$this->tittel=$this->finntittel($data); //Hent tittel hvis den ikke er satt et annet sted
		if($this->tittel===false)
			return false;
		if(!$segmentlist=$this->segmentlist($data)) //Hent segmentliste
			return false; //Hvis det ikke er mulig å laste ned programmet, returner false
		$filnavn=$this->filnavn($this->tittel); //Formater tittel for filnavn
		
		$utfil=$utmappe.$filnavn; //Sett sammen utmappe og filnavn til utfil
		if($this->sjekk->sjekkfil($utfil.'.ts',$this->varighet($data))) //Sjekk om filen allerede er lastet ned
		{
			$this->error.="{$this->tittel} er allerede lastet ned".$this->br;
			$return=false;
		}
		else
			$this->downloadts($segmentlist,$utfil); //Last ned ts
		if(!$this->sjekk->sjekkfil($utfil.'.mkv',$this->varighet($data))) //Sjekk om filen allerede er muxet
			$this->mkvmerge($utfil);
		$this->subtitle($id,$utfil);
		if($chapters=$this->chapters($data))
			file_put_contents($utfil.'.chapters.txt',$chapters);
		if(isset($return))
			return $return;
	}
	public function get($url)
	{
		curl_setopt($this->ch,CURLOPT_URL,$url);
		$result=curl_exec($this->ch);
		$http_status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		if($result===false) //Hvis curl returnerer false er noe galt
			die("Kunne ikke hente data fra NRK, sjekk internettforbindelsen".$this->br);
		elseif($http_status!=200)
		{
			$this->error.="Kunne ikke hente data fra nrk, HTTP feilkode $http_status".$this->br;
			return false;	
		}
		return $result;
	}
	//Funksjoner som heter info fra NRK
	public function segmentlist($data)
	{
		preg_match('^="(.*)master.m3u8.*"^U',$data,$result); //Finn basisurl
		if(!isset($result[1])) //Sjekk om det ble funnet en url
		{
			$this->error.="Finner ikke segmentliste for ".$this->tittel.$this->br;
			return false;
		}
		$doc = new DOMDocument();
		@$doc->loadHTML($data);
		$player=$doc->getElementById('playerelement');
		$class=$player->getAttribute('class');
		$media=$player->getAttribute('data-media');

		if(strpos($class,'radio'))
		{
			$this->mode='radio';
			$segmentlist=$this->get($result[1].'index_1_a.m3u8?null=');
		}
		elseif(strpos($class,'tv'))
		{
			$this->mode='tv';
			$segmentlist=$this->get($result[1].'index_4_av.m3u8?null='); //Hent liste over segmenter
		}
		else
		{
			$this->error.="Finner ingen avspiller for tv eller radio".$this->br;
			return false;
		}
		if(!preg_match_all('^.+segment.+^',$segmentlist,$segments)) //Finn alle segmentene
		{
			$this->error.="Ugylig segmentliste for ".$this->tittel.$this->br;
			return false;
		}
		return $segments[0];		
	}
	public function tooltip($id)
	{
		return $this->get($url='http://tv.nrk.no/programtooltip/'.$id);
	}
	public function finntittel($data) //Hent tittel fra NRK
	{
		if(preg_match('^<meta name="title" content="(.+)"^',$data,$tittel))
		{
			$name=strip_tags($tittel[1]);
			if($episodetext=$this->sesongepisode($data))
				$name.=' '.$episodetext;
			elseif(preg_match('^\<meta name="episodenumber" content="(.+)"^',$data,$episode))
				$name.=' '.$episode[1];
			return html_entity_decode($name);
		}
		else
		{
			$this->error.="Finner ikke tittel\n";
			return false;
		}
	}
	public function sesongepisode($description,$returnstring=true)
	{
		if(preg_match('^Sesong ([0-9]+).{0,2}\(([0-9]+):([0-9]+)\)^i',$description,$sesongepisode)) //Episode og sesong
		{
			if($returnstring)
			{
				$sesongepisode[1]=str_pad($sesongepisode[1],2,'0',STR_PAD_LEFT);
				$sesongepisode[2]=str_pad($sesongepisode[2],2,'0',STR_PAD_LEFT);		
				return "S$sesongepisode[1]E$sesongepisode[2]";
			}
			else
				return $sesongepisode;
		}
		elseif(preg_match('^\(([0-9]+):([0-9]+)\)^',$description,$episode)) //Episide uten sesong
		{
			if($returnstring)
			{
				$episode[1]=str_pad($episode[1],2,'0',STR_PAD_LEFT);
				return "EP$episode[1]";
			}
			else
				return $episode;
		}
		else
		{
			//var_dump($description);
			return false; //Ikke funnet
		}
	}
	private function varighet($episodedata) //Hent varighet fra beskrivelsen
	{
		if(preg_match('^Varighet.+\<dd\>(.+)\</dd\>^',$episodedata,$varighet))
			return $varighet[1];
		else
		{
			$this->error.="Finner ikke varighet\n";
			return false;	
		}
	}

	public function serieinfo($url)
	{
		$seriedata=$this->get($baseurl=$this->baseurl($url));
		preg_match('^Serietittel:.+dd\>(.+)\</dd^',$seriedata,$serietittel);
		return array('serietittel'=>html_entity_decode($serietittel[1]),'sesonger'=>$this->sesonger($seriedata),'baseurl'=>$baseurl);
	}
	private function sesonger($seriedata)
	{
		preg_match_all('^href="(/program/Episodes/.+)".+data-identifier="([0-9]+)".+\>(.+)\<^sU',$seriedata,$sesongliste);
	
		$sesongliste[1]=str_replace('/program','http://tv.nrk.no/program',$sesongliste[1]);
		unset($sesongliste[0]);
		foreach($sesongliste[2] as $key=>$id)
		{
			if($sesongliste[3][$key]=='Alle episoder')
				$sesongliste[3][$key]='';
			$sesonger[$id]=array('tittel'=>trim($sesongliste[3][$key]),'url'=>$sesongliste[1][$key],'episoder'=>$this->episoder($this->get($sesongliste[1][$key])));	
		}
		return $sesonger;
	}

	private function episoder($sesongdata)
	{
		$dom = new DOMDocument;

		@$dom->loadHTML($sesongdata);
		$lists=$dom->getElementsByTagName('ul');
	
		$episodes=$lists->item(0)->childNodes; //ul

		foreach ($episodes as $key=>$episode) //li
		{
			if(get_class($episode)=="DOMText")
				continue;
	
			$episodefields=$episode->childNodes;
			$episodeinfo['description']=$episodefields->item(3)->textContent; //Beskrivelse
			$episodeinfo['title']=$episodefields->item(1)->textContent; //Tittel
			$episodeinfo['rights']=$episodefields->item(7)->textContent; //Rettigheter
			$episodeinfo['parsedrights']=$this->rightsparser($episodeinfo['rights']); //Tolk rettigheter
			$episodeinfo['ontv']=$episodefields->item(5)->textContent; //Sist sendt/sendes neste gang
			$episodeinfo['id']=$episode->attributes->item(1)->value; //ID for episoden

			$sesongepisoder[]=$episodeinfo;
		}
		return $sesongepisoder;
	}

	
	//Funksjoner som behandler informasjonen
	public function filnavn($tittel)
	{
		$filnavn=html_entity_decode($tittel);
		$filnavn=str_replace(array(':','?','*','|','<','>','/','\\'),array('-','','','','','','',''),$filnavn); //Fjern tegn som ikke kan brukes i filnavn på windows
		if(PHP_OS=='WINNT')
			$filnavn=utf8_decode($filnavn);
		return $filnavn;
	}
	public function getid($url)
	{
		preg_match('^/([a-z]+[0-9]+/*)^',$url,$result);
		if(!isset($result[1]))
		{
			$this->error="Finner ikke id i url: $url".$this->br;
			return false;
		}
		else
			return $result[1];
	}
	public function chapters($data) //Henter kapitler og lager liste som kan brukes med mkvmerge --chapters
	{
		if(substr($data,0,4)=='http')
			$data=$this->get($data);

		$dom = new domDocument;		
		@$dom->loadHTML($data);
		$pointlist=$dom->getElementById('indexPoints');
		if(!is_object($pointlist)) //Sjekk om det er kapitler
			return false;
		$points=$pointlist->childNodes->item(1)->childNodes->item(1)->childNodes;
		$num=1;
		$chapters='';
		foreach ($points as $i=>$point)
		{
			if(!is_object($point->childNodes) || $point->childNodes->item(0)->attributes->length!=3) //Hopp over uønskede elementer
				continue;
			$num=str_pad($num,2,'0',STR_PAD_LEFT);
			$string=$point->childNodes->item(0)->attributes->item(2)->value;
			preg_match('/([0-9:]+) (.+) \(/',$string,$time);
		
			$chapters.="CHAPTER$num={$time[1]}.000\r\n";
			$chapters.="CHAPTER{$num}NAME={$time[2]}\r\n";
			$num++;
		}
		return trim($chapters);	
	}
	private function baseurl($url)
	{
		return preg_replace('^(serie/.+?)/.+^','$1',$url); //Hent bare første delen etter /serie/
	}
	public function rightsparser($string)
	{
		if($string=='Ikke på nett')
			return false;
		//Her skal x antall dager frem i tid tolkes til unix timestasmp
	}
	//Funksjoner som henter data fra NRK
	public function downloadts($segments,$utfil)
	{
		$count=count($segments);
		if(file_exists($utfil.'.tmp'))
			unlink($utfil.'.tmp');
		
		$file=fopen($utfil.'.tmp','x'); //Åpne utfil for skriving
		if(!$file)
			die("Kan ikke åpne $utfil.tmp".$this->br);
		foreach($segments as $key=>$segment)
		{
			if(!$this->silent)
			{
				$num=$key+1;
				if($this->br=="\n")
					echo "\rLaster ned segment $num av $count til $utfil   ";
				else
					echo "Laster ned segment $num av $count til $utfil".$this->br;
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
				die("\nNedlasting feilet etter $tries forsøk".$this->br);
			fwrite($file,$data);
		}
		echo "\n";
		fclose($file); //Lukk utfilen
		rename($utfil.'.tmp',$filnavn=$utfil.'.ts'); //Lag riktig filtype
		return $filnavn;
	}
	public function subtitle($id,$filnavn) //$filnavn skal være fullstendig bane uten extension
	{
		require_once 'subconvert.php'; //Verktøy for å konvertere undertekster
		$subconvert=new subconvert;
		if(!file_exists($filnavn.".srt"))
		{
			$xml=$this->get('http://tv.nrk.no/programsubtitles/'.$id);
			if(trim($xml)!='') //Sjekk at xml filen ikke er blank
			{
				file_put_contents($filnavn.".xml",$xml); //Lagre underteksten i originalt xml format
				$srt=$subconvert->xmltosrt($xml); //Konverter til srt
				file_put_contents($filnavn.".srt",$srt); //Lagre srt fil
				return $filnavn.".srt";
			}
			else
			{
				$this->error.="Ingen undertekster til ".$this->tittel.$this->br;
				return false;	
			}
		}
		else
			return false;
		
	}
	
	public function mkvmerge($filnavn)
	{
		if($this->dependcheck->depend('mkvmerge')!==true)
		{
			echo "mkvmerge ble ikke funnet, kan ikke lage mkv".$this->br;
			return false;
		}
		echo "Lager mkv".$this->br;
		$cmd="mkvmerge -o \"$filnavn.mkv\" \"$filnavn.ts\"";
		if(file_exists($filnavn.'.chapters.txt'))
			$cmd.=" --chapter-charset UTF-8 --chapters \"$filnavn.chapters.txt\"";
		$shellreturn=shell_exec($cmd." 2>&1");
		if($this->br=="\n")
			echo $shellreturn;
		else
			echo nl2br($shellreturn);
	}
}
?>