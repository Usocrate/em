<?php
function __autoload($class_name) {
	$path = '../classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../../config/host.json' );

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once '../inc/boot.php';

// récupération des utilisateurs
$users = $system->getUsers ();

?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>Génération de mot de passe htaccess</title>
<link rel="stylesheet" href="<?php echo $system->getSkinUrl() ?>/main.css" type="text/css" />
<link rel="icon" type="image/x-icon" href="<?php echo $system->getSkinUrl() ?>/favicon.ico" />
</head>
<body class="container">
	<header><?php echo $system->getHtmlLink(); ?> <h1>Génération de mot de passe htaccess</h1>
	</header>
	<blockquote>Chacun des utilisateurs enregistrés en base de données se voit attribuer un compte dans le fichier .htpassword pour pouvoir accéder aux répertoires à accès restreint.</blockquote>
<?php
//
// écriture du fichier
//
ignore_user_abort ( true );
$fp = fopen ( '.htpasswd', "w+" );
try {
	if (flock ( $fp, LOCK_EX )) {
		echo '<ul>';
		foreach ( $users as $user ) {
			$item = $user->getName () . ':' . crypt ( $user->getPassword () );
			if (fputs ( $fp, "$item\n" )) {
				echo '<li>' . ToolBox::toHtml ( $user->getName () ) . '<small> : ' . crypt ( $user->getPassword () ) . '</small></li>';
			}
		}
		echo '</ul>';
		flock ( $fp, LOCK_UN ); // ouverture du verrou
	} else {
		throw new Exception ( 'Le fichier .htpasswd est verrouillé !' );
	}
} catch ( Exception $e ) {
	$system->reportException ( __METHOD__, $e );
}
fclose ( $fp );
ignore_user_abort ( false );
?>
</body>
</html>
