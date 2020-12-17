<?php
$parsedown_start = microtime(true);



// Metainformation
if(substr($file_content, 6) != "{META}"){
    $teile        = explode("{META}", $file_content); // zerteile den String an "---"
    $yaml         = $teile[1]; // die Metaangaben stehen zwischen den ersten beiden "---"
    $file_content = str_replace('{META}'.$yaml.'{META}','',$file_content); // entferne die Metaangaben aus dem content
    $yaml         = ltrim($yaml); // entferne die erste leere Zeile
    $lines        =  substr_count( $yaml, "\n" ); // zähle die Zeilen
    // print_r("START\n".$yaml."END\n\n"); // debug
    $yaml         = str_replace("\r",'',$yaml); // entferne Zeilenumbrüche
    $yaml         = str_replace("\n",'',$yaml); // entferne Zeilenumbrüche
    for ($i=0; $i < $lines; $i++) { // für jede Zeile ein Durchlauf
        $dp   = explode(":", $yaml); // nimm den String vor dem ersten " : "
        $qu   = explode('"', $yaml); // nimm den String zwischen den ersten beiden " ' "
        $meta[$dp[0]] =  $qu[1]; // key und value für array
        $yaml = str_replace($dp[0].': "'.$qu[1].'"','',$yaml); // entferne den ersten Eintrag
    }
    if(is_array($meta)){
    	foreach ($meta as $key => $value) {
    		$meta_string .= '<b>'.$key.'</b>: '.$value.'<br>';
    	}
    }
}else{
    $meta_string ='';
}

// Markdown
// https://github.com/erusev/parsedown
// https://github.com/erusev/parsedown-extra
// https://github.com/taufik-nurrohman/parsedown-extra-plugin
require('parsedown/Parsedown.php');
require('parsedown/ParsedownExtra.php');
require('parsedown/ParsedownExtraPlugin.php');
require('parsedown/SecureParsedown.php');
$ParsedownExtraPlugin = new ParsedownExtraPlugin();
$ParsedownExtraPlugin ->setBreaksEnabled(true);
// $ParsedownExtraPlugin ->setMarkupEscaped(true);
$content =  $ParsedownExtraPlugin->text($file_content);



// Make output save against XSS
// https://github.com/ezyang/htmlpurifier
if(HTML_PURIFIER){
    require_once 'htmlpurifier/HTMLPurifier.auto.php';
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.AllowedElements', 'p, a, ul, ol, li, h1, h2, h3, h4, h5, h6, br, strong, embed, b, i');
    $config->set('HTML.AllowedAttributes', 'embed.src');
    $config->set('CSS.AllowedProperties', '');
    $config->set('AutoFormat.RemoveEmpty', true);
    $config->set('AutoFormat.AutoParagraph', true);
    $config->set('HTML.SafeEmbed', true);
    // May cause problems with empty table cells and headers
    $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
    $purifier = new HTMLPurifier($config);
    $content = $purifier->purify($content);
}




// TOC
// https://github.com/codepo8/TocIt/blob/master/tocit.php
preg_match_all("/<h([1-6])[^>]*>.*<\/h.>/Us",$content,$headlines);
$toc = '<ul>';
foreach($headlines[0] as $k=>$h){
    if(strstr($h,'id')===false){
        $x = preg_replace('/>/',' id="head_'.$k.'">',$h,1);
        $content = str_replace($h,$x,$content);
        $h = $x;
    };
    $link = preg_replace('/<(\/)?h\d/','<$1a',$h);
    $link = str_replace('id="','href="#',$link);
    if($k>0 && $headlines[1][$k-1]<$headlines[1][$k]){
        $toc.='<ul>';
    }
    $toc .= '<li>'.$link.'';
    if($headlines[1][$k+1] && $headlines[1][$k+1]<$headlines[1][$k]){
        $toc.='</li></ul></li>';
    }
    if($headlines[1][$k+1] && $headlines[1][$k+1] == $headlines[1][$k]){
        $toc.='</li>';
    }
}
$toc.='</li></ul>';

// time measurement
$parsedown_duration = microtime(true) - $parsedown_start;
// echo $toc; // debug
// echo $content; // debug
// print_r($meta); // debug
