<?php

# This script is the index.php for NigoroJr.com and the subdirectories of the 
# website.
# This script looks for a file named db.txt that contains the username and the 
# password of the database (in 2 lines) and then looks for a file that has the 
# extension .config for configuration. See the sample config file for usage.
# If there is at least 1 Markdown file (files that has the extension .md) in 
# the same directory as this file, it will display the content of the files, 
# sorted in alphabetical order (since that's the default behavior of glob). 
# The contents will be converted according to the Markdown rule (duh, it's a 
# Markdown file) using Michelf's php-markdown module.

# Install PSR-0-compatible class autoloader for Markdown
spl_autoload_register(function($class){
    require preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
});

set_include_path(get_include_path() . PATH_SEPARATOR . "/home/naoki/Src/php-markdown/");

# Get Markdown class
use \Michelf\MarkdownExtra;

# Read in personal configurations
$config_file = glob("*.config")[0];
if ($config_file) {
    # Extract Username from file name
    $username = explode(".", $config_file)[0];
    $user_config['username'] = $username;
    $config_file_handle = fopen($config_file, "r");
    while (($line = fgets($config_file_handle)) != false) {
        # Lines starting from '#' will be skipped
        if (substr($line, 0, 1) == "#")
            continue;
        $delimiter_pos = strpos($line, ":");
        $param = substr($line, 0, $delimiter_pos);
        $value = substr($line, $delimiter_pos + 1);

        # If the param is 'nav'
        if ($param == "nav") {
            $name = explode(",", $value)[0];
            $location = explode(",", $value)[1];
            $user_config[$param][$name] = $location;
        }
        else
            $user_config[$param] = $value;
    }
    fclose($config_file_handle);
}

if (isset($user_config['stylesheet']))
    $stylesheet = $user_config['stylesheet'];
else
    $stylesheet = "/styles/style.css";

if (isset($user_config['additional_stylesheet']))
    $additional_stylesheet = $user_config['additional_stylesheet'];

if (isset($user_config['logo']))
    $logo = $user_config['logo'];
else
    $logo = "/images/Logo.png";

if (file_exists("db.txt")) {
    // Note: When changing file name, also change .htaccess
    $file_handle = fopen("db.txt", 'r');
    $db_username = rtrim(fgets($file_handle));
    $db_password = rtrim(fgets($file_handle));
    fclose($file_handle);
    /* Connect to Database */
    $db = mysql_connect("localhost", "$db_username", "$db_password");
    mysql_select_db("NigoroJr", $db);
    mysql_query("set names utf8");
}

# Look for Markdown files. If there's at least 1, print them instead of 
# fetching articles from the database.
$markdown_files = glob("*.md");
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html" charset="utf-8">
<title><?php
if (isset($user_config['page_title']))
    print $user_config['page_title'];
else
    print "NigoroJr"
?></title>
<link rel="stylesheet" href="<?php print $stylesheet ?>" type="text/css" charset="utf-8">
<?php
if (isset($additional_stylesheet)) {
?>
<link rel="stylesheet" href="<?php print $additional_stylesheet ?>" type="text/css" charset="utf-8">
<?php
}
?>
<link rel="shortcut icon" href="/images/favicon.ico">
<!-- highlight.js -->
<link rel="stylesheet" href="/styles/highlight.js/styles/<?php
# Check if user wants a specific stylesheet for syntax highlighting
if (isset($user_config['highlight.js']))
    print $user_config['highlight.js'];
else
    print "github";
?>.css">
<script src="/styles/highlight.js/highlight.pack.js"></script>
<script>
hljs.tabReplace = '    ';
hljs.initHighlightingOnLoad();
</script>
</head>

<body>
<nav id="nav">
<ul>
<?php
# Prints what the user wants on the nav. Default if nothing specified.
if (isset($user_config['nav'])) {
    foreach (array_keys($user_config['nav']) as $key) {
?>
<li><a href="<?php print $user_config['nav'][$key] ?>"><?php print $key ?></a></li>
<?php
    }
}
else {
?>
<li><a href="/tips">Tips</a></li>
<li><a href="/apps">Apps</a></li>
<li><a href="/ftp">FTP</a></li>
<li><a href="/users">Users</a></li>
<?php
}

if (isset($user_config['logo_location']))
    $logo_location = $user_config['logo_location'];
else
    $logo_location = "/";
?>
<li style="float: right"><a href="mailto:nigorojr@gmail.com">Contact</a></li>
</ul>
</nav>

<div id="header">
<a href="<?php print $logo_location ?>"><img src="<?php print $logo ?>" width="686" height="200" alt="NigoroJr Logo"></a>
</div>


<div id="my_body">
  <div id="contents_wrapper">
    <div id="main_contents">

<?php
if (isset($user_config['article_per_page']))
    $article_per_page = $user_config['article_per_page'];
else
    $article_per_page = 5;

# The variable $category will be overwritten later when loading articles
if (isset($user_config['category']))
    $category = $user_config['category'];
else
    $category = "tips";

$id = $_GET['id'];
$tag = $_GET['tag'];

if (isset($_GET['page']))
    $page = $_GET['page'];
else
    $page = 1;
$offset = ($page - 1) * $article_per_page;

# *****************************************************
# If there is at least 1 Markdown file in the current
# directory, display all
# *****************************************************
if ($markdown_files) {
    foreach ($markdown_files as $markdown_file) {
        print MarkdownExtra::defaultTransform(file_get_contents($markdown_file));
        print "<hr>";
    }
}
else {  # Beginning of the huge else block TODO: find another way
$query_beginning = "select * from articles where";
# Depending on the parameter, change query
if (isset($id))
    $rs = mysql_query("$query_beginning id = $id");
# Search for tag includes comma to prevent unwanted matching
else if (isset($tag))
    $rs = mysql_query("$query_beginning tags regexp '(^|,)([[:blank:]]*($tag)[[:blank:]]*)(,|$)' order by date desc");
# If there's a user specified query_condition
else if (isset($user_config['query_condition'])) {
    $user_query = $user_config['query_condition'];
    # Add category condition if it's specified
    if (isset($user_config['category']))
        $user_query .= "category = '" . $user_config['category'] . "'";
    $rs = mysql_query("$query_beginning $user_query order by date desc limit $article_per_page offset $offset");
    $articles_without_limit = mysql_query("$query_beginning $user_query order by date desc");
}
# Print all the contents from the database ONLY IF THERE'S CONNECTION
else if (isset($db)) {
    $rs = mysql_query("$query_beginning category = '$category' order by date desc limit $article_per_page offset $offset");
    $articles_without_limit = mysql_query("$query_beginning category = '$category' order by date desc");
}
else {
    print "<h1>No connection to database nor files to show</h1>";
    print "We apologize for the inconvenience. Please contact the website's administrator about this page. Thank you.";
    return;
}

# When no article or false is returned.
if (!$rs or mysql_num_rows($rs) == 0) {
    print "<h1 style=\"text-align: center\">No such article!</h1>";
    return;
}
while ($arr = mysql_fetch_row($rs)) {
    $article_id = $arr[0];
    $title = $arr[1];
    $title = MarkdownExtra::defaultTransform($title);
    $content_file = $arr[2];
    $content = file_get_contents($content_file);
    $converted = MarkdownExtra::defaultTransform($content);
    $post_date = $arr[3];
    $edit_date = $arr[4];
    # This is just the raw username in Linux. Use table "authors" to convert 
    # them to a "screen name" that would actually be displayed.
    $author = $arr[5];
    $author_query_result = mysql_query("select screen_name from authors where author = '$author'");

    # Screen name will be overwritten if there's a user-specified screen 
    # name in the config file
    $screen_name = mysql_result($author_query_result, 0);
    if ($author == $username and isset($user_config['screen_name']))
        $screen_name = $user_config['screen_name'];
    else if ($screen_name == "")
        $screen_name = $author;

    # This overwrites the previously set $category in a non-related way
    $category = $arr[6];
    # Tags should be separated by ","s. Leading and tailing spaces will be trimmed
    $tags = array_map('trim', explode(",", $arr[7]));

    # Use specified language if any
    if (isset($user_config['language']))
        $language = $user_config['language'];
    else
        $language = $arr[8];
?>
    <div class="contents">
    <h1><a id="title" href="index.php?id=<?php print $article_id ?>"><?php print $title ?></a></h1>
    <p><?php
    # "nohl" at the beginning of a code block means no syntax highlighting
    $converted = preg_replace('/<code>nohl ?/', '<code class="no-highlight">', $converted);
    # '<code>lang: java ' should become '<code class="java">'
    $converted = preg_replace('/<code>lang(?:uage)?\:\s*(.*?) /', '<code class="$1"> ', $converted);
    print $converted;
?></p>
    <div id="tags">
<?php
    # Show tags
    foreach ($tags as $tag) {
?>
        <a class="tag" href="index.php?tag=<?php print $tag ?>"><?php print $tag?></a>
<?php
    }
?>
    </div>
<?php
    print "Posted by <a href=\"/$author\">$screen_name</a>: " . $post_date;
    if ($post_date != $edit_date)
        print " (Edited: $edit_date)";
?>
    <a href="/tips/post.php?id=<?php print $article_id ?>" style="float: right">Edit</a>
    <hr>
    </div>
<?php
}   # End of while loop (one article)
?>
    <div id="change_page">
<?php
    # Only display when it's not a 1-article page (when no id is set)
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

    # Only display when it's not a 1-article page (when no id is set)
    # Note: Used to be !isset($id) but changed because it would be a long 
    # condition when there are a lot of kinds of parameter (e.g. tags, id, 
    # etc.)
    if (isset($articles_without_limit) and $page < (mysql_num_rows($articles_without_limit) / $article_per_page)) {
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
    <a style="float: right; list-style: none;" href="/tips/post.php">Post</a>

    </div>
<?php
}   # End of huge else block TODO: find a better way
?>
  </div><!-- End of contents wrapper -->

  <div id="footer">
    <div id="copyright">
    Copyright (C) 2013 NigoroJr.com All Rights Reserved
    </div>
  </div>
</div>
</body>
</html>
