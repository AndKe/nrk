<?Php
class filsjekk
{
	public $varighettoleranse=90;
	public $error;
	private function varighetsjekk($varighet,$fil)
	{
		
		$varighet=str_replace(array(' minutter',' minutt',' timer',' time',','),array('minutes','minute','hours','hour',' '),$varighet); //Gjør om tidsangivelsen fra NRK så den kan brukes med strtotime
		$mediainfo=trim(shell_exec($cmd="mediainfo --Inform=\"Video;%Duration%\" '$fil' 2>&1")); //Hent varighet fra mediainfo
		$mediainfo=$mediainfo/1000;
		$varighet=strtotime($varighet,0);
	
		if($varighet==$mediainfo)
			return true;
		elseif($varighet>$mediainfo && $varighet-$mediainfo<=$this->varighettoleranse)
			return true;
		elseif($mediainfo>$varighet && $mediainfo-$varighet<=$this->varighettoleranse)
			return true;
		else
			return false;
	
	}
	private function varighet($episodedata)
	{
			preg_match('^Varighet.+\<dd\>(.+)\</dd\>^',$episodedata,$varighet); //Hent varighet fra NRK
			return $varighet[1];
	}
	public function sjekkfil($fil,$varighet) //Sjekk om filen eksisterer og om eventuell eksisterende fil er fullstendig. Er eksisterende fil gyldig returneres true
	{
		if(file_exists($fil)) 
		{
			if(filesize($fil)==0) //Sjekk om filen er tom
				unlink($fil);
			elseif(!$this->varighetsjekk($varighet,$fil))
				rename($fil,$fil.".feil_varighet");
			else
				return true; //Ingen problemer funnet
			$this->error.="Fil eksisterer og har riktig lengde\n";
			return false; //Problemer er funnet
		}
		else
			return true;
		
	}
}