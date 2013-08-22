<?php
# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.
/**
 * Dummy cache for those times you want to test the functionality WITHOUT the
 * cache.
 */
class BugzillaCacheDummy implements BugzillaCacheI
{
    
    public function set($key, $value, $ttl = 300)
    {
        return true;
    }
    
    public function get($key)
    {
        return;
    }
    
    public function expire($key)
    {
        return true;
    }

    public static function setup($updater)
    {
        return;
    }
}