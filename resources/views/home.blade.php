@extends('layouts.public')
@section('title', ($settings['school_name'] ?? 'School Manager').' | Home')
@section('content')
<section class="hero">
    <div class="container">
        <p class="eyebrow">{{ strtoupper($settings['school_name'] ?? 'Our School') }} · {{ $settings['academic_year'] ?: date('Y') }}</p>
        <h1>Growing curious minds. Building confident futures.</h1>
        <p>{{ $settings['mission'] ?? 'We provide a safe, inclusive and inspiring learning environment where every learner can thrive.' }}</p>
        <div class="hero-actions">
            <a class="btn" href="{{ route('admissions') }}">Apply for admission <span aria-hidden="true">→</span></a>
            <a class="btn secondary" href="{{ route('academics') }}">Explore academics</a>
        </div>
    </div>
</section>

<section class="section" style="padding-top:34px;padding-bottom:34px">
    <div class="container">
        <div class="stat-strip" aria-label="School highlights">
            <div>
                <span class="eyebrow">Academic year</span>
                <strong>{{ $settings['academic_year'] ?: date('Y') }}</strong>
                <span class="muted">{{ $settings['academic_term'] ?: 'Current term' }}</span>
            </div>
            <div>
                <span class="eyebrow">Learning</span>
                <strong>{{ $classes->count() }}</strong>
                <span class="muted">Classes currently listed</span>
            </div>
            <div>
                <span class="eyebrow">Approach</span>
                <strong>360°</strong>
                <span class="muted">Academic & character development</span>
            </div>
            <div>
                <span class="eyebrow">Admissions</span>
                <strong>Open</strong>
                <span class="muted">Online applications available</span>
            </div>
        </div>
    </div>
</section>

<section class="section alt">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">A place to belong</p>
                <h2>Learning that goes beyond the classroom</h2>
                <p>We combine strong academic foundations with character, creativity, responsibility and practical skills that help learners grow with confidence.</p>
            </div>
            <a class="btn secondary" href="{{ route('about') }}">Discover our school</a>
        </div>
        <div class="grid">
            <article class="card">
                <div class="icon" aria-hidden="true">01</div>
                <h3>Academic excellence</h3>
                <p class="muted">Structured learning, clear expectations and a supportive environment designed to help every learner make meaningful progress.</p>
            </article>
            <article class="card">
                <div class="icon" aria-hidden="true">02</div>
                <h3>Character & values</h3>
                <p class="muted">We nurture integrity, respect, responsibility, teamwork and resilience so learners are prepared for life as well as examinations.</p>
            </article>
            <article class="card">
                <div class="icon" aria-hidden="true">03</div>
                <h3>Whole-child development</h3>
                <p class="muted">Learners are encouraged to discover their strengths through creativity, collaboration, leadership and participation in school life.</p>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Learning pathways</p>
                <h2>Our classes</h2>
                <p>Explore the learning levels currently configured by the school administration.</p>
            </div>
            <a class="btn secondary" href="{{ route('academics') }}">View all academics</a>
        </div>
        <div class="grid">
            @forelse($classes as $class)
                <a class="card card-link" href="{{ route('academics') }}">
                    <div class="icon" aria-hidden="true">{{ strtoupper(substr($class->name, 0, 1)) }}</div>
                    <h3>{{ $class->name }}</h3>
                    <p class="muted">{{ $class->stream ?: 'General stream' }} @if($class->academic_year) · {{ $class->academic_year }} @endif</p>
                </a>
            @empty
                <div class="empty">
                    <h3>Classes are being prepared</h3>
                    <p>The school administration will publish class information here as it is configured.</p>
                    <a class="btn secondary" href="{{ route('contact') }}">Contact the school</a>
                </div>
            @endforelse
        </div>
    </div>
</section>

@if($announcements->count())
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Stay informed</p>
                <h2>Latest school updates</h2>
                <p>Important announcements and information for learners, parents and the wider school community.</p>
            </div>
        </div>
        <div class="grid">
            @foreach($announcements as $announcement)
                <article class="card">
                    <span class="badge">Announcement</span>
                    <h3>{{ $announcement->title }}</h3>
                    <p>{{ $announcement->body }}</p>
                    @if($announcement->published_at)
                        <small class="muted">Published {{ \Carbon\Carbon::parse($announcement->published_at)->format('d M Y') }}</small>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($events->count())
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">School calendar</p>
                <h2>Upcoming events</h2>
                <p>Keep track of important dates, activities and community moments.</p>
            </div>
            <a class="btn secondary" href="{{ route('contact') }}">Contact the office</a>
        </div>
        <div class="grid">
            @foreach($events as $event)
                <article class="card">
                    <span class="badge">{{ \Carbon\Carbon::parse($event->event_date)->format('d M') }}</span>
                    <h3>{{ $event->title }}</h3>
                    <p><strong>{{ \Carbon\Carbon::parse($event->event_date)->format('l, d F Y') }}</strong>@if($event->location)<br>{{ $event->location }}@endif</p>
                    @if($event->description)<p class="muted">{{ $event->description }}</p>@endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section alt">
    <div class="container">
        <div class="card" style="background:linear-gradient(135deg,#071d3a,#0f5bd7);color:#fff;border:0;overflow:hidden">
            <div class="split">
                <div>
                    <p class="eyebrow" style="color:#bcd7ff">{{ $settings['school_name'] ?? 'Our school' }}</p>
                    <h2 style="font-size:clamp(30px,4vw,46px);line-height:1.1;letter-spacing:-.035em;margin:7px 0 12px">A strong foundation for a bright future.</h2>
                    <p style="color:#dce9fb;max-width:650px">{{ $settings['vision'] ?? 'To nurture responsible, confident and capable young people prepared to contribute positively to society.' }}</p>
                </div>
                <div style="display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:10px">
                    <a class="btn" href="{{ route('admissions') }}">Begin an application</a>
                    <a class="btn secondary" href="{{ route('contact') }}">Talk to the school</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
