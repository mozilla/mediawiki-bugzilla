<?php

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
    
}