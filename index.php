<?php

ini_set('display_error', 1);
if($_GET['check'] != ""){
	$objStatus = new status;
	$objStatus->do_check(filter_var($_GET['check'], FILTER_SANITIZE_STRING));
	exit;
}

class status {
	
	private $arrActiveChecks = array('homepage', 'forum', 'repo', 'wiki', 'liveupdate', 'mirror1', 'mirror2');
	private $objUrlfetcher;
	
	private $arrChecks = array(
		//Core
		'homepage' => array(
			'name' => 'Homepage',
			'icon' => 'fa-globe',
			'subtitle' => '',
			'url'	=> 'https://eqdkp-plus.eu/',
		),
		'forum' => array(
			'name' => 'Forum',
			'icon' => 'fa-comments',
			'url'	=> 'https://eqdkp-plus.eu/forum/',
			'subtitle' => '',
		),
		'repo' => array(
			'name' => 'Repository',
			'icon' => 'fa-puzzle-piece',
			'url'	=> 'https://eqdkp-plus.eu/repository/',
			'subtitle' => '',
		),
		'wiki' => array(
			'name' => 'Wiki',
			'icon' => 'fa-wikipedia-w',
			'url'	=> 'https://eqdkp-plus.eu/wiki/',
			'subtitle' => '',
		),
		'liveupdate' => array(
			'name' => 'Live-Update',
			'icon' => 'fa-refresh',
			'url'	=> 'https://eqdkp-plus.eu/repository/',
			'subtitle' => '',
		),
		'mirror1' => array(
			'name' => 'Mirror 1',
			'icon' => 'fa-download',
			'url'	=> 'http://mirror1.eqdkp-plus.eu/',
			'subtitle' => '@ uberspace',
		),
		'mirror2' => array(
			'name' => 'Mirror 2',
			'icon' => 'fa-download',
			'url'	=> 'http://mirror2.eqdkp-plus.eu/',
			'subtitle' => '@ webgo',
		),
	);
	
	
	function __construct(){
		$this->objUrlfetcher = new urlfetcher();
	}
	
	function get_cache($strCheck){
		$intCacheTime = 5*60; //5 Minutes
		
		$strCache = file_get_contents('cache/cache_'.$strCheck.'.json');
		if($strCache){
			$arrJson = json_decode($strCache, true);
			$intTime = (int)$arrJson['time'];
			if(($intTime + $intCacheTime) < time() ){
				//Cache Outdated
				return false;
			}
			
			return $arrJson['result'];
		}
		
		return false;
	}
	
	function do_cache($strCheck, $strResult){
		$strOut = json_encode(array('time' => time(), 'result' => $strResult));
		file_put_contents('cache/cache_'.$strCheck.'.json', $strOut);
	}
	
	//Display the neccessary things
	public function execute(){
		$out = "";
		foreach($this->arrActiveChecks as $val){
			$checkData = $this->arrChecks[$val];
			//$blnResult = $this->_do_check($val);
			
			$out .= '
			<div class="extCategoryContainer mycheck" data-checkid="'.$val.'">
		<div>
			<div class="grid1">
				<a href="'.$checkData['url'].'" style="color:#000;"><i class="fa '.$checkData['icon'].' myicon" ></i></a>
			</div>
			
			<div class="grid7">
				<h2 style="font-size: 17px;">
					<a href="'.$checkData['url'].'" style="display:block;">'.$checkData['name'].'</a>
				</h2>
				'.((isset($checkData['subtitle'])) ? $checkData['subtitle'] : '').'
			</div>
			
			<div class="grid2 mystatus">
				 <i class="fa fa-spinner fa-spin statusicon" title="Loading"></i>
			</div>

		</div>
		<div class="clear"></div>
	</div>
		';
		}
		
		echo $out;

	}
	
	public function do_check($strCheck){
		if(in_array($strCheck, $this->arrActiveChecks)){
			$mixCache = $this->get_cache($strCheck);
			if($mixCache !== false){
				echo $mixCache;
				exit;
			}
			
			$varMethod = "check_".$strCheck;
			$blnResult = $this->{$varMethod}();
			if($blnResult){
				$this->do_cache($strCheck, "true");
				echo "true";
				exit;
			}
		}
		echo "false";
		$this->do_cache($strCheck, "false");
		exit;
	}
	
	private function _do_check($strCheck){
		if(in_array($strCheck, $this->arrActiveChecks)){
			$varMethod = "check_".$strCheck;
			$blnResult = $this->{$varMethod}();
			if($blnResult){
				return true;
			}
		}
		return false;
	}
	
	//Check Repo API (fetches Extensionlist)
	function check_liveupdate(){
		$strFetchResult = $this->objUrlfetcher->fetch("https://eqdkp-plus.eu/repository/repository.php?function=extension_list");
		if($strFetchResult){
			$arrJson = json_decode($strFetchResult, true);
			if($arrJson && isset($arrJson['extensions']) && isset($arrJson['extensions']['extension_pk'])){
				return true;
			}
		}
		
		return false;
	}
	
	//Checks Forum
	function check_forum(){
		$strFetchResult = $this->objUrlfetcher->fetch("https://eqdkp-plus.eu/forum/");
		if($strFetchResult){
			if(preg_match('#'.preg_quote('<base href="https://eqdkp-plus.eu/forum/" />').'#', $strFetchResult)){
				return true;
			}

		}
		
		return false;
	}
	
	//Checks the Homepage
	function check_homepage(){
		$strFetchResult = $this->objUrlfetcher->fetch("https://eqdkp-plus.eu/de/");
		if($strFetchResult){
			if(preg_match('#'.preg_quote('title="Herunterladen"').'#', $strFetchResult)){
				return true;
			}

		}
		
		return false;	
	}
	
	//Checks the Wiki
	function check_wiki(){
		$strFetchResult = $this->objUrlfetcher->fetch("https://eqdkp-plus.eu/wiki/");
		if($strFetchResult){
			if(preg_match('#'.preg_quote('Willkommen im EQdkp Plus Wiki').'#', $strFetchResult)){
				return true;
			}

		}
		
		return false;
	}
	
	//Checks the Repo
	function check_repo(){
		$strFetchResult = $this->objUrlfetcher->fetch("https://eqdkp-plus.eu/repository/");
		if($strFetchResult){
			if(preg_match('#'.preg_quote('<meta name="author" content="Repository" />').'#', $strFetchResult)){
				return true;
			}
		}
		
		return false;
	}
	
	//Check mirror1 (uberspace)
	function check_mirror1(){
		$strFetchResult = $this->objUrlfetcher->fetch("http://mirror1.eqdkp-plus.eu/");
		if($strFetchResult){
			if(preg_match('#'.preg_quote('Mirror last update').'#', $strFetchResult)){
				return true;
			}
		}
		
		return false;
	}
	
	//Check mirror2 (webgo)
	function check_mirror2(){
		$strFetchResult = $this->objUrlfetcher->fetch("http://mirror2.eqdkp-plus.eu/");
		if($strFetchResult){
			if(preg_match('#'.preg_quote('Mirror last update').'#', $strFetchResult)){
				return true;
			}
		}
		
		return false;
	}
	
}



class urlfetcher {

	private $useragent			= 'EQDKP-PLUS (Status Overview)';		// User Agent
	private $timeout			= 10;											// Timeout
	private $conn_timeout		= 3;											// Connection Timeout
	private $methods			= array('curl');			// available function methods
	private $method				= 'curl';											// the selected method
	private $maxRedirects		= 5;

	public function get_method(){
		return $this->method;
	}

	/**
	 * Return the Data
	 * Checks all given methods to get the date from the url
	 *
	 * @param String $geturl
	 * @return string
	 */
	public function fetch($geturl, $header='', $conn_timeout = false, $timeout = false){
		$this->method = ($this->method) ? $this->method : 'fopen';
		if (!$conn_timeout) $conn_timeout = $this->conn_timeout;
		if (!$timeout) $timeout = $this->timeout;

		return $this->{'get_'.$this->method}($geturl, $header, $conn_timeout, $timeout);
	}
	
	public function post($url, $data, $content_type = "text/html; charset=utf-8", $header='', $conn_timeout = false, $timeout = false){
		if(is_array($data)){
			$data = http_build_query($data, '', '&');
		}
		
		$this->method = ($this->method) ? $this->method : 'fopen';
		if (!$conn_timeout) $conn_timeout = $this->conn_timeout;
		if (!$timeout) $timeout = $this->timeout;

		return $this->{'post_'.$this->method}($url, $data, $content_type, $header, $conn_timeout, $timeout);
	}

	/**
	 * Try to get the data from the URL via the curl function
	 *
	 * @param string $geturl
	 * @return string
	 */
	private function get_curl($geturl, $header, $conn_timeout, $timeout){
		$curlOptions = array(
			CURLOPT_URL				=> $geturl,
			CURLOPT_USERAGENT		=> $this->useragent,
			CURLOPT_TIMEOUT			=> $timeout,
			CURLOPT_CONNECTTIMEOUT	=> $conn_timeout,
			CURLOPT_ENCODING		=> "gzip",
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_SSL_VERIFYHOST	=> false,
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_VERBOSE			=> false,
			CURLOPT_HTTPAUTH		=> CURLAUTH_ANY,
			CURLOPT_HTTPHEADER		=> ((is_array($header) && count($header) > 0) ? $header : array())
		);
		if (@ini_get('open_basedir') == '' && (!@ini_get('safe_mode') || ini_get('safe_mode') == 'Off')) {
			$curlOptions[CURLOPT_FOLLOWLOCATION] = true;
			
			$curl = curl_init();
			curl_setopt_array($curl, $curlOptions);
			$getdata = curl_exec($curl);
			
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			$arrCurlInfo = curl_getinfo($curl);
			$curl_error = curl_errno($curl);
			
			curl_close($curl);
			if(intval($code) >= 400) return false;
			
			return $getdata;	
		} else {
			$curlOptions[CURLOPT_HEADER] = true;
			$curlOptions[CURLOPT_FORBID_REUSE] = false;
			$curlOptions[CURLOPT_RETURNTRANSFER] = true;
			
			$maxRedirects = $this->maxRedirects;
			
			$curl = curl_init();
			curl_setopt_array($curl, $curlOptions);
			
			$newurl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
			$code = 0;
			do {
				curl_setopt($curl, CURLOPT_URL, $newurl);
				$header = curl_exec($curl);
				if (curl_errno($curl)) {
					$code = 0;
				} else {
					$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
					if ($code == 301 || $code == 302) {
						preg_match('/Location:(.*?)\n/', $header, $matches);
						$newurl = trim(array_pop($matches));
						if(stripos($newurl, '://') === false){
							$curlData = curl_getinfo($curl);						
							$urlData = parse_url($curlData['url']);
							$newurl = $urlData['scheme'].'://'.$urlData['host'].$newurl;
						}
						curl_setopt($curl, CURLOPT_POSTFIELDS, null); //also switch modes after Redirect
						curl_setopt($curl, CURLOPT_HTTPGET, true);
						$this->pdl->log('urlfetcher', 'Redirect to '.$newurl.' because of Code '.$code);
					} else {
						$code = 0;
					}
				}
			
			} while ($code && --$maxRedirects);
			
			if ($maxRedirects < 0) {
				trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
				return false;
			}
			
			curl_setopt($curl, CURLOPT_URL, $newurl);
			$getdata = curl_exec($curl);
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			$arrCurlInfo = curl_getinfo($curl);
			$curl_error = curl_errno($curl);
			
			curl_close($curl);
			//Remove Header
			list ($header,$page) = preg_split('/\r\n\r\n/',$getdata,2); 
			 
			return $page;
		}
	}
	
	private function post_curl($url, $data, $content_type, $header, $conn_timeout, $timeout){
		if (is_array($header) && count($header) > 0){
			$header[] = "Content-type: ".$content_type;
			$header[] = "Content-Length: ".strlen($data);
		} else {
			$header = array();
			$header[] = "Content-type: ".$content_type;
			$header[] = "Content-Length: ".strlen($data);
		}
		
		$curlOptions = array(
			CURLOPT_URL				=> $url,
			CURLOPT_USERAGENT		=> $this->useragent,
			CURLOPT_TIMEOUT			=> $timeout,
			CURLOPT_CONNECTTIMEOUT	=> $conn_timeout,
			CURLOPT_ENCODING		=> "gzip",
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_SSL_VERIFYHOST	=> false,
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_VERBOSE			=> false,
			CURLOPT_HTTPAUTH		=> CURLAUTH_ANY,
			CURLOPT_HTTPHEADER		=> $header,
			CURLOPT_POST			=> 1,
			CURLOPT_POSTFIELDS		=> $data,
			CURLOPT_FOLLOWLOCATION	=> true,
		);
		if (@ini_get('open_basedir') == '' && !@ini_get('safe_mode')) {
			$curlOptions[CURLOPT_FOLLOWLOCATION] = true;
		}
		$curl = curl_init();
		curl_setopt_array($curl, $curlOptions);
		$getdata = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		$arrCurlInfo = curl_getinfo($curl);
		$curl_error = curl_errno($curl);
		
		curl_close($curl);

		return trim($getdata);
	}

}
?>

<!DOCTYPE html>
						<html>
						<head>
							<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

							<title>EQdkp Plus - Status Overview</title>
							<link rel='stylesheet' href='src/fontawesome/font-awesome.min.css' type='text/css' media='screen' />
							<script src="src/jquery.min.js"></script>
							<style type="text/css">
							/* body */
							html {
								height: 100%;
							}
							
							body {
								background: #0e0e0e;
								font-size: 14px;
								font-family: Tahoma,Arial,Verdana,sans-serif;
								color: #000;
								padding:0;
							  	margin:0;
								line-height: 20px;
							}
								
							
							.wrapper{
								background: #2e78b0; /* Old browsers */
								background: -moz-linear-gradient(top,  #2e78b0 0%, #193759 100%); /* FF3.6+ */
								background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#2e78b0), color-stop(100%,#193759)); /* Chrome,Safari4+ */
								background: -webkit-linear-gradient(top,  #2e78b0 0%,#193759 100%); /* Chrome10+,Safari5.1+ */
								background: -o-linear-gradient(top,  #2e78b0 0%,#193759 100%); /* Opera 11.10+ */
								background: -ms-linear-gradient(top,  #2e78b0 0%,#193759 100%); /* IE10+ */
								background: linear-gradient(to bottom,  #2e78b0 0%,#193759 100%); /* W3C */
								filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#2e78b0', endColorstr='#193759',GradientType=0 ); /* IE6-9 */
								font-size: 14px;
								font-family: Tahoma,Arial,Verdana,sans-serif;
								color: #000000;
								padding:0;
							  	margin:0;
								line-height: 20px;
							}							
							
							.header {
								padding-top: 10px;
								font-size: 45px;
								font-weight: bold;
								text-shadow: 1px 1px 2px #fff;
								filter: dropshadow(color=#fff, offx=1, offy=1);
								border: none;
								color:  #fff;
								text-align:center;
								vertical-align: middle;
								font-family: 'Trebuchet MS',Arial,sans-serif;
								
								background: url(src/background-head.svg) no-repeat scroll center top transparent;
								background-size: 100%;
							}
							
							.header img {
								height: 150px;
								vertical-align: middle;
							}
							
							.footer {
								margin-top: 10px;
								color: #fff;
								text-align: center;
								background-color: #0e0e0e;
								padding: 10px 40px 10px;
							}
							
							.footer a, .footer a:link, .footer a:visited {
								color: #fff;
								text-decoration: none;
							}
							
							.footer a:hover {
								text-decoration: underline;
							}
							
							.innerWrapper {
								background-color: #f8f8f8;
							}
									
							h1, h2, h3 {
								font-family: 'Trebuchet MS',Arial,sans-serif;
							    font-weight: bold;
							    margin-bottom: 10px;
							    padding-bottom: 5px;
								border-bottom: 1px solid #CCCCCC;
								margin-top: 5px;
							}
							
							h1 {
							    font-size: 20px;
							}
							
							h2 {
								font-size: 18px;
							}
							
							h3 {
								font-size: 14px;
								border-bottom: none;
								margin-bottom: 5px;
							}
									
							/* Links */
							a,a:link,a:active,a:visited {
								color: #4E7FA8;
								text-decoration: none;
							}
							
							a:hover {
								color: #000;
								text-decoration: none;
							}
							
							.content {
								width: 960px;
								padding: 5px;
								margin: auto;
							}
							
							.extCategoryContainer {
								padding: 15px;
								border: 1px solid #DDD;
								background-color: #fff;
								margin-bottom: 10px;
							}
							
							.extCategoryContainer .grid1 i {
								font-size: 50px;
							}
							
							*[class*="grid"] {
    float: left;
    margin-left: 10px;
    margin-right: 10px;
    display: inline;
}

.grid1 {
    width: 60px;
}

.grid7 {
    width: 250px;
}

.grid50 {
	width: 460px;
}

.clear {
	clear: both;
}

.header h1 {
    padding-left: 40px;
    padding-top: 30px;
    font-size: 50px;
    font-weight: bold;
    text-shadow: 1px 1px 2px #fff;
    filter: dropshadow(color=#fff, offx=1, offy=1);
    border: none;
	line-height: 55px;
}

.header img {
	float: left;
}

.headerInner{
	width: 960px;
	margin: auto;
}

i.statusicon {
	font-size: 50px;
}

.green {
	color: green;
}

.red {
	color: red;
}
						</style>
						
						<script>
							$(document).ready(function(){
								$( ".mycheck" ).each(function( index ) {
								  console.log( index + ": " + $( this ).text() );
								  var checkid = $(this).data('checkid');
								  
									$.ajax({
										url: "index.php?check="+checkid+"&_="+Date.now(),
										context: this,
										success: function(result){

											var myobj = this;
											
											$(myobj).find('.mystatus > .fa-spin').hide();
												
											if(result == "true"){
												$(myobj).find('.mystatus').html('<i class="fa fa-check-circle green statusicon" title="Everything OK"></i>');
												$(myobj).find('.myicon').addClass('green');
											} else {
												$(myobj).find('.mystatus').html('<i class="fa fa-times red statusicon" title="Failure"></i>');
												$(myobj).find('.myicon').addClass('red');
											}
										}
									});
																  
								});
							})
						</script>
						
						</head>

						<body>
						<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_EN/sdk.js#xfbml=1&version=v2.7&appId=133597650040084";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
			
						<div class="wrapper">
							<div class="header">
								<div class="headerInner">
									<a href="index.php"><img src="src/logo.svg" alt="EQdkp Plus" class="absmiddle" style="height: 130px;"/></a>
									<h1>EQdkp Plus - Status Overview</h1>
									<div class="clear"></div>
								</div>
							</div>
		
							<div class="innerWrapper">
								<div class="content">
									
									<div class="grid50">
										<h2>Status Services</h2>
										<?php
											$objStatus = new status;
											$objStatus->execute();
										?>
										<i class="fa fa-info-circle"></i> The status of the services is checked every 5 minutes.
									</div>
									
									<div class="grid50">
										<h2>News <a href="https://www.facebook.com/EQdkpPlus"><i class="fa fa-lg fa-facebook"></i></a> </h2>
										<div style="width:800px;">
											<div class="fb-page" data-href="https://www.facebook.com/EQdkpPlus/" data-tabs="timeline" data-small-header="true" data-adapt-container-width="true" data-hide-cover="true" data-show-facepile="false" data-width="800" data-height="650"><blockquote cite="https://www.facebook.com/EQdkpPlus/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/EQdkpPlus/">EQdkp-Plus</a></blockquote></div>
										</div>
									</div>

									
									<div class="clear"></div>

									
								</div>

							</div>	

					</div>	
					<div class="footer">
						
							<a href="https://www.facebook.com/EQdkpPlus"><i class="fa fa-lg fa-facebook"></i> Facebook</a><br />
							<a href="https://twitter.com/#!/EQdkpPlus"><i class="fa fa-lg fa-twitter"></i> Twitter</a><br /><br />
						
					
					
						<a href="http://eqdkp-plus.eu" target="_new">EQDKP Plus</a> &copy; 2003 - 2016 by EQDKP Plus Developer Team
					</div>	
					</body>
					</html>