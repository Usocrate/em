<?php

function __autoload($class_name)
{
    $path = '../classes/';
    if (is_file($path . $class_name . '.class.php')) {
        include_once $path . $class_name . '.class.php';
    } elseif ($path . $class_name . '.interface.php') {
        include_once $path . $class_name . '.interface.php';
    }
}

$system = new System('../config/host.json');

if (! $system->configFileExists()) {
    header ( 'Location:'.$system->getConfigUrl() );
    exit();
}

include_once '../inc/boot.php';

session_start();

if (! $system->isUserAuthenticated()) {
    header('Location:' . $system->getLoginUrl());
    exit();
}

$maintopic = $system->getMainTopic();
$doc_title = 'Tableau de bord';

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<meta name="author" content="<?php echo $system->projectCreatorToHtml() ?>" />
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" /><link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_THEME_URI ?>" type="text/css" />
	<link rel="stylesheet" type="text/css" href="<?php echo $system->getSkinUrl(); ?>/main.css" />
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $system->getSkinUrl(); ?>/apple-touch-icon.png">
	<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-32x32.png" sizes="32x32">
	<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-16x16.png" sizes="16x16">
	<link rel="manifest" href="<?php echo $system->getSkinUrl(); ?>/manifest.json">
	<link rel="mask-icon" href="<?php echo $system->getSkinUrl(); ?>/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico">
	<meta name="msapplication-config" content="<?php echo $system->getSkinUrl(); ?>/browserconfig.xml">
	<meta name="theme-color" content="#8ea4bc">
	<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
</head>
<body id="admin">
	<?php include 'menu.inc.php'; ?>
	<div class="container-fluid">
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<div class="row">
			<div class="col-md-6">
				<section>
					<h2>Configuration</h2>
					<ul>
						<li><a href="<?php echo $system->getConfigUrl() ?>">Accès à l'écran de configuration</a></li>
						<li><a href="info.php">phpinfo</a></li>
						<li>Une <a href="<?php echo $system->getNewUserEditionUrl() ?>" class="explicit">nouvelle personne</a> va participer à la collection de ressources <em><?php echo $system->projectNameToHtml() ?> </em>.</li>
						<li>Lien à enregistrer dans le navigateur, pour <a href="<?php echo ToolBox::toHtml('javascript:{popup=window.open("'.Bookmark::getEditionUrl(null,true).'?bookmark_url="+encodeURI(document.URL),"'.$system->getProjectName().'\+\+","height=550,width=1024,screenX=100,screenY=100,resizable");popup.focus();}') ?>"><span style="display: none"><?php echo $system->getProjectName() ?> : </span>ajout de ressource.</a></li>
					</ul>
				</section>
				<section>
					<h2>Consommation</h2>
					<ul>
						<li><a href="conso.php">Les statistiques de consommation</a></li>
						<li>Accès aux <a href="https://www.google.com/webmasters/tools/dashboard?hl=fr&amp;siteUrl=<?php echo urlencode($system->getProjectUrl()) ?>">outils Webmaster de Google</a></li>
						<li><a href="seasonality.php">Consultations saisonnières</a></li>
					</ul>				
				</section>				
			</div>
			<div class="col-md-6">
				<section>
					<h2>Maintenance</h2>
					<div><a href="maintenance.php">Les ressources oubliées</a></div>
				</section>
				<section>
					<h2>Import / Export</h2>
					<div>
						<ul>
							<li><a href="<?php echo $system->getProjectUrl() ?>/import.php">Importation au format NETSCAPE-Bookmark-file-1</a></li>
							<li><a href="<?php echo $system->getProjectUrl() ?>/netscape-bookmark-file-1.php">Exportation au format NETSCAPE-Bookmark-file-1</a></li>
						</ul>
					</div>
				</section>
			</div>
		</div>
	</div>
</body>
</html>
