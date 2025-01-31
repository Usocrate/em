<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

if (! $system->configFileExists()) {
    header('Location:' . $system->getConfigUrl());
    exit();
}

include_once './inc/boot.php';
session_start();

if (! $system->isUserAuthenticated()) {
    header('Location:' . $system->getLoginUrl());
    exit();
}

$maintopic = $system->getMainTopic();

/**
 * ressource identifiée comme existante
 */
if (! empty($_REQUEST['bookmark_id'])) {
    $b = $system->getBookmarkById($_REQUEST['bookmark_id']);
    if (! ($b instanceof Bookmark)) {
        header('Location:' . $system->getProjectUrl());
        exit();
    }
    $bookmarkBeforeProcessing = clone $b;
}
/**
 * nouvelle ressource
 */
else {
    $b = new Bookmark();
    /**
     * l'url de la ressource est passée comme paramètre GET
     */
    if (isset($_GET['bookmark_url'])) {
        $data = $_GET['bookmark_url'];
        $data = strip_tags($data);
        $b->setUrl($data);
    }
    /**
     * analyse du fichier à distance, pour l'instant uniquement pour une nouvelle ressource
     */
    if ($b->getUrl()) {
        $b->hydrateFromUrl();
    }
}
// dans le cas d'ajout de ressource, on tente de déterminer la rubrique de destination
if (! $b->hasId()) {
    if (isset($_REQUEST['topic_id'])) {
        // lorsque un identifiant de rubrique est transmis, celle-ci sera présélectionnée comme destination du signet à créer
        $requestedTopic = $system->getTopicById($_REQUEST['topic_id']);
    } else {
        // on propose une destination en fonction de l'historique de navigation
        $suggestedTopic = $system->getLastInvolvedTopic();
    }
}
if (isset($_POST['task_id'])) {
    ToolBox::formatUserPost($_POST);
    switch ($_POST['task_id']) {
        case 'b_save':
            $b->hydrate($_POST, 'bookmark_');
            switch ($_POST['topic_type']) {
                case 'new':
                    if ($_POST['newtopic_title']) {
                        $t = new Topic();
                        $t->setTitle($_POST['newtopic_title']);
                        $t->setDescription($_POST['newtopic_description']);
                        $t->setPrivacy($_POST['newtopic_privacy']);
                        if (empty($_POST['newtopic_parent_id'])) {
                            $t->addTo($maintopic);
                        } else {
                            $t->addTo(new Topic($_POST['newtopic_parent_id']));
                        }
                        $b->setTopic($t);
                    }
                    break;
                case 'existing':
                    $b->setTopic(new Topic($_POST['topic_id']));
                    break;
                case 'sameAsBookmark':
                    $sibling = $system->getBookmarkByTitle($_POST['siblingBookmarkTitle']);
                    if (isset($sibling) && $sibling instanceof Bookmark) {
                        $b->setTopic($sibling->getTopic());
                        $b->setLastBookmarkUsedAsLocationRef($sibling);
                    } else {
                      $b->setTopic($maintopic);
                    }
                    break;
                case 'related':
                    $b->setTopic(new Topic($_POST['relatedT_id']));
                    break;
                default:
                    $b->setTopic($maintopic);
            }
            
            if ($b->getUrl() && $b->getTitle()) {
                $b->toDB();
                $snapshot_age = $b->getSnapshotAge();
                if (is_null($snapshot_age) || $snapshot_age > 1 || (isset($bookmarkBeforeProcessing) && strcmp($bookmarkBeforeProcessing->getUrl(),$b->getUrl()) != 0)) {
                    $b->getSnapshot();
                }
            }
            
            // si changement de rubrique, on propose d'inscrire les ressources qui ont la ressource comme référence, en termes d'emplacement, dans la même rubrique
            if ( isset($bookmarkBeforeProcessing) && strcmp($bookmarkBeforeProcessing->getTopicId(),$b->getTopicId()) != 0) {
	            $withTheSameExpectedLocation = $system->getBookmarksWithTheSameExpectedLocation($b);
	            if (count($withTheSameExpectedLocation)>0) {
		            header( 'Location:./bookmark_withTheSameExpectedLocation.php?bookmark_id='.$b->getId());
		            exit;            	
	            }
            }
            
            header( 'Location:' . $system->getTopicUrl( $b->getTopic() ) );
            exit;
            
        case 'b_remove':
            $t = $b->getTopic();
            if ($b->removeHitsFromDB()) {
                $b->removeFromDB();
            }
            header('Location:' . $system->getTopicUrl($t));
            exit;
    }
}

$doc_title = $b->hasId() ? 'Nouvelle ressource' : $b->getTitle();

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title; ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<?php echo $system->writeHeadCommonLinkTags(); ?>	
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo JQUERY_UI_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<script src="js/bookmark-type-autocomplete.js"></script>
</head>
<body id="bookmarkEdit">
	<main>
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<div>
			<form id="b_edit_f" action="<?php echo Bookmark::getEditionUrl() ?>" method="post">
				<div>
				<?php
				if ($b->getId()) {
					echo '<p>Enregistrée en <strong>' . $b->getHtmlLinkToCreationYear() . '</strong>.<br>La description actuelle date du <strong>' . $b->getLastEditDateFr() . '</strong>.</p>';
				    echo '<input type="hidden" name="bookmark_id" value="' . $b->getId() . '" />';
				} else {
				    echo '<p>Décrivons cette nouvelle ressource ...</p>';
				}
				?>
				</div>
				<div class="row">
					<div class="col-lg-4">
						<section id="b_url_s">
							<h2>Quelle ressource ?</h2>
							<div class="mb-3">
								<label class="form-label" for="b_url_i">URL</label> <input id="b_url_i" name="bookmark_url" type="url" value="<?php echo ToolBox::toHtml($b->getUrl()) ?>" size="35" maxlength="255" class="form-control" />
							</div>
							<div class="mb-3">
								<label class="form-label" for="b_title_i">Intitulé</label> <input id="b_title_i" type="text" size="35" name="bookmark_title" value="<?php echo ToolBox::toHtml($b->getTitle()) ?>" class="form-control" />
							</div>
							<div class="mb-3">
								<label class="form-label" for="b_description_i">Description</label>
								<textarea id="b_description_i" name="bookmark_description" cols="25" rows="11" class="form-control"><?php echo ToolBox::toHtml($b->getDescription()) ?></textarea>
							</div>
							<div class="mb-3">
								<label class="form-label" for="b_type_i">Type</label> <input id="b_type_i" type="text"  is="bookmark-type-autocomplete" size="35" name="bookmark_type" value="<?php echo ToolBox::toHtml($b->getType()) ?>" class="form-control" />
							</div>
							<fieldset style="display:none">
								<legend>Language</legend>
								<div class="form-check mb-3">
									<label class="form-check-label" for="b_lang_i_o1"><input class="form-check-input" id="b_lang_i_o1" type="radio" name="bookmark_language" value="en" <?php echo strcmp($b->getLanguage(), 'en')==0 ? 'checked="checked"' : '' ?> /> en</label>
									<label class="form-check-label" for="b_lang_i_o2"><input class="form-check-input" id="b_lang_i_o2" type="radio" name="bookmark_language" value="fr" <?php echo strcmp($b->getLanguage(), 'fr')==0 ? 'checked="checked"' : '' ?> /> fr</label>
									<label class="form-check-label" for="b_lang_i_o4"><input class="form-check-input" id="b_lang_i_o4" type='radio' name="bookmark_language" value="it" <?php echo strcmp($b->getLanguage(), 'it')==0 ? 'checked="checked"' : '' ?> /> it</label>
								</div>
							</fieldset>
							<div class="mb-3">
								<label class="form-label" for="b_author_i">Auteur</label> <input id="b_author_i" type="text" size="35" maxlength="255" name="bookmark_creator" value="<?php echo ToolBox::toHtml($b->getCreator()) ?>" class="form-control" />
							</div>
							<div class="mb-3">
								<label class="form-label" for="b_publisher_i">Editeur</label> <input id="b_publisher_i" type="text" name="bookmark_publisher" size="35" maxlength="255" value="<?php echo ToolBox::toHtml($b->getPublisher()) ?>" class="form-control" />
							</div>
							<fieldset>
								<legend>Confidentialité de la ressource ?</legend>
								<div class="mb-3">
									<div class="form-check form-check-inline">
										<label class="form-check-label" for="b_privacy_i_o1">
										<input class="form-check-input" id="b_privacy_i_o1" type='radio' name='bookmark_private' value='0' <?php echo $b->isPrivate() ? '' : 'checked="checked"' ?> />non</label>
									</div>
									<div class="form-check form-check-inline">
										<label class="form-check-label" for="b_privacy_i_o2">
										<input class="form-check-input" id="b_privacy_i_o2" type='radio' name='bookmark_private' value='1' <?php echo $b->isPrivate() ? 'checked="checked"' : '' ?> />oui</label>
									</div>
								</div>
							</fieldset>
						</section>
					</div>
					<div class="col-lg-4">
						<section>
							<h2>Dans quelle rubrique ?</h2>
							<div class="form-check mb-3">
								<label class="form-check-label" for="b_t_imode_i_o1">
								<input class="form-check-input" id="b_t_imode_i_o1" type="radio" name="topic_type" value="existing" checked="checked" /> Je choisis parmi les rubriques existantes</label>
							</div>
							<div id="existingT_iZone" class="radioSubSet mb-3">
								<label class="form-label" for="existingT_i">Rubrique</label>
								<select id="existingT_i" name="topic_id" class="form-control">
									<?php
							        if ($b->getTopic() instanceof Topic && $b->getTopic()->hasId()) {
							            $topicToSelect = $b->getTopic();
							        } elseif (isset($requestedTopic)) {
							            $topicToSelect = $requestedTopic;
							        } elseif (isset($suggestedTopic)) {
							            $topicToSelect = $suggestedTopic;
							        }
							        $topicsOptionsTags = isset($topicToSelect) && $topicToSelect->hasId() ? $maintopic->getDescendantsOptionsTags($topicToSelect->getId()) : $maintopic->getDescendantsOptionsTags();
							        ?>
									<option value="<?php echo $maintopic->getId() ?>">- hors rubrique -</option>
									<?php echo $topicsOptionsTags?>
								</select>
							</div>
							<div class="form-check mb-3">
								<label class="form-check-label" for="b_t_imode_i_o2">
								<input class="form-check-input" id="b_t_imode_i_o2" type="radio" name="topic_type" value="new" /> Je crée une nouvelle rubrique ...</label>
							</div>
							<div class="radioSubSet">
								<fieldset id="newT_fs">
									<legend>Nouvelle rubrique</legend>
									<div class="mb-3">
										<label class="form-label" for="newtopic_title_input">Intitulé</label>
										<input class="form-control" id="newtopic_title_input" name="newtopic_title" size="20" value="" />
									</div>
									<div class="mb-3">
										<label class="form-label" for="newtopic_parent_select">Sous-rubrique de</label>
										<select class="form-control" id="newtopic_parent_select" name="newtopic_parent_id">
											<option value="<?php $maintopic->getId() ?>">- hors rubrique -</option>
											<?php echo $topicsOptionsTags?>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label" for="newT_description_i">Description</label>
										<textarea class="form-control" id="newT_description_i" name="newtopic_description"></textarea>
									</div>
									<fieldset>
										<legend>Rubrique confidentielle ?</legend>
										<div class="mb-3">
											<div class="form-check form-check-inline">
												<label class="form-check-label" for="newtopic_privacy_radio1">
												<input class="form-check-input" id="newtopic_privacy_radio1" type='radio' name='newtopic_privacy' value='0' checked="checked" /> non</label>
											</div>
											<div class="form-check form-check-inline">
												<label class="form-check-label" for="newtopic_privacy_radio2">
												<input class="form-check-input" id="newtopic_privacy_radio2" type='radio' name='newtopic_privacy' value='1' /> oui</label>
											</div>
										</div>
									</fieldset>
								</fieldset>
							</div>
							<div class="form-check mb-3">
								<label class="form-check-label" for="b_t_imode_i_o3">
								<input class="form-check-input" id="b_t_imode_i_o3" type="radio" name="topic_type" value="sameAsBookmark" /> Au même endroit que ...</label>
							</div>
							<div class="radioSubSet mb-3">
								<label class="form-label" for="siblingBookmarkTitle_i">Quelle ressource</label>
								<input class="form-control" id="siblingBookmarkTitle_i" name="siblingBookmarkTitle" type="text" size="55"></input>
							</div>
							<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>0): ?>
							<div class="mb-3">
								<div class="form-check">
									<label class="form-check-label" id="b_t_imode_i_o4">
									<input class="form-check-input" id="b_t_imode_i_o4" type="radio" name="topic_type" value="related" /> Je prends un raccourci ...</label>
								</div>
								<div class="radioSubSet">
								<?php
							        if ($b->getTopic()->countRelatedTopics() == 1) {
							            $i = $b->getTopic()
							                ->getRelatedTopics()
							                ->getIterator();
							            echo '<input id="relatedT_i" type="hidden" name="relatedT_id" value="' . $i->current()->getId() . '" />';
							            echo '<div>';
							            echo ToolBox::toHtml($i->current()->getTitle()) . '</br>';
							            echo '<small><span class="topicPath">' . $i->current()->getHtmlPath() . '</span></small>';
							            echo '</div>';
							        } else {
							            echo '<fieldset id="relatedT_fs">';
							            echo '<legend>Rubrique</legend>';
							
							            $i = 0;
							            foreach ($b->getTopic()->getRelatedTopics() as $t) {
							                $i ++;
							                echo '<div class="mb-2">';
							                echo '<div class="form-check">';
							                echo '<label class="form-check-label" for="relatedT_i' . $i . '">';
							                echo '<input class="form-check-input" id="relatedT_i' . $i . '" type="radio" name="relatedT_id" value="' . $t->getId() . '" /> ' . ToolBox::toHtml($t->getTitle()) . '</label>';
							                echo '</div>';
							                echo '<div class="radioSubSet topicPath"><small>' . $t->getHtmlPath() . '</small></div>';
							                echo '</div>';
							            }
							            echo '</fieldset>';
							        }
							    ?>
								</div>
							</div>
							<?php endif; ?>
						</section>
					</div>
					<div class="col-lg-4">
						<section>
							<h2>Codes d&#39;accès ?</h2>
							<div class="mb-3">
								<label class="form-label" for="b_id_i">Identifiant</label>
								<input class="form-control" id="b_id_i" type="text" size="25" maxlength="255" name="bookmark_login" value="<?php echo ToolBox::toHtml($b->getLogin()) ?>" />
							</div>
							<div class="mb-3">
								<label class="form-label" for="b_password_i">Mot de passe</label>
								<input class="form-control" id="b_password_i" type="text" size="25" maxlength="255" name="bookmark_password" value="<?php echo ToolBox::toHtml($b->getPassword()) ?>" />
							</div>
						</section>
					</div>
				</div>
	
				<div class="buttonBar">
					<?php if (!$b->getId()) : ?>
					<a class="btn btn-link" href="<?php echo isset($requestedTopic) ? $system->getTopicUrl($requestedTopic) : $system->getHomeUrl() ?>">quitter</a>
					<button id="task_i_o1" name="task_id" type="submit" value="b_save" class="btn btn-primary">inscrire</button>
					<?php endif; ?>
		
					<?php if ($b->getId()) : ?>
					<a class="btn btn-link" href="<?php echo $system->getBookmarkUrl($b) ?>">quitter</a>
					<button id="task_i_o1" name="task_id" type="submit" value="b_save" class="btn btn-primary">enregistrer</button>
					<?php endif; ?>
				</div>

			</form>
			<?php
				if($b->hasId()) {
					echo '<p>Tu veux oublier cette ressource ? C\'est <a id="delete_a" href="#">ici</a>.</p>';
				}
			?>			
		</div>
	</main>
	<script>
	$(document).ready(function(){
		function checkBookmarkUrl() {
			if ($('#b_url_comment').length==0) {
				$('#b_url_s').append('<div id="b_url_comment"></div>');
			}
			$('#b_url_comment').slideUp('slow');
			$.ajax({
				  method: "GET",
				  url: "json/bookmarkCollectionFromUrl.php",
				  dataType: "json",
				  data: { url: $("#b_url_i").val() }
				}).done(function( r ) {

					var data = r.Collection;

					<?php if($b->hasId()): ?>
					var temp = new Array();
					for (var j=0; j<data.length; j++) {
						if(data[j].id=='<?php echo $b->getId() ?>') continue;
						temp.push(data[j]);
					}
					data = temp;
					<?php endif; ?>

					if (data.length>0) {
						if(data.length==1) {
							msg = 'Déjà enregistré ...';
						} else {
							msg = 'Déjà enregistrés ...';
						}
						$('#b_url_comment').append('<span></span>').text(msg);

						var html = '<ul>';
		                for (var i=0; i<data.length; i++) {
			                html+= '<li>';
		                	if ( data[i].url == $("#b_url_i").val() ) {
		                		html+= '<em>'+data[i].title+'</em>';
		                	} else {
		                		html+= data[i].title;
		                	}
		                	html+= ' <a href="<?php echo $system->getProjectUrl() ?>/bookmark_info.php?bookmark_id='+data[i].id+'"><?php echo Bookmark::getHtmlInfoIcon() ?></a><br/>';
		                	html+= '<small>'+data[i].url+'</small>';
		                	html+= '</li>';
				        }
						html+= '</ul>';
						$('#b_url_comment').append(html);
						$('#b_url_comment').slideDown('slow');
		            } else {
		            	$('#b_url_comment').slideUp('slow').remove();
		            }
				});
		}
		
		function checkBookmarkDescriptionLength(e) {
			if ($("#b_description_i").val().length>255) {
				e.preventDefault();
				alert('La description est trop longue ('+$("#b_description_i").val().length+' caractères).\nLe nombre de caractères autorisé est 255.');
				$("#b_description_i").focus();
			}
	    }
	    
		function checkTopicDescriptionLength(e) {
			if ($("#newT_description_i").val().length>255) {
				e.preventDefault();
				alert('La description est trop longue ('+$("#newT_description_i").val().length+' caractères).\nLe nombre de caractères autorisé est 255.');
				$("#newT_description_i").focus();
			}
	    }	    

		function displayInputSuggestion(id, value) {
			var i = $('#'+id);
			var sid = id+'_s';
			if (value !== null && value !== undefined && value.length>0) {
		        if ($('#'+sid)) {
		        	$('#'+sid).slideUp('slow').remove();
		        }
		        var html = '<div id="'+sid+'" class="alert alert-info suggestion">Suggestion : <button type="button" value="'+value+'">'+value+'</button></div>';
		        i.after(html);
		        $('#'+sid+' button').each(function() {
		    	    $(this).click(function () {
		    	    	i.val($(this).val());
		    	    	i.focus();
		    	    });
		    	});
			} else {
		        if ($('#'+sid)) {
		        	$('#'+sid).slideUp('slow').remove();
		        }
			}
		};
		
		function removeFormerSuggestions() {
			$('.suggestion').slideUp('slow').remove();
		};

		function suggestMetaDataFromUrl() {
			$.ajax({
			  method: "GET",
			  url: "json/virtualBookmark.php",
			  dataType: "json",
			  data: { url: $("#b_url_i").val() }
			}).done(function( r ) {
	        	displayInputSuggestion('b_title_i', r.title);
	        	displayInputSuggestion('b_description_i', r.description);
	        	displayInputSuggestion('b_author_i', r.creator);
	        	displayInputSuggestion('b_publisher_i', r.publisher);
			});
		};

		<?php if(!$b->hasId() && $b->hasUrl()): ?>
		// cas de création de signet avec passage de l'url en paramètre
		checkBookmarkUrl();
		<?php endif; ?>

		$("#b_url_i").change(checkBookmarkUrl);
		$("#b_url_i").change(removeFormerSuggestions);
		$("#b_url_i").change(suggestMetaDataFromUrl);
		
		$("#b_description_i").change(checkBookmarkDescriptionLength);
		$("#newT_description_i").change(checkTopicDescriptionLength);

		$("#siblingBookmarkTitle_i, #newT_fs input, #newT_fs textarea, #newT_fs select").attr('disabled',true);

		<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>1): ?>
		$("#relatedT_fs input").attr('disabled',true);
		<?php endif;?>

		$("#b_t_imode_i_o1").click(function() {
			$("#existingT_i").attr('disabled',false);
			$("#siblingBookmarkTitle_i, #newT_fs input, #newT_fs textarea, #newT_fs select").attr('disabled',true);
			<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>1): ?>
			$("#relatedT_fs input").attr('disabled',true);
			<?php endif;?>
		});

		$("#b_t_imode_i_o2").click(function() {
			$("#existingT_i").attr('disabled',true);
			$("#newT_fs input, #newT_fs textarea, #newT_fs select").attr('disabled',false);
			$("#siblingBookmarkTitle_i").attr('disabled',true);
			<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>1): ?>
			$("#relatedT_fs input").attr('disabled',true);
			<?php endif;?>
		});

		$("#b_t_imode_i_o3").click(function() {
			$("#siblingBookmarkTitle_i").attr('disabled',false);
	  		$("#existingT_i, #newT_fs input, #newT_fs textarea, #newT_fs select").attr('disabled',true);
			<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>1): ?>
			$("#relatedT_fs input").attr('disabled',true);
			<?php endif;?>
		});

		<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>0): ?>
		$("#b_t_imode_i_o4").click(function() {
	  		$("#existingT_i, #siblingBookmarkTitle_i, #newT_fs input, #newT_fs textarea, #newT_fs select").attr('disabled',true);
			<?php if($b->getTopic()->countRelatedTopics()>1): ?>
			$("#relatedT_fs input").attr('disabled',false);
			<?php endif; ?>
		});
		<?php endif; ?>

		<?php if($b->hasId() && $b->getTopic()->countRelatedTopics()>1): ?>
		$("#b_t_imode_i_o4").click(function() {
			$("#existingT_i, #newT_fs input, #newT_fs textarea, #newT_fs select").attr('disabled',true);
			$("#relatedT_fs input").attr('disabled',false);
		});
		<?php endif; ?>

	    $('#b_publisher_i').autocomplete({
			minLength: 3,
	   		source: function( request, response ) {
	            $.ajax({
					method:'GET',
	                url:'json/publisherCollection.php',
	                dataType: 'json',
	                data:{
	                    'query': request.term
	                 },
	                 dataFilter: function(data,type){
	                     return JSON.stringify(JSON.parse(data).publishers);
	                 },
	                 success : function(data, textStatus, jqXHR){
						response(data);
	                 }
	         	})
	   		},
	        focus: function( event, ui ) {
				$('#b_publisher_i').val( ui.item.name );
	        	return false;
	        },
	        select: function( event, ui ) {
				$('#b_publisher_i').val( ui.item.name );
	        	return false;
	        }
	   	}).autocomplete( "instance" )._renderItem = function( ul, item ) {
		    return $( "<li>" ).append(item.name.replace(new RegExp( '(' + this.term + ')', 'gi' ), '<em>'+this.term+'</em>') + ' <small>(' + item.bookmarks_nb +')</small>').appendTo( ul );
	    };

	    $('#siblingBookmarkTitle_i').autocomplete({
			minLength: 2,
	   		source: function( request, response ) {
	            $.ajax({
					method:'GET',
	                url:'json/bookmarkCollectionFromTitle.php',
	                dataType: 'json',
	                data:{
	                    'pattern': request.term
	                 },
	                 dataFilter: function(data,type){
	                     return JSON.stringify(JSON.parse(data).Collection);
	                 },
	                 success : function(data, textStatus, jqXHR){
						response(data);
	                 }
	         	})
	   		},
	        focus: function( event, ui ) {
				$('#siblingBookmarkTitle_i').val( ui.item.title );
	        	return false;
	        },
	        select: function( event, ui ) {
				$('#siblingBookmarkTitle_i').val( ui.item.title );
	        	return false;
	        }
	   	}).autocomplete( "instance" )._renderItem = function( ul, item ) {
		    return $( "<li>" ).append(item.title.replace(new RegExp( '(' + this.term + ')', 'gi' ), '<em>'+this.term+'</em>') + ' <small>(' + item.topic.title +')</small>').appendTo( ul );
	    };

		$("#b_edit_f").on("submit",checkBookmarkDescriptionLength);
		$("#b_edit_f").on("submit",checkTopicDescriptionLength);
	});
</script>
<script type="text/javascript">
	const apiUrl = '<?php echo $system->getApiUrl() ?>';
	
	document.addEventListener("DOMContentLoaded", function() {
		customElements.define("bookmark-type-autocomplete", BookmarkTypeAutocomplete, { extends: "input" });
		
		<?php if($b->hasId()): ?>
		const delete_a = document.getElementById('delete_a');
		delete_a.addEventListener('click', function (event) {
		  event.preventDefault();
		  var xhr = new XMLHttpRequest();
		  xhr.open("POST", "api/bookmarks/", true);
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
	});
</script>
</body>
</html>
