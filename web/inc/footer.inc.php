<footer>
	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<ul class="nav navbar-nav">
				<li><a href="<?php echo $system->getProjectUrl() ?>/mosthitbookmarks.php">Les plus utiles</a></li>
				<li>
					<a href="<?php echo $system->getProjectUrl() ?>/lastaddedbookmarks.php">Les nouveautés</a>
				</li>
    			<?php if ($system->isUserAuthenticated()) : ?>
    			<li><a href="<?php echo Bookmark::getEditionUrl() ?>" class="virtualBookmark">+</a></li>
    			<li><a href="<?php echo $system->getProjectUrl() ?>/lastfocusedbookmarks.php">Les dernières utilisées</a></li>
    			<?php endif; ?>
			</ul>
			<form method="post" action="search.php" class="navbar-form" role="search">
				<input type="hidden" name="bookmark_newsearch" value="1" />
				<div class="form-group">
					<label for="b_keywords_input" class="hidden">Critère de recherche</label><input id="b_keywords_input" name="bookmark_keywords" size="25" class="form-control" />
				</div>
				<button type="submit" class="btn btn-default">chercher</button>
                <?php echo $system->getBookmarkSearchHistory()->toHtml()?>
		</form>
		</div>
	</nav>
	<div>
		<span itemscope itemtype="http://schema.org/Person"> <img itemprop="image" src="https://www.gravatar.com/avatar/e8f48bba13f21816a4c930c1b31d6449.png?s=34" class="avatar" alt="" /> <a href="https://plus.google.com/116916311930652250173?rel=author" target="_blank"><strong itemprop="name">Usocrate</strong></a>
			<meta itemprop="url" content="https://plus.google.com/116916311930652250173?rel=author" />
		</span> <span><a href="<?php echo $system->getProjectUrl() ?>/about.php"><?php echo ' '.$system->getProjectLaunchYear() .'-'. date('Y'); ?></a></span><span> - </span> <span>
		<?php if (!$system->isUserAuthenticated()) : ?><a href="<?php echo $system->getLoginUrl() ?>">Identification</a><?php endif; ?>
		<?php if ($system->isUserAuthenticated()) : ?>
			<a href="<?php echo $system->getProjectUrl() ?>/admin/index.php">Admin</a> <span> - </span> <a href="<?php echo $system->getLoginUrl(array('task_id'=>'anonymat')) ?>">Se déconnecter</a>
		<?php endif; ?>
		</span>
	</div>
</footer>
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