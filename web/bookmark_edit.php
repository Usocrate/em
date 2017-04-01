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

if (! $system->isUserAuthenticated()) {
    header('Location:' . $system->getLoginUrl());
    exit();
}

$maintopic = $system->getMainTopic();

/**
 * ressource identifiée
 */
if (! empty($_REQUEST['bookmark_id'])) {
    $b = $system->getBookmarkById($_REQUEST['bookmark_id']);
    if (! ($b instanceof Bookmark)) {
        header('Location:' . $system->getProjectUrl());
        exit();
    }
} /**
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
     * analyse du fichier à distance, pour l'instant uniquement pour un nouveau signet
     */
    if ($b->getUrl()) {
        $b->hydrateFromUrl();
    }
}
// dans le cas d'ajout de ressource, on tente de déterminer la rubrique de destination
if (! $b->hasId()) {
    if (isset($_REQUEST['topic_id'])) {
        // lorsque un identifiant de ubrique est transmis, celle-ci sera présélectionnée comme destination du signet à créer
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
            $urlBeforeSave = $b->getUrl();
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
                if (is_null($snapshot_age) || $snapshot_age > 1 || $urlBeforeSave !== $b->getUrl()) {
                    // $b->getSnapshotFromBluga ();
                    $b->getSnapshotFromPhantomJS();
                }
            }
            header('Location:' . $system->getTopicUrl($b->getTopic()));
            exit();
        case 'b_remove':
            $t = $b->getTopic();
            if ($b->removeHitsFromDB()) {
                $b->removeFromDB();
            }
            header('Location:' . $system->getTopicUrl($t));
            exit();
    }
}
if ($b->hasId()) {
    $doc_title = 'Modifier la description de la ressource';
} else {
    if (isset($requestedTopic) && $requestedTopic instanceof Topic) {
        $doc_title = 'Ajouter une ressource à la rubrique ' . $requestedTopic->getTitle();
    } else {
        $doc_title = 'Ajouter une ressource au catalogue';
    }
}

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title; ?></title>
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_THEME_URI ?>" type="text/css" />
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
<script type="text/javascript" src="<?php echo JQUERY_UI_URI; ?>"></script>
<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="bookmarkEdit" class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
	</header>
	<div>
		<form action="<?php echo Bookmark::getEditionUrl() ?>" method="post" class="block">
			<div>
			<?php
if ($b->getId()) {
    echo '<p>La description actuelle date du <strong>' . $b->getLastEditDateFr() . '</strong></p>';
    if (strcmp($b->getLastEditDateFr(), $b->getCreationDateFr()) != 0) {
        echo '<p>Ressource enregistrée le <strong>' . $b->getHtmlCreationDateFr() . '</strong></p>';
    }
    echo '<input type="hidden" name="bookmark_id" value="' . $b->getId() . '" />';
} else {
    echo '<p>Décrivons cette nouvelle ressource ...</p>';
}
?>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div id="b_url_s">
						<h1>Quelle ressource ?</h1>
						<div class="form-group">
							<label for="b_url_i">URL</label> <input id="b_url_i" name="bookmark_url" type="url" value="<?php echo ToolBox::toHtml($b->getUrl()) ?>" size="35" maxlength="255" class="form-control" />
						</div>
						<div class="form-group">
							<label for="b_title_i">Intitulé</label> <input id="b_title_i" type="text" size="35" name="bookmark_title" value="<?php echo ToolBox::toHtml($b->getTitle()) ?>" class="form-control" />
						</div>
						<div class="form-group">
							<label for="b_description_i">Description</label>
							<textarea id="b_description_i" name="bookmark_description" cols="25" rows="11" class="form-control"><?php echo ToolBox::toHtml($b->getDescription()) ?></textarea>
						</div>
						<div class="form-group">
							<label for="b_type_i">Type</label> <input id="b_type_i" type="text" size="35" name="bookmark_type" value="<?php echo ToolBox::toHtml($b->getType()) ?>" class="form-control" /><small><a href="http://schema.org/docs/full.html">Aide</a></small>
						</div>
						<fieldset>
							<legend>Language</legend>
							<div class="form-group">
								<label for="b_lang_i_o1"><input id="b_lang_i_o1" type="radio" name="bookmark_language" value="en" <?php echo strcmp($b->getLanguage(), 'en')==0 ? 'checked="checked"' : '' ?> /> en</label> <label for="b_lang_i_o2"><input id="b_lang_i_o2" type="radio" name="bookmark_language" value="fr" <?php echo strcmp($b->getLanguage(), 'fr')==0 ? 'checked="checked"' : '' ?> /> fr</label> <label for="b_lang_i_o4"><input id="b_lang_i_o4" type='radio' name="bookmark_language" value="it"
									<?php echo strcmp($b->getLanguage(), 'it')==0 ? 'checked="checked"' : '' ?> /> it</label>
							</div>
						</fieldset>
						<div class="form-group">
							<label for="b_author_i">Auteur</label> <input id="b_author_i" type="text" size="35" maxlength="255" name="bookmark_creator" value="<?php echo ToolBox::toHtml($b->getCreator()) ?>" class="form-control" />
						</div>
						<div class="form-group">
							<label for="b_publisher_i">Editeur</label> <input id="b_publisher_i" type="text" name="bookmark_publisher" size="35" maxlength="255" value="<?php echo ToolBox::toHtml($b->getPublisher()) ?>" class="form-control" />
						</div>
						<fieldset>
							<legend>Confidentialité du signet ?</legend>
							<div class="form-group">
								<label for="b_privacy_i_o1"><input id="b_privacy_i_o1" type='radio' name='bookmark_private' value='0' <?php echo $b->isPrivate() ? '' : 'checked="checked"' ?> /> non</label> <label for="b_privacy_i_o2"><input id="b_privacy_i_o2" type='radio' name='bookmark_private' value='1' <?php echo $b->isPrivate() ? 'checked="checked"' : '' ?> /> oui</label>
							</div>
						</fieldset>
					</div>
				</div>
				<div class="col-md-6">
					<div>
						<h1>Dans quelle rubrique ?</h1>
						<div class="form-group">
							<label for="b_t_imode_i_o1"><input id="b_t_imode_i_o1" type="radio" name="topic_type" value="existing" checked="checked" /> Je choisis parmi les rubriques existantes</label>
						</div>
						<div id="existingT_iZone" class="radioSubSet form-group">
							<label for="existingT_i">Rubrique</label> <select id="existingT_i" name="topic_id" class="form-control">
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
						<div class="form-group">
							<label for="b_t_imode_i_o2"><input id="b_t_imode_i_o2" type="radio" name="topic_type" value="new" /> Je crée une nouvelle rubrique ...</label>
						</div>
						<div class="radioSubSet">
							<fieldset id="newT_fs">
								<legend>Nouvelle rubrique</legend>
								<div class="form-group">
									<label for="newtopic_title_input">Intitulé</label> <input id="newtopic_title_input" name="newtopic_title" size="20" value="" class="form-control" />
								</div>
								<div class="form-group">
									<label for="newtopic_parent_select">Sous-rubrique de</label> <select id="newtopic_parent_select" name="newtopic_parent_id" class="form-control">
										<option value="<?php $maintopic->getId() ?>">- hors rubrique -</option>
										<?php echo $topicsOptionsTags?>
									</select>
								</div>
								<div class="form-group">
									<label for="newT_description_i">Description</label>
									<textarea id="newT_description_i" name="newtopic_description" class="form-control"></textarea>
								</div>
								<fieldset>
									<legend>Rubrique confidentielle ?</legend>
									<div class="form-group">
										<label for="newtopic_privacy_radio1"><input id="newtopic_privacy_radio1" type='radio' name='newtopic_privacy' value='0' checked="checked" /> non</label> <label for="newtopic_privacy_radio2"><input id="newtopic_privacy_radio2" type='radio' name='newtopic_privacy' value='1' /> oui</label>
									</div>
								</fieldset>
							</fieldset>
						</div>
						<div class="form-group">
							<label for="b_t_imode_i_o3"><input id="b_t_imode_i_o3" type="radio" name="topic_type" value="sameAsBookmark" /> Au même endroit que ...</label>
						</div>
						<div class="radioSubSet form-group">
							<label for="siblingBookmarkTitle_i">Quelle ressource</label><input id="siblingBookmarkTitle_i" name="siblingBookmarkTitle" type="text" size="55" class="form-control"></input>
						</div>
						<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>0): ?>
						<label id="b_t_imode_i_o4"><input id="b_t_imode_i_o4" type="radio" name="topic_type" value="related" /> Je prends un raccourci ...</label>
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
                echo '<label for="relatedT_i' . $i . '"><input id="relatedT_i' . $i . '" type="radio" name="relatedT_id" value="' . $t->getId() . '" /> ' . ToolBox::toHtml($t->getTitle()) . '</label>';
                echo '<div class="radioSubSet topicPath"><small>' . $t->getHtmlPath() . '</small></div>';
            }
            echo '</fieldset>';
        }
        ?>
						</div>
						<?php endif; ?>
					</div>
					<div>
						<h1>Codes d&#39;accès ?</h1>
						<div class="form-group">
							<label for="b_id_i">Identifiant</label> <input id="b_id_i" type="text" size="25" maxlength="255" name="bookmark_login" value="<?php echo ToolBox::toHtml($b->getLogin()) ?>" class="form-control" />
						</div>
						<div class="form-group">
							<label for="b_password_i">Mot de passe</label> <input id="b_password_i" type="text" size="25" maxlength="255" name="bookmark_password" value="<?php echo ToolBox::toHtml($b->getPassword()) ?>" class="form-control" />
						</div>
					</div>
				</div>
			</div>

			<?php if (!$b->getId()) : ?>
			<button id="task_i_o1" name="task_id" type="submit" value="b_save" class="btn btn-primary">inscrire</button>
			<small><a href="<?php echo isset($requestedTopic) ? $system->getTopicUrl($requestedTopic) : $system->getHomeUrl() ?>">annuler</a></small>
			<?php endif; ?>
			

			<?php if ($b->getId()) : ?>
			<button id="task_i_o1" name="task_id" type="submit" value="b_save" class="btn btn-primary">enregistrer</button>
			<button id="task_i_o2" name="task_id" type="submit" value="b_remove" class="btn">supprimer</button>
			<small><a href="<?php echo $system->getBookmarkUrl($b) ?>">annuler</a></small>
			<?php endif; ?>
			
			<input id="b_rss_url_i" type="hidden" name="bookmark_rss_url" value="<?php echo ToolBox::toHtml($b->getRssUrl()) ?>" />
		</form>
	</div>
	<script type="text/javascript">
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
		                	html+= ' <a href="<?php echo $system->secureUrl($system->getProjectUrl()) ?>/bookmark_info.php?bookmark_id='+data[i].id+'"><?php echo Bookmark::getHtmlInfoIcon() ?></a><br/>';
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
	
		function displayInputSuggestion(id, value) {
			var i = $('#'+id);
			var sid = id+'_s';
			if (value !== null && value !== undefined && value.length>0) {
		        if ($('#'+sid)) {
		        	$('#'+sid).remove();
		        }
		        var html = '<div id="'+sid+'" class="info">Suggestion : <button type="button" value="'+value+'">'+value+'</button></div>';
		        i.after(html);
		        $('#'+sid+' button').each(function() {
		    	    $(this).click(function () {
		    	    	i.val($(this).val());
		    	    	i.focus();
		    	    });
		    	});
			} else {
		        if ($('#'+sid)) {
		        	$('#'+sid).remove();
		        }
			}
		}
	
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
		// cas où de création de signet avec passage en paramètre
		checkBookmarkUrl();
		<?php endif; ?>
			
		$("#b_url_i").change(checkBookmarkUrl);
		$("#b_url_i").change(suggestMetaDataFromUrl);
		
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
			<?php endif;?>
		});
		<?php endif;?>
	
		<?php if($b->hasId() && $b->getTopic()->countRelatedTopics()>1): ?>
		$("#b_t_imode_i_o4").click(function() {
			$("#existingT_i, #newT_fs input, #newT_fs textarea, #newT_fs select").attr('disabled',true);
			$("#relatedT_fs input").attr('disabled',false);
		});    	
		<?php endif;?>	
		
	    $("#b_description_i #newT_description_i").blur(function(){
			if ($(this).val().length>255) {
				alert('La description est trop longue ('+$(this).val().length+' caractères).\nLe nombre de caractères autorisé est limité à 255.');
				$(this).focus();
			}
	    });
	
		$('#b_type_i').autocomplete({
			minLength: 2,
	   		source: <?php echo json_encode(Bookmark::getTypeOptionsFromSchemaRdfsOrg())?>
	   	}).autocomplete('instance')._renderItem = function (ul, item) {
			html = item.label.replace(new RegExp( '(' + this.term + ')', 'gi' ), '<em>'+this.term+'</em>');
			if (item.ancestors.length>0) {
				html+= ' <small>(' + item.ancestors.split(" ").join(" / ") +')</small>';
			}
			return $( "<li>" ).append(html).appendTo( ul );
		};
	
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
	    
		<?php if ($b->getId()) : ?>
		$("#task_i_o2").click(function() {
			if (!confirm('Suppression définitive de la ressource ?')) {
				e.preventDefault();
			}
		});
		<?php endif;?>
	});
</script>
</body>
</html>