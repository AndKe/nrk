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
	public function __construct()
	{
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
		$this->tittel=$this->finntittel($id); //Hent tittel
		if(!$segmentlist=$this->segmentlist($data)) //Hent segmentliste
			return false; //Hvis det ikke er mulig å laste ned programmet, returner false
		$filnavn=$this->filnavn($this->tittel); //Formater tittel for filnavn
		
		$utfil=$utmappe.$filnavn; //Sett sammen utmappe og filnavn til utfil
		if($this->sjekk->sjekkfil($utfil.'.ts',$this->varighet($data))) //Sjekk om filen allerede er lastet ned
		{
			$this->error.="{$this->tittel} er allerede lastet ned\n";
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
		if($result===false) //Hvis curl returnerer false er noe galt
			die("Kunne ikke hente data fra NRK, sjekk internettforbindelsen\n");
		return $result;
	}
	//Funksjoner som heter info fra NRK
	public function segmentlist($data)
	{
		preg_match('^="(.*)master.m3u8.*"^U',$data,$result); //Finn basisurl
		if(!isset($result[1])) //Sjekk om det ble funnet en url
		{
			$this->error.="Finner ikke segmentliste for {$this->tittel}\n";
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
			$this->error.="Finner ingten avspiller for tv eller radio\n";
			return false;
		}
		if(!preg_match_all('^.+segment.+^',$segmentlist,$segments)) //Finn alle segmentene
		{
			$this->error.="Ugylig segmentliste for {$this->tittel}\n";
			return false;
		}
		return $segments[0];		
	}
	public function tooltip($id)
	{
		return $this->get($url='http://tv.nrk.no/programtooltip/'.$id);
	}
	public function finntittel($id) //Hent tittel fra tooltip hos NRK
	{
		if(preg_match('^\<h1\>.*\</h1\>^',$this->tooltip($id),$tipresult))
		{
			$name=strip_tags($tipresult[0]);
			return html_entity_decode($name);
		}
		else
		{
			$this->error.="Finner ikke tittel\n";
			return false;
		}
	}
	public function sesongepisode($tip) //Eksempel: list($sesong,$episode)=$nrk->sesongepisode($nrk->tooltip($id));
	{
		preg_match('^Sesong ([0-9]+).{0,2}\(([0-9]+):([0-9]+)\)^',$tip,$matches);
		unset($matches[0]);
		return $matches;
	}
	private function varighet($episodedata) //Hent varighet fra beskrivelsen
	{
		preg_match('^Varighet.+\<dd\>(.+)\</dd\>^',$episodedata,$varighet); 
		return $varighet[1];
	}
	public function serieinfo($url) //Hent informasjon og episoder for en serie
	{
		if(substr($url,0,4)=='http')
			$data=$this->get($url);
		else
			die("Ugyldig url til serie: $url\n");
			
		preg_match_all('^(/program/Episodes.*)" title="(.*)"^U',$data,$sesongliste);
		preg_match('^Serietittel:.+dd\>(.+)\</dd^',$data,$serietittel);
		$serietittel=html_entity_decode($serietittel[1]);
		
		$episoder=array(array(),array(),array(),array());
		foreach (array_unique($sesongliste[1]) as $seasonkey=>$seasonurl) //Gå gjennom url til sesongene
		{
			$sesong=$this->get($url='http://tv.nrk.no'.$seasonurl); //Hent liste over episodene i sesongen
			preg_match_all('^"(/.*([a-z]{4}[0-9]{8}).*)" class="p-link"\>(.*)\<^U',$sesong,$sesongdata); //Finn alle episodene i sesongen
			preg_match_all('^col-rights hidden-phone"\>(.+)\</td^Us',$sesong,$rights);
			for($key=0; $key<count($sesongdata[0]); $key++)
			{
				//echo $rights[1][$key]."\n";
				$rights[1][$key]=preg_replace('^\<time datetime="(.+)T(.+)\+.+^s','$1 $2',$rights[1][$key]);
				$rights[1][$key]=trim($rights[1][$key]);
				$sesongdata[1][$key]='http://tv.nrk.no'.$sesongdata[1][$key];
			}
			$sesonger[$seasonkey]['url']=$sesongdata[1];
			$sesonger[$seasonkey]['id']=$sesongdata[2];
			$sesonger[$seasonkey]['titler']=$sesongdata[3];
			$sesonger[$seasonkey]['sesongtittel']=str_replace('Vis programmer fra ','',array_unique($sesongliste[2])[$seasonkey]); //Denne måten å håndtere den returnerte verdien er gyldig kode fra PHP 5.4
			$sesonger[$seasonkey]['rights']=$rights[1];
		}
		return array('serietittel'=>$serietittel,'sesonger'=>$sesonger);
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
			die("Finner ikke id i url: $url\n");
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
	//Funksjoner som henter data fra NRK
	public function downloadts($segments,$utfil)
	{
		$count=count($segments);
		if(file_exists($utfil.'.tmp'))
			unlink($utfil.'.tmp');
		
		$file=fopen($utfil.'.tmp','x'); //Åpne utfil for skriving
		if(!$file)
			die("Kan ikke åpne $utfil.tmp\n");
		foreach($segments as $key=>$segment)
		{
			if(!$this->silent)
			{
				$num=$key+1;
				echo "\rLaster ned segment $num av $count til $utfil   ";
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
		if($this->mode=='radio')
			rename($utfil.'.tmp',$filnavn=$utfil.'.m4a'); //Lag riktig filtype for radio
		else
			rename($utfil.'.tmp',$filnavn=$utfil.'.ts');	//Lag riktig filtype tv
		return $filnavn;
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
				return $filnavn.".srt";
			}
			else
			{
				$this->error.="Ingen undertekster til {$this->tittel}\n";
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
			echo "mkvmerge ble ikke funnet, kan ikke lage mkv\n";
			return false;
		}
		echo "Lager mkv\n";
		$cmd="mkvmerge -o \"$filnavn.mkv\" \"$filnavn.ts\"";
		if(file_exists($filnavn.'.chapters.txt'))
			$cmd.=" --chapter-charset UTF-8 --chapters \"$filnavn.chapters.txt\"";
		echo shell_exec($cmd." 2>&1");
	}
}
?>