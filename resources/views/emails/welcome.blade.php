<?php
/**
 * laravel-api
 * Olamiposi
 * 07/01/2021
 * 01:25
 * CREATED WITH PhpStorm
 **/
?>

Hello {{$user->name}}, thanks for creating an account. Please verify your email using this link
{{ route('verify', $user->verification_token) }}
