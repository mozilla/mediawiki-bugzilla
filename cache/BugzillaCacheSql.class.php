<?php

/**
*/
class BugzillaCacheSql implements BugzillaCacheI
{
    protected function _getDatabase($type = DB_MASTER) {
        return wfGetDB($type);
    }

    /**
     * @param string  $key
     * @param string  $value
     * @param integer $ttl
     *
     * @return boolean
    */
    public function set($key, $value, $ttl = 300)
    {
        $master  = $this->_getDatabase();

        $key_c   = $master->strencode($key);
        $value_c = $master->strencode($value);

        $now     = time(); // Using time() because it's a PHP built-in.
        $expires = $now + $ttl;

        if (null === $this->get($key)) {
            $res = $master->insert(
                'bugzilla_cache',
                array(
                    'key'     => $key_c,
                    'data'    => $value_c,
                    'expires' => $expires
                ),
                __METHOD__
            );
        }

        return $res;
    }

    /**
     * @param string $key
     *
     * @return string
    */
    public function get($key)
    {
        $slave = $this->_getDatabase(DB_SLAVE);

        $key_c = $slave->strencode($key);
        $res   = $slave->select(
            'bugzilla_cache',
            array( 'id', 'data', 'expires' ),
            '`key` = "' . $key_c . '"',
            __METHOD__,
            array( 'LIMIT' => 1 )
        );

        $row = $res->fetchRow();

        if (!$row || ($row['expires'] < time())) {
            $this->expire($key_c); // This won't hurt us if the first condition is true.
            return;
        }

        return $row['data'];
    }

    /**
     * @param string $key
     *
     * @return boolean
    */
    public function expire($key)
    {
        $master = $this->_getDatabase();

        return $master->delete(
            'bugzilla_cache',
            array('`key`="' . $key . '"')
        );
    }

    /**
    */
    final public static function setup($updater)
    {
        global $wgBugzillaCacheType;

        $sqlFile = sprintf('%s/sql/%s.sql',
                           dirname(__FILE__),
                           $wgBugzillaCacheType);

        if ($updater === null) {
            // <= 1.16 support
            global $wgExtNewTables;
            global $wgExtModifiedFields;
            $wgExtNewTables[] = array(
                'bugzilla_cache',
                $sqlFile,
            );
        } else {
            // >= 1.17 support
            $updater->addExtensionUpdate(array('addTable',
                                               'bugzilla_cache',
                                               $sqlFile,
                                               TRUE));
        }
    }
}