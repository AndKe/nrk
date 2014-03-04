<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>NRKripper</title>
</head>

<body>
<?Php
require 'functions.php';
$nrk=new nrkripper;
?>
Høyreklikk i bildeflaten på en video og velg "Kopier teknisk rapport". Lim inn resultatet i feltet under, fyll inn filnavn og trykk på knappen.
<form id="form1" name="form1" method="post" action="">
  <p>Teknisk rapport:  </p>
  <p>
    <textarea name="textarea" id="textarea" cols="45" rows="5"></textarea>
  </p>
  <p>Navn: 
    <input type="text" name="textfield" id="textfield" />
  </p>
  <p>
    <input type="submit" name="button" id="button" value="Submit" />
  </p>
</form>
<?Php
if(isset($_POST['button']))
{
	preg_match('/Media file: (.+)/',$_POST['textarea'],$mediafile);
	$mediafile=str_replace('/z/wo/open','/i/wo/open',$mediafile[1]);
	$mediafile=str_replace('manifest.f4m','/index_4_av.m3u8?null=',$mediafile);
	$segmentlist=$nrk->get($mediafile);
	if(!preg_match_all('^.+segment.+^',$segmentlist,$segments))
		die("Ugyldig segmentliste");

	$utfil=$nrk->config['outpath'].$nrk->filnavn($_POST['textfield']);
	
	if(!file_exists($utfil.'.ts'))
		$nrk->downloadts($segments[0],$utfil);
	if(!file_exists($utfil.'.mkv'))
		$nrk->mkvmerge($utfil);
	
}
?>
</body>
</html>