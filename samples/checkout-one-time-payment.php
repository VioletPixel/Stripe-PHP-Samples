<?php

require_once '../vendor/autoload.php';

// Find your API keys at https://dashboard.stripe.com/test/apikeys
$config = [
	'publishableKey' => 'pk_test_TYooMQauvdEDq54NiTphI7jx',
	'secretKey' => 'sk_test_4eC39HqLyjWDarjtT1zdp7dc',
	'priceID' => 'price_1I78ce2eZvKYlo2CgF0hJUyY', // Configure Products and Prices in the Dashboard: https://dashboard.stripe.com/test/products
];

$title = 'Stripe Checkout One-Time Payment';
	
$stripeConfiguration = [
	'api_key' => $config['secretKey'],
	'stripe_version' => '2020-08-27', // See https://stripe.com/docs/upgrades for details about Stripe API versions
];

$stripe = new \Stripe\StripeClient($stripeConfiguration);

try {
	$checkoutSession = $stripe->checkout->sessions->create([ // https://stripe.com/docs/api/checkout/sessions/create?lang=php
		'success_url' => 'https://example.com?/?success', // After a successful payment Checkout will redirect to this URL
		'cancel_url' => 'https://example.com/?cancel', // The back button and logo on the Checkout page will point to this URL
		'mode' => 'payment',
		'payment_method_types' => [
			'card'
		],
		'line_items' => [
			[
				'price' => $config['priceID'], 
				'quantity' => 1,
			],
		],
	]);
}
catch (Exception $e) {
	echo 'Unable to create Checkout Session: ' . $e->getMessage();
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title><?php echo $title; ?></title>
		<style><?php include '../style.css'; ?></style>
	</head>
	<body>
		<h1><?php echo $title; ?></h1>
		
		<p>Click on the link below to purchase a $42 example product using Stripe Checkout.</p>
		
		<p><a href="<?php echo $checkoutSession->url; ?>">Checkout</a></p>
	</body>
</html>
