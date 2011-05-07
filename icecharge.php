<?php

	// Library version
	$version = "IceCharge Client v1.0";

	// ensure Curl is installed
	if (!extension_loaded("curl"))
		throw(new Exception(
					"Curl extension is required for IceCharge PHP client to work"));


	/*
	 * IceChargeException is useful to catch this exception separately
	 * from general PHP exceptions, if you want.
	 */

	class IceChargeException extends Exception {}

	/*
	 * IceChargeResponse holds all the REST response data.
	 * Before using the response, check IsError to see if an exception
	 * occurred with the data sent to IceCharge.
	 *
	 * Response: will contain the response {xml,json} object.
	 * HttpStatus: is the response code of the request.
	 * Format: will contain either 'json' or 'xml'.
	 * IsError: is true when HttpStatus != 200.
	 * ErrorMessage: equals Response when there is an error.
	 */

	class IceChargeResponse {

		public $Response;
		public $HttpStatus;
		public $Format;
		public $IsError;
		public $ErrorMessage;

		public function __construct($response, $status, $json = true) {
			$this->HttpStatus = $status;

			if ($json)
				$this->Format = "json";
			else
				$this->Format = "xml";

			$this->IsError = ($this->HttpStatus != 200);

			if ($json) {
				$this->Response = json_decode($response);

				if ($this->Response == null)
					throw (new IceChargeException("syntax error in IceCharge's JSON response"));

				if ($this->IsError)
					$this->ErrorMessage = $this->Response->errmsg;
			} else {
				$this->Response = simplexml_load_string($response);

				if ($this->Response == false)
					throw (new IceChargeException("syntax error in IceCharge's XML response"));

				if ($this->IsError)
					$this->ErrorMessage = $this->Response->ErrorMessage;
			}
		}
	}

	/*
	 * Transaction holds all the REST response data for a transaction.
	 *
	 * Status: contains the transaction status.
	 */

	class Transaction {

		public $Status;

		public function __construct($response) {
			if ($response->Format == "json") {
				$this->Status = $response->Response->status;
			} else {
				$this->Status = $response->Response->Status;
			}
		}
	}

	/*
	 * IceChargeClient: the core REST client, talks to the IceCharge
	 * REST API. Returns an IceChargeResponse object for all responses
	 * if IceCharge's API was reachable throws an IceChargeException
	 * if IceCharge's REST API was unreachable.
	 */

	class IceChargeClient {

		private $EndPoint;
		private $AccountID;
		private $APIKey;
		private $APIVersion;

		/*
		 * __construct
		 *	$username: Your AccountID
		 *	$password: Your API key
		 *	$version: IceCharge's API version
		 *	$endpoint: The IceCharge REST Service URL, currently defaults to
		 * https://api.icecharge.com
		 */
		public function __construct($username, $password, $version,
				$endpoint = "https://api.icecharge.com") {

			$this->AccountID = $username;
			$this->APIKey = $password;
			$this->APIVersion = $version;
			$this->EndPoint = $endpoint;
		}

		/*
		 * request
		 *	$path: the URI for the request
		 *	$method: the HTTP method to use, defaults to GET
		 *	$data: for POST, data to send in the form of JSON or XML
		 *	$json: a boolean flag to indicate that data is in JSON format,
		 *		defaults to true
		 *
		 *	return: IceChargeResponse
		 */
		public function request($path, $method = "GET", $data, $json = true) {
			$curl = curl_init();

			if (!$curl)
				throw (new Exception("Curl initialization failed"));

			$userpwd = $this->AccountID . ':' . $this->APIKey;

			$type;

			if ($json)
				$type = "json";
			else
				$type = "xml";

			$url = $this->EndPoint . '/' . $this->APIVersion . '/' .
				$type . '/' . $path;

			$headers = array("User-Agent: " . $version,
					"Content-Type: application/" . $type);

			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
			curl_setopt($curl, CURLOPT_USERPWD, $userpwd);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_NOPROGRESS, true);

			switch (strtoupper($method)) {
				case "GET":
					curl_setopt($curl, CURLOPT_HTTPGET, true);
					break;

				case "POST":
					curl_setopt($curl, CURLOPT_POST, true);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
					break;

				default:
					throw (new IceChargeException("unknown method $method"));
					break;
			}

			$result = curl_exec($curl);

			if ($result == false)
				throw (new IceChargeException("curl failed with error " .
							curl_error($curl)));

			$rescode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			return new IceChargeResponse($result, $rescode);
		}

		/*
		 * getTransaction
		 *	$txnid: transaction ID, provided by the merchant
		 *
		 *	return: Transaction
		 */
		public function getTransaction($txnid, $json = true) {
			$path = "transactions/" . $txnid;

			$response = $this->request($path, "GET", "", $json);

			if ($response->IsError)
				throw (new IceChargeException($response->ErrorMessage));

			return new Transaction($response);
		}
	}
?>