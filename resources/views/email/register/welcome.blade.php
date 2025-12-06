@component('mail::message')
<p>Hello <b>{{ $user->name }}</b>,</p>

<p>Thank you for registering with us!</p>

@component('mail::button', ['url' => $verificationUrl])
Verify Email
@endcomponent

<p>If you did not create an account, no further action is required.</p>

<p><b>{{ config('app.name') }}</b></p>
@endcomponent