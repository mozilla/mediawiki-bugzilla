<?php
# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.

require_once 'HTTP/Request2.php';

// Factory class
class BugzillaQuery {
    public static function create($type, $options, $title) {
        global $wgBugzillaMethod;

        switch ( strtolower( $wgBugzillaMethod ) ) {
        case 'xml-rpc':  return new BugzillaXMLRPCQuery ($type, $options, $title); break;
        case 'json-rpc': return new BugzillaJSONRPCQuery($type, $options, $title); break;
        default:         return new BugzillaRESTQuery   ($type, $options, $title); break;
        }
    }
}

// Base class
abstract class BugzillaBaseQuery {

    public function __construct($type, $options, $title) {
        global $wgBugzillaDefaultFields;

        $this->type             = $type;
        $this->title            = $title;
        $this->url              = FALSE;
        $this->id               = FALSE;
        $this->error            = FALSE;
        $this->data             = array();
        $this->synthetic_fields = array();
        $this->cached           = FALSE;
        $this->options          = $this->prepare_options($options, $wgBugzillaDefaultFields);
    }

    public function id() {
        if (!$this->id) {
            $this->id = $this->_generate_id($this->options);
        }

        return $this->id;
    }

    /**
     *
     * @param  Array  $options
     * @return String|false
     *
     * FIXME: Should we strtolower() the keys?
    */
    protected function _generate_id($options) {

        // No need to generate if there are errors
        if( !empty($this->error) ) { return false; }

        ksort($options);

        $options['include_fields'] = $this->rebase_fields(
            $options['include_fields'],
            $this->synthetic_fields
        );

        return sha1(serialize($options));
    }

    /**
     * A query to the remote API will always contain at least,
     * $synthetic_fields.
     * So, whatever fields are requested, we just make sure:
     * - all synthetic fields are included,
     * - there's no duplicate,
     * - fields are ordered,
     * so that we reduce unnecessary queries to API.
     *
     * For instance, for synthetic (A, B) fields, actual queries on
     * (A), (A,B), (B) will anyway lead to a query for (A, B).
     *
     * See BugzillaQueryTest::testRebaseFields().
     *
     * @param Array  $requested_fields
     * @param Array  $default_fields
     *
     * @return Array
    */
    public function rebase_fields($requested_fields, $synthetic_fields)
    {
        $fields = array_unique(array_merge($synthetic_fields, $requested_fields));
        sort($fields);

        return $fields;
    }

    public function rebased_options()
    {
        $options = $this->options;
        $options['include_fields'] = $this->rebase_fields(
            $options['include_fields'],
            $this->synthetic_fields
        );

        return $options;
    }

    /**
     * Wrap around sub-classes actual fetch action, with caching.
     * Uses MediaWiki main cache strategy.
     *
     * TODO: use ObjectCache::getLocalServerInstance() once MW >= 1.27
     *
     * @return string
    */
    public function fetch() {

        global $wgMainCacheType;
        global $wgBugzillaCacheTimeOut;

        if ($this->error) { return; }

        $key = implode(':', ['mediawiki', 'bugzilla', 'bugs', sha1(serialize($this->id()))]);
        $cache = wfGetCache($wgMainCacheType);
        $row = $cache->get($key);

        if ($row === false) {
                $this->cached = false;

                $this->_fetch_by_options();
                $cache->set($key, base64_encode(serialize($this->data)), $wgBugzillaCacheTimeOut * 60);

                return $this->data;
        } else {
                $this->cached = true;
                return $this->data = unserialize(base64_decode($row));
        }
    }

    /**
     * Parse/prepare query options
     * and set appropriate, working defaults.
     *
     * See BugzillaQueryTest::testPrepareOptions().
     *
     * @param String $query_options_raw
     *
     * @return array prepared options array
    */
    public function prepare_options($query_options_raw, $default_fields = array()) {

        $options = array();
        $query_options_raw = trim($query_options_raw);

        // if no query is provided, at least set a working default
        // so that first experience is nicer than an error message.
        if (!$query_options_raw) {
            $options['include_fields'] = $default_fields;

        } else {
            $options = json_decode($query_options_raw, true);

            if ($options === null) {
                $this->error = 'Query options must be valid JSON.';
                return $options;
            }

            if (!isset($options['include_fields'])
                || empty($options['include_fields'])) {

                $options['include_fields'] = $default_fields;
            }
        }

        // It happens that some define it as:
        // - either {"include_fields": "A,B,C"}
        // - either {"include_fields": ["A", "B", "C"]}
        // so we accept both.
        if (!is_array($options['include_fields'])) {
            $options['include_fields'] = explode(',', $options['include_fields']);
        }

        return $options;
    }

    abstract public function _fetch_by_options();

    protected function _update_cache()
    {
        $cache = $this->_getCache();
        $cache->set($this->id(), base64_encode(serialize($this->data)));
    }

    public function full_query_url()
    {
        global $wgBugzillaURL;
        return $wgBugzillaURL . '/buglist.cgi?' . http_build_query($this->options);
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
    }

    public function user_agent() {
        global $wgBugzillaExtVersion;
        global $wgVersion;

        return 'MediawikiBugzilla/'.$wgBugzillaExtVersion
            .' MediaWiki/'.$wgVersion
            .' PHP/'.PHP_VERSION;
    }

    // Load data from the Bugzilla REST API
    public function _fetch_by_options() {

        // Set up our HTTP request
        $options_array = array();

        $options_array = array(Net_Url2::OPTION_USE_BRACKETS => false);
        $net_url2 = new Net_Url2($this->url, $options_array);
        $request = new HTTP_Request2($net_url2,
            HTTP_Request2::METHOD_GET,
            array(
                'follow_redirects' => true,
                // TODO: Not sure if I should do this
                'ssl_verify_peer' => false
            )
        );

        // The REST API requires these
        $request->setHeader('Accept', 'application/json');
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('User-Agent', $this->user_agent());

        $url = $request->getUrl();
        $url->setQueryVariables($this->rebased_options());

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
class BugzillaJSONRPCQuery extends BugzillaBaseQuery {

    function __construct($type, $options, $title='') {

        global $wgBugzillaURL;
        global $wgBugzillaDefaultFields;

        // add include_fields
        parent::__construct($type, $options, $title);

        $this->url = $wgBugzillaURL . '/jsonrpc.cgi';

        // See what sort of REST query we are going to
        switch( $type ) {

            // Whitelist
            case 'count':
                $this->error = "Type count is not supported yet";
                break;
            // Default to a bug query
            case 'bug':
            default:
                $this->synthetic_fields = $wgBugzillaDefaultFields;
                break;
        }
    }

    // Load data from the Bugzilla JSONRPC API
    public function _fetch_by_options() {
        $this->getJsonData('Bug.search', $this->rebased_options());
    }

    protected function getJsonData($method, $params)
    {
        $query = json_encode($params, true);
        $url = $this->url."?method=$method&params=[".urlencode($query)."]";

        $req = MWHttpRequest::factory($url, array(
                    'sslVerifyHost' => false,
                    'sslVerifyCert' => false
                  )
              );
        $status = $req->execute();

        if(!$status->isOK()) {
            $this->error = $res->getMessage();
            return false;
        } else {
            $this->rawData = $req->getContent();
            $params = json_decode($this->rawData, true);
            $this->data = $params['result'];
            return true;
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
            array(
                'follow_redirects' => true,
               'ssl_verify_peer' => false
            )
        );

        $request->setHeader('Accept', 'text/xml');
        $request->setHeader('Content-Type', 'text/xml;charset=utf-8');
        $request->setBody($xml);

        try {
            $response = $request->send();

            if (200 == $response->getStatus()) {
                $x = simplexml_load_string($response->getBody());
                $this->data['bugs'] = array();

                // FIXME there must be a better way
                foreach ($x->params->param->value->struct->member->value->array->data->value as $b) {
                    $bug = array();
                    foreach ($b->struct->member as $m) {
                        if ($m->name == 'internals') {
                            continue;
                        }

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
