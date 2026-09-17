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

@if($schoolMedia->count())
<section class="section media-showcase" aria-labelledby="school-media-heading">
    <div class="container">
        <div class="section-head media-showcase-head">
            <div>
                <p class="eyebrow">Life at {{ $settings['school_name'] ?? 'our school' }}</p>
                <h2 id="school-media-heading">Our school in action</h2>
                <p>Take a closer look at learning, collaboration, sports, facilities and the everyday experiences that shape our school community.</p>
            </div>
            <div class="media-showcase-meta" aria-label="Published media count">
                <span class="media-count">{{ $schoolMedia->count() }}</span>
                <span>published {{ $schoolMedia->count() === 1 ? 'story' : 'stories' }}</span>
            </div>
        </div>

        <div class="school-gallery" role="list">
            @foreach($schoolMedia as $item)
                @php
                    $mediaUrl = '/storage/' . ltrim($item->path, '/');
                    $isVideo = $item->type === 'video';
                    $mediaTitle = trim((string) ($item->title ?: ''));
                    $mediaCaption = trim((string) ($item->caption ?: ''));
                    $mediaAlt = $mediaTitle ?: ($mediaCaption ?: 'School life and learning at ' . ($settings['school_name'] ?? 'our school'));
                @endphp

                <article class="school-media-card {{ $isVideo ? 'is-video' : '' }}" role="listitem">
                    <div class="school-media-frame">
                        @if($isVideo)
                            <video
                                class="school-media-video"
                                controls
                                preload="metadata"
                                playsinline
                                muted
                                aria-label="{{ $mediaAlt }}">
                                <source src="{{ $mediaUrl }}" type="{{ $item->mime_type ?: 'video/mp4' }}">
                                Your browser does not support the school video.
                            </video>
                            <span class="school-video-badge" aria-hidden="true">▶ VIDEO</span>
                        @else
                            <img
                                src="{{ $mediaUrl }}"
                                alt="{{ $mediaAlt }}"
                                loading="lazy"
                                decoding="async"
                                width="960"
                                height="640"
                                onerror="this.hidden=true;this.parentElement.classList.add('media-failed');this.parentElement.querySelector('.media-fallback').hidden=false;">
                            <div class="media-fallback" hidden role="img" aria-label="School image unavailable">
                                <span aria-hidden="true">IMG</span>
                                <strong>Image temporarily unavailable</strong>
                            </div>
                        @endif
                    </div>

                    <div class="school-media-content">
                        <div class="school-media-content-top">
                            <span class="school-media-type">{{ $isVideo ? 'School video' : 'School life' }}</span>
                            @if($isVideo)
                                <span class="school-media-pill">Media</span>
                            @endif
                        </div>

                        @if($mediaTitle)
                            <h3>{{ $mediaTitle }}</h3>
                        @endif
                        @if($mediaCaption)
                            <p>{{ $mediaCaption }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<style>
.media-showcase{background:linear-gradient(180deg,#f7fafc 0%,#eef5f1 100%);border-top:1px solid #e6edf1;border-bottom:1px solid #e6edf1}.media-showcase-head{align-items:end}.media-showcase-meta{display:flex;align-items:center;gap:9px;white-space:nowrap;color:#607287;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.media-count{display:grid;place-items:center;min-width:34px;height:34px;padding:0 9px;border-radius:10px;background:#0d6b45;color:#fff;font-size:13px;box-shadow:0 6px 16px rgba(13,107,69,.18)}.school-gallery{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}.school-media-card{display:flex;flex-direction:column;min-width:0;background:#fff;border:1px solid #dfe8e3;border-radius:18px;overflow:hidden;box-shadow:0 8px 26px rgba(17,51,38,.06);transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}.school-media-card:hover{transform:translateY(-4px);box-shadow:0 16px 38px rgba(17,51,38,.11);border-color:#c9dbd2}.school-media-frame{position:relative;aspect-ratio:16/10;background:#12352a;overflow:hidden}.school-media-frame img,.school-media-frame video{display:block;width:100%;height:100%;object-fit:cover}.school-media-frame img{transition:transform .4s ease}.school-media-card:hover .school-media-frame img{transform:scale(1.025)}.school-media-frame video{background:#071d17}.school-video-badge{position:absolute;top:12px;right:12px;z-index:2;padding:6px 9px;border:1px solid rgba(255,255,255,.16);border-radius:8px;background:rgba(5,28,21,.88);color:#fff;font-size:9px;font-weight:900;letter-spacing:.1em;backdrop-filter:blur(6px)}.media-fallback{position:absolute;inset:0;display:grid;place-items:center;align-content:center;gap:7px;background:linear-gradient(145deg,#12352a,#1e4e3d);color:#fff;text-align:center;padding:20px}.media-fallback span{display:grid;place-items:center;width:42px;height:42px;border-radius:11px;background:rgba(255,255,255,.12);font-size:10px;font-weight:900;letter-spacing:.08em}.media-fallback strong{font-size:11px;color:#dcebe4}.school-media-content{padding:15px 16px 17px}.school-media-content-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:7px}.school-media-type{color:#087f4f;font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.school-media-pill{border:1px solid #d9e8e0;border-radius:999px;padding:3px 7px;color:#647a6e;font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.07em}.school-media-content h3{margin:0 0 6px;color:#172c25;font-size:17px;line-height:1.3;letter-spacing:-.015em}.school-media-content p{margin:0;color:#6c7e76;font-size:12px;line-height:1.6}.school-media-card.is-video .school-media-content{background:linear-gradient(180deg,#fff,#fbfdfc)}@media(max-width:950px){.school-gallery{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:680px){.media-showcase-head{align-items:start}.media-showcase-meta{margin-top:4px}.school-gallery{grid-template-columns:1fr}.school-media-frame{aspect-ratio:16/10}}@media(prefers-reduced-motion:reduce){.school-media-card,.school-media-frame img{transition:none}.school-media-card:hover{transform:none}.school-media-card:hover .school-media-frame img{transform:none}}
</style>
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
