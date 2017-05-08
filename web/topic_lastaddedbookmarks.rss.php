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

if (! $system->configFileExists()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';

session_start();
$system->lookForAuthenticatedUser();

if (isset($_REQUEST['topic_id'])) {
	$topic = new Topic($_REQUEST['topic_id']);
	$topic->hydrate();
} else {
	$topic = $system->getMainTopic();
}

$nb = 15;
$bookmarks = $topic->getLastAddedDependentBookmarks($nb);

header('Content-type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8" ?>';
echo '<?xml-stylesheet href="'.ToolBox::xmlEntities($system->getSkinUrl()).'rss.css" type="text/css" ?>';
?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/">
	<channel rdf:about="<?php echo $_SERVER['QUERY_STRING'] ?>">
	<title><![CDATA[<?php echo $system->getProjectName().' ('.$topic->getTitle().')' ?>]]></title>
	<link>
	<![CDATA[<?php echo $system->getTopicUrl($topic) ?>]]>
	</link>
	<description>
	<![CDATA[<?php echo 'Les '.$nb.' dernières ressources ajoutées à la rubrique '.$topic->getTitle().' au format RSS' ?>]]>
	</description>
	<dc:language>fr</dc:language>
	<dc:publisher>
		<![CDATA[<?php echo $system->getProjectPublisher() ?>]]>
	</dc:publisher>
	<dc:creator>
		<![CDATA[<?php echo $system->getProjectCreator() ?>]]>
	</dc:creator>
	<dc:subject>Bookmarks</dc:subject>
	<sy:updatePeriod>weekly</sy:updatePeriod>
	<sy:updateFrequency>2</sy:updateFrequency>
	<sy:updateBase>2005-01-01T07:00+00:00</sy:updateBase>

	<items>
	<rdf:Seq>
	<?php
	if ($bookmarks->getSize()>0) {
		$i = $bookmarks->getIterator();
		do {
			if ($b = $i->current()) {
				echo '<rdf:li resource="'.ToolBox::xmlEntities($b->getUrl()).'" />';		
			}
		} while ($i->next());
	}
	?>
	</rdf:Seq>
	</items>
	</channel>

	<?php
	if ($bookmarks->getSize()>0) {
		$i->rewind();
		do {
			if ($b = $i->current()) {
				echo '<item rdf:about="'.ToolBox::xmlEntities($b->getUrl()).'">';
				echo '<title><![CDATA['.$b->getTitle().']]></title>';
				echo '<link><![CDATA['.$b->getUrl().']]></link>';
				echo '<description><![CDATA['.$b->getDescription().']]></description>';
				if ($b->getLanguage()) echo '<dc:language>'.$b->getLanguage().'</dc:language>';
				if ($b->isPublisherKnown()) echo '<dc:publisher><![CDATA['.$b->getPublisher().']]></dc:publisher>';
				if ($b->getCreator()) echo '<dc:creator><![CDATA['.$b->getCreator().']]></dc:creator>';
				echo '</item>';
			}
		} while ($i->next());
	}	
	?>
</rdf:RDF>
