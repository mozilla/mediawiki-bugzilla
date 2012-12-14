<?php

class BugzillaCacheMemcache implements BugzillaCacheI
{
    
    protected $_memcache;
    
    public function __construct() {
        // As much as I detest using a global here, it is necessary to avoid
        // needing to inject the $wgMemc object, thus breaking the usefulness
        // of the interface. Using the $wgMemc object is important for the
        // consistency of the code.
        global $wgMemc;
        $this->_memcache = $wgMemc;
    }
    
    public function set($key, $value, $ttl = 300) {
        // Get the wikimedia key style expected
        $key = wfMemcKey($key);
        return $this->_memcache->set($key, $value, $ttl);
    }
    
    public function get($key) {
        // Get the wikimedia key style expected
        $key = wfMemcKey($key);
        return $this->_memcache->get($key);
    }
    
    public function expire($key) {
        // Get the wikimedia key style expected
        $key = wfMemcKey($key);
        return $this->_memcache->delete($key);
    }

    public static function setup($updater) {
        return;
    }
}