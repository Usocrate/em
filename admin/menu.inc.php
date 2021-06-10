<nav class="navbar navbar-dark bg-dark navbar-expand-lg">
	<a class="navbar-brand" href="<?php echo $system->getProjectUrl() ?>/admin"><?php echo ToolBox::toHtml($system->getProjectName()) ?> <small>Admin</small></a>
	<button class="navbar-toggler" type="button" data-toggle="collapse"
		data-target="#navbarSupportedContent"
		aria-controls="navbarSupportedContent" aria-expanded="false"
		aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>

	<div class="collapse navbar-collapse" id="navbarSupportedContent">
		<ul class="navbar-nav mr-auto">
			<li class="nav-item"><a class="nav-link" href="config.php">Configuration</a></li>
			<li class="nav-item"><a class="nav-link" href="import.php">Import.</a></li>
			<li class="nav-item"><a class="nav-link" href="netscape-bookmark-file-1.php">Export.</a></li>
			<li class="nav-item"><a class="nav-link" href="user_edit.php" class="explicit">Nouvel utilisateur</a></li>
			<li class="nav-item"><a class="nav-link" href="about.php">Consommation</a></li>
			<li class="nav-item"><a class="nav-link" href="<?php echo ToolBox::toHtml('javascript:{popup=window.open("'.Bookmark::getEditionUrl(null,true).'?bookmark_url="+encodeURI(document.URL),"'.$system->getProjectName().'\+\+","height=550,width=1024,screenX=100,screenY=100,resizable");popup.focus();}') ?>">Lien à enregistrer</a></li>
			<li class="nav-item"><a class="nav-link" href="https://www.google.com/webmasters/tools/dashboard?hl=fr&amp;siteUrl=<?php echo urlencode($system->getProjectUrl()) ?>">Google</a></li>
			<li class="nav-item"><a class="nav-link" href="info.php">phpinfo</a></li>
			<li class="nav-item"><a class="nav-link" href="labo.php">labo</a></li>
			<li class="nav-item"><a class="nav-link" href="forgottenbookmarks.php">Ménage</a><li>
		</ul>
	</div>
</nav>