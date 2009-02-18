<?php

# Curl, CurlResponse
#
# Author  Sean Huber - shuber@huberry.com
# Date    May 2008
#
# A basic CURL wrapper for PHP
#
# See the README for documentation/examples or http://php.net/curl for more information about the libcurl extension for PHP
# trimmed to jsut the functionality we need (sam@shizzow.com)

class Util_Http {
	
	public $headers = array();
    public $options = array();
    public $referer = '';
    public $user_agent = '';
    public $include_headers_in_resp = false;
 
    protected $error = '';
    protected $handle;
 
 
    public function delete($url, $vars = array())
    {
        return $this->request('DELETE', $url, $vars);
    }
 
    public function error()
    {
        return $this->error;
    }
 
    public function get($url, $vars = array())
    {
        if ( ! empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= http_build_query($vars, '', '&');
        }
        return $this->request('GET', $url);
    }
 
    public function post($url, $vars = array())
    {
        return $this->request('POST', $url, $vars);
    }
 
    public function put($url, $vars = array())
    {
        return $this->request('PUT', $url, $vars);
    }
 
    protected function request($method, $url, $vars = array())
    {
        $this->handle = curl_init();
        
        # Set some default CURL options
        curl_setopt($this->handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->handle, CURLOPT_HEADER, $this->include_headers_in_resp);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, (is_array($vars) ? http_build_query($vars, '', '&') : $vars));
        curl_setopt($this->handle, CURLOPT_REFERER, $this->referer);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->handle, CURLOPT_URL, $url);
        curl_setopt($this->handle, CURLOPT_USERAGENT, $this->user_agent);
        
        # Format custom headers for this request and set CURL option
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key.': '.$value;
        }
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
        
        # Determine the request method and set the correct CURL option
        switch ($method) {
            case 'GET':
                curl_setopt($this->handle, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->handle, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $method);
        }
        
        # Set any custom CURL options
        foreach ($this->options as $option => $value) {
            curl_setopt($this->handle, constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
        $response = curl_exec($this->handle);
        if ( ! $response) {
            $this->error = curl_errno($this->handle).' - '.curl_error($this->handle);
        }
        curl_close($this->handle);
        return $response;
    }
}

/**
 * http://dev1.api.textmarks.com/Messaging/postAlert/?
&apik=MyAPIKey_12345
&auth_user=mytmuser
&auth_pass=mytmpass
&tm=MYKEYWORD
&msg=This+is+an+alert+test
 */

//$poster = new Helper_Http();
//$post_payload = array('apik'=>'shizzow_com_64bc4503','auth_user'=>'samk','auth_pass'=>'holycrap!','tm'=>'shzz','msg'=>'This is a test','to'=>'5034733242');
//$result = $poster->get('http://dev1.api.textmarks.com/Messaging/postAlert/',$post_payload);
/**
Usage
-----
 
### Initialization
 
Simply require and initialize the Curl class like so
 
  require_once 'curl.php';
  $curl = new Curl;
 
### Performing a Request
 
The Curl object supports 4 types of requests: GET, POST, PUT, and DELETE. You must specify a url to request and optionally specify an associative array of variables to send along with it.
 
  $response = $curl->get($url, $vars = array()); # The Curl object will append the array of $vars to the $url as a query string
  $response = $curl->post($url, $vars = array());
  $response = $curl->put($url, $vars = array());
  $response = $curl->delete($url, $vars = array());
 
Examples
 
  $response = $curl->get('google.com?q=test');
 
  # The Curl object will append '&some_variable=some_value' to the url
  $response = $curl->get('google.com?q=test', array('some_variable' => 'some_value'));
  
  $response = $curl->post('test.com/posts', array('title' => 'Test', 'body' => 'This is a test'));
### Basic Configuration Options
 
You can easily set the referer or user-agent
 
  $curl->referer = 'http://google.com';
  $curl->user_agent = 'some user agent string';
  
You may even set these headers manually if you wish (see below)
 
### Setting Custom Headers
 
You can set custom headers to send with the request
 
  $curl->headers['Host'] = 12.345.678.90;
  $curl->headers['Some-Custom-Header'] = 'Some Custom Value';
 
### Setting Custom CURL request options
 
You can set/override many different options for CURL requests (see the [curl_setopt documentation](http://php.net/curl_setopt) for a list of them)
 
  # any of these will work
  $curl->options['AUTOREFERER'] = true;
  $curl->options['autoreferer'] = true;
  $curl->options['CURLOPT_AUTOREFERER'] = true;
  $curl->options['curlopt_autoreferer'] = true;
****/


?>