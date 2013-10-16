<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html" charset="utf-8">
<title>Post an Article</title>
<link rel="stylesheet" href="/styles/style.css" type="text/css" charset="utf-8">
<link rel="stylesheet" href="post.css" type="text/css" charset="utf-8">
<link rel="shortcut icon" href="/images/favicon.ico">

<!-- highlight.js -->
<link rel="stylesheet" href="/styles/highlight.js/styles/github.css">
<script src="/styles/highlight.js/highlight.pack.js"></script>
<script>hljs.initHighlightingOnLoad();</script>

<?php
// TODO When changing file name, also change .htaccess
$file_handle = fopen("db.txt", 'r');
$db_username = rtrim(fgets($file_handle));
$db_password = rtrim(fgets($file_handle));
fclose($file_handle);
/* Connect to Database */
$db = mysql_connect("localhost", "$db_username", "$db_password");
mysql_select_db("NigoroJr", $db);
mysql_query("set names utf8;");

$article_id = $_GET['id'];
if ($article_id > 0) {
    $isEdit = true;

    $rs = mysql_query("select * from articles where id = $article_id");
    $arr = mysql_fetch_row($rs);

    /* Get all the information about the article (if it's an edit) */
    $title = $arr[1];
    $content_file = $arr[2];
    $content = file_get_contents($content_file);
    $author = $arr[5];
    $category = $arr[6];
    // Tags should be separated by ","s
    $tags = $arr[7];
    $language = $arr[8];

    // If the person editing is not the same as the person who originally posted, 
    // don't allow edit
    // TODO: Use a better method
    if ($author != $_SERVER['REMOTE_USER'])
        print "<meta http-equiv=\"refresh\" content=\"0;index.php\">\n";
}
// Find out who's posting
else
    $author = $_SERVER['REMOTE_USER'];

$author_query_result = mysql_query("select screen_name from authors where author = '$author'");
$screen_name = mysql_result($author_query_result, 0);
if ($screen_name == "")
    $screen_name = $author;

?>
</head>

<body>
<nav id="nav">
  <ul>
    <li><a href="/tips">Tips</a></li>
    <li><a href="/apps">Apps</a></li>
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

      <div class="contents">
        <form method="post" action="check.cgi" enctype="multipart/form-data">
            <p>
                <strong>Title</strong>
                <br>
                <input type="text" name="title" id="input" <?php if ($isEdit) print "value=\"$title\"" ?>>
            </p>
            <p>
                <strong>Content</strong>
                <br>
                <input type="file" name="uploaded_file">
                <br>
                or, enter directly.
                <br>
                <textarea name="content" id="input"><?php if ($isEdit) print $content ?></textarea>
            </p>
            <p>
                <strong>Tags</strong>
                <br>
                <input type="text" name="tags" id="input" <?php if ($isEdit) print "value=\"$tags\"" ?>>
                <input type="hidden" name="author" value=<?php print $author ?>>
            </p>
            <div id="columns">
            <div id="left">
                <strong>Category</strong>
                <br>
                <input type="text" name="category" <?php if ($isEdit) print "value=\"$category\""; else print "value=\"tips\""  ?>>
            </div>
            <div id="center">
                <strong>Language</strong>
                <br>
                <input type="text" name="language" <?php if ($isEdit) print "value=\"$language\""; else print "value=\"English\"" ?>>
            </div>
            <div id="right">
                <strong>Author</strong>
                <br>
                <input type="text" name="screen_name" <?php print "value=\"$screen_name\"" ?>>
            </div>
            </div>

            <p>
<?php
if ($isEdit == "true") {
?>
                <input type="submit" name="button" value="Post Edit" id="submit_button">
                <input type="submit" name="button" value="Delete Article" id="submit_button">
                <input type="hidden" name="type" value="edit">
                <input type="hidden" name="id" value="<?php print $article_id ?>">
                <input type="hidden" name="content_file" value="<?php print $content_file ?>">
<?php
}
else {
?>
                <input type="submit" name="button" value="Post" id="submit_button">
                <input type="hidden" name="type" value="post">
<?php
}
?>
            </p>
      </form>
    </div>
    <div id="instructions">
        <h3>Instructions</h3>
        <p>
        <p><pre class="bash"><code>$ mkdir -p ~/public_html/tips/archives
$ chmod 777 ~/public_html/tips/archives</code></pre></p>
        In order for this to work, you need to have the directory <code>~/public_html/tips/archives/</code> owned by apache, or if it's owned by you, the permission must be writable by others.<br>
        One way to do this is to make the permission of the "tips" directory 777 and post an article. This will allow this script to create a directory named archives with the ownership to apache.<br>
        However, considering you cannot remove/modify the direcoty without superuser privilege, you might want to use the "permission 777 owned by you" method.
        </p>
        <h3>Resources</h3>
        <p>
            Documentations for the Markdown Syntax can be found at:
            <br>
            <a href="http://daringfireball.net/projects/markdown/syntax">http://daringfireball.net/projects/markdown/syntax</a>
            <br>
            <a href="http://michelf.ca/projects/php-markdown/extra/">http://michelf.ca/projects/php-markdown/extra/</a>
            <br>
            日本語
            <a href="http://blog.2310.net/archives/6">http://blog.2310.net/archives/6</a>
        </p>
    </div>
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
