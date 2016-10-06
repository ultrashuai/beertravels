<?PHP

	if (!isset($_SESSION['breweryTravelsAdmin']) && stripos($_SERVER['PHP_SELF'], 'login.php') === false) header("Location: login.php");

?>