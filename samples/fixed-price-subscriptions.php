<?php

// Display all errors and warnings
// Make sure you don't do this in production!
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

// Find your API keys at https://dashboard.stripe.com/test/apikeys
$config = [
	'publishableKey' => 'pk_test_TYooMQauvdEDq54NiTphI7jx',
	'secretKey' => 'sk_test_4eC39HqLyjWDarjtT1zdp7dc',
];

$title = 'Stripe Fixed Price Subscriptions';

$stripeConfiguration = [
	'api_key' => $config['secretKey'],
	'stripe_version' => '2020-08-27', // See https://stripe.com/docs/upgrades
];

$stripe = new \Stripe\StripeClient($stripeConfiguration);

// If you're using your own API keys you'll need to create your own Prices and fill in the details below
$plans = [
	[
		'name' => 'Basic - $5/month',
		'slug' => 'basic',
		'price' => 'price_1ICVga2eZvKYlo2CLaMQcPSh',
		'trialDays' => 0,
	],
	[
		'name' => 'Premium - $15/month',
		'slug' => 'premium',
		'price' => 'price_1ICVgs2eZvKYlo2C8H07Xw7u',
		'trialDays' => 0,
	],
	[
		'name' => 'Annual - $100/year',
		'slug' => 'annual',
		'price' => 'price_1ICpsf2eZvKYlo2CykCMyd66',
		'trialDays' => 7,
	],
];

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title><?php echo $title; ?></title>
		<style><?php include '../style.css'; ?></style>
		
		<!-- This is required for Stripe Elements -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<!-- Include and initialize Stripe.js -->
		<script src="https://js.stripe.com/v3/"></script>
		<script> const stripe = Stripe('<?php echo $config['publishableKey']; ?>'); </script>
		
		<!-- Syntax Highlighting for PHP code displayed at the bottom of the page -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/10.5.0/styles/atom-one-dark.min.css">
		<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/10.5.0/highlight.min.js"></script>
		<script> hljs.initHighlightingOnLoad(); </script>
	</head>
	<body>
		<h1><?php echo $title; ?></h1>

		<?php
		
		$page = isset($_GET['page']) ? $_GET['page'] : null;

		/***
		
		Sign Up Page
		
		By default the sign up page below is shown.  This page does the following:
		
		- Creates a Setup Intent
		- Collects the customer's email address
		- Asks the customer to select the plan they want to subscribe to
		- Uses the Stripe Card Element to collect the customer's card number, expiration date, and CVC
		- Collects the customer's billing address
		- Uses Stripe.js to set up the payment information provided for future use
		- Submits to the next page where the Subscription is created
		
		***/
		if (!$page) {
			try {
				// A Setup Intent is required to set up the payment information provided for future use.
				$setupIntent = $stripe->setupIntents->create();
			}
			catch (Exception $e) {
				logException($e)
				?>
				
				<p>Error creating Setup Intent: <?php echo $e; ?></p>
				
				<?php
				
				exit;
			}
			?>
			
			<h2>Sign Up</h2>
			
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>?page=subscribe" method="POST">
				<p><label>Email<br><input type="email" name="email" required></label></p>
				
				<h3>Choose a Plan</h3>
				
				<?php	
				foreach($plans as $aPlan) {
					$planDisplayName = $aPlan['name'];
					
					if ($aPlan['trialDays'] > 0) {
						$planDisplayName .= ' with ' . $aPlan['trialDays'] . '-day free trial';
					}
					
					echo '<p><label><input type="radio" name="plan" value="' . $aPlan['slug'] . '" required> ' . $planDisplayName . '</label></p>';
				}
				?>
				
				<h3>Card Information</h3>
				
				<p id="card-element"></p>
				
				<input id="payment-method" name="payment-method" type="hidden">
				
				<details>
					<summary>Stripe Test Cards</summary>
					
					<p>Try the following test card numbers to simulate different scenarios:</p>
					
					<dl>
						<dt>4242424242424242</dt>
						<dd>Always succeeds without requiring authentication.</dd>
						
						<dt>4000002500003155</dt>
						<dd>Requires authentication for one-time payments, but authentication will not be required for off-session payments if this card is set up for future off-session use using a Setup or Payment Intent.</dd>
						
						<dt>4000002760003184</dt>
						<dd>Requires authentication on all transactions regardless of how the card is set up.</dd>
						
						<dt>4000000000000002</dt>
						<dd>Always declines.</dd>
					</dl>
				</details>

				<h3>Billing Address</h3>
				
				<p><label>Name<br><input type="text" name="name" required></label></p>
				<p><label>Address Line 1<br><input type="text" name="line1" required></label></p>
				<p><label>Address Line 2 <small>(Optional)</small><br><input type="text" name="line2"></label></p>
				<p><label>City<br><input type="text" name="city" required></label></p>
				<p><label>State/County/Providence/Region<br><input type="text" name="state" required></label></p>
				<p><label>Zip/Postal Code<br><input type="text" name="postal_code" required></label></p>
				
				<p><button id="subscribe">Subscribe</button></p>
			</form>
			
			<script>
			
				const elements = stripe.elements();
				const cardElement = elements.create('card', {
					hidePostalCode: true,
				});
				cardElement.mount('#card-element');
				
				const form = document.querySelector('form');				
				const paymentMethodInput = document.querySelector('#payment-method');
				const subscribeButton = document.querySelector('#subscribe');
				
				form.addEventListener('submit', async event => {
					event.preventDefault();
					
					subscribeButton.disabled = true;
					
					const formData = new FormData(form);
					
					const {setupIntent, error} = await stripe.confirmCardSetup('<?php echo $setupIntent->client_secret; ?>', {
						payment_method: {
							type: 'card',
							card: cardElement,
							billing_details: {
								name: formData.get('name'),
								email: formData.get('email'),
								address: {
									line1: formData.get('line1'),
									line2: formData.get('line2'),
									city: formData.get('city'),
									state: formData.get('state'),
									postal_code: formData.get('postal_code'),
								},
							},
						},
					});
					
					if (error) {
						alert(error.message);
						subscribeButton.disabled = false;
						return;
					}
					
					alert(setupIntent.payment_method);
					
					paymentMethodInput.value = setupIntent.payment_method;
					
					form.submit();
				});
			
			</script>
			<?php
		} // End Sign Up page

		/***
		
		Subscribe Page
		
		This is the page the form on the Sign Up page submits to.  This page does the following:
		
		- Validates the input provided by the form
		- Creates a Customer in Stripe
		- Creates subscription parameters based on the plan selection made by the customer
		- Creates a Subscription
		- Handles additional payment actions if required
		- Redirects to the Subscription Detail page
		
		***/
		elseif ($page == 'subscribe') {
			?>
			
			<h2>Creating Subscription...</h2>
			
			<?php
			$email = $_POST['email'];
			$name = $_POST['name'];
			$planSlug = $_POST['plan'];
			$paymentMethodID = $_POST['payment-method'];
			
			$validationErrors = [];
			
			// Validate email
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$validationErrors[] = 'Invalid email address.';
			}
			
			// Validate name
			if (empty($name)) {
				$validationErrors[] = 'Invalid name.';
			}
			
			// Validate plan
			$plan = getPlanWithSlug($planSlug);
			
			if (!$plan) {
				$validationErrors[] = 'Invalid plan.';
			}
			
			// Validate Payment Method
			if (empty($paymentMethodID)) {
				$validationErrors[] = 'Invalid payment method.';
			}
			
			// Display validation errors
			if (count($validationErrors) > 0) {
				?>
				
				<p>Invalid information provided:</p>
			
				<ul>
					<?php
					foreach($validationErrors as $error) {
						echo '<li>' . $error . '</li>';
					}
					?>
				</ul>
				
				<p>Please <a href="<?php echo $_SERVER['PHP_SELF']; ?>">try signing up again</a>.</p>
				
				<?php
			}
			else {
				try {
					$customer = $stripe->customers->create([
						'email' => $email,
						'name' => $name,
						'payment_method' => $paymentMethodID,
						'invoice_settings' => [
							'default_payment_method' => $paymentMethodID,
						],
					]);
					
					$subscriptionParameters = [
						'customer' => $customer->id,
						'off_session' => true,
						'items' => [
							[
								'price' => $plan['price'],
							],
						],
						'expand' => [
							'latest_invoice.payment_intent',
						],	
					];
					
					if ($plan['trialDays']) {
						$subscriptionParameters['trial_period_days'] = $plan['trialDays'];
					}
								
					$subscription = $stripe->subscriptions->create($subscriptionParameters);
					
					$paymentIntent = $subscription->latest_invoice->payment_intent;
					
					if ($subscription->status == 'incomplete' && $paymentIntent->status == 'requires_action') {
						?>
							
						<p>Authenticating payment...</p>
						
						<script>
							
							async function confirmPayment() {
								const {paymentIntent, error} = await stripe.confirmCardPayment('<?php echo $paymentIntent->client_secret; ?>', {
									payment_method: '<?php echo $paymentMethod->id; ?>',
								});
								
								if (error) {
									alert('An error occurred confirming your payment: ' + error.message + ' You will be taken back to the previous page.');
									history.back();
									return;
								}
								
								if (paymentIntent && paymentIntent.status == 'succeeded') {
									window.location.href = '<?php echo detailPageForSubscription($subscription->id); ?>';
									return;
								}
								
								alert('An unknown error occurred confirming your payment.  You will be taken back to the previous page.');
								history.back();
							}
							
							confirmPayment();
						
						</script>
						
						<?php
					}
					else if (in_array($subscription->status, ['active', 'trialing'])) {
						?>
						
						<script> window.location.href = '<?php echo detailPageForSubscription($subscription->id); ?>'; </script>
						
						<?php
					}
					else {
						$errorLogMessage = 'Subscription and/or Payment Intent in unexpected state.';
						$errorLogMessage .= PHP_EOL . 'Subscription status: ' . $subscription->status;
						$errorLogMessage .= PHP_EOL . $subscription;
						$errorLogMessage .= PHP_EOL . 'Payment Intent status: ' . $paymentIntent->status;
						$errorLogMessage .= PHP_EOL . $paymentIntent;
						
						error_log($errorLogMessage);
						
						?>
							
						<p>An unexpected error occurred.</p>
						
						<p>Please <a href="<?php echo $_SERVER['PHP_SELF']; ?>">try signing up again</a>.</p>
						
						<?php
					}
				}
				catch (Exception $e) {
					logException($e)
					
					?>
					
					<p>Error: <?php echo $e; ?></p>
					
					<p>Please <a href="<?php echo $_SERVER['PHP_SELF']; ?>">try signing up again</a>.</p>
					
					<?php
				}
			}
		} // End Subscribe page
		
		/***
		
		Subscription Details Page
		
		This page displays Subscription details:
		
		- The Subscription and the associated upcoming Invoice are retrieved from the Stripe API
		- Various details from those objects are displayed
		- An option to change to a different plan is displayed
		- An option to end the Subscription is displayed
		
		***/
		elseif ($page == 'details') {
			$subscriptionID = $_GET['subscription'];
			
			try {
				$subscription = $stripe->subscriptions->retrieve($subscriptionID);
				
				$plan = getPlanFromSubscription($subscription);
				
				if (!$plan) {
					throw new Exception('Unable to find plan for Subscription: ' . $subscription);
				}
				
				$upcomingInvoice = $stripe->invoices->upcoming([
					'customer' => $subscription->customer,
					'subscription' => $subscription->id,
				]);
				
				?>
				<h2>Subscription Details</h2>
				
				<dl>
					<dt>Plan</dt>
					<dd><?php echo $plan['name']; ?></dd>
					
					<dt>Subscription Start Date</dt>
					<dd><?php echo date('r', $subscription->created); ?></dd>
					
					<dt>Status</dt>
					<dd><?php echo ucfirst($subscription->status); ?></dd>
					
					<dt>Next Payment Date</dt>
					<dd><?php echo date('r', $subscription->current_period_end); ?></dd>
					
					<dt>Next Payment Amount</dt>
					<dd><?php echo '$' . ($upcomingInvoice->amount_due / 100.0); ?></dd>
				</dl>
				
				<h3>Change Plan</h3>
				
				<p>Select a new plan to switch to:</p>
				
				<form id="changeForm" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=change&subscription=<?php echo $subscription->id; ?>" method="POST">
				
					<?php
					foreach($plans as $aPlan) {
						if ($aPlan['slug'] != $plan['slug']) {
							echo '<p><label><input type="radio" name="plan" value="' . $aPlan['slug'] . '" required> ' . $aPlan['name'] . '</label></p>';
						}
					}
					?>
					
					<p><button id="changeButton">Change Plan</button></p>
					
				</form>
				
				<script>
				
				const changeForm = document.querySelector('#changeForm');
				const changeButton = document.querySelector('#changeButton')
				
				changeForm.addEventListener('submit', event => {
					event.preventDefault();
					
					changeButton.disabled = true;
					
					if (confirm('Changing your plan will end any free trial you may have.  Are you sure you want to change your plan?')) {
						changeForm.submit();
					}
					else {
						changeButton.disabled = false;
					}
				});
				
				</script>
				
				<h3>End Subscription</h3>
				
				<form id="endForm" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=end&subscription=<?php echo $subscription->id; ?>" method="POST">
				
					<p><button id="endButton">End Subscription</button></p>
				
				</form>
				
				<script>
				
				const endForm = document.querySelector('#endForm');
				const endButton = document.querySelector('#endButton');
				
				endForm.addEventListener('submit', event => {
					event.preventDefault();
					
					endButton.disabled = true;
					
					if (confirm('Are you sure you want to end your subscription?')) {
						endForm.submit();
					}
					else {
						endButton.disabled = false;
					}
				});
				
				</script>
					
				<?php
			}
			catch (Exception $e) {
				logException($e);
				
				echo '<p>Unable to display subscription details.</p>';
			}
		} // End Subscription Details page
		
		/***
		
		Change Subscription Page
		
		This page handles changes made to a Subscription
		
		- The Subscription is retrieved from the Stripe API
		- The requested change is attempted
		- The customer is redirected back to the Subscription Details page
		
		***/
		elseif ($page == 'change') {
			?>
			
			<h2>Changing Subscription...</h2>
			
			<?php
			
			$subscriptionID = $_GET['subscription'];
			$planSlug = $_POST['plan'];
			
			try {
				$subscription = $stripe->subscriptions->retrieve($subscriptionID);
				
				$plan = getPlanWithSlug($planSlug);
				
				$currentPriceID = $subscription->items->data[0]->price->id;
				
				if (!$plan || $plan['price'] == $currentPrice) {
					?>
					
					<p>Error: Unable to change plan.</p>
					
					<p>Go back to <a href="<?php echo detailPageForSubscription($subscription->id); ?>">the subscription detail page</a> to try again.</p>
					
					<?php
				}
				else {
					$stripe->subscriptions->update($subscription->id, [
						'proration_behavior' => 'create_prorations',
						'trial_end' => 'now',
						'items' => [
							[
								'id' => $subscription->items->data[0]->id,
								'price' => $plan['price'],
							],
						],
					]);
					
					?>
					
					<p>Subscription updated!</p>
					
					<script> window.location.href = '<?php echo detailPageForSubscription($subscription->id); ?>'; </script>
					
					<?php
				}
			}
			catch (Exception $e) {
				logException($e);
				
				?>
				
				<p>Unable to change Subscription.</p>
				
				<p>Go back to <a href="<?php echo detailPageForSubscription($subscription->id); ?>">the subscription detail page</a> to try again.</p>
				
				<?php
			}
			
		} // End Change Subscription page
		
		/***
		
		End Subscription Page
		
		This page ends a Subscription
		
		- The Subscription is canceled
		- The customer is invited to sign up again
		
		***/
		elseif ($page == 'end') {
			?>
			
			<h2>Ending Subscription...</h2>
			
			<?php
			
			$subscriptionID = $_GET['subscription'];
			
			try {
				$subscription = $stripe->subscriptions->cancel($subscriptionID);
				
				?>
				
				<p>Subscription ended!</p>
				
				<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>">Sign Up</a></p>
				
				<?php
			}
			catch (Exception $e) {
				logException($e);
				
				?>
				
				<p>Unable to end Subscription.</p>
				
				<p>Go back to <a href="<?php echo detailPageForSubscription($subscription->id); ?>">the subscription detail page</a> to try again.</p>
				
				<?php
			}
		} // End End Subscription page
		
		$selfSource = file_get_contents(__FILE__);
		$selfSource = str_replace($config['secretKey'], 'sk_REDACTED', $selfSource);
		$selfSource = htmlentities($selfSource);
		
		?>
		
		<hr>
		
		<h3>Source Code</h3>
		
		<details>
			<summary>PHP Source Code</summary>
			<pre><code class="php"><?php echo $selfSource; ?></code></pre>
		</details>
	</body>
</html>

<?php

// Helper functions

function getPlanWithSlug($planSlug) {
	global $plans;
	
	foreach($plans as $aPlan) {
		if ($aPlan['slug'] == $planSlug) {
			return $aPlan;
		}
	}
	
	return null;
}

function getPlanFromSubscription($subscription) {
	global $plans;
	
	$priceID = $subscription->items->data[0]->price->id;
	
	foreach($plans as $aPlan) {
		if ($aPlan['price'] == $priceID) {
			return $aPlan;
		}
	}
	
	return null;
}

function detailPageForSubscription($subscriptionID) {
	return $_SERVER['PHP_SELF'] . '?page=details&subscription=' . $subscriptionID;
}

function logException($e) {
	error_log('Exception thrown in ' . $e->getFile() . ' on line ' . $e->getLine() . ': ' . $e);
	error_log($e->getTraceAsString());
}

?>
