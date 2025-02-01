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

/**
 * nouvelle ressource
 */
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

$targetTopic = isset($_REQUEST['topic_id']) ? $system->getTopicById($_REQUEST['topic_id']) : $system->getMainTopic();

if (isset($_POST['cmd'])) {
    ToolBox::formatUserPost($_POST);
    switch ($_POST['cmd']) {
        case 'create':
            $b->hydrate($_POST, 'bookmark_');
			$b->setTopic(new Topic($_POST['topic_id']));	
            if ($b->getUrl() && $b->getTitle()) {
                $b->toDB();
				$b->getSnapshot();
            }
            header( 'Location:' . $system->getTopicUrl( $b->getTopic() ) );
            exit;
    }
}

$doc_title = 'Nouvelle ressource';

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
	<script src="js/bookmark-url-input.js"></script>
	<script src="js/bookmark-type-input.js"></script>
	<script src="js/bookmark-publisher-input.js"></script>
</head>
<body id="bookmarkCreation">
	<main>
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<div>
			<form id="b_create_f" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
				<input type="hidden" name="topic_id" value="<?php echo $targetTopic->getId() ?>"></input>
				<div class="row">
					<div class="col-lg-6">
						<section id="b_url_s">
							<div class="mb-3">
								<label class="form-label" for="b_url_i">URL</label> <input id="b_url_i" name="bookmark_url" type="url" is="bookmark-url-input" value="<?php echo ToolBox::toHtml($b->getUrl()) ?>" size="35" maxlength="255" class="form-control" />
							</div>
						</section>
						<section>
							<div class="mb-3">
								<label class="form-label" for="b_title_i">Intitulé</label> <input id="b_title_i" type="text" size="35" name="bookmark_title" value="<?php echo ToolBox::toHtml($b->getTitle()) ?>" class="form-control" />
							</div>
						</section>							
						<section>
							<div class="mb-3">
								<label class="form-label" for="b_author_i">Auteur</label> <input id="b_author_i" type="text" size="35" maxlength="255" name="bookmark_creator" value="<?php echo ToolBox::toHtml($b->getCreator()) ?>" class="form-control" />
							</div>
							<div class="mb-3">
								<label class="form-label" for="b_publisher_i">Editeur</label> <input id="b_publisher_i" type="text" is="bookmark-publisher-input" name="bookmark_publisher" size="35" maxlength="255" value="<?php echo ToolBox::toHtml($b->getPublisher()) ?>" class="form-control" />
							</div>
						</section>
					</div>
					<div class="col-lg-6">
					<section>
						<div class="mb-3">
							<label class="form-label" for="b_description_i">Description</label>
							<textarea id="b_description_i" name="bookmark_description" cols="25" rows="11" class="form-control"><?php echo ToolBox::toHtml($b->getDescription()) ?></textarea>
						</div>
					</section>
					</div>
				</div>
	
				<div class="buttonBar">
					<a class="btn btn-link" href="<?php echo isset($targetTopic) ? $system->getTopicUrl($targetTopic) : $system->getHomeUrl() ?>">quitter</a>
					<button name="cmd" type="submit" value="create" class="btn btn-primary">inscrire</button>
				</div>

			</form>
		</div>
	</main>
	<script>
	$(document).ready(function(){
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
			if (value !== null && value !== undefined && value.length>0 && value !== i.val()) {
		        if ($('#'+sid)) {
		        	$('#'+sid).slideUp('slow').remove();
		        }
		        var html = '<div id="'+sid+'" class="alert alert-primary suggestion"><small>Suggestion</small><p>'+value+'</p><div><button type="button" value="'+value+'">Accepter</button></div></div>';
		        i.after(html);
				$('#'+id).slideDown('slow');
		        $('#'+sid+' button').each(function() {
		    	    $(this).click(function () {
		    	    	i.val($(this).val());
		    	    	i.focus();
						$('#'+sid).slideUp('slow').remove();
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
		
		$("#b_url_i").change(removeFormerSuggestions);
		$("#b_url_i").change(suggestMetaDataFromUrl);
				
		$("#b_description_i").change(checkBookmarkDescriptionLength);
		$("#b_creation_f").on("submit",checkBookmarkDescriptionLength);
		$("#b_creation_f").on("submit",checkTopicDescriptionLength);
	});
</script>
<script type="text/javascript">
	const apiUrl = '<?php echo $system->getApiUrl() ?>';
	
	document.addEventListener("DOMContentLoaded", function() {
		customElements.define("bookmark-url-input", BookmarkUrlInputElement, { extends: "input" });
		customElements.define("bookmark-type-input", BookmarkTypeInputElement, { extends: "input" });
		customElements.define("bookmark-publisher-input", BookmarkPublisherInputElement, { extends: "input" });
	});
</script>
</body>
</html>
