#!/usr/bin/perl

use strict;
use warnings;

use DBI;
use CGI;

# In order for this to work, you need to have the directory
# ~/public_html/tips/archives/ owned by apache, or if it's owned by you, the
# permission must be writable by others.
# One way to do this is to make the permission of the "tips" directory 777 and
# post an article. This will allow this script to create a directory named
# archives with the ownership to apache.
# However, considering you cannot remove/modify the direcoty without superuser
# privilege, you might want to use the "permission 777 owned by you" method.

print "Content-type: text/html\n";
print "\n";

# Move to index.php after 1 second
print "<head><meta http-equiv=\"refresh\" content=\"1;index.php\"></head>\n";

my $d = "DBI:mysql:NigoroJr";
open DB_FILE, "db.txt";
chomp(my $u = <DB_FILE>);
chomp(my $p = <DB_FILE>);
close DB_FILE;

my $dbh = DBI->connect($d, $u, $p);
$dbh->do("set names utf8");

my $query = CGI->new;
# Limit the max file size to 10MB
$CGI::POST_MAX = 1024 * 10000;
# Get all the variables
my %form = $query->Vars;

# Handle uploaded file if any
if ($form{uploaded_file}) {
    my $uploaded_file = $query->upload("uploaded_file");
    # Initialize $form{content}
    $form{content} = "" ;
    # Read in the content of the uploaded file
    while (<$uploaded_file>) {
        $form{content} .= $_;
    }
    close $uploaded_file;
}

# Write content to file
my $content_file_dir = "/home/$form{author}/public_html/$form{category}/archives/";
# Create directory if necessary
mkdir $content_file_dir, oct 777 if not -d $content_file_dir;

# Get rid of ' and " from the title
$form{title} =~ s/\'/\\'/g;
$form{title} =~ s/\"/\\"/g;

my $content_file_name = &title_to_filename($form{title});
if (not defined $form{id}) {
    my $get_id = $dbh->prepare("select auto_increment from information_schema.tables where table_name = 'articles';");
    my $result_id = $get_id->execute;
    $form{id} = ($get_id->fetchrow_array)[0];
}
my $content_file = $content_file_dir . "$form{id}_" . $content_file_name;
open CONTENT_FILE, ">$content_file";
print CONTENT_FILE $form{content};
close CONTENT_FILE;

my $sth;
# Change MySQL command according to whether it's a first-time post or an edit
if ($form{type} eq "post") {
    $sth = $dbh->prepare("insert into articles
        (title, content_file, author, category, tags, language)
        values ('$form{title}', '$content_file', '$form{author}', '$form{category}', '$form{tags}', '$form{language}');");
}
elsif ($form{type} eq "edit") {
    if ($ENV{REMOTE_USER} eq $form{author}) {
        # Editing means that there is a previous .md file. Delete it.
        # But only if the title has changed and the file name has changed
        unlink $form{content_file} if $content_file ne $form{content_file};

        # Have to manually update edit_date because normally, nothing but the
        # content of the file will be changed.
        if ($form{button} eq "Post Edit") {
            $sth = $dbh->prepare("update articles set
                title = '$form{title}',
                content_file = '$content_file',
                edit_date = CURRENT_TIMESTAMP,
                author = '$form{author}',
                category = '$form{category}',
                tags = '$form{tags}',
                language = '$form{language}'
                where id = $form{id};");
        }
        elsif ($form{button} eq "Delete Article") {
            $sth = $dbh->prepare("delete from articles where id = $form{id};");
            # Remove .md file
            unlink $content_file;
        }
    }
    else {
        print "Only the person who posted the article can edit!\n";
    }
}
$sth->execute;

# Check if author exists and if not, add it
my $author_check = $dbh->prepare("select author from authors where author = '$form{author}';");
$author_check->execute;
my @found = $author_check->fetchrow_array;
my $author;
if (not @found) {
    # Insert screen_name in authors table
    $author = $dbh->prepare("insert into authors (author,screen_name) values ('$form{author}', '$form{screen_name}');");
}
else {
    # Update screen_name in authors table
    $author = $dbh->prepare("update authors set
        screen_name = '$form{screen_name}' where author = '$form{author}';");
}
$author->execute;

print STDOUT "<h1 style=\"text-align: center\">Success!</h1>\n";

sub title_to_filename {
    my $title = shift;

    my $filename = "";

    while ($title =~ s/[a-zA-Z0-9\ ]+?//) {
        $filename .= $&;
    }

    $filename =~ s/ /-/g;
    # Convert to all lower case
    $filename = "\L$filename";
    # Add extension (".md")
    $filename .= ".md";

    return $filename;
}
