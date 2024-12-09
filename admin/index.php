<?php
require_once '../classes/System.class.php';
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
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<?php echo $system->writeHeadCommonLinkTags(); ?>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="admin">
	<?php include 'menu.inc.php'; ?>
	<main>
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
						<li>Une <a href="user_edit.php" class="explicit">nouvelle personne</a> va participer à la collection de ressources <em><?php echo $system->projectNameToHtml() ?> </em>.</li>
						<li>Lien à enregistrer dans le navigateur, pour <a href="<?php echo ToolBox::toHtml('javascript:{popup=window.open("'.Bookmark::getEditionUrl(null,true).'?bookmark_url="+encodeURI(document.URL),"'.$system->getProjectName().'\+\+","height=550,width=1024,screenX=100,screenY=100,resizable");popup.focus();}') ?>"><span style="display: none"><?php echo $system->getProjectName() ?> : </span>ajout de ressource.</a></li>
					</ul>
				</section>
				<section>
					<h2>Consommation</h2>
					<ul>
						<li><a href="conso.php">Les statistiques de consommation</a></li>
						<li><a href="lasthitbookmarks.php">Les dernières utilisées</a></li>
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
	</main>
</body>
</html>
