<?php
function __autoload($class_name) {
	$path = '../../classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../../config/host.json' );

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once '../inc/boot.php';

header ( 'Content-Type:application/xslt+xml; charset=utf-8' );
// header('Content-Disposition: inline; filename="'.ToolBox::formatForFileName($system->getProjectName()).'_opml.xsl"');

echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="/opml">
		<!doctype html>
		<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><xsl:value-of select="head/title" /></title>
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/special.css" type="text/css" />
</head>
<body>
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1>
			Liste des flux RSS
			<!-- <xsl:value-of select="head/title"/>-->
			<br />
			<small><xsl:value-of select="head/dateCreated" /></small>
		</h1>
	</header>
	<div>
		<div class="description">Enregistre le fichier OPML en faisant un clic droit sur l'écran puis enregistrer sous ...</div>
		<ul>
			<xsl:apply-templates select="body/outline" />
		</ul>
	</div>
</body>
		</html>
	</xsl:template>
	<xsl:template match="outline[@type='rss']" xmlns="http://www.w3.org/1999/xhtml">
		<li><a href="{@xmlUrl}" class="rssLink"><xsl:value-of select="@text" /></a></li>
	</xsl:template>
</xsl:stylesheet>