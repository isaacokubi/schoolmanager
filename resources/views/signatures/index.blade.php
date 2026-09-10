@extends(auth()->user()->role === 'teacher' ? 'layouts.app' : 'layouts.admin')
@section('title','My Signature | School Manager')
@if(auth()->user()->role !== 'teacher') @section('page_title','My Signature') @endif
@section(auth()->user()->role === 'teacher' ? 'body' : 'admin_content')
@if(auth()->user()->role === 'teacher')
<div class="portal-shell"><header class="portal-header"><a class="portal-brand" href="{{ route('portal.dashboard') }}"><span class="brand-mark">{{ strtoupper(substr(config('app.name'),0,1)) }}</span><span><strong>{{ config('app.name') }}</strong><small>Teacher Portal</small></span></a><div class="portal-actions"><a class="btn secondary" href="{{ route('portal.dashboard') }}">Dashboard</a><form method="post" action="{{ route('portal.logout') }}">@csrf<button class="btn secondary">Sign out</button></form></div></header>
@endif
<div class="card" style="max-width:760px;margin:24px auto;padding:24px">
    <h1 style="margin-top:0">{{ auth()->user()->role === 'teacher' ? 'My Teacher Signature' : 'My Institution Signature' }}</h1>
    <p>Upload a clear image of your official handwritten signature. The system will automatically use it on CBC report cards where you are the assigned class teacher or designated Head of Institution.</p>
    @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @php($signaturePath = auth()->user()->role === 'teacher' ? ($teacher->signature_path ?? null) : auth()->user()->signature_path)
    @if($signaturePath)
        <div style="margin:18px 0;padding:14px;background:#f8fafc;border:1px solid #d9e2eb;border-radius:8px"><strong>Current signature</strong><br><img src="{{ asset('storage/'.$signaturePath) }}" alt="Current signature" style="display:block;max-width:320px;max-height:110px;margin-top:10px;object-fit:contain;background:#fff;border:1px solid #e2e8f0;padding:8px"></div>
    @else
        <div style="margin:18px 0;padding:14px;background:#fff8e8;border:1px solid #f0d48a;border-radius:8px">No signature has been uploaded yet.</div>
    @endif
    <form method="POST" action="{{ route('signature.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
        <label style="display:block;font-weight:700;margin-bottom:7px">Official signature image</label>
        <input type="file" name="signature" accept="image/jpeg,image/png,image/webp" required>
        <small style="display:block;margin-top:7px;color:#66758a">JPG, PNG or WebP, maximum 2MB. Use a clean scan/photo on a plain background.</small>
        <button class="btn" type="submit" style="margin-top:16px">Upload signature</button>
    </form>
    @if($signaturePath)<form method="POST" action="{{ route('signature.remove') }}" style="margin-top:10px">@csrf @method('DELETE')<button class="btn secondary" type="submit">Remove signature</button></form>@endif
</div>
@if(auth()->user()->role === 'teacher')</div><style>.portal-shell{min-height:100vh;background:#f4f7fb;color:#152238}.portal-header{display:flex;justify-content:space-between;align-items:center;padding:13px 4%;background:#fff;border-bottom:1px solid #e3eaf2}.portal-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit}.portal-brand strong,.portal-brand small{display:block}.portal-brand strong{font-size:14px}.portal-brand small{font-size:10px;color:#8793a4}.brand-mark{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:#1769df;color:#fff;font-weight:900}.portal-actions{display:flex;gap:8px}.btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:10px;border:1px solid transparent;background:#1769df;color:#fff;text-decoration:none;font-weight:800;font-size:12px;cursor:pointer}.btn.secondary{background:#edf4ff;color:#145fc9;border-color:#dbe8fb}.card{background:#fff;border:1px solid #e1e8f1;border-radius:16px;box-shadow:0 7px 25px rgba(20,40,70,.04)}.alert{padding:13px 16px;background:#eef8f1;border:1px solid #c7e9d5;border-radius:10px}.errors{padding:13px 16px;background:#fff0ef;border:1px solid #f0c8c4;border-radius:10px}.errors ul{margin:0;padding-left:20px}@media(max-width:600px){.portal-header{align-items:flex-start;gap:10px}.portal-actions{flex-wrap:wrap}}</style>@endif
@endsection
