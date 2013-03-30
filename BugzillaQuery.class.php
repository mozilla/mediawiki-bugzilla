<?php

require_once 'HTTP/Request2.php';

// Factory class
class BugzillaQuery {
    public static function create($type, $options, $title) {
        global $wgBugzillaMethod;

        if( strtolower($wgBugzillaMethod) == 'xml-rpc' ) {
            return new BugzillaXMLRPCQuery($type, $options, $title);
        }elseif( strtolower($wgBugzillaMethod) == 'json-rpc' ) {
            return new BugzillaJSONRPCQuery($type, $options, $title);
        }else {
            return new BugzillaRESTQuery($type, $options, $title);
        }
    }
}

// Base class
abstract class BugzillaBaseQuery {

    public function __construct($type, $options, $title) {
        $this->type             = $type;
        $this->title            = $title;
        $this->url              = FALSE;
        $this->id               = FALSE;
        $this->fetched_at       = FALSE;
        $this->error            = FALSE;
        $this->data             = array();
        $this->synthetic_fields = array();
        $this->cache            = FALSE;
        $this->_set_options($options);
    }
    
    protected function _getCache()
    {
        global $wgBugzillaCacheObject;
        if(!$this->cache) {
            $this->cache = new $wgBugzillaCacheObject;
        }
        
        return $this->cache;
    }

    public function id() {

        // If we have already generated an id, return it
        if( $this->id ) { return $this->id; }

        return $this->_generate_id();
    }

    protected function _generate_id() {

        // No need to generate if there are errors
        if( !empty($this->error) ) { return; }

        // FIXME: Should we strtolower() the keys?

        // Sort it so the keys are always in the same order
        ksort($this->options);

        // Treat include_fields special because we don't want to query multiple
        // times if the same fields were requested in a different order
        $saved_include_fields = array();
        if( isset($this->options['include_fields']) &&
            !empty($this->options['include_fields']) ) {

            $saved_include_fields = $this->options['include_fields'];

            // This is important. If a user asks for a subset of the default
            // fields and another user has the same query w/ a subset,
            // it is silly to cache the queries separately. We know the 
            // defaults will always be pulled, so anything asking for
            // any combination of the defaults (or any combined subset) are
            // esentially the same
            $include_fields = $this->synthetic_fields;

            $tmp = @explode(',', $this->options['include_fields']);
            foreach( $tmp as $tmp_field ) {
                $field = trim($tmp_field);
                // Catch if the user specified the same field multiple times
                if( !empty($field) && !in_array($field, $include_fields) ) {
                    array_push($include_fields, $field);
                }
            }
            sort($include_fields);
            $this->options['include_fields'] = @implode(',', $include_fields);
        }
        
        // Get a string representation of the array
        $id_string = serialize($this->options);

        // Restore the include_fields to what the user wanted
        if( $saved_include_fields ) {
            $this->options['include_fields'] = $saved_include_fields;
        }

        // Hash it
        $this->id = sha1($id_string);

        return $this->id;
    }
        
    // Connect and fetch the data
    public function fetch() {

        global $wgBugzillaCacheMins;

        // We need *some* options to do anything
        if( !isset($this->options) || empty($this->options) ) { return; }

        // Don't do anything if we already had an error
        if( $this->error ) { return; }
        
        $cache = $this->_getCache();
        $row = $cache->get($this->id());

        // If the cache entry is older than this we need to invalidate it
        $expiry = strtotime("-$wgBugzillaCacheMins minutes");
        
        if( !$row ) { 
            // No cache entry

            $this->cached = FALSE;
            $params = array( 'query_obj' => serialize($this) );

            // Does the Bugzilla query in the background and updates the cache
            $this->_fetch_by_options();
            $this->_update_cache();
            return $this->data;
        }else {
            // Cache is good, use it
            $this->data = unserialize($row);
            $this->cached = TRUE;
        }
    }

    protected function _set_options($query_options_raw) {
        // Make sure query options are valid JSON
        $this->options = json_decode($query_options_raw, TRUE);
        if( !$query_options_raw || !$this->options ) {
            $this->error = 'Query options must be valid json';
            return;
        }
    }
    
    abstract public function _fetch_by_options();
    
    protected function _update_cache()
    {
        $cache = $this->_getCache();
        $cache->set($this->id(), serialize($this->data));
    }

}

class BugzillaRESTQuery extends BugzillaBaseQuery {

    function __construct($type, $options, $title='') {
        global $wgBugzillaRESTURL;
        global $wgBugzillaDefaultFields;

        parent::__construct($type, $options, $title);

        // See what sort of REST query we are going to 
        switch( $type ) {

            // Whitelist
            case 'count':
                $this->url = $wgBugzillaRESTURL . '/' . urlencode($type);
                // Note there are no synthetic fields for count
                break;

            // Default to a bug query
            case 'bug':
            default:
                $this->url = $wgBugzillaRESTURL . '/bug';
                // Even if the user didn't specify, we need these
                $this->synthetic_fields = $wgBugzillaDefaultFields;
        }

        $this->fetch();
    }

    // Load data from the Bugzilla REST API
    public function _fetch_by_options() {

        // Set up our HTTP request
        $options_array = array();
        
        $options_array = array(Net_Url2::OPTION_USE_BRACKETS => false);
        $net_url2 = new Net_Url2($this->url, $options_array);
        $request = new HTTP_Request2($net_url2,
                                     HTTP_Request2::METHOD_GET,
                                     array('follow_redirects' => TRUE,
                                           // TODO: Not sure if I should do this
                                           'ssl_verify_peer' => FALSE));

        // The REST API requires these
        $request->setHeader('Accept', 'application/json');
        $request->setHeader('Content-Type', 'application/json');

        // Save the real options
        $saved_options = $this->options;
        
        if(!isset($this->options['include_fields'])) {
            $this->options['include_fields'] = array();
        }
        
        if(!is_array($this->options['include_fields'])) {
            (array)$this->options['include_fields'];
        }
        
        // Add any synthetic fields to the options
        if( !empty($this->synthetic_fields) ) {
            $this->options['include_fields'] = 
                @array_merge((array)$this->options['include_fields'],
                             $this->synthetic_fields);
        }
        
        if(!empty($this->options['include_fields'])) {
            $this->options['include_fields'] = implode(",", $this->options['include_fields']);
        }

        // Add the requested query options to the request
        $url = $request->getUrl();
        $url->setQueryVariables($this->options);

        // Retore the real options, removing anything we synthesized
        $this->options = $saved_options;

        // This is basically straight from the HTTP/Request2 docs
        try {
            $response = $request->send();
            if (200 == $response->getStatus()) {
                $this->data = json_decode($response->getBody(), TRUE);
            } else {
                $this->error = 'Server returned unexpected HTTP status: ' .
                               $response->getStatus() . ' ' .
                               $response->getReasonPhrase();
                return;
            }
        } catch (HTTP_Request2_Exception $e) {
            $this->error = $e->getMessage();
            return;
        }

        // Check for REST API errors
        if( isset($this->data['error']) && !empty($this->data['error']) ) {
            $this->error = "Bugzilla API returned an error: " .
                           $this->data['message'];
        }
    }
}

/**
*/
class BugzillaXMLRPCQuery extends BugzillaBaseQuery {

    function __construct($type, $options, $title='') {

        global $wgBugzillaURL;
        global $wgBugzillaDefaultFields;

        parent::__construct($type, $options, $title);

        $this->url = $wgBugzillaURL . '/xmlrpc.cgi';

        $this->fetch();
    }

    // Load data from the Bugzilla XMLRPC API
    public function _fetch_by_options() {

        $method = 'Bug.search';
        $struct = '';
        foreach ($this->options as $k => $v)
            $struct .= sprintf('<member><name>%s</name><value><%s>%s</%s></value></member>' . "\n",
                $k, 'string', $v, 'string');

        $xml = <<<X
<?xml version="1.0" encoding="utf-8"?>
<methodCall>
    <methodName>{$method}</methodName>
    <params>
        <param>
            <struct>
                {$struct}
            </struct>
        </param>
    </params>
</methodCall>
X;

        $request = new HTTP_Request2($this->url,
                                     HTTP_Request2::METHOD_POST,
                                     array('follow_redirects' => TRUE,
                                           'ssl_verify_peer' => FALSE));

        $request->setHeader('Accept', 'text/xml');
        $request->setHeader('Content-Type', 'text/xml;charset=utf-8');
        $request->setBody($xml);

        try {
            $response = $request->send();

            if (200 == $response->getStatus()) {
                $x = simplexml_load_string($response->getBody());
                $this->data['bugs'] = array();
                foreach ($x->params->param->value->struct->member->value->array->data->value as $b) {
                    $bug = array();
                    foreach ($b->struct->member as $m) {
                        if ($m->name == 'internals')
                            continue;

                        $value = (array)$m->value;
                        $bug[(string)$m->name] = (string)array_shift($value);
                    }
                    $this->data['bugs'][] = $bug;
                }
            } else {
                $this->error = 'Server returned unexpected HTTP status: ' .
                               $response->getStatus() . ' ' .
                               $response->getReasonPhrase();
                return;
            }
        } catch (HTTP_Request2_Exception $e) {
            $this->error = $e->getMessage();
            return;
        }

        if( isset($this->data['error']) && !empty($this->data['error']) ) {
            $this->error = "Bugzilla API returned an error: " .
                           $this->data['message'];
        }
    }
}
