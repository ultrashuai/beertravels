<?PHP
	
	require_once('classes/db.php');
	require_once('classes/oauth.php');
	$db = new database('sqlins', 'wheretostop');
	
	$flickrKey = '661563d780876dd3e0526fe98ced9ba8';
	$flickrUID = urlencode('38770231@N04');
	
	function jsSafe ($str) {
		return str_replace("'", "\\'", $str);
	}
	
	$photoid = (isset($_GET['photo']) && preg_match("/^\d+$/", $_GET['photo'])) ? $_GET['photo'] : NULL;
	
	$db->query("SELECT * FROM breweryTravels WHERE active = 1 ORDER BY photoID DESC");
	$db->flip();
	$dbPhotos = $db->flipResults;
	$photoids = $db->flipResults['photoID'];
	
	$url = "https://api.flickr.com/services/rest/?format=json&api_key=$flickrKey&user_id=$flickrUID&tags=brewerytravels&per_page=100&method=flickr.photos.";
	if ($photoid) $url .= 'getInfo&photo_id='.$photoid.'&secret='.$dbInfo['secret'];
	else $url .= 'search&sort=date-taken-desc&extras=date_taken,geo,tags,url_q,url_m';
	$f = file_get_contents($url);
	
	$stream = json_decode(substr($f, 14, strlen($f) - 15));
	
	$locations = array();
	$points = array();
	$regions = array();
	$streamPhotoIDs = array();
	
	foreach ($stream->photos->photo as $photo) {
		$tags = explode(' ', $photo->tags);
		$regionTag = '';
		foreach ($tags as $tag) {
			if ($tag != 'brewerytravels') $regionTag = $tag;
		}
		if (!isset($regions[$regionTag])) $regions[$regionTag] = array();
		$regions[$regionTag][] = $photo->id;
		
		$streamPhotoIDs[] = $photo->id;
		
		if (in_array($photo->id, $photoids)) {
			if (!$dbPhotos['photoSecret'][array_search($photo->id, $dbPhotos['photoID'])]) {
				$db->query("UPDATE breweryTravels SET lastUpdate = NOW(), photoSecret = ".$db->format($photo->secret)." WHERE photoID = $photo->id");
			}
			$locations[$photo->id] = $placeStream->place->locality->_content.', '.$placeStream->place->region->_content;
			continue;
		}
		$query = "INSERT INTO breweryTravels (photoID, photoSecret, businessName, lat, lng, lastUpdate) VALUES($photo->id, ";
		$query .= $db->format($photo->secret).", ".$db->format($photo->title).", ";
		if ($photo->latitude) $query .= "'$photo->latitude', '$photo->longitude'";
		else $query .= 'NULL, NULL';
		$query .= ', NOW())';
		$db->query($query);
		if ($photo->place_id) {
			$f = file_get_contents("https://api.flickr.com/services/rest/?format=json&api_key=$flickrKey&place_id=$photo->place_id&tags=brewerytravelsmethod=flickr.places.getInfo");
			$placeStream = json_decode(substr($f, 14, strlen($f) - 15));
			$locations[$photo->id] = $placeStream->place->locality->_content.', '.$placeStream->place->region->_content;
			//$points[$photo->id] = array($photo->latitude, $photo->longitude);
		}
	}
	
	$db->query("SELECT * FROM breweryTravels WHERE active = 1 ORDER BY photoID DESC");
	$db->flip();
	$dbPhotos = $db->flipResults;
	foreach ($db->results as $row) {
		if (!$row['lat']) continue;
		$points[$row['photoID']] = array($row['lat'], $row['lng']);
	}


?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Brewery Travels</title>
<script type="text/javascript" src="//code.jquery.com/jquery-latest.min.js"></script>
<script type="text/javascript" src="//maps.google.com/maps/api/js?sensor=false&libraries=places"></script>
<script type="text/javascript">

	var map;
	var points = {};
	var iws = {};
	var html, iw, openiw;
		
	$(function () {
		$('#nav div').on('click', function () {
			var divs = ['map', 'post', 'region'];
			var selected = $(this).html();
			for (var i = 0; i < divs.length; i++) {
				$('#' + divs[i] + 'Container').hide();
			}
			$('#nav div.selected').removeClass('selected');
			$(this).addClass('selected');
			switch (selected.toLowerCase()) {
				case 'map':
					$('#mapContainer').show();
				break;
				case 'list by date':
					$('#postContainer').show();
				break;
				case 'list by region':
					$('#regionContainer').show();
				break;
				default:
				break;
			}
		});
		
		$('#upcomingMenu').on('click', function () {
			$('#upcomingMenu ul').show();
		});
		
		$('.post .img img').on('click', function () {
			if (!$(this).attr('fullsize')) return false;
			window.open($(this).attr('fullsize'), '', '');
		});
		
		map = new google.maps.Map(document.getElementById('map'), {center:new google.maps.LatLng(39, -97), zoom:4});
		new google.maps.KmlLayer({
			url: 'http://lonelycrowdedwest.net<?= str_replace('index.php', 'Regions.kmz', $_SERVER['PHP_SELF']) ?>',
			map: map,
			preserveViewport:true
		});
<?PHP

	$icon = 'beericon.png';
	foreach ($points as $pic=>$point) {
		$dbIndex = array_search($pic, $dbPhotos['photoID']);
		$photoIndex = array_search($pic, $streamPhotoIDs);
		$photo = $stream->photos->photo[$photoIndex];
		echo "		points['p$pic'] = new google.maps.Marker({icon:'$icon', map:map, title:'";
		echo jsSafe($dbPhotos['businessName'][$dbIndex]);
		echo "', position:new google.maps.LatLng($point[0], $point[1])});\n";
		echo "		google.maps.event.addListener(points['p$pic'], 'click', function () {\n";
		echo '			html = \'<div class="iwpost"><div class="img"><img src="'.$photo->url_q.'" width="'.$photo->width_q;
		echo '" height="'.$photo->height_q.'" fullsize="'.$photo->url_m."\" onclick=\"window.open(\\'$photo->url_m\\', \\'\\', \\'\\');\" /></div>";
		echo '<div class="title">'.jsSafe($dbPhotos['businessName'][$dbIndex]).'</div>';
		echo '<div class="trip">'.$trips[preg_replace("/ ?brewerytravels ?/", '', $photo->tags)].'</div>';
		echo '<div class="date">'.date("n/j/Y", strtotime($photo->datetaken)).'</div>';
		echo '<div class="location">';
		if ($dbPhotos['businessAddress'][$dbIndex]) echo jsSafe($dbPhotos['businessAddress'][$dbIndex]);
		else echo $locations[$photo->id];
		//if ($dbPhotos['postText'][$dbIndex]) echo '</div><div><a href="">More &gt;&gt;</a></div>'."\n";
		echo "</div>';\n";
		echo "				iw = new google.maps.InfoWindow({content:html});
						iws['p$pic'] = iw;
						if (openiw) openiw.close();
						openiw = iw;
						iw.open(map, this);
					});\n";
	}

?>
	});

</script>
<style type="text/css">

	body {
		font-family:"Trebuchet MS", Arial, Helvetica, sans-serif;
		font-size:0.9em;
	}
	
	a {
		color:#006;
	}
	
	h1, h2, h3 {
		clear:both;
	}
	
	#menu {
		background:#333;
		padding:0 50px;
		font-size:1.3em;
		font-weight:bold;
		color:#006;
		clear:both;
	}
		#menu div {
			width:250px;
			float:left;
			padding:10px 20px;
			text-align:center;
			cursor:pointer;
			background:#EEE;
			text-decoration:underline;
			margin:0 10px;
		}
	
	#map {
		height:450px;
		width:1000px;
		margin:0 auto;
	}
	
	#nav, #mapContainer, #postContainer, #regionContainer {
		clear:both;
		margin:15px 0;
	}
	
	#postContainer, #regionContainer {
		display:none;
	}
	
	#nav div {
		margin:0 10px;
		padding:5px 20px;
		width:200px;
		text-align:center;
		float:left;
		background-color:#69F;
		cursor:pointer;
	}
	#nav div.selected {
		background-color:#CF3;
	}
	
	#upcomingMenu ul {
		display:none;
		font-size:0.5em;
		background-color:#FFF;
		padding:0;
		margin:0;
		font-weight:normal;
	}
		#upcomingMenu li {
			list-style-type:none;
			margin:2px 5px;
			padding:0;
			text-align:left;
			text-decoration:none;
		}
		
	.post {
		clear:both;
		border-bottom:solid 1px #CCC;
		padding:15px;
		margin:5px 20px;
	}
		.post .img, .iwpost .img {
			float:left;
			width:200px;
			padding:10px;
			cursor:pointer;
		}
		.post .title, .post .trip, .post .date, .post .location, .post .postText {
			margin-left:225px;
			padding:2px 5px;
		}
		.post .title {
			font-weight:bold;
			font-size:1.1em;
		}
		.post .trip, .post .date, .post .location {
			font-size:0.9em;
		}

</style>
</head>

<body>
	<!--<div id="menu">
        <div id="upcomingMenu">
        	Upcoming Brewery Travels
            <ul>
                <li>Mid October 2015: Denver, Colorado Springs, Albuquerque, Phoenix, Tucson</li>
                <li>Early November 2015: San Francisco &amp; San Mateo County</li>
                <li>Mid November 2015: North Coast California</li>
                <li>Early December 2015: Seattle, Tacoma, Portland</li>
                <li>Mid January 2016: New Orleans &amp; Louisiana</li>
            </ul>
        </div>
        <div><a href="about.html">About This</a></div>
        <div style="float:none; width:1px; background-color:#333; margin-left:600px;">&nbsp;</div>
    </div>-->
    
    <h2 style="margin:15px 0;">Recent Brewery Travels</h2>
    
    <div id="nav"><div class="selected">Map</div><div>List by Date</div><div>List by Region</div></div>
    
    <div id="mapContainer" style="text-align:center;"><div id="map"></div></div>
    
    <div id="postContainer">
    
<?PHP

		foreach ($stream->photos->photo as $i=>$photo) {
			$dbIndex = array_search($photo->id, $dbPhotos['photoID']);
			echo '	<div class="post"><div class="img"><img src="'.$photo->url_q.'" width="'.$photo->width_q;
			echo '" height="'.$photo->height_q.'" fullsize="'.$photo->url_m.'" /></div><div class="title">'.$dbPhotos['businessName'][$dbIndex].'</div>';
			echo '<div class="trip">'.$trips[preg_replace("/ ?brewerytravels ?/", '', $photo->tags)].'</div>';
			echo '<div class="date">'.date("n/j/Y", strtotime($photo->datetaken)).'</div>';
			echo '<div class="location">';
			if ($dbPhotos['businessAddress'][$dbIndex]) echo $dbPhotos['businessAddress'][$dbIndex];
			else echo $locations[$photo->id];
			echo '</div><div class="postText">'.$dbPhotos['postText'][$dbIndex];
			echo "</div><div style=\"clear:both;\">&nbsp;</div></div>\n";
		}

?>
	</div>
    
    <div id="regionContainer">
<?PHP

		foreach ($regions as $region=>$photos) {
			echo "		<h3>$region</h3>\n";
			foreach ($photos as $photoid) {
				$idx = array_search($photoid, $streamPhotoIDs);
				$dbIndex = array_search($photoid, $dbPhotos['photoID']);
				$photo = $stream->photos->photo[$idx];
				echo '		<div class="post"><div class="img"><img src="'.$photo->url_q.'" width="'.$photo->width_q;
				echo '" height="'.$photo->height_q.'" fullsize="'.$photo->url_m.'" /></div><div class="title">'.$dbPhotos['businessName'][$dbIndex].'</div>';
				echo '<div class="trip">'.$trips[preg_replace("/ ?brewerytravels ?/", '', $photo->tags)].'</div>';
				echo '<div class="date">'.date("n/j/Y", strtotime($photo->datetaken)).'</div>';
				echo '<div class="location">';
				if ($dbPhotos['businessAddress'][$dbIndex]) echo $dbPhotos['businessAddress'][$dbIndex];
				else echo $locations[$photo->id];
				echo '</div><div class="postText">'.$dbPhotos['postText'][$dbIndex];
				echo "</div><div style=\"clear:both;\">&nbsp;</div></div>\n";
			}
		}

?>
    </div>
</body>
</html>