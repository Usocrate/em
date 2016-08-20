<?php
function __autoload($class_name) {
	$path = './classes/';
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

$system->lookForAuthenticatedUser ();

// le titre du document
$doc_title = 'Les ressources oubliées';

// paramètres de publication
$count = isset ( $_REQUEST ['nb'] ) ? $_REQUEST ['nb'] : 20;

$bookmarks = $system->getForgottenBookmarkCollection ( $count );

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title ?></title>
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
</head>
<body class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
	</header>
	<div>
		<div class="description">En mettant en avant quelques ressources dont les dates de dernière consultation, de dernière modification et d&#39;enregistrement sont anciennes, cet écran permet de vérifier périodiquement la pérénité du catalogue.</div>
		<div><?php
		if ($bookmarks->getSize () > 0) {
			echo '<ul>';
			$i = $bookmarks->getIterator ();
			do {
				$b = $i->current ();
				$class = $b->isPrivate () ? 'lockedBookmark' : 'unlockedBookmark';
				echo '<li class="' . $class . '">';
				echo $b->getHtmlLink ();
				echo ' ';
				
				// lien vers la rubrique
				echo '<div>' . $b->getHtmlLinkToTopic () . '</div>';
				if ($b->isLastHitDateKnown ()) {
					echo '<small>Dernière consultation :</small> ';
					echo $b->getLastHitDateFr () ? $b->getLastHitDateFr () : '?';
					echo '<br />';
				}
				echo '<small>Dernière édition :</small> ';
				echo $b->getLastEditDateFr () ? $b->getLastEditDateFr () : '?';
				echo '<br />';
				echo $b->getHtmlDescription ();
				echo '</li>';
			} while ( $i->next () );
			echo '</ul>';
		} else {
			echo '<p>Aucune ressource !</p>';
		}
		?>
		</div>
	</div>
	<?php include './inc/footer.inc.php'; ?>
</body>
</html>
