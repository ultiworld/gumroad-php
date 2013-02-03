<?php

/**
 * PHP Gumroad API Wrapper
 *
 * @package Gumroad
 * @author  Orion Burt <orionjburt@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

define( 'GUMROAD_API_URL', 'https://gumroad.com/api/v1' );
define( 'GUMROAD_API_TIMEOUT', 3000 );

class Gumroad_Client
{
	public $requester;
	public $sessions;
	public $links;

	public function __construct() {
		$this->requester = new Gumroad_Requester();
		$this->sessions = new Gumroad_Sessions_Endpoint($this->requester);
		$this->links = new Gumroad_Links_Endpoint($this->requester);
	}

	//shortcut for auth
	public function authenticate( $email, $password ) {
		$params = array('email' => $email, 'password' => $password);
		return $this->sessions->authenticate($params);
	}

	//shortcut for deauth
	public function deauthenticate() {
		return $this->sessions->deauthenticate();
	}

	public function __destruct() {
		if( $this->requester->getToken() ) {
			$this->deauthenticate();
		}
	}
}

class Gumroad_Requester 
{
	private $_timeout;
	private $_token;

	public function __construct( $timeout=GUMROAD_API_TIMEOUT ) {
		$this->_timeout = $timeout;
		$this->_token = null;
	}

	public function setTimeout( $timeout ) {
		return $this->_timeout = $timeout;
	}

	public function getTimeout( $timeout ) {
		return $this->_timeout;
	}

	public function setToken( $token ) {
		return $this->_token = $token;
	}

	public function getToken() {
		return $this->_token;
	}

	public function request( $method, $url, $params = array() ) {
		//Start request
        $ch   = curl_init();
        $params['token'] = $this->_token;
        $query = http_build_query($params);

        switch ( strtoupper($method) ) {
        	case 'HEAD': 
        		curl_setopt($ch, CURLOPT_NOBODY, true);
        		break;
            case 'GET':
                if ( !empty($query) ) {
                    $url = $url . '?' . $query;
                }
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
                break;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->_timeout);
        # End of build request

        $response = curl_exec($ch);
        
        # Check response for errors
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ( $httpCode >= 300 ) {
            throw new Gumroad_Exception($httpCode);
        } 

        $curlError = curl_error($ch);
        if ( $curlError ) {
            throw new Gumroad_Exception($curlError);
        }

        $response = json_decode($response);
        if ( !$response->success ) {
            throw new Gumroad_Exception($response->error->message);
        }

        return $response;
    }
}

abstract class Gumroad_EndpointAbstract
{
	public $url;
	public $requester;

	public function __construct( $requester, $url=GUMROAD_API_URL ) {
		$this->requester = $requester;
		$this->url = $url;
	}
}

class Gumroad_Sessions_Endpoint extends Gumroad_EndpointAbstract
{
	public function __construct( $requester, $path='/sessions' ) {
		parent::__construct($requester);
		$this->url = $this->url . $path;
	}

	public function authenticate( $params ) {
		$response = $this->requester->request('POST', $this->url, $params);
		$this->requester->setToken($response->token);
		return $response;

	}

	public function deauthenticate() {
		$response = $this->requester->request('DELETE', $this->url);
		$this->requester->setToken(null);
		return $response;
	}
}

class Gumroad_Links_Endpoint extends Gumroad_EndpointAbstract
{
	public function __construct( $requester, $path='/links' ) {
		parent::__construct($requester);
		$this->url = $this->url . $path;
	}

	public function createLink( $params ) {
		return $this->requester->request('POST', $this->url, $params);
	}

	public function editLink( $id, $params ) {
		return $this->requester->request('PUT', $this->url . '/' . $id, $params);
	}

	public function deleteLink( $id ) {
		return $this->requester->request('DELETE', $this->url . '/' . $id);
	}

	public function enableLink( $id ) {
		return $this->requester->request('PUT', $this->url . '/' . $id . '/enable');
	}

	public function disableLink( $id ) {
		return $this->requester->request('PUT', $this->url . '/' . $id . '/disable');
	}

	public function getLink( $id ) {
		return $this->requester->request('GET', $this->url . '/' . $id);
	}

	public function getLinks() {
		return $this->requester->request('GET', $this->url);
	}
}

class Gumroad_Exception extends Exception
{

}

?>
