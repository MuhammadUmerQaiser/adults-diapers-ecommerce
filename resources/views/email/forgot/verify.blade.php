@component('mail::message')
<p>Hello <b>{{ trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) }}</b>,</p>
<p>We have received a request to reset the password for your {{config('app.name')}} account. As requested,</p>
<p>Your password reset code is: <b><u>{{$token}}</u></b></p>
<p>For any further assistance or questions, feel free to reach out to our support team. We are here to assist you.</p>
<p>Thank you for choosing {{config('app.name')}}. We value your security and appreciate your cooperation.</p>
<p>Best regards</p>
@endcomponent