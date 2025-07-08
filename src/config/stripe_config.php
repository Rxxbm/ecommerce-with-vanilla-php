<?php
require_once __DIR__.'/../vendor/autoload.php';

$stripeKey = $_ENV['STRIPE_SECRET_KEY'];

\Stripe\Stripe::setApiKey($stripeKey); // Use sua chave de teste