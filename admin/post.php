<?PHP

	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	require_once('classes/db.php');
	$db = new database('sqlins', 'wheretostop');
	
	$photoid = (isset($_GET['photo']) && preg_match("/^\d+$/", $_GET['photo'])) ? $_GET['photo'] : 0;
	
	$db->query("SELECT * FROM breweryTravels WHERE photoID = '$photoid'");
	$dbPhoto = ($db->rows) ? $db->firstRow : null;
	
	if (isset($_POST['submitPost'])) {
		$db->query("UPDATE breweryTravels SET lastUpdate = NOW(), postText = ".$db->format($_POST['postText'])." WHERE photoID = $photoid");
		header("Location: ./");
		exit;
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Edit Post</title>
</head>

<body>
	<h3><?= ($dbPhoto) ? $dbPhoto['businessName'] : '' ?></h3>
    <form method="post">
    	<div>
        	<textarea name="postText" rows="6" cols="70"><?= ($dbPhoto) ? $dbPhoto['postText'] : '' ?></textarea>
        </div>
        <div style="text-align:center;">
        	<input type="submit" name="submitPost" value="Save" />
            <input type="button" value="Cancel" onclick="window.location.href='./';" />
        </div>
    </form>
</body>
</html>