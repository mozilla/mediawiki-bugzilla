<?php

class BugzillaCacheMysql implements BugzillaCacheI
{
        

    public function set($key, $value, $ttl = 300)
    {
        $master = $this->_getDatabase();
        $key_c   = $key;
        $value_c = $value;

        $date    = wfTimestamp(TS_DB);
        $now     = time(); // Using time() because it's a PHP built-in.
        $expires = $now + $ttl;
        if (null === $this->get($key)) {
            $res = $master->insert(
                'bugzilla_cache',
                array(
                    '`key`'        => $key_c,
                    'fetched_at' => $date,
                    'data'       => $value_c,
                    'expires'    => $expires
                ),
                __METHOD__
            );
        } else {
            $res = $this->update(
                'bugzilla_cache',
                array(
                    'fetched_at' => $date
                ),
                '`key` = "' . $key_c . '"',

                __METHOD__
            );
        }

        return $res;
    }
    
    protected function _getDatabase($type = DB_MASTER) {
        return wfGetDB($type);
    }
    
    public function get($key)
    {
         $slave = $this->_getDatabase(DB_SLAVE);
         $res = $slave->select(
                        'bugzilla_cache',
                        array('id', 'fetched_at', 'data', 'expires'),
                        '`key` = "' . $key . '"',
                        __METHOD__,
                        array( 'ORDER BY' => 'fetched_at DESC',
                               'LIMIT' => 1)
            );

		if( !$res ) {
			$this->expire( $key );
		}

        $row = $res->fetchRow();

        if(!$row || ($row['expires'] < time())) {
            $this->expire($key); // This won't hurt us if the first condition is true.
            return;
        }
        
        return $row['data'];
    }
    
    public function expire($key)
    {
        $master = $this->_getDatabase();
        return $master->delete(
                'bugzilla_cache',
                array('`key`="' . $key .'"')
            );
        
    }
}