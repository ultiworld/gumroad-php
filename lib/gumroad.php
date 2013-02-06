<?php
/**
 * Gumroad PHP Client Library
 *
 * PHP wrapper for Gumroad API. Originally developed for Ultiworld.
 * 
 * @package Gumroad
 * @author  Orion Burt <orionjburt@gmail.com>
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */

/** base API URL */
define( 'GUMROAD_API_URL', 'https://gumroad.com/api/v1' );
/** default timeout to use with cURL */
define( 'GUMROAD_API_TIMEOUT', 5000 );

/**
 * Gumroad Client Class
 * 
 * Core class used to access all Gumroad functionality
 */
class Gumroad_Client
{
	/**
	 * A requester object to pass between the various endpoint objects
	 * 
	 * @var Gumroad_Requester
	 */
	public $requester;

	/**
	 * The API endpoint for /sessions
	 * 
	 * @var Gumroad_Sessions_Endpoint
	 */
	public $sessions;

	/**
	 * The API endpoint for /links
	 * 
	 * @var Gumroad_Links_Endpoint
	 */
	public $links;

	/**
	 * Initializes a Gumroad_Client object
	 * 
	 * Creates a new requester object for the client to use and passes it to the endpoint objects
	 */
	public function __construct() {
		$this->requester = new Gumroad_Requester();
		$this->sessions = new Gumroad_Sessions_Endpoint($this->requester);
		$this->links = new Gumroad_Links_Endpoint($this->requester);
	}

	/**
	 * Shortcut function for authentication
	 * 
	 * @param string $email
	 * @param string $password
	 * @return array $response
	 */
	public function auth( $email, $password ) {
		$params = array('email' => $email, 'password' => $password);
		return $this->sessions->authenticate($params);
	}

	/**
	 * Shortcut function for deauthentication
	 * 
	 * @return array $response
	 */
	public function deauth() {
		return $this->sessions->deauthenticate();
	}

	/**
	 * Ensures authenticated sessions are deauthenticated on destruction
	 */
	public function __destruct() {
		if( $this->requester->token ) {
			$this->deauth();
		}
	}
}

/**
 * Gumroad Requester Class
 * 
 * Executes HTTP requests with cURL
 * Uses session token (if one is stored)
 */
class Gumroad_Requester 
{
	/**
	 * Timeout (milliseconds) to use with cURL requests
	 * 
	 * @var int
	 */
	public $timeout;

	/**
	 * Stored session token
	 * 
	 * @var mixed
	 */
	public $token;

	/**
	 * Initializes Gumroad_Requester object
	 * 
	 * Sets $timeout value and empty $token
	 * @param int $timeout defaults to GUMROAD_API_TIMEOUT
	 */
	public function __construct( $timeout=GUMROAD_API_TIMEOUT ) {
		$this->timeout = $timeout;
		$this->token = null;
	}

	/**
	 * Build and execute HTTP request
	 * 
	 * @param string $method
	 * @param string $url
	 * @param array $params
	 * @return array $response
	 */
	public function request( $method, $url, $params = array() ) {
		# Build request
		$ch   = curl_init();	
		$params['token'] = $this->token;
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
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->timeout);
		# End of build request

		$response = curl_exec($ch);
		
		# Check response for cURL errors
		$curlError = curl_error($ch);
		if ( $curlError ) {
			throw new Gumroad_Exception($curlError);
		}

		# Convert JSON response to an associative array 
		# and append the HTTP status code
		$response = json_decode($response, TRUE);
		$response['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		return $response;
	}
}

/**
 * Gumroad Endpoint Abstract Class
 * 
 * Stores the endpoint URL and a requester object
 */
abstract class Gumroad_EndpointAbstract
{
	/**
	 * Endpoint URL
	 * 
	 * @var string
	 */
	public $url;

	/**
	 * Requester object
	 * 
	 * @var Gumroad_Requester
	 */
	public $requester;

	/**
	 * Initializes Gumroad_EndpointAbstract object
	 * 
	 * Should be called by child classes to set the requester object and base API URL
	 * @param Gumroad_Requester $requester
	 * @param string $url defaults to GUMROAD_API_URL
	 */
	public function __construct( $requester, $url=GUMROAD_API_URL ) {
		$this->requester = $requester;
		$this->url = $url;
	}
}

/**
 * Gumroad Sessions Endpoint Class
 * 
 * Used in conjunction with a Gumroad_Requester object to access the /sessions endpoint
 */
class Gumroad_Sessions_Endpoint extends Gumroad_EndpointAbstract
{
	/**
	 * Initializes a Gumroad_Sessions_Endpoint object
	 * 
	 * Calls parent constructor and adds on endpoint path to base API URL
	 * @param Gumroad_Requester $requester
	 * @param string $path defaults to '/sessions'
	 */
	public function __construct( $requester, $path='/sessions' ) {
		parent::__construct($requester);
		$this->url = $this->url . $path;
	}

	/**
	 * Sends request to authenticate a new session 
	 * 
	 * @param array $params
	 * @return array $response
	 */
	public function authenticate( $params ) {
		$response = $this->requester->request('POST', $this->url, $params);
		$this->requester->token = $response['token'];
		return $response;

	}

	/**
	 * Sends request to deautheticate the active session
	 * 
	 * @return array $response
	 */
	public function deauthenticate() {
		$response = $this->requester->request('DELETE', $this->url);
		$this->requester->token = null;
		return $response;
	}
}

/**
 * Gumroad Links Endpoint Class
 * 
 * Used in conjunction with a Gumroad_Requester object to access the /links endpoint
 */
class Gumroad_Links_Endpoint extends Gumroad_EndpointAbstract
{
	/**
	 * Initializes a Gumroad_Links_Endpoint object
	 * 
	 * Calls parent constructor and adds on endpoint path to base API URL
	 * @param Gumroad_Requester $requester
	 * @param string $path defaults to '/links'
	 */
	public function __construct( $requester, $path='/links' ) {
		parent::__construct($requester);
		$this->url = $this->url . $path;
	}

	/**
	 * Sends request to get all links
	 * 
	 * @return array $response
	 */
	public function getLinks() {
		return $this->requester->request('GET', $this->url);
	}

	/**
	 * Sends request to get a link
	 * 
	 * @param string $id
	 * @return array $response
	 */
	public function getLink( $id ) {
		return $this->requester->request('GET', $this->url . '/' . $id);
	}

	/**
	 * Sends request to create a link
	 * 
	 * @param array $params
	 * @return array $response
	 */
	public function createLink( $params ) {
		return $this->requester->request('POST', $this->url, $params);
	}

	/**
	 * Sends request to edit a link
	 * 
	 * @param string $id
	 * @param array $params
	 * @return array $response
	 */
	public function editLink( $id, $params ) {
		return $this->requester->request('PUT', $this->url . '/' . $id, $params);
	}

	/**
	 * Sends request to enable (aka publish) a link
	 * 
	 * @param string $id
	 * @return array $response
	 */
	public function enableLink( $id ) {
		return $this->requester->request('PUT', $this->url . '/' . $id . '/enable');
	}

	/**
	 * Sends request to disable (aka unpublish) a link
	 * 
	 * @param string $id
	 * @return array $response
	 */
	public function disableLink( $id ) {
		return $this->requester->request('PUT', $this->url . '/' . $id . '/disable');
	}

	/**
	 * Sends request to delete a link
	 * 
	 * @param string $id
	 * @return array $response
	 */
	public function deleteLink( $id ) {
		return $this->requester->request('DELETE', $this->url . '/' . $id);
	}
}

/**
 * Gumroad Exception Class
 * 
 * Simple extension of the Exception class
 */
class Gumroad_Exception extends Exception
{

}

?>
