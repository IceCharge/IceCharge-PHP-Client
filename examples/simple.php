<?php
/*  Copyright (c) 2011 Databracket LLC
 *  All rights reserved.
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions
 *  are met:
 *  1. Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *  2. Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 *  3. Neither the name of Databracket LLC nor the names of its contributors
 *     may be used to endorse or promote products derived from this software
 *     without specific prior written permission.
 *
 *  THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 *  IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 *  OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 *  IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 *  INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 *  NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 *  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 *  THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 *  THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
	require_once('../icecharge.php');

	$mid = 'YOUR_MID';
	$api_key = 'YOUR_API_KEY';

	$sid = isset($_COOKIE['manny']) ? $_COOKIE['manny'] : "".time();
	setcookie('manny', $sid);

	if (isset($_POST['ccn'])) {
		$txn = new TransactionSubmission();
		$txn->id = "" . time();
		$txn->sid = $_COOKIE['manny'];
		$txn->amount = 42000;
		$txn->currency = "USD";

		$txn->card = new Card();
		$txn->card->ccn = $_POST['ccn'];
		$txn->card->cvv = $_POST['cvv'];

		$ba = new Address();
		$ba->name = $_POST['name'];
		$ba->country = $_POST['country'];
		$ba->city = $_POST['city'];
		$ba->state = $_POST['state'];
		$ba->street = $_POST['street'];
		$ba->zip = $_POST['zip'];
		$txn->card->billing_address = $ba;

		try {
			$client = new IceChargeClient($mid, $api_key);
			die($client->submitTransaction($txn));
		} catch(Exception $e) {
			die("Error: <pre>$e</pre>");
		}
	}
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Sample Checkout</title>
	<style type="text/css">
	label {
		width: 5em;
		display: inline-block;
	}
	</style>

	<script type="text/javascript">
		var IC_config = {
			mid: '<?=$mid?>',
			sid_cookie: 'manny'
		};

		(function(d,t){
			var ic=d.createElement(t),s=d.getElementsByTagName(t)[0];
			ic.async=ic.src='https://api.icecharge.com/ic.js';
			s.parentNode.insertBefore(ic,s)
		})(document,'script');
	</script>
</head>
<body>
	<form action="" method="POST">
		<p>
			<label for="ccn">CCN:</label>
			<input type="text" id="ccn" name="ccn" value="1234444789"/>
		</p>
		<p>
			<label for="cvv">CVV:</label>
			<input type="text" id="cvv" name="cvv" value="4242"/>
		</p>
		<p>
			<label for="name">Name:</label>
			<input type="text" id="name" name="name" value="Nick Zirchofsky" />
		</p>
		<p>
			<label for="country">Country:</label>
			<input type="text" id="country" name="country" value="USA" />
		</p>
		<p>
			<label for="city">City:</label>
			<input type="text" id="city" name="city" value="NYC" />
		</p>
		<p>
			<label for="state">State:</label>
			<input type="text" id="state" name="state" value="NY" />
		</p>
		<p>
			<label for="street">Street:</label>
			<input type="text" id="street" name="street" value="1234 Broadway Ave" />
		</p>
		<p>
			<label for="zip">ZIP:</label>
			<input type="text" id="zip" name="zip" value="11111" />
		</p>

		<input type="submit" value="Checkout" />
	</form>
</body>
</html>
