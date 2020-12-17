<?php

// require_once "functions/backup.php";
// exit;

session_start();
$_SESSION['last_visit'] = time();
// read cofig file
require_once "functions/spyc.php";
$config = spyc_load_file('config.yaml');

////////////////////////////////////////// CONFIG //////////////////////////////////////////
define('DS',                DIRECTORY_SEPARATOR);
define('HTTP_HOST',         $_SERVER['REQUEST_SCHEME'].':'.DS.DS.$_SERVER['HTTP_HOST']);
// define('HTTP_HOST',         sprintf("%s://",isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . $_SERVER['HTTP_HOST']); // https:// domain.tld
define('APP_FOLDER',        basename(__DIR__)); // picoDocs
define('APP_URL',           HTTP_HOST.DS.APP_FOLDER);
define('HOME_PATH',         dirname($_SERVER['SCRIPT_FILENAME'])); // /www/htdocs/w034e9d5/domain.de/APP_FOLDER
define('CURRENT_URL',       pathinfo($_SERVER['REQUEST_URI'], PATHINFO_DIRNAME).DS.pathinfo($_SERVER['REQUEST_URI'], PATHINFO_FILENAME));



define('APP_NAME',          $config['App_Name']);
define('FILE_FOLDER',       $config['Doc_Folder']);
define('THEME_FOLDER',      $config['Theme_Folder']);
define('TEMPLATE_FILE',     $config['Template_File']);
define('TEMPLATE_CSS',      $config['Template_Style']);
define('EXTENSION',         $config['Doc_Extension']);
define('INVISIBLE_FILES',   $config['Invisible_Files']);
define('PARSER',            'functions/md_parser.php');
define('EASYMDE_CSS',       APP_URL.DS.'functions/easyMDE/easymde.css');
define('EASYMDE_JS',        APP_URL.DS.'functions/easyMDE/easymde.js');
define('USER',              $config['User']);
define('SESSION_TIMEOUT',   $config['Session_Timeout']); // 1800 Sek = 30 Minuten
define('DEBUGGING',         $config['Show_Debug_Aray']);
define('HTML_PURIFIER',     $config['Use_HTML_Purifier']);

define('IMAGE_PATH',        (is_dir(str_replace(DS.APP_FOLDER, FILE_FOLDER,CURRENT_URL)) ? str_replace(DS.APP_FOLDER, APP_FOLDER.DS.FILE_FOLDER,CURRENT_URL) : dirname(str_replace(DS.APP_FOLDER, APP_FOLDER.DS.FILE_FOLDER,CURRENT_URL))).DS.'images');

// $pfad = str_replace(DS.APP_FOLDER, FILE_FOLDER, CURRENT_URL);
// if(is_dir($pfad)){
//     echo 'TRUE<br>';
//     echo $pfad;
// }else{
//     echo 'FALSE<br>';
//     echo $pfad;
// }

$invisible_folders        = $config['Invisible_Folders'];

// add all privat folder to forbidden folder
foreach (USER as $user => $folder){
    if(is_array($folder['Private_Folder'])){
        foreach ($folder['Private_Folder'] as $key => $value) {
            $private_folders[] = $value;
        }
    }else{
        $private_folders[] = $folder['Private_Folder'];
    }
}
// merge the forbidden with the private folder
$forbidden_folders = array_merge($invisible_folders,$private_folders );
////////////////////////////////////////// CONFIG //////////////////////////////////////////


// session_destroy();
// pprint($_SESSION,'$_SESSION');
// pprint(USER);
// pprint($invisible_folders);
// pprint($invisible_folders,'$invisible_folders');
// pprint($private_folder_value);


////////////////////////////////////////// LOGIN //////////////////////////////////////////
// timeout
if (!isset($_SESSION['last_visit'])) {$_SESSION['last_visit'] = time();}
if ((time() - $_SESSION['last_visit']) > SESSION_TIMEOUT){session_destroy(); header('Location: '.APP_URL);}
// Logout
if (isset($_GET['logout']) ){session_destroy(); header('Location: '.APP_URL);} // AND $_SESSION['eingeloggt'] == true
// Login
if (isset($_POST['Name']) and $_POST['Name'] != "" and isset($_POST['Passwort']) and $_POST['Passwort'] != "") {
    // check password
    if ((USER[$_POST['Name']]['Password'] == $_POST['Passwort']) ){
        $_SESSION['Name']           = $_POST['Name'];
        $user_name                  = $_SESSION['Name'];
        $_SESSION['eingeloggt']     = true;
    }else{// if PW is false
        $_SESSION['eingeloggt']    = false;
        session_destroy();
        header('Location: '.APP_URL);
    }
}
// logged in
if($_SESSION['eingeloggt'] == true){
    // set username for template
    $user_name = $_SESSION['Name'];
    // remove the private folder of user from the forbidden folders
    foreach (USER[$user_name]['Private_Folder'] as $key => $value) {
        if (($private_folder_value = array_search(USER[$user_name]['Private_Folder'][$key], $forbidden_folders)) !== false) {
            unset($forbidden_folders[$private_folder_value]);}
    }
}
// define FORBIDDEN_FOLDERS without the privat_folders
define('FORBIDDEN_FOLDERS',  $forbidden_folders);
////////////////////////////////////////// LOGIN //////////////////////////////////////////



////////////////////////////////////////// IMAGE UPLOAD //////////////////////////////////////////
if(isset($_GET['img_upload']) && $_SESSION['eingeloggt'] == true){    // $_GET['img_upload'] looks like this: folder/folder/images
    // check if folder exists and if not, create it
    if (!file_exists('../'.$_GET['img_upload'])){
        mkdir('../'.$_GET['img_upload'], 0777, TRUE);
        $folder_created = 'Folder created';
    }
    // upload image file
    if(isset($_FILES['image'])) {
        // replace whitespaces
        $_FILES['image']['name'] = str_replace(' ', '_', $_FILES['image']['name']);
        $file_path               = $_GET['img_upload'].'/'.$_FILES['image']['name'];

        // output for debugging
        file_put_contents('docs/error.md', print_r(array($_FILES, $file_path, $_GET, $folder_created), true));
        // file_put_contents('docs/error.md', "<code>".print_r(array($_FILES, $_GET,$file_path)."</code>", true));

        // error message if file is not uploaded
        if(!is_uploaded_file($_FILES['image']['tmp_name'])) {
            echo '{"error": "file NOT uploaded"}';
            http_response_code(400);
            exit;
        }
        // send filepath as response
        if (move_uploaded_file($_FILES['image']['tmp_name'], '../'.$file_path)) {
            echo '{"data": {"filePath": "'.$file_path.'"}}';
            http_response_code(200);
            exit;
        }
    }
}
////////////////////////////////////////// IMAGE UPLOAD //////////////////////////////////////////



if (isset($_GET['backup']) ){
    $case = 'backup';

        $_GET['file'] = 'backup';
    require_once "functions/backup.php";


}

////////////////////////////////////////// SEARCH //////////////////////////////////////////
if (isset($_GET['search'])){
    $search_start = microtime(true);
    $case = 'search';
    // get the searchstring
    $needle = trim((isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : 'PicoDocs');
    // get all files as array
    $dir_iterator = new RecursiveDirectoryIterator(FILE_FOLDER);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    // iterate over each file
    foreach ($iterator as $filename){
        // find the content files with the right extension
        if (substr($filename, -3) == EXTENSION && substr($filename, -9) != 'search'.EXTENSION){
            // iterate over each line in file
            foreach (file($filename) as $file_row => $row_content){
                // find searchstring in row_content
                if (stripos($row_content, $needle) !== false){
                    $row_content = str_replace($needle, '<span class="red">'.$needle.'</span>', $row_content);
                    str_replace('A', 'B', $row_content);
                    // make filename nicer
                    $filename = str_replace(array(FILE_FOLDER.DS, 'index'.EXTENSION, EXTENSION), '', $filename);
                    $filename = (substr($filename, -1) == '/') ? substr($filename, 0,-1) : $filename;
                    // get the search results
                    $results .=   '##### ['.$filename.']('.APP_URL.DS.$filename.'): '.($file_row + 1).PHP_EOL;
                    $results .=   $row_content.PHP_EOL;
                    // count results
                    $i++;
                }
            }
        }
    }
    $search_content = '# '.$i.' Ergebnisse für: '.$needle.PHP_EOL;
    $search_content .= $results;
    $search_size = strlen($search_content);
    $_GET['file'] = 'search';
    $search_duration = microtime(true) - $search_start;
}
////////////////////////////////////////// SEARCH //////////////////////////////////////////


////////////////////////////////////////// CONTENT //////////////////////////////////////////
// set index for rootpage
if(!isset($_GET['file'])){$_GET['file'] = 'index';}
if(isset($_GET['file'])) {
    $filename = $_GET['file'];
    // delete the last "/"
    if(substr($filename, -1) == "/"){$filename = substr($filename, 0, -1);}
    // make pagename from last part of filename
    $pagename  = basename($filename);
    // if is dir, choose the index file
    if(is_dir(FILE_FOLDER.DS.$filename)){$filename = $filename.DS.'index';}
    // make url for the form action
    $file_url  = APP_URL.DS.$filename;
    // make path for form
    $file_path = FILE_FOLDER.DS.$filename.EXTENSION;
    // if new content via POST, save it to file
    if(isset($_POST['new_content']) && $_SESSION['eingeloggt'] == true){@file_put_contents($file_path, $_POST['new_content']);}
    // delete file
    if(isset($_POST['delete_file']) && $_SESSION['eingeloggt'] == true){
        @unlink($file_path);
        // delete folder if it was the last file
        if(is_readable(dirname($file_path))&&count(scandir(dirname($file_path)))==2){
            rmdir(dirname($file_path));
        }
    }
    // if new file via POST, create the file
    if(isset($_POST['new_file']) && $_SESSION['eingeloggt'] == true) {
        // check if folder exists and if not, create it
        if (!file_exists(dirname($file_path))){mkdir(dirname($file_path), 0777, TRUE);}
        // create new file with META Information
        $datei = fopen($file_path,"w");
        $date = date('m.d.y');
        // $user_name = $_SESSION['Name'];
        fwrite($datei, "{META}\nAutor: \"".$user_name."\"\nDatum: \"".$date."\"\n{META}\n# $filename",100);
        fclose($datei);
    }
    // redirect to root, if there is no permission to the private (first) folder
    $first_folder = explode('/',   $file_path);
    if (in_array($first_folder[1], FORBIDDEN_FOLDERS)){$file_path = '/';}
    // read file content


    // if($_GET['file'] != 'search'){
    //     $file_content = @file_get_contents($file_path);
    //     $file_size    = @filesize($file_path);
    // }else{
    //     $file_content = $search_content;
    //     $file_size    = $search_size;
    // }

    if($_GET['file'] == 'search'){
        $file_content = $search_content;
        $file_size    = $search_size;
    }elseif ($_GET['file'] == 'backup') {
        $file_content = $html;
        $file_size    ='';
    }
    else{
        $file_content = @file_get_contents($file_path);
        $file_size    = @filesize($file_path);
    }


    // if no content show 404
    if($file_content == ''){
        $pagename = '404';
        // if authorised user, let him create the file
        if($_SESSION['eingeloggt'] == true){
            $file_content = <<<EOD
            <h1>404, Datei nicht gefunden</h1>
            <form class="button" method="POST" action="{$file_url}">
                <input type="hidden" name="new_file" value="{$file_path}">
                <input type="hidden" name="edit" value="{$file_path}">
                <button type="submit" class="button" >Datei erstellen</button>
            </form>
            EOD;
        }else{
            $file_content = '<h1>404, Datei nicht gefunden</h1>';
        }
    }
     // EDIT
     if(isset($_POST['edit']) && $_SESSION['eingeloggt'] == true) {
         // import link for HTML header
         $easyMDE  = "<link rel='stylesheet' href='".EASYMDE_CSS."'>\n\t\t<script src='".EASYMDE_JS."'></script>\n";
         // create form as content
         $content .= "<form method='POST' action=$file_url>";
         $content .= "<button class='easyMDE_Button delete' name='delete_file' value='$file_path' type='submit' /><i class='far fa-trash-alt'></i></button>";
         $content .= "<button class='easyMDE_Button save' type='submit' /><i class='far fa-save'></i></button>";
         $content .= "<textarea id='EasyMDE_field' name='new_content'></textarea>";
         $content .= "</form>";
    }
    // OUTPUT
    else{
         // parse file content
         require(PARSER);
         // button as link to edit file content
         $edit_link = <<<EOD
         <form class="button" method="POST" action="{$file_url}">
             <input type="hidden" name="edit" value="{$file_path}">
             <button type="submit" class="button" >edit</button>
         </form>
         EOD;
     }
}
////////////////////////////////////////// CONTENT //////////////////////////////////////////


////////////////////////////////////////// DIRTREE //////////////////////////////////////////
function dirtree_array($folder){
    // counter for $docnr
    static $i = 1;
    // if folder can be opend
    if($handle = opendir($folder)){
        // read folder content
        while(($file_name = readdir($handle)) !== false){
            // make dir_path for link
            $dir_path = str_replace(FILE_FOLDER,DS.APP_FOLDER,$folder);
            // cleanup filename
            if(!preg_match("#^\.#", $file_name))
            // make folder entry and call this function again for files
            if(is_dir($folder.DS.$file_name )){
                // jump over forbidden file or folder
                if (in_array($file_name, FORBIDDEN_FOLDERS)){continue;}
                // dreate file properties
                $file_properties[$file_name] = array(
                    "nr"     => $i++,
                    "path"   => $dir_path,
                    "name"   => $file_name,
                    "ext"    => "folder",
                    "childs" => dirtree_array($folder.DS.$file_name));
            }else{
                $ext = '.'.pathinfo($file_name, PATHINFO_EXTENSION);
                // read only files with allowed extensions
                if($ext != EXTENSION){continue;}
                $file_name = str_replace($ext,'',$file_name);
                // jump over forbidden file or folder
                if (in_array($file_name, INVISIBLE_FILES)){continue;}
                // dreate file properties
                $file_properties[$file_name] = array(
                    "nr"     => $i++,
                    "path"   => $dir_path,
                    "name"   => $file_name,
                    "ext"    => $ext,
                    "childs" => false);
            }
        }
        closedir($handle);
    }
    return $file_properties ;
}


function dirtree_html($dirtree_array){
    global $docnr,$current_nr;
    // start output collector var
    $html = "\n\t\t\t<ul>";
    // if there are files in the folder
    if(is_array($dirtree_array)){
        // run through the array
        foreach($dirtree_array as $folder => $file){
            // make nicer vars out of the array
            $docpath = $dirtree_array[$folder]['path']; // picoDocs/folder/folder
            $docname = $dirtree_array[$folder]['name']; // file
            $docnr   = $dirtree_array[$folder]['nr']; // file
            // make or clean vars for links
            $class   = '';
            $checked = '';
            $indicator = '';// indicator is just for debugging
            // if this is the current page, set the class
            if(CURRENT_URL == $docpath.DS.$docname){
                $class .= 'current ';
                // indicator is just for debugging
                $indicator .= 'C_';
                $current_nr = $docnr;
            }
            // does url starts with current url, then activate and check
            if (strpos(dirname(CURRENT_URL), ($docpath.DS.$docname)) === 0 || CURRENT_URL == $docpath.DS.$docname){$class .= 'active';  $checked = 'checked';  $indicator .= 'A_';}
            // make menu level for css
            $level = substr_count($docpath, '/');
            // indicator is just for debugging
            $indicator .= $level;
            // level_1 is always open
            if ($level == 1){$checked = 'checked';}
            // avoid to much level
            if ($level > 3) $level = 3;
            // indicator is just for debugging
            $indicator = '';
            // make the html
            // if we have a folder
            if($dirtree_array[$folder]['ext'] == 'folder'){
                // the checkbox in the input field have to know for whitch label the check is, so they get the same (randomly generated) id.
                $ID = rand(100, 999);
                // make label for expandeble menue
                $html .= "\n\t\t\t\t<li class='Level_$level childs $class'><input type='checkbox' id='#$ID' $checked><label for='#$ID'>";
                $html .= "\n\t\t\t\t\t<a href='$docpath/$docname'>$docname $indicator</a></label>";
                // call this function again to do the files
                $html .= dirtree_html($dirtree_array[$folder]['childs']);
                $html .= '</li>';
            }
            // if we have a file
            elseif($dirtree_array[$folder]['ext'] == EXTENSION){
                // make the list entry
                $html .= "\n\t\t\t\t<li class='Level_$level $class '><label class='empty' ><a href='$docpath/$docname'>$docname $indicator</a></label></li>";
            }
        }
    }
    // close the list
    $html .= "\n\t\t\t</ul>";
    return $html;
}
// start time measurement
$dirtree_start = microtime(true);
// make array of the file_folder
$dirtree_array = dirtree_array(FILE_FOLDER);
// sort array ASC
asort($dirtree_array);
// do the HTML for template
$dirtree = dirtree_html($dirtree_array);
// stop time measurement
$dirtree_duration = microtime(true) - $dirtree_start;
////////////////////////////////////////// DIRTREE //////////////////////////////////////////


////////////////////////////////////////// PREV & NEXT LINKS //////////////////////////////////////////
// start time measurement
$prev_next_links_start = microtime(true);

function prev_next_link($array, $key, $value){
    $results = array();
    if (is_array($array)){
        if (isset($array[$key]) && $array[$key] == $value){
            $results[] = $array;
        }
        foreach ($array as $subarray){
            $results = array_merge($results, prev_next_link($subarray, $key, $value));
        }
    }
    return $results;
}

$prev_link_array = prev_next_link($dirtree_array, 'nr', $current_nr-1);
if($prev_link_array){
    $prev_link = "<a class='prev_next_link left' href='{$prev_link_array[0]['path']}/{$prev_link_array[0]['name']}'><span class='fas fa-arrow-circle-left'></span> &nbsp;{$prev_link_array[0]['name']}</a>";
}
$next_link_array = prev_next_link($dirtree_array, 'nr', $current_nr+1);
// pprint($next_link_array);
if($next_link_array){
    $next_link = "<a class='prev_next_link right' href='{$next_link_array[0]['path']}/{$next_link_array[0]['name']}'>{$next_link_array[0]['name']} &nbsp;<span class='fas fa-arrow-circle-right'></span></a>";
}
// stop time measurement
$prev_next_links_duration = microtime(true) - $prev_next_links_start;
////////////////////////////////////////// PREV & NEXT LINKS //////////////////////////////////////////


////////////////////////////////////////// DEBUGGING //////////////////////////////////////////
if(DEBUGGING){
    $debug_array = array(
        // '$config'           => $config,
        '$debug_case'       => $debug_case,
        'IMAGE_PATH'        => IMAGE_PATH,
        'APP_NAME'          => APP_NAME,
        'HTTP_HOST'         => HTTP_HOST,
        'APP_FOLDER'        => APP_FOLDER,
        'APP_URL'           => APP_URL,
        'CURRENT_URL'       => CURRENT_URL,
        'HOME_PATH'         => HOME_PATH,
        'PATHINFO_DIRNAME'  => pathinfo($_SERVER['REQUEST_URI'], PATHINFO_DIRNAME),
        'PATHINFO_FILENAME' => pathinfo($_SERVER['REQUEST_URI'], PATHINFO_FILENAME),
        'USER'              => USER,
        '$private_folders'  => $private_folders,
        'FORBIDDEN_FOLDERS' => FORBIDDEN_FOLDERS,
        'INVISIBLE_FILES'   => INVISIBLE_FILES,
        '$current_nr'       => $current_nr,
        '$_SESSION'         => $_SESSION,
        'POST'              => $_POST,
        'GET'               => $_GET,
        '$dirtree_array'    => $dirtree_array,
    );
    // $debug_case = array('case' => "SHOW", '$file_path' => $file_path, '$file_url' => $file_url, '$filename' => $filename, '$pagename' => $pagename);
}
////////////////////////////////////////// DEBUGGING //////////////////////////////////////////


////////////////////////////////////////// TEMPLATE //////////////////////////////////////////
// load template file
require(THEME_FOLDER.DS.TEMPLATE_FILE);
////////////////////////////////////////// TEMPLATE //////////////////////////////////////////


////////////////////////////////////////// DEBUGGING //////////////////////////////////////////
function pprint($array, $file_name='array'){
    /**
     * Pretty print Arrays  - in collapsible and scrollable boxes
     *
     * @param Array to be printed
     * @param String as name of the printed box
     *
     * @return HTML non semantic...
     */
    $id = random_int(0, 999);
    echo '<style type="text/css">input#'.$id.':checked ~ div.ijwe {display: none;}</style>';
    echo '<br><div style="background-color:#b1b1b1; border: 1px solid #949494; width:96%;  margin: auto;">';
    echo '<input type="checkbox" id="toggle-'.$id.'" style="display:inline; width:15px; background-color:transparent;"><label style=" white-space:nowrap; clear: both; width:0px;" for="toggle-'.$id.'"><h3 style="display:inline; line-height: 2px; margin: 1em;">'.$file_name.':</h3></label>';
    echo '<div class="ijwe" style="background-color:#c4c4c4; overflow-y: scroll; padding: 0.3em; max-height:400px;"><pre><xmp>';
    print_r($array);
    echo '</xmp></pre></div>';
    echo '</div>';
}
////////////////////////////////////////// DEBUGGING //////////////////////////////////////////










?>
