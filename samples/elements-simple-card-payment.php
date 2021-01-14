<?php

require_once '../vendor/autoload.php';

// Find your own API keys at https://dashboard.stripe.com/test/apikeys
$config = [
	'publishableKey' => 'pk_test_TYooMQauvdEDq54NiTphI7jx',
	'secretKey' => 'sk_test_4eC39HqLyjWDarjtT1zdp7dc',
];

$title = 'Stripe Elements Simple Card Payment';
	
$stripeConfiguration = [
	'api_key' => $config['secretKey'],
	'stripe_version' => '2020-08-27', // See https://stripe.com/docs/upgrades for details about Stripe API versions
];

$stripe = new \Stripe\StripeClient($stripeConfiguration);

try {
	$paymentIntent = $stripe->paymentIntents->create([
		'amount' => 4200,
		'currency' => 'usd',
	]);
}
catch (Exception $e) {
	echo 'Unable to create Payment Intent: ' . $e->getMessage();
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title><?php echo $title; ?></title>
		<script src="https://js.stripe.com/v3/"></script>
	</head>
	<body>
		<h1><?php echo $title; ?></h1>
		
		<p id="card-element"></p>
		
		<p><button onclick="pay()">Pay Now</button></p>
		
		<hr>
		
		<pre id="output"></pre>
		
		<script>
			
			var stripe = Stripe('<?php echo $config['publishableKey']; ?>');
			
			var output = document.getElementById('output');
			
			var elements = stripe.elements();
			
			var cardElement = elements.create('card');
			
			cardElement.mount('#card-element');
			
			function pay() {
				stripe.confirmCardPayment('<?php echo $paymentIntent->client_secret; ?>', {
					payment_method: {
						card: cardElement,
					}
				})
				.then(function (result) {
					if (result.error) {
						output.innerHTML = result.error.message;
						return;
					}
					
					output.innerHTML = JSON.stringify(result.paymentIntent, null, 4);
				});
			}
			
		</script>
	</body>
</html>
