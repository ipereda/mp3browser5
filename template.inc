<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link rel="icon" type="image/png" href="images/favicon.png" />
<title>MP3 Web Browser with HTML5 Player</title>
<meta name="robots" value="noindex" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!-- Alertify CSS -->
<link rel="stylesheet" type="text/css" href="css/alertify.css" /> 
<!-- MP3 Web Browser CSS -->
<link rel="stylesheet" type="text/css" href="css/mp3browser5.css" />
<!-- SoundManager2 Bar-UI CSS -->
<link rel="stylesheet" type="text/css" href="css/bar-ui.css" />
<!-- Spinner js -->
<script type="text/javascript" src="scripts/spin.min.js"></script>
<!-- Alertify js -->
<script type="text/javascript" src="scripts/alertify.min.js"></script>
<!-- SoundManager API -->
<script type="text/javascript" src="scripts/soundmanager2-nodebug-jsmin.js"></script>
<!-- MP3 Web Browser js -->
<script type="text/javascript" src="scripts/mp3browser5.js"></script>
<!-- SoundManager Bar-UI js -->
<script type="text/javascript" src="scripts/bar-ui.js"></script>
</head>

<body class="body">
<!-- BEGIN: Sticky Header -->
<div id="header_container">
    <div id="header_content">
		<div id="itemtitle"></div>
    </div>
</div>		
<!-- END: Sticky Header -->

<!-- BEGIN: Page Content with The HTML5 Page Player -->
<div id="container">
	<div align=center id="cover"></div>
    <div id="content">
		<div class=informa>Initializing...</div>
	</div>
</div>
<!-- END: Page Content -->

<!-- BEGIN: The HMTL5 Bar-UI Player for selected songs or album -->
<div id="player_container">
	<div align=center id="myplayer">%myplayer%</div>
</div>
<!-- END: Bar-UI Player -->

<!-- BEGIN: Sticky Footer -->
<div id="footer_container">
    <div id="footer_content">
        <div align="left" id="breadcrumb"></div>
	</div>
</div>
<!-- END: Sticky Footer -->
</body>
</html>