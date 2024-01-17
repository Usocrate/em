<nav class="navbar navbar-dark bg-dark navbar-expand-lg">
<div class="container-fluid">
	<span class="navbar-brand"><a href="<?php echo $system->getProjectUrl() ?>"><?php echo ToolBox::toHtml($system->getProjectName()) ?></a>&nbsp;<a href="<?php echo $system->getProjectUrl() ?>/admin"><small>Admin</small></a></span>
	<button class="navbar-toggler" type="button" data-bs-toggle="collapse"
		data-bs-target="#navbarSupportedContent"
		aria-controls="navbarSupportedContent" aria-expanded="false"
		aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>

	<div class="collapse navbar-collapse" id="navbarSupportedContent">
		<ul class="navbar-nav me-auto">
			<li class="nav-item"><a class="nav-link" href="config.php">Configuration</a></li>
			<li class="nav-item"><a class="nav-link" href="conso.php">Consommation</a></li>
			<li class="nav-item"><a class="nav-link" href="maintenance.php">Maintenance</a><li>
			<li class="nav-item"><a class="nav-link" href="<?php echo $system->getLoginUrl(array('task_id'=>'anonymat')) ?>">Déconnexion</a></li>
		</ul>
	</div>
</div>
</nav>