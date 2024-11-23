<?php
/**
 * Permet le rappel des codes permettant l'accès à un éventuel compte utilisateur
 * associée à une ressource
 *
 * @since 05/2007
 */
require_once './classes/System.class.php';
$system = new System('./config/host.json');

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
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
</head>
<body>
	<?php include './inc/menu.inc.php'; ?>
	<main>
		<header>
			<h1><?php echo ToolBox::toHtml($bookmark->getTitle()).' <small>('.ToolBox::toHtml($doc_title).')</small>' ?></h1>
		</header>
		<?php
		$description = 'Les codes d’accès associé à la ressource <a href="' . ToolBox::toHtml ( $bookmark->getUrl () ) . '" target="_blank" rel="nofollow">' . ToolBox::toHtml ( $bookmark->getTitle () ) . '</a>';
		$description.= ' '.$bookmark->getHtmlLinkToInfo ();
		if ($t->getHtmlLink ()) {
			$description .= ' '.@$bookmark->getHtmlLinkToTopic ();
		}
		?>
		<div class="description"><?php echo $description ?></div>
		<div>
			<p><em><?php echo $bookmark->getLogin() ? $bookmark->getLogin() : '<span title="Non communiqué">nc</span>' ?></em><br /> (identifiant)</p>
			<p>/</p>
			<p><em><?php echo $bookmark->getPassword() ? $bookmark->getPassword() : '<span title="Non communiqué">nc</span>' ?></em><br /> (mot de passe)</p>
		</div>
	</main>
</body>
</html>