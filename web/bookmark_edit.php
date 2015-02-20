<?php
function __autoload($class_name) {
	$path = './classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../config/host.json' );

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

$maintopic = $system->getMainTopic ();

/**
 * ressource identifiée
 */
if (! empty ( $_REQUEST ['bookmark_id'] )) {
	$b = $system->getBookmarkById ( $_REQUEST ['bookmark_id'] );
	if (! ($b instanceof Bookmark)) {
		header ( 'Location:' . $system->getProjectUrl () );
		exit ();
	}
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
	 * analyse du fichier à distance, pour l'instant uniquement pour un nouveau signet
	 */
	if ($b->getUrl ()) {
		$b->hydrateFromUrl ();
	}
}
// dans le cas d'ajout de ressource, on tente de déterminer la catégorie cible
if (! $b->hasId ()) {
	if (isset ( $_REQUEST ['topic_id'] )) {
		// lorsque un identifiant de catégorie est transmis, celle-ci sera présélectionnée comme destination du signet à créer
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
			$urlBeforeSave = $b->getUrl ();
			$b->hydrate ( $_POST, 'bookmark_' );
			switch ($_POST ['topic_type']) {
				case 'new' :
					// Création d'une nouvelle rubrique (indépendante de la création de la ressource)
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
				if (strcmp ( $system->getHostPurpose (), 'production' ) == 0) {
					$snapshot_age = $b->getSnapshotAge ();
					if (is_null ( $snapshot_age ) || $snapshot_age > 1 || $urlBeforeSave !== $b->getUrl ()) {
						$b->getSnapshotFromBluga ();
					}
				}
			}
			header ( 'Location:' . $system->getTopicUrl ( $b->getTopic () ) );
			exit ();
		case 'b_remove' :
			$t = $b->getTopic();
			if ($b->removeHitsFromDB ()) {
				$b->removeFromDB ();
			}
			header ( 'Location:' . $system->getTopicUrl ( $t ) );
			exit ();
	}
}
if ($b->hasId ()) {
	$doc_title = 'Modifier la description de la ressource';
} else {
	if (isset ( $requestedTopic ) && $requestedTopic instanceof Topic) {
		$doc_title = 'Ajouter une ressource à la rubrique ' . $requestedTopic->getTitle ();
	} else {
		$doc_title = 'Ajouter une ressource au catalogue';
	}
}

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title; ?></title>
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo FONT_AWESOME_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
<link rel="icon" type="image/x-icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico" />
<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
<script type="text/javascript" src="<?php echo YUI3_SEEDFILE_URI; ?>"></script>
<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
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
			if ($b->getId ()) {
				echo '<p>La description actuelle date du <strong>' . $b->getLastEditDateFr () . '</strong></p>';
				if (strcmp ( $b->getLastEditDateFr (), $b->getCreationDateFr () ) != 0) {
					echo '<p>Ressource enregistrée le <strong>' . $b->getHtmlCreationDateFr () . '</strong></p>';
				}
				echo '<input type="hidden" name="bookmark_id" value="' . $b->getId () . '" />';
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
					</div>

					<div>
						<h1>Sa description ?</h1>
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
								<div class="form-group">
						
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
								if ($b->getTopic () instanceof Topic && $b->getTopic ()->hasId ()) {
									$topicToSelect = $b->getTopic ();
								} elseif (isset ( $requestedTopic )) {
									$topicToSelect = $requestedTopic;
								} elseif (isset ( $suggestedTopic )) {
									$topicToSelect = $suggestedTopic;
								}
								$topicsOptionsTags = isset ( $topicToSelect ) && $topicToSelect->hasId () ? $maintopic->getDescendantsOptionsTags ( $topicToSelect->getId () ) : $maintopic->getDescendantsOptionsTags ();
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
							if ($b->getTopic ()->countRelatedTopics () == 1) {
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
									echo '<label for="relatedT_i' . $i . '"><input id="relatedT_i' . $i . '" type="radio" name="relatedT_id" value="' . $t->getId () . '" /> ' . ToolBox::toHtml ( $t->getTitle () ) . '</label>';
									echo '<div class="radioSubSet topicPath"><small>' . $t->getHtmlPath () . '</small></div>';
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
					<div>
						<h1>Flux RSS associé à la ressource ?</h1>
						<div class="form-group">
							<label for="b_rss_url_i">URL</label> <input id="b_rss_url_i" type="text" size="35" name="bookmark_rss_url" value="<?php echo ToolBox::toHtml($b->getRssUrl()) ?>" maxlength="255" class="form-control" />
						</div>
					</div>
				</div>
			</div>

			<button id="task_i_o1" name="task_id" type="submit" value="b_save" class="btn btn-primary"><?php echo $b->getId() ? 'enregistrer' : 'inscrire' ?></button>
			<?php if ($b->getId()) : ?>
			<button id="task_i_o2" name="task_id" type="submit" value="b_remove" class="btn">Supprimer</button>
			<small><a href="<?php echo $system->secureUrl($system->getProjectUrl()) ?>/bookmark_info.php?bookmark_id=<?php echo $b->getId() ?>">Annuler</a></small>
			<?php endif; ?>
		
		</form>
	</div>
	<script type="text/javascript">
YUI().use("autocomplete", "autocomplete-filters", "autocomplete-highlighters", "node", "event", "datasource","json-parse", function (Y) {
	function publisherOptionFormatter(query, results) {
		return Y.Array.map(results, function (result) {
			return result.highlighted + ' <small>(' + result.raw.bookmarks_nb +')</small>';
		});
	};

	function bookmarkOptionFormatter(query, results) {
		return Y.Array.map(results, function (result) {
			return result.highlighted + ' <small>(' + result.raw.topic.title +')</small>';
		});
	};

	function typeOptionFormatter(query, results) {
		return Y.Array.map(results, function (result) {
			var output = '';
			if(result.raw.ancestors.length>0) {
				var a = result.raw.ancestors.split(" ");
				output+= '<small>' + a.join(" / ") +'</small><br />';
			}
			output+= result.highlighted;
			if(result.raw.properties.length>0) {
				output+= ' <small> : ' + result.raw.properties +'</small>';
			}
			return output;
		});
	};

	function displayInputSuggestion(id, value) {
		var i = Y.one('#'+id);
		var sid = id+'_s';
		if (value !== null && value !== undefined && value.length>0) {
	        if (Y.one('#'+sid)) {
	        	Y.one('#'+sid).remove();
	        }
	        var html = '<div id="'+sid+'" class="info">Suggestion : <button type="button" value="'+Y.Escape.html(value)+'"/>'+Y.Escape.html(value)+'</div>';
	        i.insert(html, 'after');
	        Y.one('#'+sid).all('button').each(function (b) {
	    	    b.on('click', function (e) {
	    	    	i.set('value', this.get('value'));
	    	    	i.focus();
	    	    });
	    	});
		} else {
	        if (Y.one('#'+sid)) {
	        	Y.one('#'+sid).remove();
	        }
		}
	};

	function suggestMetaDataFromUrl(e) {
		var dataSource = new Y.DataSource.Get({
            source: "json/virtualBookmark.php?"
        });

        dataSource.sendRequest({
        	request: "url="+Y.one("#b_url_i").get('value'),
        	on:{
            	success: function(e) {
            		displayInputSuggestion('b_title_i', e.response.results[0].title);
            		displayInputSuggestion('b_description_i', e.response.results[0].description);
            		displayInputSuggestion('b_author_i', e.response.results[0].creator);
            		displayInputSuggestion('b_publisher_i', e.response.results[0].publisher);
                }
            }
         });
	};

	function checkBookmarkUrl(e) {
		var dataSource = new Y.DataSource.Get({
            source: "json/bookmarkCollectionFromUrl.php?"
        });

		dataSource.plug(Y.Plugin.DataSourceJSONSchema, {
            schema: {
                resultListLocator: "Collection",
                resultFields: ["id","title","url","description"]
            }
        });
        		    	
        dataSource.sendRequest({
            request: "url="+Y.one("#b_url_i").get('value'),
            on: {
                success: function(e){
					var data = e.response.results;
					
					<?php if($b->hasId()): ?>
						var temp = new Array();
						for (var j=0; j<data.length; j++) {
							if(data[j].id=='<?php echo $b->getId() ?>') continue;
							temp.push(data[j]);
						}
						data = temp;
					<?php endif; ?>
								
					if (data.length>0) {
		                var html;
						if(data.length==1) {
							html = '<span>Déjà enregistré ...</span>';
						} else {
							html = '<span>Déjà enregistrés ...</span>';
						}
		                html+= '<ul>';
		                for (var i=0; i<data.length; i++) {
			                html+= '<li>';
		                	if (data[i].url == Y.one("#b_url_i").get('value')) { 
		                		html+= '<em>';
			                	html+= Y.Escape.html(data[i].title);
			                	html+= '</em>';
		                	} else {
		                		html+= Y.Escape.html(data[i].title);
		                	}
		                	html+= ' <a href="<?php echo $system->secureUrl($system->getProjectUrl()) ?>/bookmark_info.php?bookmark_id='+data[i].id+'"><?php echo Bookmark::getHtmlInfoIcon() ?></a><br/>';
		                	html+= '<small>'+Y.Escape.html(data[i].url)+'</small>';
		                	html+= '</li>';
				        }
		                html+= '</ul>';
		                if (Y.one('#b_url_comment')) {
		                	Y.one('#b_url_comment').setHTML(html);
		                } else {
			            	Y.one('#b_url_s').append('<div id="b_url_comment">'+html+'</div>');
			            }
					} else {
						if (Y.one('#b_url_comment')) {
							Y.one('#b_url_comment').remove();
						}
					}
                }
            }
        });
    }

    Y.on('domready', function () {
    	Y.one("#newT_fs").all('input').setAttribute('disabled',true);
    	Y.one("#newT_fs").all('textarea').setAttribute('disabled',true);
    	Y.one("#newT_fs").all('select').setAttribute('disabled',true);
    	Y.one("#siblingBookmarkTitle_i").setAttribute('disabled');
    	<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>1): ?>
		Y.one("#relatedT_fs").all('input').setAttribute('disabled',true);
		<?php endif;?>

    	<?php if(!$b->hasId() && $b->hasUrl()): ?>
    	// cas où de création de signet avec passage en paramètre
    	checkBookmarkUrl();
    	<?php endif; ?>
    		
    	Y.one("#b_url_i").on('change', checkBookmarkUrl);
    	Y.one("#b_url_i").on('change', suggestMetaDataFromUrl);
    	    	
    	Y.one('#b_publisher_i').plug(Y.Plugin.AutoComplete, {
        	resultHighlighter: 'phraseMatch',
       		resultListLocator: 'publishers',
       		resultFormatter: publisherOptionFormatter,
       		resultTextLocator: 'name',
       		minQueryLength: 3,
       		source: 'json/publisherCollection.php?query={query}'
       	});
       	
    	Y.one('#siblingBookmarkTitle_i').plug(Y.Plugin.AutoComplete, {
        	resultHighlighter: 'phraseMatch',
       		resultListLocator: 'Collection',
       		resultFormatter: bookmarkOptionFormatter,
       		resultTextLocator: 'title',
       		minQueryLength: 2,
       		source: 'json/bookmarkCollectionFromTitle.php?pattern={query}'
       	});
       	
    	Y.one('#b_type_i').plug(Y.Plugin.AutoComplete, {
    		resultFilters    : 'phraseMatch',
        	resultHighlighter: 'phraseMatch',
        	resultFormatter: typeOptionFormatter,
       		resultTextLocator: 'id',
       		minQueryLength: 2,
       		source: <?php echo json_encode(Bookmark::getTypeOptionsFromSchemaRdfsOrg())?>
       	});

    	Y.one("#b_description_i").on('blur', function(e) {
        	if (this.get('value').length>255) {
    			alert('La description est trop longue ('+this.get('value').length+' caractères).\nLe nombre de caractères max autorisé est de 255.');
    			this.focus();
    		}		 	
     	});

    	Y.one("#b_t_imode_i_o1").on('click', function(e) {
    		Y.one("#existingT_i").removeAttribute('disabled');
    		Y.one("#newT_fs").all('input').setAttribute('disabled');
    		Y.one("#newT_fs").all('textarea').setAttribute('disabled');
    		Y.one("#newT_fs").all('select').setAttribute('disabled');
    		Y.one("#siblingBookmarkTitle_i").setAttribute('disabled');
    		<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>1): ?>
    		Y.one("#relatedT_fs").all('input').setAttribute('disabled',true);
    		<?php endif;?>
    	});
    	
    	Y.one("#b_t_imode_i_o2").on('click', function(e) {
    		Y.one("#existingT_i").setAttribute('disabled',true);
    		Y.one("#newT_fs").all('input').removeAttribute('disabled');
    		Y.one("#newT_fs").all('textarea').removeAttribute('disabled');
    		Y.one("#newT_fs").all('select').removeAttribute('disabled');
    		Y.one("#siblingBookmarkTitle_i").setAttribute('disabled');
    		<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>1): ?>
    		Y.one("#relatedT_fs").all('input').setAttribute('disabled',true);
    		<?php endif;?>
    	});

    	Y.one("#b_t_imode_i_o3").on('click', function(e) {
    		Y.one("#siblingBookmarkTitle_i").removeAttribute('disabled');
      		Y.one("#existingT_i").setAttribute('disabled',true);
      		Y.one("#newT_fs").all('input').setAttribute('disabled');
    		Y.one("#newT_fs").all('textarea').setAttribute('disabled');
    		Y.one("#newT_fs").all('select').setAttribute('disabled');
    		<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>1): ?>
    		Y.one("#relatedT_fs").all('input').setAttribute('disabled',true);
    		<?php endif;?>
    	});
    	
    	<?php if($b->isTopicKnown() && $b->getTopic()->countRelatedTopics()>0): ?>
    	Y.one("#b_t_imode_i_o4").on('click', function(e) {
      		Y.one("#existingT_i").setAttribute('disabled',true);
      		Y.one("#newT_fs").all('input').setAttribute('disabled');
    		Y.one("#newT_fs").all('textarea').setAttribute('disabled');
    		Y.one("#newT_fs").all('select').setAttribute('disabled');
    		Y.one("#siblingBookmarkTitle_i").setAttribute('disabled');
    		<?php if($b->getTopic()->countRelatedTopics()>1): ?>
    		Y.one("#relatedT_fs").all('input').removeAttribute('disabled');
    		<?php endif;?>
    	});
    	<?php endif;?>
  
    	Y.one("#newT_description_i").on('blur', function(e) {
    		if (this.get('value').length>255) {
    			alert('La description est trop longue ('+this.get('value').length+' caractères).\nLe nombre de caractères max autorisé est de 255.');
    			this.focus();
    		}		 	
     	});
    	<?php if($b->hasId() && $b->getTopic()->countRelatedTopics()>1): ?>
    	Y.one("#b_t_imode_i_o4").on('click', function(e) {
    		Y.one("#existingT_i").setAttribute('disabled',true);
    		Y.one("#newT_fs").all('input').setAttribute('disabled');
    		Y.one("#newT_fs").all('textarea').setAttribute('disabled');
    		Y.one("#newT_fs").all('select').setAttribute('disabled');
    		Y.one("#relatedT_fs").all('input').removeAttribute('disabled');
    	});    	
    	<?php endif;?>
    	<?php if ($b->getId()) : ?>
    	Y.one("#task_i_o2").on('click', function(e) {
			if (!confirm('Suppression définitive de la ressource ?')) {
				e.preventDefault();
			}
    	});
    	<?php endif;?>
    });
});
</script>
</body>
</html>