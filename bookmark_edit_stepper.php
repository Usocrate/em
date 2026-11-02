<?php
/**
 * Alternative à bookmark_edit.php.
 *
 * Même traitement, même résultat, mais la saisie est découpée en trois étapes
 * (les trois sections du formulaire d'origine) accompagnées d'un stepper.
 *
 *   1. Quelle ressource ?
 *   2. Dans quelle rubrique ?
 *   3. Confidentialité de la ressource ? (+ codes d'accès)
 */
require_once './classes/System.class.php';
$system = new System ( './config/host.json' );

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

$maintopic = $system->getMainTopic ();

/**
 * ressource identifiée comme existante
 */
if (! empty ( $_REQUEST ['bookmark_id'] )) {
	$b = $system->getBookmarkById ( $_REQUEST ['bookmark_id'] );
	if (! ($b instanceof Bookmark)) {
		header ( 'Location:' . $system->getProjectUrl () );
		exit ();
	}
	$bookmarkBeforeProcessing = clone $b;
} /**
 * nouvelle ressource
 */
else {
	$b = new Bookmark ();
	/**
	 * l'url de la ressource est passée comme paramètre GET
	 */
	if (isset ( $_GET ['bookmark_url'] )) {
		$data = $_GET ['bookmark_url'];
		$data = strip_tags ( $data );
		$b->setUrl ( $data );
	}
	/**
	 * analyse du fichier à distance, pour l'instant uniquement pour une nouvelle ressource
	 */
	if ($b->getUrl ()) {
		$b->hydrateFromUrl ();
	}
}
// dans le cas d'ajout de ressource, on tente de déterminer la rubrique de destination
if (! $b->hasId ()) {
	if (isset ( $_REQUEST ['topic_id'] )) {
		// lorsque un identifiant de rubrique est transmis, celle-ci sera présélectionnée comme destination du signet à créer
		$requestedTopic = $system->getTopicById ( $_REQUEST ['topic_id'] );
	} else {
		// on propose une destination en fonction de l'historique de navigation
		$suggestedTopic = $system->getLastInvolvedTopic ();
	}
}
if (isset ( $_POST ['task_id'] )) {
	ToolBox::formatUserPost ( $_POST );
	switch ($_POST ['task_id']) {
		case 'b_save' :
			$b->hydrate ( $_POST, 'bookmark_' );
			switch ($_POST ['topic_type']) {
				case 'new' :
					if ($_POST ['newtopic_title']) {
						$t = new Topic ();
						$t->setTitle ( $_POST ['newtopic_title'] );
						$t->setDescription ( $_POST ['newtopic_description'] );
						$t->setPrivacy ( $_POST ['newtopic_privacy'] );
						if (empty ( $_POST ['newtopic_parent_id'] )) {
							$t->addTo ( $maintopic );
						} else {
							$t->addTo ( new Topic ( $_POST ['newtopic_parent_id'] ) );
						}
						$b->setTopic ( $t );
					}
					break;
				case 'existing' :
					$b->setTopic ( new Topic ( $_POST ['topic_id'] ) );
					break;
				case 'sameAsBookmark' :
					$sibling = $system->getBookmarkByTitle ( $_POST ['siblingBookmarkTitle'] );
					if (isset ( $sibling ) && $sibling instanceof Bookmark) {
						$b->setTopic ( $sibling->getTopic () );
						$b->setLastBookmarkUsedAsLocationRef ( $sibling );
					} else {
						$b->setTopic ( $maintopic );
					}
					break;
				case 'related' :
					$b->setTopic ( new Topic ( $_POST ['relatedT_id'] ) );
					break;
				default :
					$b->setTopic ( $maintopic );
			}

			if ($b->getUrl () && $b->getTitle ()) {
				$b->toDB ();
				$snapshot_age = $b->getSnapshotAge ();
				if (is_null ( $snapshot_age ) || $snapshot_age > 1 || (isset ( $bookmarkBeforeProcessing ) && strcmp ( $bookmarkBeforeProcessing->getUrl (), $b->getUrl () ) != 0)) {
					$b->getSnapshot ();
				}
			}

			// si changement de rubrique, on propose d'inscrire les ressources qui ont la ressource comme référence, en termes d'emplacement, dans la même rubrique
			if (isset ( $bookmarkBeforeProcessing ) && strcmp ( $bookmarkBeforeProcessing->getTopicId (), $b->getTopicId () ) != 0) {
				$withTheSameExpectedLocation = $system->getBookmarksWithTheSameExpectedLocation ( $b );
				if (count ( $withTheSameExpectedLocation ) > 0) {
					header ( 'Location:./bookmark_withTheSameExpectedLocation.php?bookmark_id=' . $b->getId () );
					exit ();
				}
			}

			header ( 'Location:' . $system->getTopicUrl ( $b->getTopic () ) );
			exit ();

		case 'b_remove' :
			$t = $b->getTopic ();
			if ($b->removeHitsFromDB ()) {
				$b->removeFromDB ();
			}
			header ( 'Location:' . $system->getTopicUrl ( $t ) );
			exit ();
	}
}

$doc_title = $b->hasId () ? $b->getTitle () : 'Nouvelle ressource';

$relatedTopicsCount = $b->isTopicKnown () ? $b->getTopic ()->countRelatedTopics () : 0;

$quitUrl = $b->hasId () ? $system->getBookmarkUrl ( $b ) : (isset ( $requestedTopic ) ? $system->getTopicUrl ( $requestedTopic ) : $system->getHomeUrl ());

$form_action = isset ( $_SERVER ['PHP_SELF'] ) ? $_SERVER ['PHP_SELF'] : './bookmark_edit_stepper.php';

$step_titles = array (
		'Quelle ressource ?',
		'Dans quelle rubrique ?',
		'Confidentialité de la ressource ?'
);
$step_count = count ( $step_titles );

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title; ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<?php echo $system->writeHeadCommonLinkTags(); ?>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
<script src="js/bookmark-url-input.js"></script>
<script src="js/bookmark-title-input.js"></script>
<script src="js/bookmark-publisher-input.js"></script>
<style type="text/css">
/* ---- Assistant de saisie en 3 étapes ---- */
#bookmarkEditStepper .bw-wizard {
	max-width: 880px;
	margin: 0;
}

#bookmarkEditStepper .bw-lead {
	color: var(--bs-gray-600);
	margin-bottom: 1.25rem;
}

/* stepper */
#bookmarkEditStepper .bw-stepper ol {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	margin: 0 0 .75rem;
	padding: 0;
	list-style: none;
}

#bookmarkEditStepper .bw-stepper li {
	position: relative;
	min-width: 0;
}

/* trait de liaison entre deux étapes */
#bookmarkEditStepper .bw-stepper li+li::before {
	content: "";
	position: absolute;
	top: 1.05rem;
	right: 50%;
	left: -50%;
	height: 2px;
	background-color: var(--bs-gray-300);
	transition: background-color .25s ease;
}

#bookmarkEditStepper .bw-stepper li.is-complete::before,
#bookmarkEditStepper .bw-stepper li.is-current::before {
	background-color: var(--bs-primary);
}

#bookmarkEditStepper .bw-step {
	position: relative;
	z-index: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	width: 100%;
	padding: 0;
	border: 0;
	background: none;
	color: inherit;
	font: inherit;
	line-height: 1.2;
	text-align: center;
	text-transform: none;
}

#bookmarkEditStepper .bw-step:not([disabled]) {
	cursor: pointer;
}

#bookmarkEditStepper .bw-step-index {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 2.1rem;
	height: 2.1rem;
	margin-bottom: .35rem;
	border: 2px solid var(--bs-gray-400);
	border-radius: 50%;
	background-color: var(--bs-white);
	color: var(--bs-gray-600);
	font-family: "Oswald", sans-serif;
	font-size: 1rem;
	transition: background-color .25s ease, border-color .25s ease, box-shadow .25s ease;
}

#bookmarkEditStepper .bw-step-label {
	padding: 0 .25rem;
	color: var(--bs-gray-500);
	font-family: "Oswald", sans-serif;
	font-size: .85rem;
	font-weight: 300;
}

#bookmarkEditStepper .bw-stepper li.is-current .bw-step-index {
	border-color: var(--bs-primary);
	color: var(--bs-dark);
	box-shadow: 0 0 0 .28rem rgba(var(--bs-primary-rgb), .22);
}

#bookmarkEditStepper .bw-stepper li.is-current .bw-step-label {
	color: var(--bs-dark);
}

#bookmarkEditStepper .bw-stepper li.is-complete .bw-step-index {
	border-color: var(--bs-primary);
	background-color: var(--bs-primary);
	color: var(--bs-dark);
}

#bookmarkEditStepper .bw-stepper li.is-complete .bw-step-label {
	color: var(--bs-gray-700);
}

#bookmarkEditStepper .bw-progress {
	height: 4px;
	margin-bottom: .4rem;
	border-radius: 999px;
	background-color: var(--bs-gray-200);
	overflow: hidden;
}

#bookmarkEditStepper .bw-progress-bar {
	display: block;
	width: 33.333%;
	height: 100%;
	border-radius: inherit;
	background-color: var(--bs-primary);
	transition: width .3s ease;
}

#bookmarkEditStepper .bw-status {
	margin-bottom: 1.5rem;
	color: var(--bs-gray-600);
	font-size: .85rem;
}

/* panneaux */
#bookmarkEditStepper .bw-panel {
	display: none;
}

#bookmarkEditStepper .bw-panel.is-active {
	display: block;
	animation: bwPanelIn .22s ease;
}

@keyframes bwPanelIn {
	from {
		opacity: 0;
		transform: translateY(4px);
	}
	to {
		opacity: 1;
		transform: none;
	}
}

#bookmarkEditStepper .bw-panel>h2 {
	scroll-margin-top: 1rem;
	outline: 0;
}

#bookmarkEditStepper .bw-subheading {
	margin: 0 0 1rem;
	color: var(--bs-secondary);
	font-family: "Oswald", sans-serif;
	font-size: 1.1rem;
	font-weight: 400;
}

#bookmarkEditStepper .bw-alert {
	margin: .75rem 0 1rem;
	padding: .65rem .9rem;
	border-left: 4px solid var(--bs-danger);
	background-color: var(--bs-danger-bg-subtle);
	color: var(--bs-danger-text-emphasis);
	font-size: .9rem;
}

#bookmarkEditStepper .form-control[aria-invalid="true"] {
	border-color: var(--bs-danger);
	box-shadow: 0 0 0 .15rem rgba(var(--bs-danger-rgb), .18);
}

/* barre de navigation */
#bookmarkEditStepper .bw-nav {
	position: sticky;
	bottom: 0;
	z-index: 5;
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: .5rem;
	margin-top: 1.5rem;
	padding: .8rem 0;
	background-color: rgba(255, 255, 255, .96);
	border-top: 1px solid var(--bs-gray-300);
	box-shadow: 0 -8px 14px -14px rgba(0, 0, 0, .55);
}

#bookmarkEditStepper .bw-nav-actions {
	display: flex;
	flex-wrap: wrap;
	gap: .5rem;
	margin-left: auto;
}

@media ( max-width : 575.98px) {
	#bookmarkEditStepper .bw-step-index {
		width: 1.8rem;
		height: 1.8rem;
	}
	#bookmarkEditStepper .bw-step-label {
		font-size: .7rem;
	}
	#bookmarkEditStepper .bw-stepper li+li::before {
		top: .9rem;
	}
	#bookmarkEditStepper .bw-nav-actions {
		width: 100%;
		margin-left: 0;
	}
	#bookmarkEditStepper .bw-nav-actions .btn {
		flex: 1 1 0;
	}
}

@media ( prefers-reduced-motion : reduce) {
	#bookmarkEditStepper .bw-panel.is-active {
		animation: none;
	}
	#bookmarkEditStepper .bw-progress-bar,
	#bookmarkEditStepper .bw-step-index,
	#bookmarkEditStepper .bw-stepper li+li::before {
		transition: none;
	}
}
</style>
</head>
<body id="bookmarkEditStepper">
	<main>
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<div class="bw-wizard">
			<p class="bw-lead">
				<?php
				if ($b->hasId ()) {
					echo 'Dernière mise à jour le <strong>' . $b->getLastEditDateFr () . '</strong>.';
				} else {
					echo 'Décrivons cette nouvelle ressource ...';
				}
				?>
			</p>

			<nav id="bw_stepper" class="bw-stepper"
				aria-label="Étapes de l'édition">
				<ol>
					<?php foreach ($step_titles as $index => $title): $n = $index + 1; ?>
					<li class="<?php echo $n === 1 ? 'is-current' : 'is-upcoming'; ?>">
						<button type="button" class="bw-step" data-bw-goto="<?php echo $n; ?>"
							<?php echo $n === 1 ? 'aria-current="step"' : 'disabled'; ?>>
							<span class="bw-step-index" aria-hidden="true"><?php echo $n; ?></span>
							<span class="bw-step-label"><?php echo ToolBox::toHtml($title); ?></span>
						</button>
					</li>
					<?php endforeach; ?>
				</ol>
			</nav>
			<div class="bw-progress" aria-hidden="true">
				<span id="bw_progress" class="bw-progress-bar"></span>
			</div>
			<p id="bw_status" class="bw-status" aria-live="polite">Étape 1 sur
				<?php echo $step_count; ?></p>

			<form id="b_edit_f"
				action="<?php echo htmlspecialchars($form_action, ENT_QUOTES, 'UTF-8'); ?>"
				method="post">
				<input type="hidden" name="task_id" value="b_save" />
				<?php if ($b->hasId()): ?>
				<input type="hidden" name="bookmark_id"
					value="<?php echo $b->getId() ?>" />
				<?php endif; ?>

				<div class="bw-panels">
					<!-- Étape 1 : la ressource -->
					<section class="bw-panel is-active" id="bw_panel_1" data-bw-panel="1"
						aria-labelledby="bw_panel_1_title">
						<h2 id="bw_panel_1_title" tabindex="-1"><?php echo ToolBox::toHtml($step_titles[0]); ?></h2>
						<p class="bw-alert" data-bw-alert role="alert" hidden></p>
						<div class="row">
							<div class="col-lg-7">
								<div class="mb-3">
									<label class="form-label" for="b_url_i">URL</label> <input
										id="b_url_i" name="bookmark_url" type="url"
										is="bookmark-url-input"
										value="<?php echo ToolBox::toHtml($b->getUrl()) ?>" size="35"
										maxlength="255" class="form-control" />
								</div>
								<div class="mb-3">
									<label class="form-label" for="b_title_i">Intitulé</label> <input
										id="b_title_i" type="text" size="35" name="bookmark_title"
										value="<?php echo ToolBox::toHtml($b->getTitle()) ?>"
										class="form-control" />
								</div>
								<div class="row">
									<div class="col-md-6">
										<div class="mb-3">
											<label class="form-label" for="b_author_i">Auteur</label> <input
												id="b_author_i" type="text" size="35" maxlength="255"
												name="bookmark_creator"
												value="<?php echo ToolBox::toHtml($b->getCreator()) ?>"
												class="form-control" />
										</div>
									</div>
									<div class="col-md-6">
										<div class="mb-3">
											<label class="form-label" for="b_publisher_i">Editeur</label> <input
												id="b_publisher_i" type="text" is="bookmark-publisher-input"
												name="bookmark_publisher" size="35" maxlength="255"
												value="<?php echo ToolBox::toHtml($b->getPublisher()) ?>"
												class="form-control" />
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-5">
								<div class="mb-3">
									<label class="form-label" for="b_description_i">Description</label>
									<textarea id="b_description_i" name="bookmark_description"
										cols="25" rows="9" class="form-control"><?php echo ToolBox::toHtml($b->getDescription()) ?></textarea>
									<div class="form-text">255 caractères maximum.</div>
								</div>
							</div>
						</div>
					</section>

					<!-- Étape 2 : la rubrique -->
					<section class="bw-panel" id="bw_panel_2" data-bw-panel="2"
						aria-labelledby="bw_panel_2_title">
						<h2 id="bw_panel_2_title" tabindex="-1"><?php echo ToolBox::toHtml($step_titles[1]); ?></h2>
						<p class="bw-alert" data-bw-alert role="alert" hidden></p>
						<div class="form-check mb-3">
							<label class="form-check-label" for="b_t_imode_i_o1"> <input
								class="form-check-input" id="b_t_imode_i_o1" type="radio"
								name="topic_type" value="existing" checked="checked" /> Je
								choisis parmi les rubriques existantes
							</label>
						</div>
						<div id="existingT_iZone" class="radioSubSet mb-3">
							<label class="form-label" for="existingT_i">Rubrique</label> <select
								id="existingT_i" name="topic_id" class="form-control">
								<?php
								if ($b->getTopic () instanceof Topic && $b->getTopic ()->hasId ()) {
									$topicToSelect = $b->getTopic ();
								} elseif (isset ( $requestedTopic )) {
									$topicToSelect = $requestedTopic;
								} elseif (isset ( $suggestedTopic )) {
									$topicToSelect = $suggestedTopic;
								}
								$topicsOptionsTags = isset ( $topicToSelect ) && $topicToSelect->hasId () ? $maintopic->getDescendantsOptionsTags ( $topicToSelect->getId () ) : $maintopic->getDescendantsOptionsTags ();
								?>
								<option value="<?php echo $maintopic->getId() ?>">- hors
									rubrique -</option>
								<?php echo $topicsOptionsTags?>
							</select>
						</div>
						<div class="form-check mb-3">
							<label class="form-check-label" for="b_t_imode_i_o2"> <input
								class="form-check-input" id="b_t_imode_i_o2" type="radio"
								name="topic_type" value="new" /> Je crée une nouvelle rubrique
								...
							</label>
						</div>
						<div class="radioSubSet">
							<fieldset id="newT_fs">
								<legend>Nouvelle rubrique</legend>
								<div class="mb-3">
									<label class="form-label" for="newtopic_title_input">Intitulé</label>
									<input class="form-control" id="newtopic_title_input"
										name="newtopic_title" size="20" value="" />
								</div>
								<div class="mb-3">
									<label class="form-label" for="newtopic_parent_select">Sous-rubrique
										de</label> <select class="form-control"
										id="newtopic_parent_select" name="newtopic_parent_id">
										<option value="<?php echo $maintopic->getId() ?>">- hors rubrique
											-</option>
										<?php echo $topicsOptionsTags?>
									</select>
								</div>
								<div class="mb-3">
									<label class="form-label" for="newT_description_i">Description</label>
									<textarea class="form-control" id="newT_description_i"
										name="newtopic_description"></textarea>
								</div>
							</fieldset>
						</div>
						<div class="form-check mb-3">
							<label class="form-check-label" for="b_t_imode_i_o3"> <input
								class="form-check-input" id="b_t_imode_i_o3" type="radio"
								name="topic_type" value="sameAsBookmark" /> Au même endroit que
								...
							</label>
						</div>
						<div class="radioSubSet mb-3">
							<label class="form-label" for="siblingBookmarkTitle_i">Quelle
								ressource</label> <input id="siblingBookmarkTitle_i" type="text"
								is="bookmark-title-input" class="form-control"
								name="siblingBookmarkTitle" size="75" />
						</div>
						<?php if($relatedTopicsCount > 0): ?>
						<div class="mb-3">
							<div class="form-check">
								<label class="form-check-label" for="b_t_imode_i_o4"> <input
									class="form-check-input" id="b_t_imode_i_o4" type="radio"
									name="topic_type" value="related" /> Je prends un raccourci
									...
								</label>
							</div>
							<div class="radioSubSet">
								<?php
								if ($relatedTopicsCount == 1) {
									$i = $b->getTopic ()->getRelatedTopics ()->getIterator ();
									echo '<input id="relatedT_i" type="hidden" name="relatedT_id" value="' . $i->current ()->getId () . '" />';
									echo '<div>';
									echo ToolBox::toHtml ( $i->current ()->getTitle () ) . '</br>';
									echo '<small><span class="topicPath">' . $i->current ()->getHtmlPath () . '</span></small>';
									echo '</div>';
								} else {
									echo '<fieldset id="relatedT_fs">';
									echo '<legend>Rubrique</legend>';

									$i = 0;
									foreach ( $b->getTopic ()->getRelatedTopics () as $t ) {
										$i ++;
										echo '<div class="mb-2">';
										echo '<div class="form-check">';
										echo '<label class="form-check-label" for="relatedT_i' . $i . '">';
										echo '<input class="form-check-input" id="relatedT_i' . $i . '" type="radio" name="relatedT_id" value="' . $t->getId () . '" /> ' . ToolBox::toHtml ( $t->getTitle () ) . '</label>';
										echo '</div>';
										echo '<div class="SubSet topicPath"><small>' . $t->getHtmlPath () . '</small></div>';
										echo '</div>';
									}
									echo '</fieldset>';
								}
								?>
							</div>
						</div>
						<?php endif; ?>
					</section>

					<!-- Étape 3 : la confidentialité et les codes d'accès -->
					<section class="bw-panel" id="bw_panel_3" data-bw-panel="3"
						aria-labelledby="bw_panel_3_title">
						<h2 id="bw_panel_3_title" tabindex="-1"><?php echo ToolBox::toHtml($step_titles[2]); ?></h2>
						<p class="bw-alert" data-bw-alert role="alert" hidden></p>
						<div class="row">
							<div class="col-lg-6">
								<div class="mb-3">
									<div class="form-check form-check-inline">
										<label class="form-check-label" for="b_privacy_i_o1"> <input
											class="form-check-input" id="b_privacy_i_o1" type="radio"
											name="bookmark_private" value="0"
											<?php echo $b->isPrivate() ? '' : 'checked="checked"' ?> />non
										</label>
									</div>
									<div class="form-check form-check-inline">
										<label class="form-check-label" for="b_privacy_i_o2"> <input
											class="form-check-input" id="b_privacy_i_o2" type="radio"
											name="bookmark_private" value="1"
											<?php echo $b->isPrivate() ? 'checked="checked"' : '' ?> />oui
									</label>
									</div>
								</div>
							</div>
							<div class="col-lg-6">
								<h3 class="bw-subheading">Codes d'accès ?</h3>
								<div class="mb-3">
									<label class="form-label" for="b_id_i">Identifiant</label> <input
										class="form-control" id="b_id_i" type="text" size="25"
										maxlength="255" name="bookmark_login"
										value="<?php echo ToolBox::toHtml($b->getLogin()) ?>" />
								</div>
								<div class="mb-3">
									<label class="form-label" for="b_password_i">Mot de passe</label>
									<input class="form-control" id="b_password_i" type="text"
										size="25" maxlength="255" name="bookmark_password"
										value="<?php echo ToolBox::toHtml($b->getPassword()) ?>" />
								</div>
							</div>
						</div>
						<?php
						if ($b->hasId ()) {
							echo '<p class="text-muted">Tu veux oublier cette ressource ? C\'est <a id="delete_a" href="#">ici</a>.</p>';
						}
						?>
					</section>
				</div>

				<div class="bw-nav">
					<a class="btn btn-link"
						href="<?php echo htmlspecialchars($quitUrl, ENT_QUOTES, 'UTF-8'); ?>">quitter</a>
					<div class="bw-nav-actions">
						<button type="button" class="btn btn-outline-secondary"
							id="bw_prev" hidden>précédent</button>
						<button type="button" class="btn btn-primary" id="bw_next" hidden>suivant</button>
						<button type="submit" class="btn btn-primary" id="bw_save" hidden><?php echo $b->hasId() ? 'enregistrer' : 'inscrire'; ?></button>
					</div>
				</div>
			</form>
		</div>
	</main>
	<script type="text/javascript">
		const apiUrl = '<?php echo $system->getApiUrl() ?>';

		document.addEventListener("DOMContentLoaded", function() {

			/* ---------- Assistant de saisie en 3 étapes ---------- */
			(function initStepper() {
				const wizard = document.getElementById("b_edit_f");
				const stepper = document.getElementById("bw_stepper");
				if (!wizard || !stepper) {
					return;
				}
				const panels = Array.from(wizard.querySelectorAll("[data-bw-panel]"));
				const stepButtons = Array.from(stepper.querySelectorAll("[data-bw-goto]"));
				const prevButton = document.getElementById("bw_prev");
				const nextButton = document.getElementById("bw_next");
				const saveButton = document.getElementById("bw_save");
				const progressBar = document.getElementById("bw_progress");
				const statusText = document.getElementById("bw_status");
				const total = panels.length;
				let current = 1;
				let maxVisited = 1;

				function panelAt(index) {
					return panels[index - 1];
				}

				function clearError(panel) {
					const alert = panel.querySelector("[data-bw-alert]");
					if (alert) {
						alert.textContent = "";
						alert.hidden = true;
					}
					panel.querySelectorAll('[aria-invalid="true"]').forEach(function(field) {
						field.removeAttribute("aria-invalid");
					});
				}

				function fail(panel, field, message) {
					const alert = panel.querySelector("[data-bw-alert]");
					if (alert) {
						alert.textContent = message;
						alert.hidden = false;
					}
					if (field) {
						field.setAttribute("aria-invalid", "true");
						field.focus();
					}
					return false;
				}

				function validate(index) {
					const panel = panelAt(index);
					clearError(panel);

					if (index === 1) {
						const url = document.getElementById("b_url_i");
						const title = document.getElementById("b_title_i");
						const description = document.getElementById("b_description_i");
						if (!url.value.trim()) {
							return fail(panel, url, "Indique l'URL de la ressource.");
						}
						if (!url.checkValidity()) {
							return fail(panel, url, "L'URL saisie n'est pas valide.");
						}
						if (!title.value.trim()) {
							return fail(panel, title, "Donne un intitulé à la ressource.");
						}
						if (description.value.length > 255) {
							return fail(panel, description, "La description est trop longue (" + description.value.length + " caractères). Le nombre de caractères autorisé est 255.");
						}
						return true;
					}

					if (index === 2) {
						const mode = wizard.querySelector('input[name="topic_type"]:checked');
						const modeValue = mode ? mode.value : "existing";
						if (modeValue === "new") {
							const newTitle = document.getElementById("newtopic_title_input");
							const newDescription = document.getElementById("newT_description_i");
							if (!newTitle.value.trim()) {
								return fail(panel, newTitle, "Donne un intitulé à la nouvelle rubrique.");
							}
							if (newDescription.value.length > 255) {
								return fail(panel, newDescription, "La description de la rubrique est trop longue (" + newDescription.value.length + " caractères). Le nombre de caractères autorisé est 255.");
							}
						} else if (modeValue === "sameAsBookmark") {
							const sibling = document.getElementById("siblingBookmarkTitle_i");
							if (!sibling.value.trim()) {
								return fail(panel, sibling, "Indique la ressource qui partage le même emplacement.");
							}
						} else if (modeValue === "related") {
							const choices = Array.from(wizard.querySelectorAll('input[name="relatedT_id"]'));
							const hasChoice = choices.some(function(input) {
								return input.type === "hidden" || input.checked;
							});
							if (!hasChoice) {
								return fail(panel, choices[0] || null, "Choisis la rubrique de destination.");
							}
						}
						return true;
					}

					return true;
				}

				function updatePanels() {
					panels.forEach(function(panel, i) {
						panel.classList.toggle("is-active", i + 1 === current);
					});
				}

				function updateStepper() {
					stepButtons.forEach(function(button) {
						const index = Number(button.getAttribute("data-bw-goto"));
						const item = button.closest("li");
						item.classList.toggle("is-complete", index < current);
						item.classList.toggle("is-current", index === current);
						item.classList.toggle("is-upcoming", index > current);
						button.disabled = index > maxVisited;
						if (index === current) {
							button.setAttribute("aria-current", "step");
						} else {
							button.removeAttribute("aria-current");
						}
					});
				}

				function updateNav() {
					prevButton.hidden = current === 1;
					nextButton.hidden = current === total;
					saveButton.hidden = current !== total;
				}

				function updateStatus() {
					if (progressBar) {
						progressBar.style.width = (current / total * 100) + "%";
					}
					if (statusText) {
						statusText.textContent = "Étape " + current + " sur " + total;
					}
				}

				function goTo(index) {
					if (index === current) {
						return;
					}
					current = Math.min(Math.max(index, 1), total);
					maxVisited = Math.max(maxVisited, current);
					updatePanels();
					updateStepper();
					updateNav();
					updateStatus();

					const panel = panelAt(current);
					const invalidField = panel.querySelector('[aria-invalid="true"]');
					if (invalidField) {
						invalidField.focus();
					} else {
						const heading = panel.querySelector("h2");
						if (heading) {
							heading.focus();
						}
					}
				}

				function requestStep(index) {
					if (index < 1 || index > total || index > maxVisited || index === current) {
						return;
					}
					if (index > current) {
						for (let step = current; step < index; step++) {
							if (!validate(step)) {
								goTo(step);
								return;
							}
						}
					}
					goTo(index);
				}

				nextButton.addEventListener("click", function() {
					if (validate(current)) {
						goTo(current + 1);
					}
				});

				prevButton.addEventListener("click", function() {
					goTo(current - 1);
				});

				stepButtons.forEach(function(button) {
					button.addEventListener("click", function() {
						requestStep(Number(button.getAttribute("data-bw-goto")));
					});
				});

				// la touche entrée ne doit pas court-circuiter les étapes
				wizard.addEventListener("submit", function(event) {
					if (current !== total) {
						event.preventDefault();
						if (validate(current)) {
							goTo(current + 1);
						}
						return;
					}
					// dernière étape : contrôle de sécurité des étapes précédentes
					for (let step = 1; step < total; step++) {
						if (!validate(step)) {
							event.preventDefault();
							goTo(step);
							return;
						}
					}
				});

				wizard.addEventListener("input", function(event) {
					const field = event.target;
					if (field.matches && field.matches('[aria-invalid="true"]')) {
						field.removeAttribute("aria-invalid");
					}
					const panel = field.closest ? field.closest("[data-bw-panel]") : null;
					if (panel) {
						const alert = panel.querySelector("[data-bw-alert]");
						if (alert && !alert.hidden) {
							alert.textContent = "";
							alert.hidden = true;
						}
					}
				});

				updatePanels();
				updateStepper();
				updateNav();
				updateStatus();
			})();

			/* ---------- Rubrique de destination ---------- */
			function setFieldsDisabled(selector, disabled) {
				document.querySelectorAll(selector).forEach(function(element) {
					element.disabled = disabled;
				});
			}

			function applyTopicMode(mode) {
				setFieldsDisabled("#newT_fs input, #newT_fs textarea, #newT_fs select", mode !== "new");
				setFieldsDisabled("#siblingBookmarkTitle_i", mode !== "sameAsBookmark");
				setFieldsDisabled("#existingT_i", mode !== "existing");
				if (document.getElementById("relatedT_fs")) {
					setFieldsDisabled("#relatedT_fs input", mode !== "related");
				}
			}

			[
				["b_t_imode_i_o1", "existing"],
				["b_t_imode_i_o2", "new"],
				["b_t_imode_i_o3", "sameAsBookmark"],
				["b_t_imode_i_o4", "related"]
			].forEach(function(pair) {
				const radio = document.getElementById(pair[0]);
				if (radio) {
					radio.addEventListener("click", function() {
						applyTopicMode(pair[1]);
					});
				}
			});

			const checkedTopicMode = document.querySelector('input[name="topic_type"]:checked');
			applyTopicMode(checkedTopicMode ? checkedTopicMode.value : "existing");

			/* ---------- Champs personnalisés ---------- */
			customElements.define("bookmark-url-input", BookmarkUrlInputElement, { extends: "input" });
			customElements.define("bookmark-publisher-input", BookmarkPublisherInputElement, { extends: "input" });
			customElements.define("bookmark-title-input", BookmarkTitleInputElement, { extends: "input" });

			/* ---------- Suppression ---------- */
			<?php if($b->hasId()): ?>
			const delete_a = document.getElementById('delete_a');
			delete_a.addEventListener('click', function (event) {
			  event.preventDefault();
			  var xhr = new XMLHttpRequest();
			  xhr.open("POST", apiUrl+"/bookmarks/", true);
			  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			  xhr.responseType = 'json';
			  xhr.onreadystatechange = function () {
			    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
			    	alert(this.response.message);
			    	if (this.response.data.location !== undefined) {
				    	window.location.replace(this.response.data.location);
			    	}
		    	}				  
			  };
			  xhr.send("id=<?php echo $b->getId() ?>&task=deletion");
			});
			<?php endif; ?>

			/* ---------- Contrôles de longueur ---------- */
			function checkBookmarkDescriptionLength(e) {
		        const descriptionInput = document.getElementById("b_description_i");
		        if (descriptionInput.value.length > 255) {
		            e.preventDefault();
		            alert(`La description est trop longue (${descriptionInput.value.length} caractères).\nLe nombre de caractères autorisé est 255.`);
		            descriptionInput.focus();
		        }
		    }
		    
		    function checkTopicDescriptionLength(e) {
		    	const descriptionInput = document.getElementById("newT_description_i");
				if (descriptionInput.value.length > 255) {
					e.preventDefault();
		            alert(`La description est trop longue (${descriptionInput.value.length} caractères).\nLe nombre de caractères autorisé est 255.`);
		            descriptionInput.focus();			}
		    }
		    
		  	function displayInputSuggestion(id, value) {
		        const inputField = document.getElementById(id);
		        const suggestionId = id + "_s";
		        let existingSuggestion = document.getElementById(suggestionId);
	
		        if (value && value.length > 0 && value !== inputField.value) {
		            if (existingSuggestion) {
		                existingSuggestion.remove();
		            }
	
		            const suggestionDiv = document.createElement("div");
		            suggestionDiv.id = suggestionId;
		            suggestionDiv.className = "alert alert-primary suggestion";
		            suggestionDiv.innerHTML = `
		                <small>Suggestion</small>
		                <p>${value}</p>
		                <div><button type="button" value="${value}">Accepter</button></div>
		            `;
	
		            inputField.insertAdjacentElement("afterend", suggestionDiv);
		            suggestionDiv.style.display = "none";
		            suggestionDiv.style.transition = "opacity 0.5s";
		            setTimeout(() => (suggestionDiv.style.display = "block"), 50);
	
		            suggestionDiv.querySelector("button").addEventListener("click", function () {
		                inputField.value = this.value;
		                inputField.focus();
		                suggestionDiv.style.opacity = "0";
		                setTimeout(() => suggestionDiv.remove(), 500);
		            });
		        } else {
		            if (existingSuggestion) {
		                existingSuggestion.style.opacity = "0";
		                setTimeout(() => existingSuggestion.remove(), 500);
		            }
		        }
		    }	    
   
    	function removeFormerSuggestions() {
	        document.querySelectorAll(".suggestion").forEach((suggestion) => {
	            suggestion.style.opacity = "0";
	            setTimeout(() => suggestion.remove(), 500);
	        });
	    }

	    function suggestMetaDataFromUrl() {
	        fetch(apiUrl+`/bookmarks/virtualBookmark.php?url=${encodeURIComponent(document.getElementById("b_url_i").value)}`)
	            .then((response) => response.json())
	            .then((data) => {
	                displayInputSuggestion("b_title_i", data.title);
	                displayInputSuggestion("b_description_i", data.description);
	                displayInputSuggestion("b_author_i", data.creator);
	                displayInputSuggestion("b_publisher_i", data.publisher);
	            });
	    }
	    
	    document.getElementById("b_url_i").addEventListener("change", removeFormerSuggestions);
		document.getElementById("b_url_i").addEventListener("change", suggestMetaDataFromUrl);
	    document.getElementById("b_edit_f").addEventListener("submit", checkBookmarkDescriptionLength);
	    document.getElementById("b_edit_f").addEventListener("submit", checkTopicDescriptionLength);
	});
</script>
</body>
</html>
