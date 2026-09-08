<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Manager</title>
    <style>
        body{font-family:system-ui,sans-serif;margin:0;color:#172033;background:#f7f9fc}nav{padding:20px 7%;display:flex;gap:24px;background:#fff}nav a{text-decoration:none;color:#172033}.hero{padding:90px 7%;background:#eaf1ff}.hero h1{font-size:48px;margin:0 0 16px}.hero p{max-width:700px;font-size:20px;line-height:1.6}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;padding:50px 7%}.card{background:#fff;padding:28px;border-radius:14px;box-shadow:0 5px 20px #0000000d}footer{padding:30px 7%;background:#172033;color:#fff}
    </style>
</head>
<body>
<nav><a href="{{ route('home') }}"><strong>School Manager</strong></a><a href="{{ route('about') }}">About</a><a href="{{ route('academics') }}">Academics</a><a href="{{ route('admissions') }}">Admissions</a><a href="{{ route('contact') }}">Contact</a></nav>
<section class="hero"><h1>Welcome to Our School</h1><p>A modern school website and management platform connecting the school, students and parents with admissions, information and secure M-Pesa payments.</p></section>
<section class="grid"><div class="card"><h2>Admissions</h2><p>View requirements and submit an admission enquiry online.</p></div><div class="card"><h2>Academics</h2><p>Explore programmes, departments, facilities and school life.</p></div><div class="card"><h2>M-Pesa Payments</h2><p>Pay school charges securely using Safaricom M-Pesa.</p></div><div class="card"><h2>School Updates</h2><p>Keep up with news, announcements, events and gallery updates.</p></div></section>
<footer>School Manager &mdash; School Website & Management Platform</footer>
</body>
</html>
