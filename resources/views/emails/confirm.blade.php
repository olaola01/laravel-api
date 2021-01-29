<?php
/**
 * laravel-api
 * Olamiposi
 * 08/01/2021
 * 20:17
 * CREATED WITH PhpStorm
 **/
?>

Hello {{$user->name}},
You changed your email, so we need to verify this new address. Please use the link below
{{ route('verify', $user->verification_token) }}
