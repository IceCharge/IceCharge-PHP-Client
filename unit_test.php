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

	require_once ('icecharge.php');

	// Expected results.
	$address_json_text = '{"name":"Amr Ali","country":"Egypt","city":"Alexandria","state":"Alexandria","street":"Salame St.","zip":"12345"}';
	$saddress_xml_text = '<?xml version="1.0"?>' . "\n" . '<shipping_address name="Amr Ali" country="Egypt" city="Alexandria" state="Alexandria" street="Salame St." zip="12345"/>' . "\n";
	$baddress_xml_text = '<?xml version="1.0"?>' . "\n" . '<billing_address name="Amr Ali" country="Egypt" city="Alexandria" state="Alexandria" street="Salame St." zip="12345"/>' . "\n";
	$card_json_text = '{"ccn":"3c0ec228d630fc929ed5e64542a9d39e45871a78e6fcaab068dfbc4defb2decf62a870dc9d42f05a2bea624d12631752ac469ddf9bb8a694a04c66b063a5b234","cvv":"c687d26589675a7fb57e3b05d2099f0ce644508dc496c3f7282119569c4cf827787375db6c5e500f9a32c46211b5d5230590e4fd86b1600ba70822819c16378e","token":"1234XXXXXXXXXXXXXX345","billing_address":{"name":"Amr Ali","country":"Egypt","city":"Alexandria","state":"Alexandria","street":"Salame St.","zip":"12345"}}';
	$card_xml_text = '<?xml version="1.0"?>' . "\n" . '<card ccn="3c0ec228d630fc929ed5e64542a9d39e45871a78e6fcaab068dfbc4defb2decf62a870dc9d42f05a2bea624d12631752ac469ddf9bb8a694a04c66b063a5b234" cvv="c687d26589675a7fb57e3b05d2099f0ce644508dc496c3f7282119569c4cf827787375db6c5e500f9a32c46211b5d5230590e4fd86b1600ba70822819c16378e" token="1234XXXXXXXXXXXXXX345"><billing_address name="Amr Ali" country="Egypt" city="Alexandria" state="Alexandria" street="Salame St." zip="12345"/></card>' . "\n";
	$txn_json_text = '{"id":"T2887","sid":null,"amount":445,"currency":"USD","card":{"ccn":"3c0ec228d630fc929ed5e64542a9d39e45871a78e6fcaab068dfbc4defb2decf62a870dc9d42f05a2bea624d12631752ac469ddf9bb8a694a04c66b063a5b234","cvv":"c687d26589675a7fb57e3b05d2099f0ce644508dc496c3f7282119569c4cf827787375db6c5e500f9a32c46211b5d5230590e4fd86b1600ba70822819c16378e","token":"1234XXXXXXXXXXXXXX345","billing_address":{"name":"Amr Ali","country":"Egypt","city":"Alexandria","state":"Alexandria","street":"Salame St.","zip":"12345"}},"shipping_address":{"name":"Amr Ali","country":"Egypt","city":"Alexandria","state":"Alexandria","street":"Salame St.","zip":"12345"}}';
	$txn_xml_text = '<?xml version="1.0"?>' . "\n" . '<transaction id="T2887" sid="" amount="445" currency="USD"><card ccn="3c0ec228d630fc929ed5e64542a9d39e45871a78e6fcaab068dfbc4defb2decf62a870dc9d42f05a2bea624d12631752ac469ddf9bb8a694a04c66b063a5b234" cvv="c687d26589675a7fb57e3b05d2099f0ce644508dc496c3f7282119569c4cf827787375db6c5e500f9a32c46211b5d5230590e4fd86b1600ba70822819c16378e" token="1234XXXXXXXXXXXXXX345"><billing_address name="Amr Ali" country="Egypt" city="Alexandria" state="Alexandria" street="Salame St." zip="12345"/></card><shipping_address name="Amr Ali" country="Egypt" city="Alexandria" state="Alexandria" street="Salame St." zip="12345"/></transaction>' . "\n";

	// Initializing Card.
	$card = new Card();

	$card->ccn = '123451234512134512345';
	$card->cvv = '456';

	// Setting card's billing address.
	$card->billing_address = new Address();
	$card->billing_address->name = 'Amr Ali';
	$card->billing_address->country = 'Egypt';
	$card->billing_address->city = 'Alexandria';
	$card->billing_address->state = 'Alexandria';
	$card->billing_address->street = 'Salame St.';
	$card->billing_address->zip = '12345';

	// Initializing TransactionSubmission.
	$txn = new TransactionSubmission();

	$txn->id = 'T2887';
	$txn->amount = 445.0;
	$txn->currency = 'USD';
	$txn->card = $card;

	// Setting transaction's shipping address.
	$txn->shipping_address = $card->billing_address;

	// Testing JSON formats.
	$isCardBillingAddressExpected = ($card->billing_address->toJSON() == $address_json_text);
	$isTxnShippingAddressExpected = ($txn->shipping_address->toJSON() == $address_json_text);

	if ($isCardBillingAddressExpected) {
		echo "JSON: Billing Address [+].\n";
	} else {
		echo "JSON: Billing Address [-].\n";
	}

	if ($isTxnShippingAddressExpected) {
		echo "JSON: Shipping Address [+].\n";
	} else {
		echo "JSON: Shipping Address [-].\n";
	}

	$isCardExpected = ($card->toJSON() == $card_json_text);

	if ($isCardExpected) {
		echo "JSON: Card [+].\n";
	} else {
		echo "JSON: Card [-].\n";
	}

	// Resetting CCN and CVV fields to be tested with TransactionSubmission..
	$card->ccn = '123451234512134512345';
	$card->cvv = '456';

	$isTxnExpected = ($txn->toJSON() == $txn_json_text);

	if ($isTxnExpected) {
		echo "JSON: Transaction [+].\n";
	} else {
		echo "JSON: Transaction [-].\n";
	}

	// Resetting CCN and CVV fields to be tested with XML format.
	$card->ccn = '123451234512134512345';
	$card->cvv = '456';

	$isCardBillingAddressExpected = ($card->billing_address->toXML('billing_address')->asXML() == $baddress_xml_text);
	$isTxnShippingAddressExpected = ($txn->shipping_address->toXML('shipping_address')->asXML() == $saddress_xml_text);

	if ($isCardBillingAddressExpected) {
		echo "XML: Billing Address [+].\n";
	} else {
		echo "XML: Billing Address [-].\n";
	}

	if ($isTxnShippingAddressExpected) {
		echo "XML: Shipping Address [+].\n";
	} else {
		echo "XML: Shipping Address [-].\n";
	}

	$isCardExpected = ($card->toXML('card')->asXML() == $card_xml_text);

	if ($isCardExpected) {
		echo "XML: Card [+].\n";
	} else {
		echo "XML: Card [-].\n";
	}

	// Resetting CCN and CVV fields to be tested with TransactionSubmission.
	$card->ccn = '123451234512134512345';
	$card->cvv = '456';

	$isTxnExpected = ($txn->toXML('transaction')->asXML() == $txn_xml_text);

	if ($isTxnExpected) {
		echo "XML: Transaction [+].\n";
	} else {
		echo "XML: Transaction [-].\n";
	}

?>
