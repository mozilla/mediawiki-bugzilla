<?php

class BugzillaCacheMysql implements BugzillaCacheI
{
    protected $_slave;
    protected $_master;
    
    public function __construct()
    {
        // TO-DO: This methodology creates some difficulties to unit testing.
        $this->_slave = wfGetDB( DB_SLAVE );
        $this->_master = wfGetDB( DB_MASTER );
    }
    
    public function set($key, $value, $ttl = 300)
    {
        //TO-DO: It's probably a bad thing to write straight SQL against an
        //       abstraction layer. The abstraction layer doens't offer full
        //       functionality, though, and this reduces the number of queries
        //       for something as simple as caching. Also, the doQuery() method
        //       is marked "private" but that is commented out; that may change
        //       in a future release.
        $key_c = $this->_master->strencode($key);
        $value_c = $this->_master->strencode($value);
        $date = wfTimestamp(TS_DB);
        $now = time(); // Using time() because it's a PHP built-in.
        $expires = $now+$ttl;
        
        $sql = 'REPLACE INTO bugzilla_cache
                (`key`, `fetched_at`, `data`, `expires`) 
                VALUES
                ("%s", "%s", "%s", %d)';
                
        $sql = sprintf($sql, $key_c, $date, $value_c, $expires);
        $res = $this->_master->doQuery($sql);
        return $res;
    }
    
    public function get($key)
    {
         $res = $this->_slave->select(
                        'bugzilla_cache',
                        array('id', 'fetched_at', 'data', 'expires'),
                        '`key` = "' . $key . '"',
                        __METHOD__,
                        array( 'ORDER BY' => 'fetched_at DESC',
                               'LIMIT' => 1)
            );

        $row = $res->fetchRow();

        if(!$row || ($row['expires'] < time())) {
            $this->expire($key); // This won't hurt us if the first condition is true.
            return;
        }
        
        return $row;
    }
    
    public function expire($key)
    {
        return $this->_master->delete(
                'bugzilla_cache',
                array('`key`="' . $key .'"')
            );
        
    }
}