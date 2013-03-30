<?php 
    global $wgBugzillaURL;

    echo "<a href='$wgBugzillaURL/show_bug.cgi?id=" .
             urlencode($bug['id']) ."'>";
    echo htmlspecialchars($bug['id']);
    echo "</a>";
?>
