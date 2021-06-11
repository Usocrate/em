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
			<li class="nav-item"><a class="nav-link" href="conso.php">Consommation</a></li>
			<li class="nav-item"><a class="nav-link" href="maintenance.php">Maintenance</a><li>
		</ul>
	</div>
</nav>