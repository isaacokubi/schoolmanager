@extends('layouts.public')
@section('title', ($settings['school_name'] ?? 'School Manager').' | Home')
@section('content')
<section class="hero home-hero">
    <div class="container">
        <div class="hero-copy">
            <p class="eyebrow">Admissions · Learning · Community · {{ $settings['academic_year'] ?: date('Y') }}</p>
            <h1>Growing confident learners. Building stronger futures.</h1>
            <p class="hero-lead">{{ $settings['mission'] ?? 'Quality education, character development and compassionate care in a community where every child is encouraged to discover their potential.' }}</p>
            <div class="hero-actions">
                <a class="btn" href="{{ route('admissions') }}">Enquire about admissions <span aria-hidden="true">→</span></a>
                <a class="btn secondary" href="{{ route('contact') }}">Talk to the school</a>
            </div>
            <div class="hero-points" aria-label="Our school promise">
                <span>✓ Caring learning environment</span>
                <span>✓ Purposeful teaching</span>
                <span>✓ Character-led development</span>
            </div>
        </div>
    </div>
</section>

<section class="section home-stats" aria-label="School highlights">
    <div class="container">
        <div class="stat-strip">
            <div><strong>{{ $classes->count() }}</strong><span>Learning levels</span></div>
            <div><strong>{{ $announcements->count() }}</strong><span>Recent updates</span></div>
            <div><strong>{{ $events->count() }}</strong><span>Upcoming events</span></div>
            <div><strong>{{ $schoolMedia->count() }}</strong><span>School stories</span></div>
        </div>
    </div>
</section>

<section class="section alt promise-section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Education with purpose</p>
                <h2>Education that shapes character, purpose and opportunity.</h2>
                <p>At {{ $settings['school_name'] ?? 'our school' }}, learning, character development and compassionate care come together to help every learner discover their potential and prepare for a meaningful future.</p>
            </div>
            <div class="section-actions">
                <a class="btn secondary" href="{{ route('academics') }}">Explore academics</a>
                <a class="btn secondary" href="{{ route('about') }}">Discover our school</a>
            </div>
        </div>
        <div class="grid promise-grid">
            <article class="card promise-card"><div class="icon">01</div><p class="eyebrow">Our promise</p><h3>Academic excellence</h3><p class="muted">Strong foundations, purposeful teaching and measurable learner progress in a supportive school environment.</p></article>
            <article class="card promise-card"><div class="icon">02</div><p class="eyebrow">Our promise</p><h3>Character & values</h3><p class="muted">Integrity, respect, responsibility, teamwork and resilience are developed alongside academic achievement.</p></article>
            <article class="card promise-card"><div class="icon">03</div><p class="eyebrow">Our promise</p><h3>Whole-child care</h3><p class="muted">Pastoral support, creativity, sport, leadership and mentorship help learners flourish beyond the classroom.</p></article>
        </div>
    </div>
</section>

<section class="section community-section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">A community built around learners</p>
                <h2>One school. One community. One future.</h2>
                <p>Whether you are a parent, teacher, guardian or community partner, there is a meaningful way to participate in the life of {{ $settings['school_name'] ?? 'our school' }}.</p>
            </div>
        </div>
        <div class="grid community-grid">
            <a class="card community-card card-link" href="{{ route('register') }}?role=parent">
                <span class="community-kicker">For families</span>
                <h3>Parents & learners</h3>
                <p>Access school information, admissions support and secure portal services for your family.</p>
                <strong>Explore family services <span aria-hidden="true">→</span></strong>
            </a>
            <a class="card community-card card-link" href="{{ route('register') }}?role=teacher">
                <span class="community-kicker">For educators</span>
                <h3>Teachers & staff</h3>
                <p>Connect with the school community, support learning and communicate effectively with families.</p>
                <strong>Join our school community <span aria-hidden="true">→</span></strong>
            </a>
            <a class="card community-card card-link" href="{{ route('contact') }}">
                <span class="community-kicker">For partners</span>
                <h3>Community & partners</h3>
                <p>Work with the school on programmes, learner support, community initiatives and school development.</p>
                <strong>Partner with the school <span aria-hidden="true">→</span></strong>
            </a>
        </div>
    </div>
</section>

@if($announcements->count())
<section class="section alt announcements-section">
    <div class="container">
        <div class="section-head">
            <div><p class="eyebrow">Stay connected</p><h2>Latest school announcements</h2><p>Useful updates for learners, parents, guardians and the wider school community.</p></div>
            <a class="btn secondary" href="{{ route('contact') }}">Contact the school</a>
        </div>
        <div class="grid">
            @foreach($announcements as $announcement)
                <article class="card announcement-card"><span class="badge">Announcement</span><h3>{{ $announcement->title }}</h3><p>{{ $announcement->body }}</p>@if($announcement->published_at)<small class="muted">Published {{ \Carbon\Carbon::parse($announcement->published_at)->format('d M Y') }}</small>@endif</article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- School media is intentionally retained as the homepage's visual gallery. --}}
@if($schoolMedia->count())
<section class="section media-showcase" aria-labelledby="school-media-heading">
    <div class="container">
        <div class="section-head media-showcase-head">
            <div>
                <p class="eyebrow">Life at {{ $settings['school_name'] ?? 'our school' }}</p>
                <h2 id="school-media-heading">Our school in action</h2>
                <p>Take a closer look at learning, collaboration, sports, facilities and the everyday experiences that shape our school community.</p>
            </div>
            <div class="media-showcase-meta" aria-label="Published media count"><span class="media-count">{{ $schoolMedia->count() }}</span><span>published {{ $schoolMedia->count() === 1 ? 'story' : 'stories' }}</span></div>
        </div>

        <div class="school-gallery" role="list">
            @foreach($schoolMedia as $item)
                @php
                    $mediaUrl = \Illuminate\Support\Facades\Storage::disk(config('filesystems.upload_disk', 'public'))->url(ltrim($item->path, '/'));
                    $isVideo = $item->type === 'video';
                    $mediaTitle = trim((string) ($item->title ?: ''));
                    $mediaCaption = trim((string) ($item->caption ?: ''));
                    $mediaAlt = $mediaTitle ?: ($mediaCaption ?: 'School life and learning at ' . ($settings['school_name'] ?? 'our school'));
                @endphp
                <article class="school-media-card {{ $isVideo ? 'is-video' : '' }}" role="listitem">
                    <div class="school-media-frame">
                        @if($isVideo)
                            <video class="school-media-video" controls preload="metadata" playsinline aria-label="{{ $mediaAlt }}">
                                <source src="{{ $mediaUrl }}" type="{{ $item->mime_type ?: 'video/mp4' }}">
                                Your browser does not support the school video.
                            </video>
                            <span class="school-video-badge" aria-hidden="true">▶ VIDEO</span>
                        @else
                            <img class="school-media-image" src="{{ $mediaUrl }}" alt="{{ $mediaAlt }}" loading="lazy" decoding="async" width="960" height="640">
                            <div class="media-fallback" hidden role="img" aria-label="School image unavailable"><span aria-hidden="true">IMG</span><strong>Image temporarily unavailable</strong></div>
                        @endif
                    </div>
                    <div class="school-media-content">
                        <div class="school-media-content-top"><span class="school-media-type">{{ $isVideo ? 'School video' : 'School life' }}</span>@if($isVideo)<span class="school-media-pill">Media</span>@endif</div>
                        @if($mediaTitle)<h3>{{ $mediaTitle }}</h3>@endif
                        @if($mediaCaption)<p>{{ $mediaCaption }}</p>@endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.school-media-image').forEach(function (image) {
        image.addEventListener('error', function () {
            image.hidden = true;
            image.closest('.school-media-frame').classList.add('media-failed');
            var fallback = image.parentElement.querySelector('.media-fallback');
            if (fallback) fallback.hidden = false;
        }, { once: true });
    });
});
</script>

<style>
.media-showcase{background:linear-gradient(180deg,#f7fafc 0%,#eef5f1 100%);border-top:1px solid #e6edf1;border-bottom:1px solid #e6edf1}.media-showcase-head{align-items:end}.media-showcase-meta{display:flex;align-items:center;gap:9px;white-space:nowrap;color:#607287;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.media-count{display:grid;place-items:center;min-width:34px;height:34px;padding:0 9px;border-radius:10px;background:#0d6b45;color:#fff;font-size:13px;box-shadow:0 6px 16px rgba(13,107,69,.18)}.school-gallery{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}.school-media-card{display:flex;flex-direction:column;min-width:0;background:#fff;border:1px solid #dfe8e3;border-radius:18px;overflow:hidden;box-shadow:0 8px 26px rgba(17,51,38,.06);transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}.school-media-card:hover{transform:translateY(-4px);box-shadow:0 16px 38px rgba(17,51,38,.11);border-color:#c9dbd2}.school-media-frame{position:relative;aspect-ratio:16/10;background:#12352a;overflow:hidden}.school-media-frame img,.school-media-frame video{display:block;width:100%;height:100%;object-fit:cover}.school-media-frame img{transition:transform .4s ease}.school-media-card:hover .school-media-frame img{transform:scale(1.025)}.school-media-frame video{background:#071d17}.school-video-badge{position:absolute;top:12px;right:12px;z-index:2;padding:6px 9px;border:1px solid rgba(255,255,255,.16);border-radius:8px;background:rgba(5,28,21,.88);color:#fff;font-size:9px;font-weight:900;letter-spacing:.1em;backdrop-filter:blur(6px)}.media-fallback{position:absolute;inset:0;place-items:center;align-content:center;gap:7px;background:linear-gradient(145deg,#12352a,#1e4e3d);color:#fff;text-align:center;padding:20px}.media-fallback[hidden]{display:none}.media-fallback:not([hidden]){display:grid}.media-fallback span{display:grid;place-items:center;width:42px;height:42px;border-radius:11px;background:rgba(255,255,255,.12);font-size:10px;font-weight:900;letter-spacing:.08em}.media-fallback strong{font-size:11px;color:#dcebe4}.school-media-content{padding:15px 16px 17px}.school-media-content-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:7px}.school-media-type{color:#087f4f;font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.school-media-pill{border:1px solid #d9e8e0;border-radius:999px;padding:3px 7px;color:#647a6e;font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.07em}.school-media-content h3{margin:0 0 6px;color:#172c25;font-size:17px;line-height:1.3;letter-spacing:-.015em}.school-media-content p{margin:0;color:#6c7e76;font-size:12px;line-height:1.6}.school-media-card.is-video .school-media-content{background:linear-gradient(180deg,#fff,#fbfdfc)}.home-hero{position:relative;overflow:hidden;background:radial-gradient(circle at 82% 24%,rgba(255,255,255,.12),transparent 30%),linear-gradient(120deg,#071d3a 0%,#0b5b45 58%,#0d7452 100%);color:#fff}.home-hero:after{content:"";position:absolute;inset:auto -10% -130px 45%;height:260px;border-radius:50%;background:rgba(255,255,255,.06);transform:rotate(-7deg)}.hero-copy{position:relative;z-index:1;max-width:820px}.home-hero .eyebrow{color:#bfe7d6}.home-hero h1{max-width:800px;font-size:clamp(42px,6vw,76px);line-height:.98;letter-spacing:-.055em;margin:10px 0 20px}.hero-lead{max-width:720px;color:#e4f3ee!important;font-size:17px!important;line-height:1.75!important}.hero-points{display:flex;flex-wrap:wrap;gap:10px 18px;margin-top:28px;color:#d6eee5;font-size:11px;font-weight:800}.hero-points span{padding:8px 11px;border:1px solid rgba(255,255,255,.15);border-radius:999px;background:rgba(255,255,255,.06)}.home-stats{padding-top:28px;padding-bottom:28px;background:#fff}.home-stats .stat-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:0;padding:0;border:1px solid #dfe8e3;border-radius:18px;overflow:hidden;background:#fff;box-shadow:0 10px 30px rgba(17,51,38,.06)}.home-stats .stat-strip>div{padding:21px 22px;border-right:1px solid #e5ece8}.home-stats .stat-strip>div:last-child{border-right:0}.home-stats strong{display:block;color:#0b6845;font-size:30px;line-height:1;margin-bottom:7px}.home-stats span{display:block;color:#71817a;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.promise-section{background:#f5f8f6}.promise-grid .promise-card{min-height:230px}.promise-card .icon{margin-bottom:24px}.promise-card .eyebrow{margin-bottom:8px;color:#087f4f}.promise-card h3{margin-bottom:8px}.section-actions{display:flex;flex-wrap:wrap;gap:10px}.community-section{background:#fff}.community-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.community-card{position:relative;min-height:250px;padding:26px;display:flex;flex-direction:column}.community-card:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:linear-gradient(#0b6845,#e1a33b)}.community-kicker{color:#0b6845;font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;margin-bottom:18px}.community-card h3{font-size:23px;margin-bottom:10px}.community-card p{color:#697a73;line-height:1.65}.community-card strong{margin-top:auto;color:#0b6845;font-size:12px}.announcements-section .announcement-card{min-height:220px}.announcement-card .badge{margin-bottom:14px}.announcement-card h3{margin-bottom:10px}.announcement-card small{display:block;margin-top:16px}.media-failed{background:#12352a}.media-showcase .section-head p{max-width:720px}@media(max-width:950px){.home-stats .stat-strip{grid-template-columns:repeat(2,1fr)}.home-stats .stat-strip>div:nth-child(2){border-right:0}.home-stats .stat-strip>div:nth-child(-n+2){border-bottom:1px solid #e5ece8}.community-grid{grid-template-columns:1fr 1fr}.school-gallery{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:680px){.home-hero h1{font-size:clamp(38px,12vw,58px)}.hero-lead{font-size:15px!important}.hero-points{display:grid}.home-stats .stat-strip{grid-template-columns:1fr}.home-stats .stat-strip>div{border-right:0!important;border-bottom:1px solid #e5ece8!important}.home-stats .stat-strip>div:last-child{border-bottom:0!important}.community-grid{grid-template-columns:1fr}.section-actions{width:100%}.school-gallery{grid-template-columns:1fr}.school-media-frame{aspect-ratio:16/10}}@media(prefers-reduced-motion:reduce){.school-media-card,.school-media-frame img{transition:none}.school-media-card:hover{transform:none}.school-media-card:hover .school-media-frame img{transform:none}}
</style>
@endif

@if($events->count())
<section class="section calendar-preview">
    <div class="container">
        <div class="section-head">
            <div><p class="eyebrow">School calendar</p><h2>What's happening at school</h2><p>Keep track of important dates, activities and community moments.</p></div>
            <a class="btn secondary" href="{{ route('contact') }}">Contact the office</a>
        </div>
        <div class="grid">
            @foreach($events as $event)
                <article class="card"><span class="badge">{{ \Carbon\Carbon::parse($event->event_date)->format('d M') }}</span><h3>{{ $event->title }}</h3><p><strong>{{ \Carbon\Carbon::parse($event->event_date)->format('l, d F Y') }}</strong>@if($event->location)<br>{{ $event->location }}@endif</p>@if($event->description)<p class="muted">{{ $event->description }}</p>@endif</article>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section alt final-cta">
    <div class="container">
        <div class="card cta-card">
            <div>
                <p class="eyebrow">Ready to take the next step?</p>
                <h2>Discover what {{ $settings['school_name'] ?? 'our school' }} can mean for your child.</h2>
                <p>Talk to our school team about admissions, learning, family support and opportunities to become part of our community.</p>
            </div>
            <div class="cta-actions"><a class="btn" href="{{ route('contact') }}">Contact us</a><a class="btn secondary" href="{{ route('admissions') }}">Start an enquiry</a></div>
        </div>
    </div>
</section>
@endsection
