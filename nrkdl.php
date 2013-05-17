<?Php
unset($argv[0]);
include 'functions.php';
$nrk=new nrkripper;
foreach ($argv as $url)
{
	$data=get($url,false,false,$agent); //Hent data om episoden
	$outfile=$config['outpath'].title(getid($url));
	
//die($outfile);
	file_put_contents($outfile.'.htm',$data);

	//$baseurl=getbaseurl($data);

	//$segmentlist=get($baseurl.'index_4_av.m3u8?e=6303f0e8a89ec6bd&id=',false,$url,$agent); //Hent liste over segmenter
	$segmentlist=segmentlist($data); //Hent liste over segmenter
	

	echo subtitle(getid($url),$outfile);
	if(!download($segmentlist,$outfile))
		die("Nedlasting feilet\n");
	varighetsjekk($data,$outfile.'.mkv');
	
	/*$mkvfile=substr($outpath.$outfile,0,-3).'.mkv';
	if(!file_exists($mkvfile))
	{
		echo "Lager mkv\n";
		echo shell_exec("mkvmerge -o '$mkvfile' '$outpath$outfile' 2>&1");
	}*/
	
}
?>
