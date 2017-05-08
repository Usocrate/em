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
header('Content-Type:application/opensearchdescription+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<OpenSearchDescription xmlns:moz="http://www.mozilla.org/2006/browser/search/" xmlns="http://a9.com/-/spec/opensearch/1.1/" >
<ShortName><?php echo ToolBox::xmlEntities($system->getProjectName()) ?></ShortName>
<Description><?php echo ToolBox::xmlEntities($system->getProjectDescription()) ?></Description>
<Image height="16" type="image/x-icon" width="16" ><?php echo $system->getSkinUrl(); ?>/favicon.ico</Image>
<Url method="get" template="<?php echo $system->getProjectUrl() ?>/search.php?bookmark_newsearch=1&bookmark_keywords={searchTerms}" type="text/html" />
</OpenSearchDescription>