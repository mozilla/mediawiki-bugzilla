<?php
# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.
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

        $now     = time(); // Using time() because it's a PHP built-in.
        $expires = $now + $ttl;

        if (null === $this->get($key)) {
            $res = $master->insert(
                'bugzilla_cache',
                array(
                    $master->addIdentifierQuotes( 'key' )     => $key,
                    'data'    => $value,
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
     * @return string|null
     */
    public function get($key)
    {
        $slave = $this->_getDatabase(DB_SLAVE);

        $res   = $slave->select(
            'bugzilla_cache',
            array( 'id', 'data', 'expires' ),
            array( $slave->addIdentifierQuotes( 'key' ) => $key ),
            __METHOD__,
            array( 'LIMIT' => 1 )
        );

        if( !$res ) {
            $this->expire($key);
            return null;
        }
        $row = $res->fetchRow();

        if (!$row || ($row['expires'] < time())) {
            $this->expire($key); // This won't hurt us if the first condition is true.
            return null;
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
        global $wgBugzillaCacheType;

        if ($wgBugzillaCacheType == 'mysql') {
            return $master->delete(
                'bugzilla_cache',
                array('`key`="' . $key . '"')
            );
        } else {
            return $master->delete(
                'bugzilla_cache',
                array('key' => $key)
            );
        }
    }

    /**
     * @param $updater DatabaseUpdater
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
