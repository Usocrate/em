<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start ();

$system->lookForAuthenticatedUser();

if (! $system->isUserAuthenticated () && ! $system->isTourRequested()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

$nbToDisplay = isset ( $_REQUEST ['nb'] ) ? $_REQUEST ['nb'] : 20;
$periodToCheck = isset ( $_REQUEST ['period'] ) ? $_REQUEST ['period'] : ACTIVITY_THRESHOLD1;
$date = date ( "Y-m-d", strtotime ( '-' . $periodToCheck . ' day' ) );

$publishers = $system->getTopPublishers ( $nbToDisplay );

$doc_title = 'Les éditeurs les plus représentés (TOP ' . $nbToDisplay . ')';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>	
</head>
<body>
	<?php include './inc/menu.inc.php'; ?>
	<main>
		<header><h1><?php echo ToolBox::toHtml($doc_title); ?></h1></header>

		<?php
		echo '<ol>';
		foreach ( $publishers as $p ) {
			echo '<li>';
			echo $p->getHtmlName ();
			echo ' <strong>';
			echo $p->countBookmarks () > 0 ? $p->getHtmlLinkTo ( $p->countBookmarks () ) : $p->countBookmarks ();
			echo ' </strong>';
			echo '</li>';
		}
		echo '</ol>';
		?>

	</main>
</body>
</html>