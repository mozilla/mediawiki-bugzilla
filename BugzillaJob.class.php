<?php

abstract class BugzillaJob extends Job {

    public $cache;

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
    
    protected function _getCache()
    {
        global $wgCacheObject;
        if(!$this->cache) {
            $this->cache = new $wgCacheObject;
        }
        
        return $this->cache;
    }

}

class BugzillaInsertJob extends BugzillaJob {
    // Set up the background job
    public function __construct( $title, $params ) {
        parent::__construct('queryBugzillaInsert', $title, $params );
    }

    public function _database_work() {

        $cache = $this->_getCache();
        $cache->set($this->query->id(), serialize($this->query->data));

    }

}

class BugzillaUpdateJob extends BugzillaJob {
    // Set up the background job
    public function __construct( $title, $params ) {
        parent::__construct('queryBugzillaUpdate', $title, $params );
    }

    public function _database_work() {

        $cache = $this->_getCache();
        $cache->set($this->query->id(), serialize($this->query->data));

    }
}

?>
