<?php

/**
 * Supports all databases provided by mediawiki DB backend.
*/
class BugzillaCacheGeneric implements BugzillaCacheI
{
    protected $_slave;
    protected $_master;

    public function __construct()
    {
        // TO-DO: This methodology creates some difficulties to unit testing.
        $this->_slave  = wfGetDB( DB_SLAVE );
        $this->_master = wfGetDB( DB_MASTER );
    }

    public function set($key, $value, $ttl = 300)
    {
        $key_c   = $this->_master->strencode($key);
        $value_c = $this->_master->strencode($value);
        $date    = wfTimestamp(TS_DB);
        $now     = time(); // Using time() because it's a PHP built-in.
        $expires = $now + $ttl;

        if (null === $this->get($key)) {
            $res = $this->_master->insert(
                'bugzilla_cache',
                array(
                    'key'        => $key_c,
                    'fetched_at' => $date,
                    'data'       => $value_c,
                    'expires'    => $expires
                ),
                __METHOD__
            );
        } else {
            $res = $this->_master->update(
                'bugzilla_cache',
                array(
                    'fetched_at' => $date
                ),
                'key = "' . $key_c . '"',
                __METHOD__
            );
        }

        return $res;
    }

    public function get($key)
    {
        $key_c = $this->_slave->strencode($key);
        $res   = $this->_slave->select(
                        'bugzilla_cache',
                        array('id', 'fetched_at', 'data', 'expires'),
                        '`key` = "' . $key_c . '"',
                        __METHOD__,
                        array( 'ORDER BY' => 'fetched_at DESC',
                               'LIMIT' => 1)
            );

        $row = $res->fetchRow();

        if(!$row || ($row['expires'] < time())) {
            $this->expire($key_c); // This won't hurt us if the first condition is true.
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