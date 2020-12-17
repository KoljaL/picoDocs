<!DOCTYPE html>
<html lang="de">
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	    <meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?= APP_NAME.' - '.$pagename; ?></title>
		<link rel="shortcut icon" href="<?=APP_URL.DS?>favicon.ico">
		<link rel="icon" type="image/png" href="<?= APP_URL.DS.THEME_FOLDER.DS?>images/icons/favicon.png" sizes="32x32">
		<link rel="icon" type="image/png" href="<?= APP_URL.DS.THEME_FOLDER.DS?>images/icons/favicon.png" sizes="96x96">
		<?=$easyMDE?>
		<link rel="stylesheet" href="<?= APP_URL.DS.THEME_FOLDER.DS.TEMPLATE_CSS; ?>">
		<link href="<?= APP_URL.DS.THEME_FOLDER.DS;?>webfonts/Font_Awesome_Free_5.15.1.css" rel="stylesheet">


		<!-- HIGHLIGHT.JS -->
		<!-- <script src="theme/style/highlight.js"></script>
		<link rel="stylesheet" href="< ?= APP_URL.DS.THEME_FOLDER.DS?>atom-one-dark.css">
		<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/10.4.0/highlight.min.js"></script>
		<script>
		  document.addEventListener('DOMContentLoaded', (event) => {document.querySelectorAll('code').forEach((block) => {hljs.highlightBlock(block);});});
		  hljs.initHighlightingOnLoad();
		</script> -->
		<!-- HIGHLIGHT.JS -->

		<script type="text/javascript">

		</script>
	<body onload="ismobile()">
	<script>function ismobile() {if(window.innerWidth < 721){document.getElementById("state_sidebar_off").checked = true;}}</script>

    <!-- HIDDEN CHECKBOXES -->
    <input type="radio" name="sidebar" id="state_sidebar_on"  class="hide" checked>
    <input type="radio" name="sidebar" id="state_sidebar_off" class="hide" >
    <input type="radio" name="TOC"     id="state_toc_on"      class="hide" >
    <input type="radio" name="TOC"     id="state_toc_off"     class="hide" checked>
    <!-- HIDDEN CHECKBOXES -->

    <div class="wrapper">

		<div class="toggle_on_label">
			<label class="sidebar_on_toggle_label" for="state_sidebar_on">&#9776;</label>
	        <label class="toc_on_toggle_label"     for="state_toc_on">&#9776;</label>
		</div>


		<div class="content" id="content">
		<? if(DEBUGGING){pprint($debug_array,'$debug_array');}?>
		<!-- < ?=pprint($_SESSION)?> -->


		<?=$content?>
		<?=$prev_link?>
		<?=$next_link?>

		<div class="height100"></div>
		<?="<div class='content_footer'>
			<span>
			<b>Duration:</b>
			Dirtree: ".round($dirtree_duration*1000,2)."ms &nbsp;
			Parsedown: ".round($parsedown_duration*1000,2)."ms &nbsp;
			Prev-Next-Link: ".round($prev_next_links_duration*1000,2)."ms &nbsp;
			Filesize: ".round($file_size/1000,2)."kb &nbsp;"?>
			<?
			if($case =="search"){
				echo "Search: ".round($search_duration*1000,2)."ms &nbsp";
			}?>
			</span>
		</div>
		</div><!-- CONTENT -->



      <div class="sidebar">
        <label class="sidebar_off_toggle_label" for="state_sidebar_off"><i class="logo" style="position:fixed;top:15px; left:28px;"></i></label>
        <div id="header">
            <a href="<?=APP_URL?>" class="header_link"><?=APP_NAME?></a>
            <div class="searchbox">
                <form id="search_form" action="<?=APP_URL?>">
                    <input type="search" placeholder="Search docs" id="search_input" name="search" value="" /><input class="search_submit" type="submit" value="&#10095;&#10095;">
                </form>
            </div>
        </div>

        <div class="pageslist">
            <?= $dirtree; ?>
			<div class='height100'></div>
        </div>

        <div id="footer">

			<div id="login_up" class="overlay">
				<div class="login_up">
					<a class="close" href="#">&times;</a>
					<form action="<?=$_SERVER['SCRIPT_NAME']?>" method="POST">
					<input type="text" placeholder="Name" name="Name" value="">&nbsp;
					<input type="password" placeholder="Passwort" name="Passwort" value="">&nbsp;
					<input type="submit" class="button" value="&check;" />
					</form>
				</div>
			</div>


			<?php
			// echo $_SESSION['eingeloggt'] ;
			if ($_SESSION['eingeloggt'] == false) {echo "<a class='button' href='#login_up'>login</a>";}
			if ($_SESSION['eingeloggt'] == true) {
				$user_name = $_SESSION['Name'];
				echo $edit_link;
				// echo "<a href='".APP_URL.DS."functions/backup.php' class='button'>backup</a>";
				echo "<a href='?backup' class='button'>backup</a>";
				echo "<a href='?logout' class='button'>&nbsp;&nbsp;$user_name</a>";
			}
			?>
			<a href='https://github.com/KoljaL/picodocs' class='GH_icon'><img src='<?=APP_URL.DS.THEME_FOLDER.DS ?>images/icons/GitHub_20.png'></a>
        </div>
	</div><!-- SIDEBAR -->


    <div class="toc">
	    <label class="toc_off_toggle_label" for="state_toc_off">&#10006;</label>
        <br><br><br>
		<?=$meta_string?>
		<br><br>
        <?if(!empty($toc)) {echo $toc;}?>
    </div>

	<div class="toc_overlay"></div>

</div><!-- WRAPPER -->

<?if(isset($_POST['edit'])) {?>
	<script>
		new EasyMDE({
			autoDownloadFontAwesome: false,
			element: document.getElementById('EasyMDE_field'),
			uploadImage:true,
			imageUploadEndpoint: '<?= APP_URL.DS ?>?img_upload=<?=IMAGE_PATH?>',
			showIcons: ["code", "table", "strikethrough", "clean-block", "horizontal-rule",'upload-image'],
			hideIcons: ["fullscreen", "guide", "side-by-side"],
			initialValue: "<?=str_replace (array("\r\n", "\n", "\r"), ' \n', addslashes($file_content))?>"
		});
	</script>
	<script>
	</script>
<?}?>
	</body>
</html>
