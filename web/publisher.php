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

if (isset ( $_REQUEST ['b_sort_key'] )) {
	$_SESSION ['b_sort_key'] = $_REQUEST ['b_sort_key'];
}

if (! isset ( $_SESSION ['b_sort_key'] )) {
	$_SESSION ['b_sort_key'] = 'hit_frequency';
}

if (isset ( $_REQUEST ['publisher_name'] )) {
	$publisher = new Publisher ( $_REQUEST ['publisher_name'] );
	switch ($_SESSION ['b_sort_key']) {
		case 'last_hit_date' :
			$bookmarks = $publisher->getBookmarkCollectionSortedByLastHitDate ();
			break;
		case 'creation_date' :
			$bookmarks = $publisher->getBookmarkCollectionSortedByCreationDate ();
			break;
		default :
			$bookmarks = $publisher->getBookmarkCollectionSortedByHitFrequency ();
			break;
	}
}

$doc_title = $publisher->getName ();

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><?php echo ToolBox::toHtml($doc_title).' ('.$system->projectNameToHtml().')' ?></title>
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" /><link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_THEME_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
<link rel="icon" type="image/x-icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico" />
<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="publisher" class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
	</header>
	<div>
		<div>
			<?php
			if ($bookmarks->getSize () > 0) {
				$i = $bookmarks->getIterator ();
				echo '<ul class="bl">';
				do {
					$cssClasses = array ();
					$cssClasses [] = $i->current ()->isPrivate () ? 'lockedBookmark' : 'unlockedBookmark';
					
					if ($i->current ()->isInactive ()) {
						$cssClasses [] = 'inactive';
					}
					
					$html = '<li class="' . implode ( ' ', $cssClasses ) . '">';
					$html .= $i->current ()->getHtmlSnapshotLink();
					$html .= '<div class="text">';
					$html .= $i->current ()->getHtmlLink ();
					$html .= ' ' . $i->current ()->getHtmlLinkToInfo ();
					$dataToDisplay = array ();
					if ($i->current ()->getCreator ()) {
						$dataToDisplay [] = ToolBox::toHtml ( $i->current ()->getCreator () );
					}
					if ($i->current ()->getCreationYear ()) {
						$dataToDisplay [] = Year::getHtmlLinkToYearDoc ( $i->current ()->getCreationYear () );
					}
					if (count ( $dataToDisplay )) {
						$html .= '<div class="baseline">' . implode ( ' - ', $dataToDisplay ) . '</div>';
					}
					if ($i->current ()->getTopic ()) {
						$html .= '<p>' . $i->current ()->getHtmlLinkToTopic () . '</p>';
					}
					$html .= $i->current ()->getHtmlDescription ();
					$html .= '</div>';
					$html .= '</li>';
					echo $html;
				} while ( $i->next () );
				echo '</ul>';
			}
			?>
		</div>
		<div id="sortBar">
			<?php
			$sortBarItems = array ();
			$sortBarItems [] = array (
					'creation_date',
					'Par date de découverte' 
			);
			$sortBarItems [] = array (
					'hit_frequency',
					'Fréquence de consultation' 
			);
			$sortBarItems [] = array (
					'lasthit_date',
					'Date de dernière consultation' 
			);
			echo '<ul>';
			foreach ( $sortBarItems as $i ) {
				if (strcasecmp ( $i [0], $_SESSION ['b_sort_key'] ) == 0) {
					echo '<li>' . ToolBox::toHtml ( $i [1] ) . '</li>';
				} else {
					echo '<li><a href="'.$system->getProjectUrl().'/publisher.php?publisher_name=' . urlencode ( $publisher->getName () ) . '&amp;b_sort_key=' . $i [0] . '">' . ToolBox::toHtml ( $i [1] ) . '</a></li>';
				}
			}
			echo '</ul>';
			?>
			</div>
	</div>
	<?php include './inc/footer.inc.php'; ?>
</body>
</html>
