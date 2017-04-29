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

$system->lookForAuthenticatedUser ();

$searchHistory = $system->getBookmarkSearchHistory ();

/**
 * paramètres de pagination.
 */
$page_items_nb = 14;

if (isset ( $_REQUEST )) {
	ToolBox::formatUserPost ( $_REQUEST );
}

/**
 * initialisation de la recherche.
 */
if (isset ( $_REQUEST ['bookmark_newsearch'] ) || $searchHistory->getSize () == 0) {
	$currentSearch = new BookmarkSearch ();
	$currentSearch->setHitFrequencyAsSortCriteria ();
	
	/**
	 * Enregistrement d'un critère 'mot-clefs'.
	 */
	if (isset ( $_REQUEST ['bookmark_keywords'] )) {
		$currentSearch->setKeywords ( $_REQUEST ['bookmark_keywords'] );
	}
	/**
	 * Enregistrement d'un critère 'rubrique'.
	 */
	if (isset ( $_REQUEST ['topic_id'] )) {
		$currentSearch->setTopicId ( $_REQUEST ['topic_id'] );
	}
} else {
	$currentSearch = $searchHistory->getLastElement ();
}

if (isset ( $_REQUEST ['sift_task'] )) {
	$currentSearch->setPageIndex ( 1 );
	switch ($_REQUEST ['sift_task']) {
		case 'filtrer' :
			
			// mise en place d'un filtrage
			if (! empty ( $_REQUEST ['bookmark_publisher'] )) {
				$currentSearch->setPublisher ( $_REQUEST ['bookmark_publisher'] );
			}
			break;
		case 'annuler' :
			
			// retrait du filtre
			$currentSearch->setPublisher ( NULL );
			break;
	}
}

/**
 * construction des critères de recherche au format SQL
 * à partir des critères de recherche enregistrés en Session
 */
$base_criteria = array ();
$publishers_criteria = array ();

if ($currentSearch->hasKeyword ()) {
	$base_criteria ['bookmark_keywords'] = $currentSearch->getKeywords ();
	
	$publishers_criteria ['bookmarkClues'] = array ();
	foreach ( $currentSearch->getKeywords () as $k ) {
		$publishers_criteria ['bookmarkClues'] [] = $k;
	}
	
	$highlighter = new Highlighter ( $currentSearch->getKeywords () );
}
if ($currentSearch->getTopicId () !== NULL) {
	$topic = new Topic ( $currentSearch->getTopicId () );
	$topic->hydrate ();
	$criteria ['topic_interval_lowerlimit'] = $topic->getIntervalLowerLimit ();
	$criteria ['topic_interval_higherlimit'] = $topic->getIntervalHigherLimit ();
	$publishers_criteria ['topic'] = $topic;
}

/**
 * déclaration d'un tableau de critères complémentaires (filtrage) au format SQL.
 */
$sift_criteria = array ();

if ($currentSearch->getPublisher () !== NULL) {
	$sift_criteria ['bookmark_publisher'] = $currentSearch->getPublisher ();
	$publishers_criteria ['nameClue'] = $currentSearch->getPublisher ();
}

$criteria = array_merge ( $base_criteria, $sift_criteria );
$bookmarks_nb = $system->countBookmarks ( $criteria );
$pages_nb = ceil ( $bookmarks_nb / $page_items_nb );

// changement de page
if (isset ( $_REQUEST ['bookmark_search_page_index'] )) {
	$currentSearch->setPageIndex ( $_REQUEST ['bookmark_search_page_index'] );
}

// sélection de bookmarks correspondant aux critères (dont le nombre dépend de la variable $page_items_nb)
$page_debut = ($currentSearch->getPageIndex () - 1) * $page_items_nb;
$statement = $system->getBookmarkCollectionStatement ( $criteria, $currentSearch->getSortKey (), $currentSearch->getSortOrder (), $page_debut, $page_items_nb );
$bookmarks = new BookmarkCollection ( $statement );

/**
 * Enregistrement de la dernière recherche
 */
if (isset ( $_REQUEST ['bookmark_newsearch'] ) || $searchHistory->getSize () == 0) {
	if ($currentSearch->hasKeyword ()) {
		$currentSearch->setBookmarksNb ( $bookmarks_nb );
		$searchHistory->addElement ( $currentSearch );
		$searchHistory->save ();
	}
}

/*
 * Titre du document
 */
$doc_title = $bookmarks_nb > 1 ? $bookmarks_nb . ' ressources' : $bookmarks_nb . ' ressource';
if ($currentSearch->hasKeyword ()) {
	$keywords = $currentSearch->getKeywords ();
	$doc_title = '"' . implode ( ', ', $keywords ) . '" : ' . $doc_title;
}

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
<body id="search" class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1>
		<?php
		echo ToolBox::toHtml ( $doc_title );
		if (isset ( $topic )) {
			echo ' <small>(' . $topic->getHtmlTitle () . ')</small>';
		}
		?>
        </h1>
	</header>
	<?php if ($bookmarks->getSize() > 0): ?>
	<div>
		<div class="row">
			<div class="col-md-8">
				<ol class="bl">
				<?php
				$i = $bookmarks->getIterator ();
				do {
					$b = $i->current ();
					$cssClasses = array ('card');
					$cssClasses [] = $b->isPrivate () ? 'lockedBookmark' : 'unlockedBookmark';
					if ($b->isInactive ()) {
						$cssClasses [] = 'inactive';
					}
					echo '<li class="' . implode ( ' ', $cssClasses ) . '">';
					echo $b->getHtmlSnapshotLink ();
					echo '<div class="text">';
					echo isset ( $highlighter ) ? $highlighter->getString ( $b->getHtmlLink () ) : $b->getHtmlLink ();
					if ($system->isUserAuthenticated ()) {
						echo ' ';
						echo $b->getHtmlLinkToInfo ();
					}
					$dataToDisplay = array ();
					if ($b->getCreator ())
						$dataToDisplay [] = ToolBox::toHtml ( $b->getCreator () );
					if ($b->isPublisherKnown ()) {
						$dataToDisplay [] = $b->getHtmlLinkToPublisher ();
					}
					if (count ( $dataToDisplay )) {
						echo '<div class="baseline">' . implode ( ' - ', $dataToDisplay ) . '</div>';
					}
					$t = $b->getTopic ();
					if ($t instanceof Topic && (! isset ( $topic ) || $t->getId () != $topic->getId ())) {
						if ($t->getHtmlLink ()) {
							echo '<div class="topic">' . $t->getHtmlLink () . '</div>';
						}
					}
					echo isset ( $highlighter ) ? $highlighter->getString ( $b->getHtmlDescription () ) : $b->getHtmlDescription ();
					echo '</div>';
					echo '</li>';
				} while ( $i->next () );
				?>
				</ol>
			</div>
			<div class="col-md-4">
				<h2>Par éditeur</h2>
				<?php
				$publishers_criteria ['rowCount'] = 50;
				
				$matchingPublishers = $system->getPublishers ( $publishers_criteria );
				if (count ( $matchingPublishers ) > 0) {
					echo '<ol>';
					foreach ( $matchingPublishers as $m ) {
						echo '<li>';
						echo '<a href="' . $_SERVER ['PHP_SELF'] . '?sift_task=filtrer&amp;bookmark_publisher=' . urldecode ( $m->getName () ) . '">' . $m->getHtmlName () . '</a>';
						echo ' <small>(' . $m->countBookmarks () . ')</small>';
						if ($currentSearch->getPublisher () !== NULL && strcmp ( $currentSearch->getPublisher (), $m->getName () ) == 0) {
							echo ' <a href="' . $_SERVER ['PHP_SELF'] . '?sift_task=annuler">X</a>';
						}
						echo '</li>';
					}
					echo '</ol>';
				} else {
					echo '<p><small>Aucun éditeur représenté</small></p>';
				}
				?>
			</div>
		</div>
	</div>
	<nav>
		<?php
		if ($pages_nb > 1) {
			$params = array ();
			echo ToolBox::getHtmlPagesNav ( $currentSearch->getPageIndex (), $pages_nb, $params, 'bookmark_search_page_index' );
		}
		?>
	</nav>
	<?php endif; ?>
	<?php if(count ( $bookmarks ) == 0): ?>
	<div>
		<p>Pas de résultat ...</p>
	</div>
	<?php endif; ?>
	<?php include './inc/footer.inc.php'; ?>
</body>
</html>