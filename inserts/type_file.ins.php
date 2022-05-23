<!DOCTYPE html>
<html>
<head>
<title>
$siteTitle
</title>
	<link rel="icon" href="favicon.ico">
	<link rel="stylesheet" type="text/css" href="css/styles.css" />
	<script src="js/jquery-1.7.1.min.js"></script>
	<script src="js/wordcount.js"></script>
	<script src="js/filesave.js"></script>
</head>
<body>

<textarea class="readBox" rows="4" cols="82" placeholder="Notes..."></textarea>
<br />
<br />
<textarea id="writingArea" class="writingBox" rows="12" cols="82" onPaste="return false" onCut="return false" onDrag="return false" onDrop="return false" autocomplete=off placeholder="Write content here..."></textarea>
<br />
<br />
<span id="wordCount" class="wordCounter" >0</span><br />
<div id="result">
<br />
<textarea id="saveName" class="writingBox" placeholder="File_name" rows="1" cols="3"></textarea><br /><br />
<button type="button" class="darkButton" onclick="download()">Download</button>
</div>
<br />

</body>
</html>
