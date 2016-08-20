<?php
/**
 * Permet le rappel des codes permettant l'accès à un éventuel compte utilisateur
 * associée à une ressource
 *
 * @since 20/05/2007
 */
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

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

$bookmark = new Bookmark ( $_REQUEST ['bookmark_id'] );
$bookmark->hydrate ();
$t = $bookmark->getTopic ();
if ($t instanceof Topic) {
	$t->hydrate ();
}
$doc_title = 'Rappel des codes d’accès';

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
		<h1><?php echo ToolBox::toHtml($bookmark->getTitle()).' <small>('.ToolBox::toHtml($doc_title).')</small>' ?></h1>
	</header>
	<div>
		<?php
		$description = 'Les codes d’accès associé à la ressource <a href="' . ToolBox::toHtml ( $bookmark->getUrl () ) . '" target="_blank" rel="nofollow">' . ToolBox::toHtml ( $bookmark->getTitle () ) . '</a>';
		$description .= $bookmark->getHtmlLinkToInfo ();
		if ($t->getHtmlLink ()) {
			$description .= $bookmark->getHtmlLinkToTopic ();
		}
		?>
		<div class="description"><?php echo $description ?></div>
		<div>
			<p>
				<em><?php echo $bookmark->getLogin() ? $bookmark->getLogin() : '<span title="Non communiqué">nc</span>' ?></em><br /> (identifiant)
			</p>
			<p>/</p>
			<p>
				<em><?php echo $bookmark->getPassword() ? $bookmark->getPassword() : '<span title="Non communiqué">nc</span>' ?></em><br /> (mot de passe)
			</p>
		</div>
	</div>
	<?php include './inc/footer.inc.php'; ?>
</body>
</html>