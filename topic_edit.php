<?php
function __autoload($class_name) {
	$path = './classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( './config/host.json' );

if (! $system->configFileExists ()) {
	header ( 'Location:' . $system->getConfigUrl () );
	exit ();
}

include_once './inc/boot.php';
session_start ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

/**
 * identification de la rubrique.
 */
if (isset ( $_REQUEST ['topic_id'] )) {
	$topic = $system->getTopicById ( $_REQUEST ['topic_id'] );
	if ($topic instanceof Topic) {
		$parent = $topic->getParent ();
	} else {
		$topic = new Topic ();
	}
} else {
	$topic = new Topic ();
}

$requestedParent = empty ( $_REQUEST ['parent_id'] ) ? $system->getMainTopic () : new Topic ( $_REQUEST ['parent_id'] );

if (isset ( $_POST ['task_id'] )) {
	ToolBox::formatUserPost ( $_POST );
	switch ($_POST ['task_id']) {
		case 'topic_save' :
			if (isset ( $_POST ['title'] )) {
				$topic->setTitle ( $_POST ['title'] );
			}
			if (isset ( $_POST ['description'] )) {
				$topic->setDescription ( $_POST ['description'] );
			}
			
			/**
			 * enregistrement de la rubrique parente
			 */
			if (isset ( $requestedParent )) {
				if (! $topic->getId ()) {
					// la rubrique à enregistrer est nouvelle (en cours de création)
					$topic->addTo ( $requestedParent );
				} elseif (! isset ( $parent ) || $requestedParent->getId () != $parent->getId ()) {
					$topic->transferTo ( $requestedParent );
				}
			}
			/**
			 * gestion de la confidentialité
			 */
			if (isset ( $_POST ['privacy'] ) && $_POST ['privacy'] != $topic->getPrivacy ()) {
				$topic->setPrivacy ( $_POST ['privacy'] );
				$topic->toDB ();
				/**
				 * répercussion de la nouvelle confidentialité à toutes les sous-rubriques
				 */
				if ($topic->isPrivate ()) {
					$topic->spreadPrivacyToDescendants ();
				}
			} else {
				$topic->toDB ();
			}
			
			/**
			 * traitement du tranfert des ressources sélectionnées parmi les ressources de la rubrique parente.
			 */
			if (isset ( $_POST ['bookmarksToTransfer_ids'] ) && is_array ( $_POST ['bookmarksToTransfer_ids'] )) {
				foreach ( $_POST ['bookmarksToTransfer_ids'] as $bookmarkToTransfer_id ) {
					$bookmarkToTransfer = new Bookmark ( $bookmarkToTransfer_id );
					$bookmarkToTransfer->setTopic ( $topic );
					$bookmarkToTransfer->updateTopicInDB ();
				}
			}
			
			/**
			 * traitement du tranfert des sous-rubriques sélectionnées
			 * parmi les sous-rubriques de la rubrique parente.
			 */
			if (isset ( $_POST ['topicsToTransfer_ids'] ) && is_array ( $_POST ['topicsToTransfer_ids'] )) {
				foreach ( $_POST ['topicsToTransfer_ids'] as $topicToTransfer_id ) {
					$topicToTransfer = new Topic ( $topicToTransfer_id );
					$topicToTransfer->transferTo ( $topic );
				}
			}
			header ( 'Location:' . $system->getTopicUrl ( $topic ) );
			exit ();
	}
}

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<title><?php echo 'Edition d&apos;une rubrique ('.$system->projectNameToHtml().')' ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
	<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="topicEdit">
	<div class="container-fluid">
		<header>
			<?php
			if ($topic->hasId ()) {
				echo '<div class="topicPath">' . $topic->getHtmlPath () . '</div>';
				echo '<h1>' . ToolBox::toHtml ( $topic->getTitle () ) . '</h1>';
			} else {
				echo '<h1>Nouvelle rubrique</h1>';
			}
			?>
		</header>
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
			<?php if ($topic->hasId()) echo '<input type="hidden" name="topic_id" value="'.$topic->getId().'" />'?>
			<div class="row">
				<div class="col-md-6">
					<fieldset>
						<legend>Metadonnées</legend>
						<div class="form-group">
							<label for="t_title_i">Intitulé</label><input id="t_title_i" size="35" name="title" value="<?php echo $topic->getTitle() ?>" class="form-control" />
						</div>
						<?php if (!$topic->isMainTopic()) : ?>
						<div class="form-group">
							<label for="t_parent_id_i">Sous rubrique de</label>
							<?php $maintopic = $system->getMainTopic(); ?>
							<select id="t_parent_id_i" name="parent_id" class="form-control">
								<option value="<?php echo $maintopic->getId() ?>">- hors rubrique -</option>
								<?php
							if ($topic->getId ()) {
								// la rubrique ne peut être déplacée dans elle même, ni dans l'une de ses sous-rubriques
								$topicstoexclude_ids = array (
										$topic->getId () 
								);
								$topicstoexclude_ids = array_merge ( $topicstoexclude_ids, $topic->getDescendantsIds () );
							} else {
								$topicstoexclude_ids = NULL;
							}
							if (isset ( $parent ) && $parent instanceof Topic && $parent->getId ()) {
								echo $maintopic->getDescendantsOptionsTags ( $parent->getId (), $topicstoexclude_ids );
							} elseif ($requestedParent instanceof Topic && $requestedParent->getId ()) {
								echo $maintopic->getDescendantsOptionsTags ( $requestedParent->getId (), $topicstoexclude_ids );
							} else {
								echo $maintopic->getDescendantsOptionsTags ( NULL, $topicstoexclude_ids );
							}
							?>
							</select>
						</div>
						<?php endif; ?>
						<div class="form-group">
							<label for="t_description_i">Description</label>
							<textarea id="t_description_i" name="description" cols="25" rows="11" class="form-control"><?php echo ToolBox::toHtml($topic->getDescription()) ?></textarea>
						</div>
				
						<?php if($topic->hasPrivateAncestor()): ?>
						<div>
							<p>
								La rubrique est confidentielle <small>(car elle même partie d'une rubrique confidentielle)</small>
							</p>
							<input type="hidden" name="privacy" value="1" />
						</div>
						<?php endif; ?>
						
						<?php if(!$topic->hasPrivateAncestor()): ?>
						
						<?php if ($topic->isPrivate()): ?>
						<div class="checkbox">
							<label><input type="radio" name="privacy" value="0" />Visible de tous</label>
						</div>
						<div class="checkbox">
							<label><input type="radio" name="privacy" value="1" checked="checked" />Confidentiel</label>
						</div>
						<?php endif; ?>
						
						<?php if (!$topic->isPrivate()): ?>
						<div class="checkbox">
							<label><input type="radio" name="privacy" value="0" checked="checked" />Visible de tous</label>
						</div>
						<div class="checkbox">
							<label><input type="radio" name="privacy" value="1" />Confidentiel</label>
						</div>
						<?php endif; ?>
						
						<?php endif; ?>
				</fieldset>
				</div>
			<?php
			/**
			 * on détermine la liste de ressources qu'on propose de transférer dans cette rubrique
			 */
			if (isset ( $parent ) && $parent instanceof Topic && $parent->getId ()) {
				$transferableBookmarks = $parent->getBookmarks ();
			} elseif ($requestedParent instanceof Topic && $requestedParent->getId ()) {
				$transferableBookmarks = $requestedParent->getBookmarks ();
			} else {
				$transferableBookmarks = $maintopic->getBookmarks ();
			}
			
			/**
			 * on détermine la liste des rubriques qu'on propose de transférer dans cette rubrique
			 */
			if ($topic->getId ()) {
				$transferableTopics = $topic->getSiblings ();
			} elseif ($requestedParent instanceof Topic && $requestedParent->getId ()) {
				$transferableTopics = $requestedParent->getChildren ();
			} else {
				$transferableTopics = $mainTopic->getChildren ();
			}
			?>
			<div class="col-md-6">
					<fieldset>
						<legend>Que transférer dans cette rubrique ?</legend>
					<?php
					$bCount = $transferableBookmarks instanceof BookmarkCollection ? $transferableBookmarks->getSize () : 0;
					$tCount = $transferableTopics instanceof TopicCollection ? $transferableTopics->getSize () : 0;
					
					if ($bCount + $tCount == 0) {
						echo '<p><small>Désolé rien de transférable</small></p>';
					} else {
						if ($bCount > 0) {
							$i = $transferableBookmarks->getIterator ();
							do {
								$b = $i->current ();
								$id = 'b' . $b->getId () . '_i';
								echo '<div class="checkbox">';
								echo '<label>';
								echo '<input id=' . $id . '" type="checkbox" name="bookmarksToTransfer_ids[]" value="' . $b->getId () . '" />';
								echo $b->hasDescription () ? '<span title="' . ToolBox::toHtml ( $b->getTitle () ) . ' : ' . ToolBox::toHtml ( $b->getDescription () ) . '">' : '<span>';
								echo ToolBox::toHtml ( $b->getTitle () ) . '</span>';
								echo $b->getHtmlLinkToInfo ();
								echo '</label>';
								echo '</div>';
							} while ( $i->next () );
						}
						if ($tCount > 0) {
							echo '<div class="tl">';
							$i = $transferableTopics->getIterator ();
							do {
								$sibling = $i->current ();
								if ($sibling->getId () != $topic->getId ()) {
									$id = 't' . $topic->getId () . '_i';
									echo '<div class="checkbox">';
									echo '<label>';
									echo '<input id=".$id." type="checkbox" name="topicsToTransfer_ids[]" value="' . $sibling->getId () . '" />';
									echo $sibling->getHtmlLink ();
									echo '</label>';
									echo '</div>';
								}
							} while ( $i->next () );
							echo '</div>';
						}
					}
					?>
				</fieldset>
				</div>
			</div>
			<button class="btn btn-primary" type="submit" name="task_id" value="topic_save">enregistrer</button>
			<a class="btn btn-link" href="<?php echo $system->getTopicUrl($topic); ?>">annuler</a>
		</form>
	</div>
	<script type="text/javascript">
	$(document).ready(function(){
	    $("#t_description_i").blur(function(){
			if ($(this).val().length>255) {
				alert('La description est trop longue ('+$(this).val().length+' caractères).\nLe nombre de caractères autorisé est limité à 255.');
				$(this).focus();
			}
	    });
	});
	</script>
</body>
</html>