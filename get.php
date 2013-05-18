<?Php
function get($url,$cookiefile=false,$referer=false,$useragent=false,$debug=false,&$ch_in=false)
{	

	if($ch_in===false)
		$curl = curl_init();
	else
		$curl=$ch_in;

	curl_setopt($curl, CURLOPT_URL, $url);
	
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	
	if ($cookiefile)
	{
		if(!file_exists($cookiefile))
			die("Finner ikke $cookiefile");
		curl_setopt($curl,CURLOPT_COOKIEFILE,$cookiefile);
		curl_setopt($curl,CURLOPT_COOKIEJAR,$cookiefile);
	}
	if ($referer)
		curl_setopt($curl,CURLOPT_REFERER,$referer);	
	if($useragent)
		curl_setopt($curl,CURLOPT_USERAGENT,$useragent);
	$return = curl_exec($curl);
	if($debug && $return==false)
		$return=curl_error ($curl);
	if($ch_in===false)
		curl_close ($curl);

	return $return;
}
?>