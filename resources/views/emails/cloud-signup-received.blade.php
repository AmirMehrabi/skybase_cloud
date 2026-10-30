<div>
    <!-- It always seems impossible until it is done. - Nelson Mandela -->
</div>
<h1>A new ISP has joined SkyBase Cloud</h1>

<p>A new business has started a 14-day trial.</p>

<dl>
    <dt>Business</dt>
    <dd>{{ $signupDetails['business_name'] }}</dd>

    <dt>Owner</dt>
    <dd>{{ $signupDetails['owner_name'] }}</dd>

    <dt>Email</dt>
    <dd><a href="mailto:{{ $signupDetails['email'] }}">{{ $signupDetails['email'] }}</a></dd>

    <dt>Phone</dt>
    <dd>{{ $signupDetails['phone'] ?: 'Not provided' }}</dd>

    <dt>Country</dt>
    <dd>{{ $signupDetails['country'] ?: 'Not provided' }}</dd>
</dl>
