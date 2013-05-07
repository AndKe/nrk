<?Php
class subconvert
{
	public $debug;
	private function endtime($starttime,$durationtime)
	{
		$debug=false;
		date_default_timezone_set('UTC');
		$start=date_create_from_format('H:i:s.u',$starttime); //Lag object av starttidspunkt
		$dur=date_create_from_format('H:i:s.u',$durationtime); //Lag object av varighet
		$start_seconds=date_format($start,'s.u'); //Hent sekunder og mikrosekunder av starttidspunkt
		$dur_seconds=date_format($dur,'s.u');//Hent sekunder og mikrosekunder av varighet
		//$start_microseconds=date_format($start,'u')/1000000;//Hent sekunder og mikrosekunder av starttidspunkt
		//$dur_microseconds=date_format($dur,'u')/1000000;//Hent sekunder og mikrosekunder av varighet
		$start_useconds=date_format($start,'u');//Hent sekunder og mikrosekunder av starttidspunkt
		$dur_useconds=date_format($dur,'u');//Hent sekunder og mikrosekunder av varighet
		$start_microseconds=substr($starttime,strpos($starttime,'.')+1);
		$dur_microseconds=substr($durationtime,strpos($durationtime,'.')+1);
		$start_microseconds='0.'.str_pad($start_microseconds,3,'0',STR_PAD_LEFT);
		$dur_microseconds='0.'.str_pad($dur_microseconds,3,'0',STR_PAD_LEFT);
		
		//var_dump(date_format($dur,'u')."\n$durationtime");
		$dur_format=date_format($dur,'H:i:s'); 
		$interval=new DateInterval('P0000-00-00T'.$dur_format); //Lag intervalobjekt av varigheten
		
		$end=$start;
		date_add($end,$interval); //Legg varigheten til starttidspunktet		

		$temp=date_format($end,'s');
		$temp_dur_seconds=date_format($dur,'u');
				
		//Sjekk av beregning av mikrosekunder
		if($this->debug)
		{
			echo "$starttime+$durationtime\n";
			echo "$start_microseconds+$dur_microseconds=";
			echo $start_microseconds+$dur_microseconds;
			//echo "$start_useconds+$dur_useconds=";
			//echo $start_useconds+$dur_useconds;
			
			echo "\n";
		}
		$end_useconds=$start_useconds+$dur_useconds;
		$end_microseconds=$start_microseconds+$dur_microseconds;
		if($this->debug)
			var_dump((string)$end_useconds);
	
		if($end_microseconds>=1)
		{
			$interval_addseconds=new DateInterval('P0000-00-00T00:00:01'); //Lag intervalobjekt av ett sekund
			date_add($end,$interval_addseconds); //Legg til sekundet
			$end_useconds=$end_useconds-1000000; //Trekk sekundet fra mikrosekundene
			$end_useconds=$end_useconds.'0'; //Legg på en null bak
		}
		$end_microseconds=str_pad(substr($end_microseconds,2),3,'0');
		$end_useconds=str_pad($end_useconds,7,0,STR_PAD_LEFT); //Sørg for at mikrosekundene er oppgitt med 7 siffer
		if($this->debug)
			var_dump((string)$end_microseconds);
		$end_useconds=substr($end_useconds,0,3); //Forkort til 3 siffer
		$end_format=date_format($end,'H:i:s'); //Hent timer og minutter fra sluttpunktet

		return $end_format.','.$end_microseconds;
	}
	
	
	public function xmltosrt($xml)
	{
		preg_match_all('^\<p begin="([0-9:\.]+)\" dur="([0-9:\.]+)".*\>(.*)\</p\>^Us',$xml,$result);//.*"\>(.*)\</p\>"
		$srt='';
		foreach ($result[0] as $key=>$data)
		{
			$end=$this->endtime($result[1][$key],$result[2][$key]); //Finn slutttidspunktet
			$srt.=$key+1; //Lag linjenummer
			$srt.="\r\n";
			$srt.=str_replace(".",',',$result[1][$key]).' --> '.$end."\r\n"; //Sett sammen start og sluttidspunkt
			
			if(strpos($result[3][$key],'<br></br>')) //Finn ut hvilken type linjeskift som er brukt
				$break="<br></br>";
			else
				$break='<br />';
	
			if(strpos($result[3][$key],'italic'))
			{
				
				$lines=explode($break,$result[3][$key]);
				foreach($lines as $line)
				{
					$srt.=strip_tags(trim($line))."\r\n";
				}
			}
			else
				$srt.=strip_tags(str_replace($break,"\r\n",$result[3][$key]))."\r\n"; //Lag riktige linjeskift
				//$srt.=strip_tags($result[3][$key])."\r\n";
			$srt.="\r\n";
		}
		$srt=trim($srt);
		return $srt;
	}
}