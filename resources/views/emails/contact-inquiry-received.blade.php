<!DOCTYPE html>
<html lang="en">
<body>
    <h1>New SkyBase contact inquiry #{{ $inquiry->id }}</h1>
    <p><strong>Name:</strong> {{ $inquiry->name }}</p>
    <p><strong>Email:</strong> {{ $inquiry->email }}</p>
    <p><strong>Phone:</strong> {{ $inquiry->phone ?: 'Not provided' }}</p>
    <p><strong>Company:</strong> {{ $inquiry->company_name ?: 'Not provided' }}</p>
    <p><strong>Subject:</strong> {{ $inquiry->subject }}</p>
    <p><strong>Message:</strong></p>
    <div style="white-space: pre-wrap">{{ $inquiry->message }}</div>
    <p>Reply to this email to respond to {{ $inquiry->name }}.</p>
</body>
</html>
