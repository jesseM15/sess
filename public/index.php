<?php 

require_once('../vendor/autoload.php');

$dotenv = Dotenv\Dotenv::create(__DIR__ . '/../');
$dotenv->load();

// Set database connection settings
$database = [
	'host' 	=> getenv('HOST'),
	'db' 	=> getenv('DB'),
	'user' 	=> getenv('USER'),
	'pass' 	=> getenv('PASS'),
];

// Set session settings
$settings = [
	'https' 		=> true,
	'httpOnly' 		=> true,
	'saveHandler' 	=> 'db',
	'database' 		=> $database,
];

// Initialize session
$session = new Session($settings);

// TODO: Add helpers like $session->set('key', $val) and $session->key

// Use $_SESSION
$_SESSION['hey'] = 'hello session';

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>jfry session</title>
</head>
<body>
	<pre><?= print_r($_SESSION) ?></pre>
</body>
</html>
