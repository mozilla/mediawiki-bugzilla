<?php

interface BugzillaCacheI
{
    
    public function set($key, $value, $ttl = 300);
    
    public function get($key);
    
    public function expire($key);
    
}