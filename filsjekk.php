<?Php
class filsjekk
{
	public $varighettoleranse=90;
	public $error;
	public $video;
	function __construct()
	{
		require_once 'tools/video.php';
		$this->video=new video;
	}
	private function varighetsjekk($varighet_nrk,$fil)
	{
		$varighet_nrk=str_replace(array(' minutter',' minutt',' timer',' time',','),array('minutes','minute','hours','hour',' '),$varighet_nrk); //Gjør om tidsangivelsen fra NRK så den kan brukes med strtotime
		$varighet_nrk=strtotime($varighet_nrk,0);
		$varighet_fil=$this->video->duration($fil); //Hent varighet på filen
	
		if($varighet_nrk==$varighet_fil) //Varighet er riktig
			return true;
		elseif($varighet_nrk>$varighet_fil && $varighet_nrk-$varighet_fil<=$this->varighettoleranse) //Varighet er innenfor toleransen
			return true;
		elseif($varighet_fil>$varighet_nrk && $varighet_fil-$varighet_nrk<=$this->varighettoleranse) //Varighet er innenfor toleransen
			return true;
		else
		{
			$this->error.="Feil varighet: NRK oppgir $varighet_nrk, filen er $varighet_fil\n";
			return false;
		}
	
	}
	public function varighet($episodedata)
	{	
		if(preg_match('^Varighet.+\<dd\>(.+)\</dd\>^sU',$episodedata,$varighet))
			return $varighet[1];
		else
		{
			$this->error.="Finner ikke varighet\n";
			return false;	
		}
	}
	public function sjekkfil($fil,$varighet) //Sjekk om filen eksisterer og om eventuell eksisterende fil er fullstendig. Er eksisterende fil gyldig returneres true
	{
		if(file_exists($fil)) 
		{
			if(filesize($fil)==0) //Sjekk om filen er tom
			{
				$this->error.="Tom fil\n";
				unlink($fil);
				return false;
			}
			elseif(!$this->varighetsjekk($varighet,$fil))
			{
				$this->error.="Feil varighet\n";
				rename($fil,$fil.".feil_varighet");
				return false;
			}
			else
			{
				$this->error.="Fil eksisterer og har riktig lengde\n";
				return true; //Filen eksisterer og har riktig lengde
			}
		}
		else
			return false; //Filen eksisterer ikke
		
	}
}