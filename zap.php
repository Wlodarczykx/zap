<?php
require_once 'zaplib.php';

// open db connection
$db = new mysqli('localhost', 'root', '');
$db->select_db('zap');

// inject db
$zap = Zap::instance();
$zap->setDb($db);

// read querystring 
$where = (isset($_GET['r']) ? $zap->decodePeriods($_GET['r']) : false);
$who = !array_key_exists('zapper', $_COOKIE) ? false : intval($_COOKIE['zapper']);
$listing = isset($_GET['l']);

if (array_key_exists('submit', $_POST)) {
	$who = $zap->storeZapperInCookie($_POST['zapper']);
}

if ($who && false !== $where) {
	$zap->store($where, $who);
}

if ($who && !$listing) {
	// the following call issues a header redirect:
	$zap->load();
}

// OR:
?>

<html>
<head>
	<title>Zap</title>
	<link rel="stylesheet" type="text/css" href="zap.css">
</head>
<body>
	<?php echo (false === $who ? $zap->getUserFormHtml() : '')?>
	Zaps:<br>
	<br>
	<?php echo $zap->getListingHtml();?>
</body>
</html>

<?php
mysqli_close($db);
