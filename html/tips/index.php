<?php

# This script is the index.php for NigoroJr.com and the subdirectories of the 
# website.
# This script looks for a file named db.txt that contains the username and the 
# password of the database (in 2 lines) and then looks for a file that has the 
# extension .config for configuration. See the sample config file for usage.

# Install PSR-0-compatible class autoloader for Markdown
spl_autoload_register(function($class){
    require preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
});

set_include_path(get_include_path() . PATH_SEPARATOR . "/home/naoki/Src/php-markdown/");

# Get Markdown class
use \Michelf\Markdown;

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html" charset="utf-8">
<title>Tips</title>
<link rel="stylesheet" href="/styles/style.css" type="text/css" charset="utf-8">
<link rel="shortcut icon" href="/images/favicon.ico">
<!-- highlight.js -->
<link rel="stylesheet" href="/styles/highlight.js/styles/github.css">
<script src="/styles/highlight.js/highlight.pack.js"></script>
<script>
hljs.tabReplace = '    ';
hljs.initHighlightingOnLoad();
</script>
<?php
// Note: When changing file name, also change .htaccess
$file_handle = fopen("db.txt", 'r');
$db_username = rtrim(fgets($file_handle));
$db_password = rtrim(fgets($file_handle));
fclose($file_handle);
/* Connect to Database */
$db = mysql_connect("localhost", "$db_username", "$db_password");
mysql_select_db("NigoroJr", $db);
mysql_query("set names utf8");
?>
</head>

<body>
<nav id="nav">
  <ul>
    <li><a href="/tips">Tips</a></li>
    <li><a href="/apps">Apps</a></li>
    <li><a href="http://nigorojr.com:3000">Torch</a></li>
    <li style="float: right"><a href="mailto:nigorojr@gmail.com">Contact</a></li>
  </ul>
</nav>

<div id="header">
  <a href="/">
    <img src="/images/Logo.png" width="686" height="200" alt="NigoroJr Logo">
  </a>
</div>


<div id="my_body">
    <div id="contents_wrapper">
    <div id="main_contents">

<?php
$id = $_GET['id'];
$article_per_page = 5;
if (isset($_GET['page']))
    $page = $_GET['page'];
else
    $page = 1;
$offset = ($page - 1) * $article_per_page;
/* Print all the contents from the database */
$whole_articles_query = mysql_query("select * from articles where category = 'tips' order by date desc");
// Depending on the parameter, change query
if (isset($id))
    $rs = mysql_query("select * from articles where id = $id");
else
    $rs = mysql_query("select * from articles where category = 'tips' order by date desc limit $article_per_page offset $offset");

// When no article or false is returned.
if (!$rs or mysql_num_rows($rs) == 0) {
    print "<h1 style=\"text-align: center\">No such article!</h1>";
    return;
}
while ($arr = mysql_fetch_row($rs)) {
    $article_id = $arr[0];
    $title = $arr[1];
    $title = Markdown::defaultTransform($title);
    $content_file = $arr[2];
    $content = file_get_contents($content_file);
    $converted = Markdown::defaultTransform($content);
    $post_date = $arr[3];
    $edit_date = $arr[4];
    // This is just the raw username in Linux. Use table "authors" to convert 
    // them to a "screen name" that would actually be displayed.
    $author = $arr[5];
    $author_query_result = mysql_query("select screen_name from authors where author = '$author'");
    $screen_name = mysql_result($author_query_result, 0);
    if ($screen_name == "")
        $screen_name = $author;
    // Category is always "tips" in this case
    // $category = $arr[6];
    // Tags should be separated by ","s
    $tags = array_map('trim', explode(",", $arr[7]));
    $language = $arr[8];
?>
    <div class="contents">
    <!-- Add href to /$author/tips/$content_file (without the trailing ".md") -->
    <h1><a id="title" href="index.php?id=<?php print $article_id ?>"><?php print $title ?></a></h1>
    <p><?php
    /* "nohl" at the beginning of a code block means no syntax highlighting */
    $converted = str_replace('<code>nohl ', '<code class="no-highlight">', $converted);
    $converted = str_replace('<code>nohl', '<code class="no-highlight">', $converted);
    /* '<code>lang: java ' should become '<code class="java">' */
    $converted = preg_replace('/<code>lang(?:uage)?\:\s*(.*?) /', '<code class="$1"> ', $converted);
    print $converted;
?></p>
<?php
    print "Posted by <a href=\"/$author\">$screen_name</a>: " . $post_date;
    if ($post_date != $edit_date)
        print " (Edited: $edit_date)";
?>
    <a href="post.php?id=<?php print $article_id ?>" style="float: right">Edit</a>
    <hr>
    </div>
<?php
}   // End of while loop (one article)
?>
    <div id="change_page">
<?php
    // Only display when it's not a 1-article page (when no id is set)
    if ($page > 1 and !isset($id)) {
?>
        <a style="float: left" href="index.php<?php if ($page != 2) print "?page=" . ($page - 1) ?>">&lt;&nbsp;Prev</a>
<?php
    }
    else
        print "<span style=\"float: left\">&lt;&nbsp;Prev</span>";
?>
    <a href="/tips">Tips</a>
<?php

    // Only display when it's not a 1-article page (when no id is set)
    if ($page < (mysql_num_rows($whole_articles_query) / $article_per_page)  and !isset($id)) {
?>
        <a style="float: right" href="index.php?page=<?php print $page + 1 ?>">Next&nbsp;&gt;</a>
<?php
    }
    else
        print "<span style=\"float: right\">Next&nbsp;&gt;</span>";
?>
    </div>
    <!-- Improve the link!! -->
<br><br>
    <a style="float: right; list-style: none;" href="post.php">Post</a>

        </div>
      </div>

      <div id="footer">
        <div id="copyright">
          Copyright (C) 2013 NigoroJr.com All Rights Reserved
        </div>
      </div>
    </div>
  </body>
</html>
