<?Php
include 'functions.php';
$data=file_get_contents($url='http://tv.nrk.no/serie/med-hjartet-paa-rette-staden/koif91006411/07-01-2013');

//echo subtitle(getid($url),$outpath);
//die();
//preg_match_all('^koid[0-9]+^',$data,$result);
//preg_match_all('^[a-z]{4}[0-9]{8}^',$data,$result);
$episodelist=episodelist($data);
//print_r($episodelist);
//die();

//echo $data;
foreach ($episodelist[0]['id'] as $key=>$id)
{
/*$tip=file_get_contents('http://tv.nrk.no/programtooltip/'.$id);
preg_match('^\<h1\>.*\</h1\>^',$tip,$tipresult);
$name=strip_tags($tipresult[0]);
$name=str_replace(':','-',$name);
if(!file_exists("subs/$name.xml"))
{
echo "$name: http://tv.nrk.no/programsubtitles/$id<br>\n";
copy('http://tv.nrk.no/programsubtitles/'.$id,"subs/$name.xml");
}*/
echo $id."\n";
echo subtitle($id,$config['outpath'].filnavn($episodelist[0]['titler'][$key]));
}