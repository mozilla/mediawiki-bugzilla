<?php 
    global $wgBugzillaURL;

if ( isset( $bug['id'] ) ) {
    echo "<a href='$wgBugzillaURL/show_bug.cgi?id=" .
             urlencode($bug['id']) ."'>";
    if ( isset( $bug['url'] ) ) {
        echo     htmlspecialchars($bug['url']);
    } else {
        echo "no url";
    }
    echo  "</a>";
}
?>
