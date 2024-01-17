<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

if (! $system->configFileExists ()) {
	header ( 'Location:' . $system->getConfigUrl () );
	exit ();
}

include_once './inc/boot.php';
session_start ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl () );
	exit ();
}

/**
 * identification de la rubrique.
 */
if (isset ( $_REQUEST ['from_topic_id'] )) {
	$topic = $system->getTopicById ( $_REQUEST ['from_topic_id'] );
} else {
	header ( 'Location:' . $system->getProjectUrl () );
	exit ();
}

if (isset ( $_REQUEST ['task_id'] )) {
	ToolBox::formatUserPost ( $_REQUEST ); // formatage des données fournies par l'utilisateur
	
	switch ($_REQUEST ['task_id']) {
		case 'shortcut_add' :
			if (isset ( $_POST ['to_topic_id'] )) {
				$topic->addRelationWith ( new Topic ( $_POST ['to_topic_id'] ) );
			}
			break;
		case 'shortcut_remove' :
			foreach ( $_POST ['to_topic_id'] as $id ) {
				// $topic->removeRelationWith(new Topic($id));
				$topic->removeShortCutTo ( new Topic ( $id ) );
			}
			break;
	}
}
$relatedtopics = $topic->getRelatedTopics ();
$doc_title = 'Gestion des raccourcis';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>	
</head>
<body>
	<main class="container-fluid">
		<header>
			<div class="topicPath"><?php echo $topic->getHtmlPath() ?></div>
			<h1><?php echo ToolBox::toHtml($doc_title).' <small>('.ToolBox::toHtml($topic->getTitle()).')</small>' ?></h1>
		</header>
		<div>
			<?php $maintopic = $system->getMainTopic(); ?>
			<h2>Nouveau raccourci</h2>	
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" class="form-inline">
				<input name="task_id" type="hidden" value="shortcut_add" /> <input type="hidden" name="from_topic_id" value="<?php echo $topic->getId() ?>" />
				<div class="mb-3">
					<label class="form-label" for="to_topic_i">De <strong><?php echo $topic->getHtmlLink() ?></strong> vers </label> 
					<select id="to_topic_i" name="to_topic_id" class="form-control">
						<option value="<?php echo $maintopic->getId() ?>">- hors rubrique -</option>
						<?php echo $maintopic->getDescendantsOptionsTags(NULL, array($topic->getId()));	?>
					</select>
				</div>
				<button type="submit" class="btn btn-primary">Ok</button>
			</form>
			<?php
			if ($relatedtopics instanceof TopicCollection && $relatedtopics->hasElement ()) {
				echo '<h2>Déjà enregistrés</h2>';
				echo '<form action="' . $_SERVER ['PHP_SELF'] . '" method="post">';
				echo '<input type="hidden" name="from_topic_id" value="' . $topic->getId () . '"/>';
				echo '<ol class="tl">';
				$i = $relatedtopics->getIterator ();
				while ( $i->current () ) {
					$class = $i->current ()->isPrivate () ? 'lockedtopic' : 'unlockedtopic';
					echo '<li class="' . $class . ' mb-2">';
					$id = 'sc' . $i->current ()->getId () . '_i';
					echo '<div class="form-check">';
					echo '<label class="form-check-label" for="' . $id . '">';
					echo '<input class="form-check-input" id="' . $id . '" name="to_topic_id[]" type="checkbox" value="' . $i->current ()->getId () . '" />' . $i->current ()->getHtmlLink ();
					echo '</div>';
					if ($i->current ()->countAncestors () > 1) {
						echo ' <small>(<span class="topicPath">' . $i->current ()->getHtmlPath () . '</span>)</small>';
					}
					echo '</label>';
					echo '</li>';
					$i->next ();
				}
				echo '</ol>';
				echo '<button name="task_id" type="submit" value="shortcut_remove" class="btn">supprimer</button>';
				echo '<a class="btn btn-link" href="'.$topic->getUrl().'">annuler</a>';
				echo '</form>';
			}
			?>
		</div>
	</main>
</body>
</html>