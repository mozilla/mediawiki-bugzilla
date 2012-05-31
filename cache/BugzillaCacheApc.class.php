<?php

class BugzillaCacheApc implements BugzillaCacheI
{
    
    public function set($key, $value, $ttl = 300) {
        return apc_store($key, $value, $ttl);
    }
    
    public function get($key) {
        return apc_fetch($key);
    }
    
    public function expire($key) {
        return apc_delete($key);
    }
    
}