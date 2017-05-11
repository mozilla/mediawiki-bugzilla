<?php 
    global $wgBugzillaURL;

if ( isset( $bug['id'] ) ) {
    echo "<a href='$wgBugzillaURL/show_bug.cgi?id=" .
             urlencode($bug['id']) ."'>";
    echo htmlspecialchars($bug['id']);
    echo "</a>";
} else {
    echo "no id";
}
?>
