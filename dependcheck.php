<?Php
function depend($check)
{
if(isset($_SERVER['HTTP_USER_AGENT']))
	$break="<br>\n";
else
	$break="\n";

	if(!is_array($check))
		$check=array($check);
	$notfound=array();
	foreach ($check as $command)
	{
		
	$return=shell_exec("$command 2>&1"); //Prøv kommandoen
	//var_dump($return);
	if(strpos($return,'not found'))
		$notfound[]=$command; //Legg den på listen over kommandoer som ikke ble funnet
	}
	
	if(count($notfound)>0)
	{
		$text="Følgende avhengigheter ble ikke funnet:$break";
		$text.=implode($break,$notfound);
		die($text.$break);
	}

}
depend('mkvmerge');
if(!function_exists('curl_init'))
	die("Dette scriptet krever curl");
?>