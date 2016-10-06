<?PHP

	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	require_once('classes/db.php');
	require_once('classes/oauth.php');
	$db = new database('sqlins', 'wheretostop');
	
	$flickrKey = '661563d780876dd3e0526fe98ced9ba8';
	$flickrUID = '38770231@N04';
	
	// flickr
	$album = (isset($_GET['album']) && preg_match("/^\d+$/", $_GET['album'])) ? $_GET['album'] : 0;
	$photoid = (isset($_GET['photo']) && preg_match("/^\d+$/", $_GET['photo'])) ? $_GET['photo'] : 0;
	$url = "https://api.flickr.com/services/rest/?format=json&api_key=$flickrKey&user_id=$flickrUID&method=flickr.";
	if ($photoid) $url .= 'photos.';
	else $url .= "photosets.";
	if ($photoid) $url .= 'getInfo&photo_id='.$photoid.'&secret='.$_GET['secret'];
	elseif ($album) $url .= 'getPhotos&extras=date_taken,geo,tags,url_q&photoset_id='.$_GET['album'];
	else $url .= "getList";
	$f = file_get_contents($url);
	
	//$f = file_get_contents("https://api.flickr.com/services/rest/?method=flickr.people.getPhotos&api_key=$flickrKey&user_id=$flickrUID&extras=date_taken,geo,tags,url_q,url_o&per_page=25&page=$page&format=json");
	$stream = json_decode(substr($f, 14, strlen($f) - 15));
	
	if ($photoid) {
		$db->query("SELECT * FROM breweryTravels WHERE photoID = '$photoid'");
		$dbPhoto = ($db->rows) ? $db->firstRow : null;
		
		if (isset($_POST['submitPhoto'])) {
			$values = array(
				'businessName'=>$db->format($_POST['businessName']),
				'lat'=>$db->format($_POST['lat']),
				'lng'=>$db->format($_POST['lng']),
				'businessAddress'=>$db->format($_POST['businessAddress']),
				'yelpID'=>$db->format($_POST['yelpID']),
				'yelpRatingImg'=>$db->format($_POST['yelpRatingImg']),
				'yelpReviewCount'=>$db->format($_POST['yelpReviewCount']),
				'lastUpdate'=>'NOW()'
			);
			if ($dbPhoto) {
				$queryString = "photoID=$photoid";
				foreach ($values as $field=>$val) {
					$queryString .= ",$field=$val";
				}
				$db->query("UPDATE breweryTravels SET $queryString WHERE photoID=$photoid");
			}
			else $db->query("INSERT INTO breweryTravels (photoID, active, ".implode(',', array_keys($values)).") VALUES('$photoid', 1, ".implode(',', $values).')');
			header("Location: ?album=$album&albumName=".$_GET['albumName']);
			exit;
		}
	}
	
	elseif ($album) {
		$photoids = array();
		foreach ($stream->photoset->photo as $i=>$photo) {
			if (stripos($photo->tags, 'brewerytravels') === false) continue;
			array_push($photoids, $photo->id);
		}
		
		$db->query("SELECT * FROM breweryTravels WHERE photoID IN ('".implode("','", $photoids)."') ORDER BY photoID");
		$existingPhotoIDs = array();
		foreach ($db->results as $row) {
			$existingPhotoIDs[] = $row['photoID'];
		}
		
		/*
		$insertQuery = array();
		$updateQuery = array();
		foreach ($photosToPull as $id) {
			$useID = (substr($id, 0, 1) == 'p') ? $id : "p$id";
			// google search for city & state
			$google = json_decode(file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng='.$posts[$useID]->latitude.','.$posts[$useID]->longitude));
			$location = '';
			foreach ($google->results as $ac) {
				if (!in_array('locality', $ac->types)) continue;
				$location = $ac->formatted_address;
			}
			if (!$location) continue;
			// yelp search, bitches
			$yelpFeed = json_decode(yelpRequest("http://api.yelp.com/v2/search?oauth_consumer_key=$yelpKey&oauth_token=$yelpToken&oauth_signature_method=hmac-sha1&oauth_signature=&oauth_timestamp=".time()."&oauth_nonce=".uniqid()."&term=".$posts[$useID]->title."&location=$location&cll=".$posts[$useID]->latitude.','.$posts[$useID]->longitude));
			$yelp = false;
			$firstWord = explode(' ', $posts[$useID]->title);
			$firstWord = $firstWord[0];
			
			foreach ($yelpFeed->businesses as $i=>$business) {
				if (stripos($business->name, $firstWord) === false) continue;
				$yelp = $i;
				break;
			}
			
			if ($yelp === false) continue;
			
			$yelp = $yelpFeed->businesses[$yelp];
			$valArray = array('businessName'=>$db->format($yelp->name), 'businessAddress'=>$db->format($yelp->location->address[0].', '.$yelp->location->city.', '.$yelp->location->state_code), 'yelpRatingImg'=>$db->format($yelp->rating_img_url), 'yelpReviewCount'=>$db->format($yelp->review_count), 'yelpURL'=>$db->format($yelp->url));
			
			if (in_array($useID, $photosToInsert)) $insertQuery[$useID] = "'".substr($id, 1)."','$yelp->id',".implode(',', array_values($valArray)).','.$posts[$useID]->latitude.','.$posts[$useID]->longitude.',NOW()';
			else {
				if ($yelp->is_closed) $valArray['active'] = 0;
				$updateQuery[$useID] = $valArray;
			}
		}
		
		if (count($insertQuery)) {
			$db->query("INSERT INTO breweryTravels (photoID, yelpID, yelpRatingImg, yelpReviewCount, yelpURL, businessName, businessAddress, lat, lng, lastUpdate) VALUES(".implode('),(', $insertQuery).")", false);
		}
		if (count($updateQuery)) {
			foreach ($updateQuery as $id=>$qry) {
				$query = "";
				foreach ($qry as $field=>$val) {
					$query .= ",$field=".$db->format($val);
				}
				$db->query("UPDATE breweryTravels SET lastUpdate=NOW()$query WHERE photoID='".substr($id,1)."'");
			}
		}
		*/
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Beer Travels</title>
<!-- Add jQuery library -->
<script type="text/javascript" src="//code.jquery.com/jquery-latest.min.js"></script>

<!-- Add fancyBox -->
<link rel="stylesheet" href="/js/fancybox/source/jquery.fancybox.css?v=2.1.5" type="text/css" media="screen" />
<script type="text/javascript" src="/js/fancybox/source/jquery.fancybox.pack.js?v=2.1.5"></script>

<!-- Optionally add helpers - button, thumbnail and/or media -->
<link rel="stylesheet" href="/js/fancybox/source/helpers/jquery.fancybox-buttons.css?v=1.0.5" type="text/css" media="screen" />
<script type="text/javascript" src="/js/fancybox/source/helpers/jquery.fancybox-buttons.js?v=1.0.5"></script>
<script type="text/javascript" src="/js/fancybox/source/helpers/jquery.fancybox-media.js?v=1.0.6"></script>

<link rel="stylesheet" href="/js/fancybox/source/helpers/jquery.fancybox-thumbs.css?v=1.0.7" type="text/css" media="screen" />
<script type="text/javascript" src="/js/fancybox/source/helpers/jquery.fancybox-thumbs.js?v=1.0.7"></script>
<script type="text/javascript">

	$(function () {
		$('.fancybox').fancybox();
		var eles = document.getElementsByName('businessName');
		for (var i = 0; i < eles.length; i++) {
			new google.maps.places.Autocomplete(eles[i], {types:['geocode']});
		}
	});
	
	function getYelp (formObj) {
		var dt = new Date();
		var url = 'yelp.php?title=' + $('#businessName').val() + '&location=' + $('#cityState').val() + '&lat=' + $('#lat').val() + '&lng=' + $('#lng').val();
		$.ajax(url, {
			complete:function (xhr, status) {
				//alert(xhr.responseText);
			},
			success:function (result) {
				var html = '';
				var data = $.parseJSON(result);
				for (var i = 0; i < data.businesses.length; i++) {
					var bus = data.businesses[i];
					html += '<div yelpID="' + bus.url.replace(/http:\/\/www\.yelp\.com\/biz\//, '') + '" yelpImg="' + bus.rating_img_url + '" yelpReviewCount="' + bus.review_count + '" address="' + bus.location.display_address.join(', ') + '" lat="' + bus.location.coordinate.latitude + '" lng="' + bus.location.coordinate.longitude + '" style="cursor:pointer;" onclick="selectYelp(this);">' + bus.name + '</div>';
				}
				$('#yelpResults').html(html);
			}
		});
		return false;
	}
	
	function selectYelp (div) {
		$('#yelpID').val($(div).attr('yelpID'));
		$('#businessAddress').val($(div).attr('address'));
		$('#yelpRatingImg').val($(div).attr('yelpImg'));
		$('#yelpReviewCount').val($(div).attr('yelpReviewCount'));
		if (!$('#lat').val()) $('#lat').val($(div).attr('lat'));
		if (!$('#lng').val()) $('#lng').val($(div).attr('lng'));
	}

</script>

<style type="text/css">

	body {
		font-family:Tahoma, Geneva, sans-serif;
		font-size:0.8em;
	}
	
	td {
		text-align:center;
		padding:20px 0;
		border:solid 1px #CCC;
	}
	
	td.existing {
		background-color:#CCC;
	}
	td.noLocation {
		background-color:#F99;
	}
	td .title {
		padding:5px 0;
	}
	td .tags {
		padding:5px 0;
		font-size:0.7em;
	}

</style>
</head>

<body>

<h1>Brewery Travels</h1>
<?PHP

	if ($photoid) {

?>

	<form method="post">
    	<div style="float:left; width:250px;"><img src="<?= "//farm{$stream->photo->farm}.staticflickr.com/{$stream->photo->server}/{$stream->photo->id}_{$stream->photo->secret}_q.jpg" ?>" /></div>
        <div style="margin-left:260px;">
        	<div><input type="text" name="businessName" id="businessName" value="<?= ($dbPhoto) ? $dbPhoto['businessName'] : $stream->photo->title->_content ?>" size="50" /></div>
            <div><input type="text" name="lat" id="lat" value="<?= ($dbPhoto) ? $dbPhoto['lat'] : (isset($stream->photo->location)) ? $stream->photo->location->latitude : '' ?>" size="12" /> <input type="text" name="lng" id="lng" value="<?= ($dbPhoto) ? $dbPhoto['lng'] : (isset($stream->photo->location)) ? $stream->photo->location->longitude : '' ?>" size="12" /></div>
            <div><input type="text" name="cityState" id="cityState" value="<?= (isset($stream->photo->location)) ? $stream->photo->location->locality->_content.', '.$stream->photo->location->region->_content : '' ?>" size="50" /></div>
            <div><input type="text" name="businessAddress" id="businessAddress" value="<?= ($dbPhoto) ? $dbPhoto['businessAddress'] : '' ?>" size="50" /></div>
            <div>
            	<input type="text" name="yelpID" id="yelpID" value="<?= ($dbPhoto) ? $dbPhoto['yelpID'] : '' ?>" />
                <button onclick="return getYelp(this.form);">Get Yelp Results</button>
            </div>
            <div style="height:50px; overflow:auto; font-size:0.8em; margin:10px 100px; border:solid 1px #CCC; padding:5px;" id="yelpResults"></div>
            <div style="text-align:center;">
            	<button onclick="window.location.href='?album=<?= $album ?>&albumName=<?= $_GET['albumName'] ?>'; return false;">Back</button>
            	<input type="submit" name="submitPhoto" value="Submit" />
                <input type="hidden" name="yelpReviewCount" id="yelpReviewCount" value="<?= ($dbPhoto) ? $dbPhoto['yelpReviewCount'] : '' ?>" />
                <input type="hidden" name="yelpRatingImg" id="yelpRatingImg" value="<?= ($dbPhoto) ? $dbPhoto['yelpRatingImg'] : '' ?>" />
            </div>
        </div>
    </form>

<?PHP

	}
	elseif ($album) {

?>

<div><b><?= $_GET['albumName'] ?>:</b></div>

	<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr><th width="25%">&nbsp;</th><th width="25%">&nbsp;</th><th width="25%">&nbsp;</th><th width="25%">&nbsp;</th></tr>
<?PHP

		$photoCount = count($stream->photoset->photo);
		foreach ($stream->photoset->photo as $i=>$photo) {
			echo '<td class="';
			if (!$photo->latitude) echo 'noLocation';
			elseif (in_array($photo->id, $existingPhotoIDs)) echo 'existing';
			echo '"><div class="img"><a href="?album='.$album.'&albumName='.$_GET['albumName'].'&photo='.$photo->id.'&secret='.$photo->secret.'"><img src="'.$photo->url_q.'" border="0" width="'.$photo->width_q.'" height="'.$photo->height_q.'" /></a></div>';
			echo '<div class="title">'.$photo->title.'</div><div class="tags">'.$photo->tags.'</div>';
			echo "<div class=\"editLink\"><a href=\"post.php?photo=$photo->id\">Edit Post</div></td>\n";
			
			if (!$i || (($i + 1) % 4 === 0) || $i == ($photoCount - 1)) {
				if ($i) echo "	</tr>\n";
				if ($i == ($photoCount - 1)) echo '	<tr valign="top">'."\n";
			}
		}
?>

	</table>
    <p align="center"><input type="button" value="Back" onclick="window.location.href='./';" /></p>

<?PHP	
	}
	else {
		echo "<h3>Album: <select onchange=\"window.location.href='?albumName=' + this.options[this.selectedIndex].text.substr(0, this.options[this.selectedIndex].text.indexOf(' (')) + '&album=' + this.options[this.selectedIndex].value;\"><option value=\"\">-- Albums --</option>";
		foreach ($stream->photosets->photoset as $i=>$album) {
			echo '<option value="'.$album->id.'">'.$album->title->_content.' ('.$album->photos.')</option>';
		}
		echo "</select></h3>\n";
	}

?>
</body>
</html>