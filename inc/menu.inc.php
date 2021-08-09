<nav class="navbar navbar-dark bg-dark navbar-expand-lg">
	<a class="navbar-brand" href="<?php echo $system->getProjectUrl() ?>"><?php echo ToolBox::toHtml($system->getProjectName()) ?></a>
	<button class="navbar-toggler" type="button" data-toggle="collapse"
		data-target="#navbarSupportedContent"
		aria-controls="navbarSupportedContent" aria-expanded="false"
		aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>

	<div class="collapse navbar-collapse" id="navbarSupportedContent">
		<ul class="navbar-nav mr-auto">
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
		<form method="post" action="search.php"	class="form-inline my-2 my-lg-0" role="search">
				<input type="hidden" name="bookmark_newsearch" value="1">
				<input class="form-control mr-sm-2" type="search" id="b_keywords_input"	name="bookmark_keywords">
				<button type="submit" class="btn btn-outline-primary my-2 my-sm-0">chercher</button>
		</form>
		<?php //echo $system->getBookmarkSearchHistory()->toHtml() ?>
	</div>
</nav>
<script>
$(document).ready(function(){
	if ($('#b_search_history') != null) {
    	$('#b_search_history button').removeClass('jsContingent').each(function() {
	    	$(this).click(function () {
		    	$('#b_keywords_input').val($(this).val());
		    	$('#b_keywords_input').focus();
	        });
	    });
	}
	<?php if ($system->isUserAuthenticated()) : ?>
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(p){
			$('a.hitTrigger').each(function(){
				$(this).attr('href', $(this).attr('href')+'&latitude='+p.coords.latitude+'&longitude='+p.coords.longitude);
			});
		});	
	}
	<?php endif; ?>
});
</script>