<?PHP

	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	require_once('classes/db.php');
	require_once('classes/oauth.php');
	require_once('yelpInfo.php');
	
	$server = 'http://'.$_SERVER['SERVER_NAME'];
	if (substr($_SERVER['HTTP_REFERER'], 0, strlen($server)) != $server) exit;
	
	function yelpRequest($url) {
		global $yelpKey, $yelpCSecret, $yelpToken, $yelpSecret;
		
		// Token object built using the OAuth library
		$token = new OAuthToken($yelpToken, $yelpSecret);
		// Consumer object built using the OAuth library
		$consumer = new OAuthConsumer($yelpKey, $yelpCSecret);
		// Yelp uses HMAC SHA1 encoding
		$signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		$oauthrequest = OAuthRequest::from_consumer_and_token(
			$consumer, 
			$token, 
			'GET', 
			$url
		);
		
		// Sign the request
		$oauthrequest->sign_request($signature_method, $consumer, $token);
		
		// Get the signed URL
		$signed_url = $oauthrequest->to_url();
		
		// Send Yelp API Call
		$ch = curl_init($signed_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data;
	}
	
	echo yelpRequest("http://api.yelp.com/v2/search?oauth_consumer_key=$yelpKey&oauth_token=$yelpToken&oauth_signature_method=hmac-sha1&oauth_signature=&oauth_timestamp=".time()."&oauth_nonce=".uniqid()."&term=".$_GET['title']."&location=".$_GET['location']."&cll=".$_GET['lat'].','.$_GET['lng']);

?>