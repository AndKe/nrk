<?Php
require 'functions.php';
$nrk=new nrkripper;
$data=$nrk->get($argv[1]);

preg_match('^data-video-id="([0-9]+)"^',$data,$id);

$info=$nrk->get($url="http://v6.psapi.nrk.no/public/mediaelement/".$id[1]);
$info=json_decode($info,true);

$url=pathinfo($info[0]['mediaUrl'],PATHINFO_DIRNAME).'/index_4_av.m3u8?null=';

$segmentlist=$nrk->get($url);
preg_match_all('^.+segment.+^',$segmentlist,$segments);
echo $segmentlist;
//print_r($segments);

$utfil=$nrk->filnavn($info[0]['title']);
if(!file_exists($utfil.'.ts'))
	$nrk->downloadts($segments[0],$utfil);
if(!file_exists($utfil.'.mkv'))
	$nrk->mkvmerge($utfil);

?>