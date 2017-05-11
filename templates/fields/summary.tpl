<?php 
    global $wgBugzillaURL;

if ( isset( $bug['id'] ) ) {
    echo "<a href='$wgBugzillaURL/show_bug.cgi?id=" .
             urlencode($bug['id']) ."'>";
    if ( isset( $bug['summary'] ) ) {
        echo     htmlspecialchars($bug['summary']);
    } else {
        echo "no summary";
    }
    echo  "</a>";
}
?>
