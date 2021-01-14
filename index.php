<?php

$title = 'Stripe PHP Samples';

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
		
		<ul>
			<?php
				
				$samples = scandir('samples');
				
				foreach ($samples as $sample) {
					if (in_array($sample, ['.', '..'])) {
						continue;
					}
					
					echo '<li><a href="samples/' . $sample . '">' . $sample . '</a></li>';
				}
				
			?>
		</ul>
	</body>
</html>
