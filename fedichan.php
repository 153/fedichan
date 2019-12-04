
<?php
/*
            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
                   Version 2, December 2004
 
Copyright (C) 2004 Sam Hocevar <sam@hocevar.net>

Everyone is permitted to copy and distribute verbatim or modified
copies of this license document, and changing it is allowed as long
as the name is changed.
 
           DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

0. You just DO WHAT THE FUCK YOU WANT TO.
*/

header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
error_reporting(0);
$instance = (isset($_GET['instance']) ? htmlentities($_GET['instance']) : false);
$id_thread = (isset($_GET['thread']) ? htmlentities($_GET['thread']) : false);
$id_user = (isset($_GET['user']) ? htmlentities($_GET['user']) : false);
$next = (isset($_GET['next']) ? htmlentities($_GET['next']) : false);
$ajax = (isset($_GET['load']) && $_GET['load'] ? true : false);
$anonymous = (isset($_GET['anonymous']) && $_GET['anonymous'] ? true : false);

$srv = ($instance ? $instance : "blob.cat");
$log = "";

if ($id_thread) {
    $time = time();
    $t = 0;

    $elem = json_decode(file_get_contents("https://$srv/api/v1/statuses/" . $id_thread) , true);

    if (is_null($elem['id'])) {
        die("Couldn't fetch data.");
    }

    $threads[$t] = array();
    $context = json_decode(context($elem['id']) , true);
    foreach ($context['ancestors'] as $item) {
        $threads[$t][] = $item;
    }

    $threads[$t][] = $elem;

    foreach ($context['descendants'] as $item) {
        $threads[$t][] = $item;
    }

}
elseif ($id_user) {
    $time = time();
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://$srv/api/v1/accounts/$id_user/statuses?limit=50&exclude_replies=true" . ($next ? "&max_id=$next" : ""));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    $array = array_reverse(json_decode($result, true));

    if (is_null($array[0]['id'])) {
        die("Couldn't fetch data.");
    }

    $next = reset($array) ['id'];
    $thread = array();

    foreach ($array as $elem) {
        if (is_null($elem['reblog']) && is_null($elem['in_reply_to_id'])) {
            $thread[$elem['pleroma']['conversation_id']] = $elem;
        }
    }

    $thread = array_reverse($thread);

    $t = 0;
    $time = time();
    $threads = array();
    foreach ($thread as $th) {
        $threads[$t] = array();
        $context = json_decode(context($th['id']) , true);
        foreach ($context['ancestors'] as $item) {
            $threads[$t][] = $item;
        }

        $threads[$t][] = $th;

        foreach ($context['descendants'] as $item) {
            $threads[$t][] = $item;
        }
        $t++;
    }

}
else {
    $time = time();
    $curl = curl_init();
    //curl_setopt($curl, CURLOPT_URL,"https://$srv/api/v1/timelines/home?limit=100");
    //curl_setopt($curl, CURLOPT_USERPWD, "$acc:$pwd");
    curl_setopt($curl, CURLOPT_URL, "https://$srv/api/v1/timelines/public?limit=25&exclude_types[]=reblog" . ($next ? "&max_id=$next" : ""));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    //echo 'fetching posts'.(time()-$time);
    $array = array_reverse(json_decode($result, true));

    if (is_null($array[0]['id'])) {
        die("Couldn't fetch data.");
    }

    $next = reset($array) ['id'];
    $thread = array();

    foreach ($array as $elem) {
        if ($elem['reblog'] == null) {
            $thread[$elem['pleroma']['conversation_id']] = $elem;
        }
    }

    $thread = array_reverse($thread);

    $t = 0;
    $time = time();
    foreach ($thread as $th) {
        $tm = time();
        if (is_null($th['in_reply_to_id'])) {
            $threads[$t] = array(
                $th
            );
        }
        else {
            $threads[$t] = array();
            $context = json_decode(context($th['id']) , true);
            foreach ($context['ancestors'] as $item) {
                $threads[$t][] = $item;
            }

            $threads[$t][] = $th;

            foreach ($context['descendants'] as $item) {
                $threads[$t][] = $item;
            }
        }
        //  echo '<br>'.$t.':'.(time()-$tm);
        $t++;
    }
    //echo '<br>getting contextes '.(time()-$time);
    
}
if (!$ajax):
?>
<head>
    <script src="jquery.min.js"></script>
    <title>Fedichan<?php echo ($id_thread ? " - Thread View" : ""); ?></title>
    <style type="text/css">
        * {
            font-family: arial,helvetica,sans-serif;
            font-size: 11pt;
            color: maroon;
        }
        
        body {
            background: #ffe url(fade.png) top center repeat-x;
        }
        
        .replies a{
            font-size: 9pt;
        }
        
        .post_info{
            font-weight:bold;
            color:#117743;
        }
        
        .file{
            float:left;
            margin-right:10px;
            margin-bottom:10px;
            display:block;
        }
        
        .img{
            width:200px;
        }
        
        .op{
            clear:both; 
            padding:5px; 
            margin:5px;
        }
        
        .reply{
            border: 1px solid #d9bfb7;
            display:table;
            background-color: #f0e0d6;
            min-width:600px;
            padding:10px;
            margin-bottom:5px;
            margin-top:5px;
        }
        
        .boardTitle {
            font-family: Tahoma,sans-serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -2px;
            margin-top: 0;
        }
        
        :target {
           background-color: #d9bfb7;
        }
    </style>
</head>

<body>
<div id="top"></div>
<?php echo "<a href='./" . ($instance ? "?instance=" . $instance : "") . "'> [ Index ]</a>" ?>
[ <a href="http://atonofcows.x10.mx/fedichan/?thread=9lmpPsRiuFZ4gaHqLY">Official Thread</a> ][ <a href="#bottom">Bottom</a> ] 
<br>
<center><div class='boardtitle'>FEDICHAN</div></center>
<br>
<hr style='clear:both; color:red;'>
<div id='wrapper'>
<?php
endif;
foreach ($threads as $th) {
    $thread_id = $th[0]['id'];
    $n = 1;
    $omit = 0;
    foreach ($th as $post) {

        $cnt = count($th);
        if ($n == 1 || $id_thread) {
            $load = true;
        }
        else {
            if ($cnt > 3) {
                if ($n > ($cnt - 3)) {
                    $load = true;
                }
                else {
                    $load = false;
                    $omit++;
                }
            }
            else {
                $load = true;
            }
        }

        $id = $post['id'];
        $posts[$id]['n'] = $n;
        $posts[$id]['id'] = $post['id'];
        $posts[$id]['url'] = $post['url'];
        $posts[$id]['date'] = date("d/m/y(D)H:i:s", strtotime($post['created_at']));
        $posts[$id]['thread_id'] = strtotime($post['created_at']);
        $posts[$id]['name'] = ($anonymous ? "Anonymous" : $post['account']['display_name']);
        foreach ($post['account']['emojis'] as $emoji) {
            $posts[$id]['name'] = str_replace(":" . $emoji['shortcode'] . ":", "<img src='" . $emoji['static_url'] . "' height=20 style='vertical-align: middle;'>", $posts[$id]['name']);
        }

        $posts[$id]['acct'] = ($anonymous ? substr(crc32($post['account']['display_name']),-3) : $post['account']['acct']);
        $posts[$id]['acct_url'] = $post['account']['url'];
        $posts[$id]['uid'] = $post['account']['id'];
        $posts[$id]['avatar'] = $post['account']['avatar'];
        $posts[$id]['title'] = $post['spoiler_text'];
        $posts[$id]['replies'] = array();
        $posts[$id]['load'] = $load;

        $text = strip_tags($post['content'], '<br><p>');
        $text = str_replace('<br />', ' <br>', $text);
        $text = preg_replace('/(\s+|^)@\S+/', '', trim($text));
        if ($load) {
            $text = str_replace('<br>', "\n", $text);
            $text = str_replace('<p>', "[p]", $text);
            $text = str_replace('</p>', "[/p]", $text);

            if (is_numeric(strpos(html_entity_decode($text) , ">"))) {
                $s = explode("\n", $text);
                unset($ln);
                foreach ($s as $line) {
                    if (is_numeric(strpos(html_entity_decode($line) , ">"))) {
                        $ln[] = str_replace(">", "<span style='color:green;'>>", html_entity_decode($line)) . "</span>";
                    }
                    else {
                        $ln[] = $line;
                    }

                }
                $text = implode("\n", $ln);
            }
            $text = str_replace("\n", "<br>", $text);
            $text = str_replace('[p]', "<p>", $text);
            $text = str_replace('[/p]', "</p>", $text);
            $text = urlparser($text);

            foreach ($post['emojis'] as $emoji) {
                $text = str_replace(":" . $emoji['shortcode'] . ":", "<img src='" . $emoji['static_url'] . "' height=30 style='vertical-align: middle;'>", $text);
            }
        }

        $posts[$id]['reply_to'] = $post['in_reply_to_id'];

        if ($load) {
            if ($posts[$id]['reply_to'] != $thread_id && $posts[$id]['reply_to'] != null) {
                $posts[$posts[$id]['reply_to']]['replies'][] = $posts[$id]['id'];

                $title = "title='" . ($anonymous ? "" : $posts[$posts[$id]['reply_to']]['acct']) . ": " . preg_replace('/(\s+|^)@\S+/', '', str_replace("'", "&#39;", strip_tags($posts[$posts[$id]['reply_to']]['content']))) . "'";

                $replypost = "<a style='color:navy' class='hoverlink' $title href='" . ($id_thread ? '' : "?thread=$thread_id" . ($instance ? "&instance=" . $instance : "")) . "#" . $posts[$id]['reply_to'] . "'>>>" . $posts[$posts[$id]['reply_to']]['n'] . "</a><br>";
            }
            else {
                $replypost = "";
            }
        }
        $posts[$id]['content'] = "$replypost $text";
        @$posts[$id]['file'] = $post['media_attachments'][0]['url'];
        foreach ($post['media_attachments'] as $f) {
            if (is_numeric(strpos($posts[$id]['content'], substr($f['description'], 0, 10)))) {
                $posts[$id]['content'] = substr($posts[$id]['content'], 0, strpos($posts[$id]['content'], substr($f['description'], 0, 10)));
            }
        }
        $posts[$id]['sensitive'] = $post['sensitive'];
        $n++;
    }

    $p = 0;
    foreach ($posts as $post) {
        if ($post['load']) {
            $class = 'reply';
            $file = "";
            $sources = "[<a target='_blank' href='https://iqdb.org/?url=".$post['file']."'>IQDB</a>][<a target='_blank' href='https://saucenao.com/search.php?db=999&url=".$post['file']."'>Saucenao</a>]";
            
            if ($post['file'] != null) {
                $file = "<div class='file'>
                    <a target='_blank' onClick='return false' href='" . $post['file'] . "'><img class='img' src='" . ($post['sensitive'] == false ? $post['file'] : "spoiler.png") . "'></a><br>
                    $sources                
                </div>";
            }

            if ($p == 0) {
                $class = 'op';
                $file = ($anonymous ? "<div class='file' style='background-color: #f0e0d6;'><img style='opacity: 0.4;' src='avatar.jpg' height=100></div>" : "<div class='file' style='background-color: #f0e0d6;'><img style='opacity: 0.4;' src='" . $post['avatar'] . "' height=100></div>");
                if ($post['file'] != null) {
                    $file = "<div class='file'>
                        <a target='_blank' onClick='return false' href='" . $post['file'] . "'><img class='img' src='" . ($post['sensitive'] == false ? $post['file'] : "spoiler.png") . "'></a><br>
                        $sources
                    </div>";
                }
                $query = http_build_query(array_filter(array(
                    'instance' => ($instance ? $instance : false) ,
                    'anonymous' => ($anonymous ? true : false) ,
                    'thread' => $post['id'],
                )));

            }

            $replies = "<span style='color:blue;'>&#9658;</span>";
            if ($p != 0) {
                foreach ($post['replies'] as $reply) {
                    $titler = "title='" . $posts[$reply]['acct'] . ": " . preg_replace('/(\s+|^)@\S+/', '', str_replace("'", "&#39;", strip_tags($posts[$reply]['content']))) . "'";
                    $replies .= " <a style='color:navy' $titler class='hoverlink' href='#" . $posts[$reply]['id'] . "'>>>" . $posts[$reply]['n'] . "</a>";
                }
            }

            echo "<div class='$class' id='" . $post['id'] . "'>
            $file
            <span class='post_info'><b style='color:#D41105'>" . (is_null($post['reply_to']) ? $post['title'] : "") . "</b>
            " . ($anonymous ? "
                        <span style='color: #117743;'> " . $post['name'] . "</span>
            </span> 
            <span>
                <span style='color:#cc1105;'>(" . $post['acct'] . ")</span>" : "
                        <a href='?user=" . $post['uid'] . ($instance ? "&instance=" . $instance : "") . "' style='color: #117743;   text-decoration:none;'> " . $post['name'] . "</a>
            </span> 
            <span>
                <a href='" . $post['acct_url'] . "' target='_blank' style='color:#cc1105; text-decoration:none;'>(@" . $post['acct'] . ")</a> " . "<img src='" . $post['avatar'] . "' height=20 style='vertical-align: middle;'>") . "
            </span>
            " . $post['date'] . " id:" . $post['thread_id'] . " 
            No. <a target='_blank' href='" . $post['url'] . "'>" . $post['n'] . "</a> 
            " . ($p == 0 && !$id_thread ? "<b><a target='_blank' href='?$query' style='color: blue;'>[See All]</a> </b>" : "") . " $replies
            <p>" . $post['content'] . "</p></div>
            ";
            echo ($omit > 0 && $p == 0 ? "<p><b>$omit</b> posts ommited. Click <b><a target='_blank' href='?$query' style='color: blue;'>[See All]</a> </b> to see the entire thread.</p>" : "");
        }
        $p++;
    }
    echo "<hr style='clear:both; color:red;'>";

    unset($post);
    unset($posts);
}

echo "</div>";
function context($post) {
    global $srv;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://$srv/api/v1/statuses/$post/context");
    //curl_setopt($curl, CURLOPT_USERPWD, "$acc:$pwd");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

function emoji($text, $size = 30, $instance) {
    $data = json_decode(file_get_contents("https://$instance/api/v1/custom_emojis") , true) [0]['url'];
    $u = explode("/", $data);
    array_pop($u);
    $url = implode("/", $u);

    $text = str_replace("http:", "http;", $text);
    $text = str_replace("https:", "https;", $text);
    $text = preg_replace('~:([a-z0-9_]+):~', "<img src='$url/$1.png' height=$size style='vertical-align: middle;'>", $text);
    $text = str_replace("http;", "http:", $text);
    $text = str_replace("https;", "https:", $text);

    return $text;
}

function urlparser($s) {
    $tweet = $s;
    $tweet = preg_replace("/(?<!=\")(\b[\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/", "<a target=\"_blank\" style='color:navy;' href=\"$1\">$1</a>", $tweet);

    return $tweet;
}

if (!$ajax):
    echo "<br><div class='reply' style='min-width:none; width:auto;'>[ <a href='#top'>Top</a> ] ";

    if (!$id_thread) {
        $query = http_build_query(array_filter(array(
            'instance' => ($instance ? $instance : false) ,
            'anonymous' => ($anonymous ? true : false) ,
            'next' => $next,
            'user' => ($id_user ? $id_user : false) ,
        )));

        echo "<a href='./" . ($instance ? "?instance=" . $instance : "") . "'> [ Index ]</a>";
        echo " <a href='?$query'> [ Next ]</a>";
    }
    else {
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        echo "<a href='./" . ($instance ? "?instance=" . $instance : "") . "'> [ Index ]</a><a href='{$actual_link}' id='replies' onClick='return false'> [ Refresh ]</a>";
    }
    echo "</div><div id='bottom'></div>";

    $query = http_build_query(array_filter(array(
        'instance' => ($instance ? $instance : false) ,
        'anonymous' => ($anonymous ? true : false) ,
        'user' => ($id_user ? $id_user : false) ,
        'load' => true,
        'thread' => $id_thread,
    )));

?>

<script type="text/javascript">
    $('body').on('click','#replies',function() {
        var id = $('.op').first().attr('id')
        $.get( "index.php?<?php echo $query; ?>", function( data ) {
          $('#wrapper').html( data );
        });
    }); 
    
    <?php/* if ($id_thread): 
    window.setInterval(function(){
        var id = $('.op').first().attr('id')
        $.get( "index.php?<?php echo $query; ?>", function( data ) {
          $('#wrapper').html( data );
        });
    }, 20000);
    endif;*/ ?>

    $('body').on('click','.img',function() {
        var width = $(this).css('width');
        var src = $(this).attr('src');
        var phref = $(this).parent('a').attr('href');

        if($(this).attr('alt') == 'spoiler'){
            $(this).attr('src','spoiler.png');
            $(this).attr('alt','');
        } else {
            if(src == 'spoiler.png'){
                $(this).attr('src',phref);
                $(this).attr('alt','spoiler');
            } 
        }
        
        if(width == '200px'){
            $(this).css('width','100%');
        } else {
            $(this).css('width','200px');
        }
    });
    
    $('body').on('mouseenter','.hoverlink',function() {
        var myHref = $(this).attr('href');
        $(myHref).css('background-color', '#d9bfb7');
        $(this).attr('alt','test');
    });
    $('body').on('mouseleave','.hoverlink',function() {
        var myHref = $(this).attr('href');
        $(myHref).css('background-color', '#f0e0d6');
    });
    </script>

</body>
<?php endif; ?>
