<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

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
$doc_title = 'Suppression de la rubrique ' . $topic->getTitle();

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<?php echo $system->writeHeadCommonLinkTags(); ?>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body>
	<main>
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<div>
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
				<?php if ($topic->getId()) echo '<input type="hidden" name="topic_id" value="'.$topic->getId().'" />'?>
				<p>Que souhaites-tu faire du contenu de la rubrique (ressources et sous-rubriques) ?</p>
				<div class="checkbox">
					<label><input name="content_deletion" type="radio" value="0" checked="checked" /> le conserver <strong>(recommandé)</strong></label>
				</div>
				<div class="checkbox">
					<label><input name="content_deletion" type="radio" value="1" /> supprimer la rubrique <em>ET</em> son contenu <strong>(dangereux)</strong></label>
				</div>
				<div class="buttonBar">
					<a class="btn btn-link" href="<?php echo $topic->getUrl() ?>">abandonner</a>
					<input class="btn btn-primary" name="topic_task" type="submit" value="supprimer" />
				</div>
			</form>
		</div>
	</main>
</body>
</html>