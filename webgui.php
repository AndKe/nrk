<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>NRK</title>
</head>

<body>
<?Php
ini_set('display_errors',1);
error_reporting(E_ALL);
ini_set('max_execution_time',0);
require 'functions.php';
$nrk=new nrkripper;
$starttime=time();
function checkboxvalue($field)
{
	if(isset($_POST[$field]) && !empty($_POST[$field]))
		return 'checked="checked"';
}
function boolfield($field)
{
	if(isset($_POST[$field]) && !empty($_POST[$field]))
		return true;
	else
		return false;
}


?>
<form id="form1" name="form1" method="post" action="">
  <p>
    Hva skal rippes? 
    <select name="mode" id="mode">
      <option value="episode" <?Php if(!isset($_POST['script']) || $_POST['script']=='episode') echo 'selected="selected"'; ?>>Enkelt program/episode</option>
      <option value="serie" <?Php if(isset($_POST['script']) && $_POST['script']=='serie') echo 'selected="selected"'; ?>>Hel serie</option>
    </select>
  </p>
  <p>URL til serie eller episode: 
    <input name="source" type="text" id="source" value="<?Php if(isset($_POST['source'])) echo $_POST['source']; ?>" />
  </p>
  <p>
    <input type="submit" name="button" id="button" value="Submit" />
  </p>
</form>
<?Php
if(isset($_POST['button']))
{
	$error='';
	$source=$_POST['source'];
	if(empty($source))
		$error.="URL til spilleliste eller album er ikke opgitt<br />\n";
	$compilation=boolfield('compilation');
	$albumfolders=boolfield('albumfolders');
	$artistalbumfolder=boolfield('artistalbumfolder');
	$covername=boolfield('covername');
	if(!empty($error))
		die("Følgende feil oppstod:<br />\n$error");
	if($_POST['mode']=='episode')
	{
		$status=$nrk->nrkrip($_POST['source'],$nrk->config['outpath']);
		if($status!==false)
			var_dump($status);
		else
			echo $nrk->error;	
	}
	elseif($_POST['mode']=='serie')
	{
		//echo nl2br($nrk->serierip($url));
		$argv[1]=$_POST['source'];
		require 'serie.php';
	}
}	
$endtime=time();
echo "Scriptet startet $starttime og ble ferdig $endtime og har brukt ";
echo $endtime-$starttime;
echo " sekunder";
?>
</body>
</html>