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
		const apiUrl = '<?php echo $system->getApiUrl() ?>';
		
		document.addEventListener("DOMContentLoaded", function () {
			customElements.define("bookmark-url-input", BookmarkUrlInputElement, { extends: "input" });
			customElements.define("bookmark-publisher-input", BookmarkPublisherInputElement, { extends: "input" });

		    function checkBookmarkDescriptionLength(e) {
		        const descriptionInput = document.getElementById("b_description_i");
		        if (descriptionInput.value.length > 255) {
		            e.preventDefault();
		            alert(`La description est trop longue (${descriptionInput.value.length} caractères).\nLe nombre de caractères autorisé est 255.`);
		            descriptionInput.focus();
		        }
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
		        fetch(`json/virtualBookmark.php?url=${encodeURIComponent(document.getElementById("b_url_i").value)}`)
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
		    document.getElementById("b_description_i").addEventListener("change", checkBookmarkDescriptionLength);
		    document.getElementById("b_creation_f").addEventListener("submit", checkBookmarkDescriptionLength);
		});
	</script>
</body>
</html>
