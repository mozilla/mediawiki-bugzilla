<?php

abstract class BugzillaJob extends Job {

    // Run the job 
    public function run() {

        $this->query = unserialize($this->params['query_obj']);
        $article = new Article( $this->title );
                
        if( $article ) {

            // Pull from Bugzilla
            $this->query->_fetch_by_options();

            // Mess with the database
            $this->_database_work();

        }

        return TRUE;
    }

}

class BugzillaInsertJob extends BugzillaJob {
    // Set up the background job
    public function __construct( $title, $params ) {
        parent::__construct('queryBugzillaInsert', $title, $params );
    }

    public function _database_work() {

        // Get the master because we are writing
        $dbw = wfGetDB( DB_MASTER );

        // Add it to the cache
        $res = $dbw->insert(
                       'bugzilla_cache',
                       array('id'         => $this->query->id(),
                             'fetched_at' => wfTimestamp(TS_DB),
                             'data'       => serialize($this->query->data)),
                       __METHOD__
        );

    }

}

class BugzillaUpdateJob extends BugzillaJob {
    // Set up the background job
    public function __construct( $title, $params ) {
        parent::__construct('queryBugzillaUpdate', $title, $params );
    }

    public function _database_work() {

        // Get the master because we are writing
        $dbw = wfGetDB( DB_MASTER );

        // Update cache entry
        $res = $dbw->update(
                        'bugzilla_cache',
                        array('id'         => $this->query->id(),
                              'fetched_at' => wfTimestamp(TS_DB),
                              'data'       => serialize($this->query->data)),
                        array('id' => $this->query->id()),
                        __METHOD__
        );

    }
}

?>
