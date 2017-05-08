<?php
function __autoload($class_name) {
	$path = '../classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../config/host.json' );

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

$messages = array ();
$user = new User ( $system->getAuthenticatedUserId () );
$user->hydrate ();

if (isset ( $_POST ['user_task'] ) && strcmp ( $_POST ['user_task'], 'supprimer' ) == 0) {
	if (strcmp ( $_POST ['user_password'], $_POST ['user_password_bis'] ) == 0) {
		$user->setName ( $_POST ['user_name'] );
		$user->setPassword ( $_POST ['user_password'] );
		$user->setEmail ( $_POST ['user_email'] );
		$messages [] = $user->toDB () ? 'Enregistrement effectif' : 'Echec de l\'enregistrement';
	} else {
		$messages [] = 'Le mot de passe n\'a pas été correctement confirmé, il doit être saisi à nouveau ...';
	}
}
$doc_title = 'Modifier mes données personnelles';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" /><link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_THEME_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $system->getSkinUrl(); ?>/apple-touch-icon.png">
<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-32x32.png" sizes="32x32">
<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-16x16.png" sizes="16x16">
<link rel="manifest" href="<?php echo $system->getSkinUrl(); ?>/manifest.json">
<link rel="mask-icon" href="<?php echo $system->getSkinUrl(); ?>/safari-pinned-tab.svg" color="#5bbad5">
<link rel="shortcut icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico">
<meta name="msapplication-config" content="<?php echo $system->getSkinUrl(); ?>/browserconfig.xml">
<meta name="theme-color" content="#8ea4bc">
<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
	</header>
	<div>
		<?php if (count($messages)>0) echo '<div class="alerte">'.implode('<br />', $messages).'</div>'?>
		<form target="_self" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
			<div class="form-group">
				<label for="user_name_input">Nom de l&rsquo;utilisateur</label> <input id="user_name_input" name="user_name" value="<?php if (isset($_REQUEST['user_name'])) echo $_REQUEST['user_name'] ?>" class="form-control" />
			</div>
			<div class="form-group">
				<label for="user_password_input">Mot de passe</label> <input id="user_password_input" name="user_password" type="password" class="form-control" />
			</div>
			<div class="form-group">
				<label for="user_password_bis_input">Confirmation du mot de passe</label> <input id="user_password_bis_input" name="user_password_bis" type="password" class="form-control" />
			</div>
			<div class="form-group">
				<label for="user_email_input">Mail</label> <input id="user_email_input" name="user_email" type="text" value="<?php if (isset($_REQUEST['user_email'])) echo $_REQUEST['user_email'] ?>" class="form-control" />
			</div>
			<input name="user_id" type="hidden" value="<?php echo $user->getId() ?>" /> <input name="user_task" type="submit" value="enregistrer" class="btn btn-primary" />
		</form>
	</div>
</body>
</html>
