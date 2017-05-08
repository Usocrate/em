<?php

function __autoload($class_name)
{
    $path = '../classes/';
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

$topic = new Topic($_REQUEST['topic_id']);
$topic->hydrate();
$parent = $topic->getParent();

if (! $system->isUserAuthenticated()) {
    header('Location:' . $system->getTopicUrl($topic));
    exit();
}

if (isset($_POST['topic_task'])) {
    switch ($_POST['topic_task']) {
        case 'supprimer':
            $redirection_script = $parent instanceof Topic && $parent->getId() ? 'topic.php?topic_id=' . $parent->getId() : 'index.php';
            if ($_POST['content_deletion']) {
                $topic->delete();
            } else {
                if ($topic->sendContentTo($parent)) {
                    $topic->delete();
                }
            }
            header('Location:' . $system->getProjectUrl() . '/' . $redirection_script);
            exit();
    }
}
$doc_title = 'Supprimer la rubrique ' . $topic->getTitle();

header('charset=utf-8');
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
		<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
	</header>
	<div>
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" class="block">
		<?php if ($topic->getId()) echo '<input type="hidden" name="topic_id" value="'.$topic->getId().'" />'?>
		<p>Que souhaites-tu faire du contenu de la rubrique (ressources et sous-rubriques) ?</p>
			<div class="checkbox">
				<label><input name="content_deletion" type="radio" value="0" checked="checked" /> le conserver <strong>(recommandé)</strong></label>
			</div>
			<div class="checkbox">
				<label><input name="content_deletion" type="radio" value="1" /> supprimer la rubrique <em>ET</em> son contenu <strong>(dangereux)</strong></label>
			</div>
			<input name="topic_task" type="submit" value="supprimer" class="btn btn-primary" /> <small><a href="<?php echo $topic->getUrl() ?>">Annuler</a></small>
		</form>
	</div>
</body>
</html>