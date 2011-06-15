<?php

	// Library version
	$libVersion = "IceCharge Client v1.0";

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
	 * Utils: Some utility functions.
	 */

	class utils {
		public static function incEntropy($val) {
			return hash("sha256", hash("sha256", $val, true) . $val);
		}
	}

	/*
	 * Interface for objects that are supposed to return JSON and XML objects.
	 *
	 * toJSON: Returns a JSON object.
	 * toXML: Returns a XML object.
	 */

	interface iFormat {
		public function toJSON();
		public function toXML($element);
	}

	/*
	 * A class to imitate an enum for response formats.
	 */

	class ResponseFormat {
		const Json = 0;
		const Xml = 1;
	}

	/*
	 * A class to imitate an enum for HTTP methods.
	 */

	class HttpMethod {
		const GET = 0;
		const POST = 1;
	}

	/*
	 * A class to imitate an enum for HTTP statuses.
	 */

	class HttpStatus {
		const OK = 200;
		const BAD_REQUEST = 400;
		const UNAUTHORIZED = 401;
		const NOT_FOUND = 404;
		const HTTP_METHOD_NOT_ALLOWED = 405;
		const INTERNAL_SERVER_ERROR = 500;
		const HTTP_VERSION_NOT_SUPPORTED = 505;
	}

	/*
	 * A class to imitate an enum for a transaction payment status.
	 */

	class PaymentStatus {
		const PENDING = 'P';
		const ACCEPTED = 'A';
		const REJECTED = 'R';
		const CHARGEBACK = 'C';
	}

	/*
	 * A class to imitate an enum for a transaction verdict.
	 */

	class Verdict {
		const PENDING = 'P';
		const ACCEPTED = 'A';
		const REJECTED = 'R';
		const MANUAL = 'M';
		const OOB = 'O';
	}

	/*
	 * IceChargeResponse holds all the REST response data.
	 * Before using the response, check IsError to see if an exception
	 * occurred with the data sent to IceCharge.
	 *
	 * Response: will contain the response {xml,json} object.
	 * HttpStatus: is the response code of the request.
	 * Format: will be either one of two values in ResponseFormat.
	 * IsError: is true when HttpStatus != 200.
	 * ErrorMessage: error message returned if available.
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
				$this->Format = ResponseFormat::Json;
			else
				$this->Format = ResponseFormat::Xml;

			$this->IsError = ($this->HttpStatus != HttpStatus::OK);

			if ($json) {
				$this->Response = json_decode($response);

				if ($this->Response == null)
					throw (new IceChargeException("syntax error in IceCharge's JSON response"));

				if ($this->IsError)
					$this->ErrorMessage = $this->Response->error->message;
			} else {
				$this->Response = simplexml_load_string($response);

				if ($this->Response == false)
					throw (new IceChargeException("syntax error in IceCharge's XML response"));

				if ($this->IsError)
					$this->ErrorMessage = $this->Response->error['message'];
			}
		}

		public function throw_if_error($action) {
			if ($this->IsError)
				throw (new IceChargeException("failed to $action: $this->ErrorMessage"));
		}
	}

	/*
	 * Transaction holds all the REST response data for a transaction.
	 *
	 * PaymentStatus: contains the transaction payment status.
	 * Verdict: contains the transaction verdict.
	 */

	class Transaction {
		public $PaymentStatus;
		public $Verdict;

		public function __construct($response) {
			if ($response->Format == ResponseFormat::Json) {
				$this->PaymentStatus = $response->Response->transaction->payment_status;
				$this->Verdict = $response->Response->transaction->verdict;
			} else {
				$this->PaymentStatus = $response->Response->transaction['payment_status'];
				$this->Verdict = $response->Response->transaction['verdict'];
			}
		}
	}

	/* OOB holds all possible REST responses for an OOB related action.
	 *
	 * Token: contains a generated token based on either a SID or a TID.
	 * Status: contains whether sending SMS was successful or not.
	 */

	class OOB {
		public $Token;
		public $Status;

		public function __construct($response) {
			if ($response->Format == ResponseFormat::Json) {
				$this->Token = $response->Response->oob->token;
				$this->Status = $response->Response->oob->status;
			} else {
				$this->Token = $response->Response->oob['token'];
				$this->Status = $response->Response->oob['status'];
			}
		}
	}

	/*
	 * Address is a structure to store an address detail without having to
	 * deal with JSON or XML format directly. Another level of abstraction
	 * if you will.
	 *
	 * name: Person's name exists at that address.
	 * ctry: Country Part of the address. [ISO 3166-1]
	 * city: City Part of the address.
	 * state: State Part of the address.
	 * st: Street Part of the address.
	 * zip: ZIP codes can also hold postal codes and it is optional.
	 */

	class Address implements iFormat {
		public $name;
		public $ctry;
		public $city;
		public $state;
		public $st;
		public $zip;

		public function toJSON() {
			return json_encode($this);
		}

		public function toXML($element) {
			$xml = new SimpleXMLElement('<' . $element . '>' . '</' . $element . '>');

			$xml->addAttribute('name', $this->name);
			$xml->addAttribute('ctry', $this->ctry);
			$xml->addAttribute('city', $this->city);
			$xml->addAttribute('state', $this->state);
			$xml->addAttribute('st', $this->st);
			$xml->addAttribute('zip', $this->zip);

			return $xml;
		}
	}

	/*
	 * Card is a structure to store a card details without having to
	 * deal with JSON or XML formats directly. Another level of abstration
	 * if you will.
	 *
	 * ccn: Credit Card Number. (Sent Encrypted)
	 * cvv: Card Verification Value. (Sent Encrypted)
	 * tok: CCN Token in the form "1234XX...XX123" [READONLY]
	 * ba: Billing Address Takes an Address object
	 */

	class Card implements iFormat {
		public $ccn;
		public $cvv;
		public $tok;
		public $ba;

		private function _hash_val($val) {
			return hash("sha512", hash("sha512", $val, true) . $val);
		}

		// Tokenizing CCN to 1234XXX...XXX123.
		private function _tokenize() {
			$len = strlen($this->ccn);
			$arr = str_split($this->ccn);

			for ($i = 3; $i++ < $len - 4;) {
				$arr[$i] = 'X';
			}

			$this->tok = implode($arr);
		}

		public function toJSON() {
			$this->_tokenize();
			$this->ccn = $this->_hash_val($this->ccn);
			$this->cvv = $this->_hash_val($this->cvv);
			return json_encode($this);
		}

		public function toXML($element) {
			$this->_tokenize();
			$this->ccn = $this->_hash_val($this->ccn);
			$this->cvv = $this->_hash_val($this->cvv);

			$xml = new SimpleXMLElement('<' . $element . '>' . '</' . $element . '>');

			$xml->addAttribute('ccn', $this->ccn);
			$xml->addAttribute('cvv', $this->cvv);
			$xml->addAttribute('tok', $this->tok);
			$xml->addAttribute('ba', $this->ba->toXML("ba"));

			return $xml;
		}
	}

	/*
	 * TransactionSubmission is a structure to pass on all the data required
	 * to profile a transaction.
	 *
	 * id: Merchant generated ID for each transaction
	 * sid: Merchant provided Session ID to correlate between a device submission and a transaction.
	 * amt: Amount will be paid for this transaction
	 * cur: Currency in which the amount will be paid in
	 * card: Takes a Card object
	 * sa: Shipping Address Takes an Address object
	 */

	class TransactionSubmission implements iFormat {
		public $id;
		public $sid;
		public $amt;
		public $cur;
		public $card;
		public $sa;

		public function toJSON() {
			$this->sid = utils::incEntropy($this->sid);
			return json_encode($this);
		}

		public function toXML($element) {
			$this->sid = utils::incEntropy($this->sid);
			$xml = new SimpleXMLElement('<' . $element . '>' . '</' . $element . '>');

			$xml->addAttribute('id', $this->id);
			$xml->addAttribute('sid', $this->sid);
			$xml->addAttribute('amt', $this->amt);
			$xml->addAttribute('cur', $this->cur);
			$xml->addChild($this->card->toXML("card"));
			$xml->addChild($this->sa->toXML("sa"));

			return $xml;
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
		 *
		 * username: Your AccountID
		 * password: Your API key
		 * version: IceCharge's API version
		 * endpoint: The IceCharge REST Service URL, currently defaults to
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
		 *
		 * path: the URI for the request
		 * method: the HTTP method to use, defaults to GET
		 * data: for POST, data to send in the form of JSON or XML
		 * json: a boolean flag to indicate that data is in JSON format,
		 *		defaults to true
		 *
		 * return: IceChargeResponse
		 */
		public function request($path, $method = HttpMethod::GET, $data, $json = true) {
			$curl = curl_init();

			if (!$curl)
				throw (new Exception("Curl initialization failed"));

			$userpwd = $this->AccountID . ':' . $this->APIKey;

			$format;

			if ($json)
				$format = "json";
			else
				$format = "xml";

			$url = "$this->EndPoint/$this->APIVersion/$format/$path";

			$headers = array("User-Agent: " . $libVersion,
					"Content-Type: application/" . $type);

			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
			curl_setopt($curl, CURLOPT_USERPWD, $userpwd);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($curl, CURLOPT_NOPROGRESS, true);

			switch (strtoupper($method)) {
				case HttpMethod::GET:
					curl_setopt($curl, CURLOPT_HTTPGET, true);
					break;

				case HttpMethod::POST:
					curl_setopt($curl, CURLOPT_POST, true);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
					break;

				default:
					throw (new IceChargeException("unsupported method $method"));
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
		 * txnID: Transaction ID, provided by the merchant.
		 *
		 * return: Transaction
		 */
		public function getTransaction($txnID, $json = true) {
			$path = "transactions/$txnID";
			$method = HttpMethod::GET;

			$response = $this->request($path, $method, "", $json);

			$response->throw_if_error("getTransaction");

			return new Transaction($response);
		}
	}
?>
