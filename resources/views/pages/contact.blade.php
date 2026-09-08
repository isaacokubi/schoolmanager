@extends('layouts.public')
@section('title','Contact | '.$settings['school_name'])
@section('content')
<section class="section"><div class="container"><p><strong>CONTACT</strong></p><h1>Contact {{ $settings['school_name'] }}</h1><div class="grid"><div class="card"><h3>School office</h3><p>{{ $settings['school_address'] ?: 'Address available from the school office.' }}</p><p><strong>Phone:</strong> {{ $settings['school_phone'] ?: 'Not yet configured' }}<br><strong>Email:</strong> {{ $settings['school_email'] ?: 'Not yet configured' }}</p></div><div class="card"><h3>Admissions</h3><p>For admissions enquiries, use the online application form and the administration team will follow up.</p><a class="btn" href="{{ route('admissions') }}">Apply for Admission</a></div></div>
@if($events->count())<h2 style="margin-top:2rem">Upcoming school events</h2><div class="grid">@foreach($events as $event)<div class="card"><h3>{{ $event->title }}</h3><p><strong>{{ \Carbon\Carbon::parse($event->event_date)->format('D, d M Y') }}</strong>@if($event->location) · {{ $event->location }}@endif</p><p>{{ $event->description }}</p></div>@endforeach</div>@endif
</div></section>
@endsection
