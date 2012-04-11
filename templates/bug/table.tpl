<table class="bugzilla ui-helper-reset">
    <thead>
        <tr>
            <th>ID</th>
            <th>Summary</th>
            <th>Status</th>
            <th>Priority</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($response->bugs as $bug): ?>
            <tr>
                <td>
                    <a href="https://bugzilla.mozilla.org/show_bug.cgi?id=<?php echo $bug->id ?>">
                        <?php echo $bug->id ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($bug->summary) ?></td>
                <td><?php echo htmlspecialchars($bug->status) ?></td>
                <td><?php echo htmlspecialchars($bug->priority) ?></td>
            </tr>
        <?php endforeach; ?>    
    </tbody>
</table>
