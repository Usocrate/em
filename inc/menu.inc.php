<nav class="navbar navbar-dark bg-dark navbar-expand-lg">
	<div class="container-fluid">
		<a class="navbar-brand" href="<?php echo $system->getProjectUrl() ?>"><?php echo ToolBox::toHtml($system->getProjectName()) ?></a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse"
			data-bs-target="#navbarSupportedContent"
			aria-controls="navbarSupportedContent" aria-expanded="false"
			aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
	
		<div class="collapse navbar-collapse" id="navbarSupportedContent">
			<ul class="navbar-nav me-auto mb-2 mb-lg-0">
				<li class="nav-item"><a class="nav-link" href="<?php echo $system->getProjectUrl() ?>/mosthitbookmarks.php">Les	plus utiles</a></li>
				<li class="nav-item"><a class="nav-link" href="<?php echo $system->getProjectUrl() ?>/lastaddedbookmarks.php">Les nouveautés</a></li>
	    		<?php if ($system->isUserAuthenticated()) : ?>
	    			<li class="nav-item"><a class="nav-link" href="<?php echo Bookmark::getEditionUrl() ?>"	class="virtualBookmark">+</a></li>
					<li class="nav-item"><a class="nav-link" href="<?php echo $system->getProjectUrl() ?>/admin/index.php">Admin</a></li>
	    		<?php endif; ?>
	    		<?php if (!$system->isUserAuthenticated()) : ?>
	    			<li class="nav-item"><a class="nav-link"  href="<?php echo $system->getLoginUrl() ?>">Identification</a></li>
	    		<?php endif; ?>
			</ul>
			<form method="post" action="search.php"	class="d-flex" role="search">
				<input type="hidden" name="bookmark_newsearch" value="1">
				<input class="form-control me-2" type="search" id="b_keywords_input" name="bookmark_keywords">
				<button type="submit" class="btn btn-outline-primary">chercher</button>
			</form>
		</div>
	</div>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function() {
		<?php if ($system->isUserAuthenticated()) : ?>
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(function(p){
				const items = document.querySelectorAll('a.hitTrigger');
				for (let i of items) {
					i.setAttribute('href', i.getAttribute('href')+'&latitude='+p.coords.latitude+'&longitude='+p.coords.longitude);
				}
			});	
		}
		<?php endif; ?>
});
</script>