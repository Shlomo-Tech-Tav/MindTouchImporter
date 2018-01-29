<body>
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-32429827-1', 'auto');
		ga('send', 'pageview');
	</script>
	<!-- Fixed navbar -->
	<div class="navbar navbar-default navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand logo" href="<?php echo ABSURL; ?>" title="<?php echo PROJECT_NAME; ?>"><img src="<?php echo ABSURL; ?>includes/images/mt-importer-logo.png" alt="<?php echo PROJECT_NAME; ?> Logo"><span class="logo-title">MindTouch Importer</span></a>
			</div>
			<div id="navbar" class="navbar-collapse collapse">
<?php echo $menu; ?>
			</div><!-- End navbar -->
		</div>
	</div>

	<div class="container">
