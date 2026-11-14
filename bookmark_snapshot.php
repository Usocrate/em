<?php
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
}
else {
	header ( 'Location:' . $system->getProjectUrl () );
	exit ();
}

$feedbackHtml = '';
if (isset ( $_POST ['task_id'] )) {
	ToolBox::formatUserPost ( $_POST );
	switch ($_POST ['task_id']) {
		case 'b_snapshot_upload' :
			$file = isset ( $_FILES ['snapshot_file'] ) ? $_FILES ['snapshot_file'] : array ();
			$fb = $b->storeSnapshotFromUploadedFile ( $file );
			header ( 'Location:' . $system->getBookmarkUrl ( $b ) );
			break;
	}
}

$quitUrl = empty ( $_SERVER ['HTTP_REFERER'] ) ? $system->getBookmarkUrl ( $b ) : $_SERVER ['HTTP_REFERER'];

$doc_title = 'Image de la ressource';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title; ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<?php echo $system->writeHeadCommonLinkTags(); ?>	
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="bookmarkEdit">
	<main>
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<?php if (isset($fb)) echo $fb->messageToHtml (); ?>
		<section>
			<form id="snapshot_upload_f"
				action="<?php echo ToolBox::toHtml($system->getBookmarkSnapshotEditorUrl($b)) ?>"
				method="post" enctype="multipart/form-data">
				<div class="mb-3">
					<label class="form-label" for="snapshot_file_i">Fichier image
						(JPEG, PNG, GIF ou WebP, 5 Mo maximum)</label> <input
						class="form-control" id="snapshot_file_i" name="snapshot_file"
						type="file" accept="image/jpeg,image/png,image/gif,image/webp"
						required="required" />
				</div>
				<div class="buttonBar">
					<a class="btn btn-link" href="<?php echo ToolBox::toHtml($quitUrl) ?>">Quitter</a>
					<button id="task_i_o1" name="task_id" type="submit"
						value="b_snapshot_upload" class="btn btn-primary">Téléverser</button>
				</div>
			</form>
		</section>
	</main>
</body>
</html>
