<?php

function __autoload($class_name)
{
    $path = './classes/';
    if (is_file($path . $class_name . '.class.php')) {
        include_once $path . $class_name . '.class.php';
    } elseif ($path . $class_name . '.interface.php') {
        include_once $path . $class_name . '.interface.php';
    }
}

$system = new System('../config/host.json');

if (! $system->configFileExists()) {
    header('Location:' . $system->getConfigUrl());
    exit();
}

include_once './inc/boot.php';
session_start();

$system->lookForAuthenticatedUser();

if (isset($_REQUEST['b_sort_key'])) {
    $_SESSION['b_sort_key'] = $_REQUEST['b_sort_key'];
}

if (! isset($_SESSION['b_sort_key'])) {
    $_SESSION['b_sort_key'] = 'hit_frequency';
}

if (! empty($_REQUEST['topic_id'])) {
    $topic = $system->getTopicById($_REQUEST['topic_id']);
    if (! $topic instanceof Topic || ($topic->isPrivate() && ! $system->isUserAuthenticated())) {
        header('Location:' . $system->getProjectUrl());
        exit();
    } else {
        $subtopics = $topic->getChildren();
        $relatedtopics = $topic->getRelatedTopics();
        switch ($_SESSION['b_sort_key']) {
            case 'lasthit_date':
                $bookmarks = $topic->getBookmarksSortByLastHitDate();
                break;
            case 'creation_date':
                $bookmarks = $topic->getBookmarksSortByCreationDate();
                break;
            default:
                $bookmarks = $topic->getBookmarks();
        }
        $bookmarkCreationStats = $topic->countDependentBookmarkCreationYearly();
    }
    $system->setLastInvolvedTopic($topic);
} else {
    header('Location:' . $system->getProjectUrl());
    exit();
}

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<?php $meta_description_content =  $topic->getDescription() ? $topic->getDescription() : $topic->countDependentBookmarks().' ressources web.'?>
	<meta name="description" content="<?php echo ToolBox::toHtml($meta_description_content) ?>" />
<meta name="author" content="<?php echo $system->projectCreatorToHtml() ?>" />
<title><?php echo ToolBox::toHtml($topic->getTitle(). ' ('.$system->getProjectName().')') ?></title>
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo FONT_AWESOME_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
<link rel="icon" type="image/x-icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico" />
<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
<link rel="alternate" type="application/rss+xml" title="<?php echo $system->getProjectName() ?> &gt; Canal RSS (les nouveautés de la rubrique)" href="topic_lastaddedbookmarks.rss.php?topic_id=<?php echo $topic->getId(); ?>" />
<script type="text/javascript" src="<?php echo YUI3_SEEDFILE_URI ?>"></script>
<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php include_once './inc/ga_tracker.inc.php'?>
</head>
<body id="topic" class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo $topic->countAncestors() > 1 ? $topic->getHtmlTitle().' <small class="topicPath">('.$topic->getHtmlPath().')</small>' : $topic->getHtmlTitle(); ?></h1>
	</header>
		<?php
if ($subtopics->getSize() > 0) {
    echo '<section id="subtopics">';
    echo '<ol  class="tl">';
    $subtopics->setTopicsWeight();
    $i = $subtopics->getIterator();
    while ($i->current()) {
        echo '<li>' . $i->current()->getHtmlLink() . '</li>';
        $i->next();
    }
    if ($system->isUserAuthenticated()) {
        echo '<li class="virtual"><a href="' . $system->getProjectUrl() . '/topic_edit.php?parent_id=' . $topic->getId() . '">+</a></li>';
    }
    echo '</ol>';
    echo '</section>';
}
?>
	<section>
		<?php
if ($bookmarks->getSize() > 0) {
    $levelBreakDown = array(
        'n1' => 3,
        'n2' => 4,
        'n3' => ($bookmarks->getSize() + 1 - 7)
    );
    $levels = array_keys($levelBreakDown);
    
    $i = $bookmarks->getIterator();
    
    echo '<ol class="bl">';
    for ($l = 0; $l < count($levels); $l ++) {
        $processItems = 0;
        
        while ($i->current() && $processItems < $levelBreakDown[$levels[$l]]) {
            $b = $i->current();
            $cssClasses = array();
            $cssClasses[] = $levels[$l];
            $cssClasses[] = $b->isPrivate() ? 'lockedBookmark' : 'unlockedBookmark';
            if ($b->isInactive()) {
                $cssClasses[] = 'inactive';
            }
            echo '<li class="' . implode(' ', $cssClasses) . '">';
            echo strcmp($levels[$l], 'n1') == 0 ? $b->getHtmlSnapshotLink() : $b->getHtmlSnapshotLink('bonus');
            echo '<div class="text">';
            echo $b->getHtmlLink();
            if ($system->isUserAuthenticated()) {
                echo ' ' . $b->getHtmlLinkToInfo();
            }
            if ($b->isPublisherKnown()) {
                echo '<div class="baseline">' . $b->getHtmlLinkToPublisher() . '</div>';
            }
            echo $b->getHtmlDescription();
            
            switch ($_SESSION['b_sort_key']) {
                case 'hit_frequency':
                    if ($b->getHitFrequency() > 0) {
                        echo '<div><span>Taux de consultation : </span><em>' . Round($b->getHitFrequency() * 100, 2) . '</em>%</div>';
                    }
                    break;
                case 'lasthit_date':
                    if ($b->getLastHitDateFr()) {
                        echo '<div><span>Consulté le </span><em>' . $b->getLastHitDateFr() . '</strong></em>';
                    }
                    break;
                case 'creation_date':
                    echo '<div><span>Découvert en </span><em>' . $b->getHtmlLinkToCreationYear() . '</em></div>';
            }
            
            echo '</div>';
            echo '</li>';
            $processItems ++;
            $i->next();
        }
    }
    if ($system->isUserAuthenticated()) {
        echo '<li class="virtual"><a href="' . $system->getProjectUrl() . '/bookmark_edit.php?topic_id=' . $topic->getId() . '">+</a></li>';
    }
    echo '</ol>';
    ?>
		<?php
    $sortBarItems = array();
    $sortBarItems[] = array(
        'creation_date',
        'Date de découverte'
    );
    $sortBarItems[] = array(
        'hit_frequency',
        'Fréquence de consultation'
    );
    $sortBarItems[] = array(
        'lasthit_date',
        'Date de dernière consultation'
    );
    echo '<div id="sortBar"><span>Tri par ...</span>';
    echo '<ul>';
    foreach ($sortBarItems as $i) {
        if (strcasecmp($i[0], $_SESSION['b_sort_key']) == 0) {
            echo '<li class="emphased">' . ToolBox::toHtml($i[1]) . '</li>';
        } else {
            echo '<li><a href="' . $system->getTopicUrl($topic) . '&amp;b_sort_key=' . $i[0] . '">' . ToolBox::toHtml($i[1]) . '</a></li>';
        }
    }
    echo '</ul>';
    echo '</div>';
} else {
    echo '<p>Aucune ressource enregistrée.';
    if ($system->isUserAuthenticated()) {
        echo '<br/><a href="' . Bookmark::getEditionUrl(array(
            'topic_id' => $topic->getId()
        )) . '"><small>Nouvelle ressource</small></a>';
    }
    echo '</p>';
}
?>
		<?php if ($system->isUserAuthenticated ()) : ?>
		<div class="btn-group">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				Action <span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a href="<?php echo $system->getProjectUrl().'/bookmark_edit.php?topic_id=' . $topic->getId ()  ?>">Nouvelle ressource</a></li>
				<li><a href="<?php echo $system->getProjectUrl().'/topic_edit.php?topic_id=' . $topic->getId ()  ?>">Modification</a></li>
				<li><a href="<?php echo $system->getProjectUrl().'/topic_edit.php?parent_id=' . $topic->getId ()  ?>">Nouvelle sous-rubrique</a></li>
				<li><a href="<?php echo $system->getProjectUrl().'/shortcut_edit.php?from_topic_id=' . $topic->getId () ?>">Raccourcis</a></li>
				<?php
    if (! $topic->isMainTopic()) {
        echo '<li><a href="' . $system->getProjectUrl() . '/topic_remove.php?topic_id=' . $topic->getId() . '">Suppression</a></li>';
    }
    ?>
				<li><a href="<?php echo $system->getProjectUrl() ?>/netscape-bookmark-file-1.php?topic_id='<?php echo $topic->getId() ?>'" target="_blank"">Exportation</a></li>
			</ul>
		</div>
		<?php endif; ?>
		</section>
		<?php
if ($relatedtopics instanceof TopicCollection && $relatedtopics->hasElement()) {
    echo '<section>';
    echo '<h2>Voir aussi</h2>';
    echo '<ul>';
    $i = $relatedtopics->getIterator();
    while ($i->current()) {
        echo '<li>';
        echo $i->current()->getHtmlLink();
        if ($i->current()->countAncestors() > 1) {
            echo ' <small>(<span class="topicPath">' . $i->current()->getHtmlPath() . '</span>)</small>';
        }
        echo '</li>';
        $i->next();
    }
    echo '</ul>';
    echo '</section>';
}
if (isset($bookmarkCreationStats) && is_array($bookmarkCreationStats)) {
    echo '<section class="bonus">';
    echo '<h2>Découvertes</h2>';
    echo '<div id="chart_container" class="chart_container"></div>';
    // echo '<div class="chart_legend">Ci-dessus le nombre de découvertes sur ce thème <strong>' . ToolBox::toHtml ( $topic->getTitle () ) . '</strong>, par année.</div>';
    echo '</section>';
}
?>
	<?php include './inc/footer.inc.php'; ?>
	<script>
		YUI().use("charts", "event", function (Y) {
			var chart_data =
			[
			<?php
$pieces = array();
foreach ($bookmarkCreationStats as $year => $count) {
    $pieces[] = '{ year: "' . $year . '", count: ' . $count . ' }';
}
echo implode(',', $pieces);
?> 
			];
		
		    var chart_series =
		    [
		      {xKey:"year", xDisplayName:"Année", yKey:"count", yDisplayName:"Nombre de découvertes", styles:{fill:{color:"#819fbb"}}}
		    ];
		
			var chart1 = new Y.Chart({
				dataProvider:chart_data,
				categoryKey:"year",
				type:"column",
				seriesCollection:chart_series,
				render:"#chart_container",
				xAxis:{type:"numeric",keys:["count"],roundingMethod:"auto"}
			});
		});
	</script>
</body>
</html>