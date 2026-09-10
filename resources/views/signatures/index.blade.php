@extends(auth()->user()->role === 'teacher' ? 'layouts.portal' : 'layouts.admin')
@section('title','My Signature | School Manager')
@if(auth()->user()->role !== 'teacher') @section('page_title','My Signature') @endif
@section(auth()->user()->role === 'teacher' ? 'content' : 'admin_content')
<div class="card" style="max-width:760px;margin:24px auto;padding:24px">
    <h1 style="margin-top:0">{{ auth()->user()->role === 'teacher' ? 'My Teacher Signature' : 'My Institution Signature' }}</h1>
    <p>Upload a clear image of your official handwritten signature. The system will automatically use it on CBC report cards where you are the assigned class teacher or designated Head of Institution.</p>

    @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @php($signaturePath = auth()->user()->role === 'teacher' ? ($teacher->signature_path ?? null) : auth()->user()->signature_path)
    @if($signaturePath)
        <div style="margin:18px 0;padding:14px;background:#f8fafc;border:1px solid #d9e2eb;border-radius:8px">
            <strong>Current signature</strong><br>
            <img src="{{ asset('storage/'.$signaturePath) }}" alt="Current signature" style="display:block;max-width:320px;max-height:110px;margin-top:10px;object-fit:contain;background:#fff;border:1px solid #e2e8f0;padding:8px">
        </div>
    @else
        <div style="margin:18px 0;padding:14px;background:#fff8e8;border:1px solid #f0d48a;border-radius:8px">No signature has been uploaded yet.</div>
    @endif

    <form method="POST" action="{{ route('signature.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <label style="display:block;font-weight:700;margin-bottom:7px">Official signature image</label>
        <input type="file" name="signature" accept="image/jpeg,image/png,image/webp" required>
        <small style="display:block;margin-top:7px;color:#66758a">JPG, PNG or WebP, maximum 2MB. Use a clean scan/photo on a plain background.</small>
        <button class="btn" type="submit" style="margin-top:16px">Upload signature</button>
    </form>

    @if($signaturePath)
        <form method="POST" action="{{ route('signature.remove') }}" style="margin-top:10px">
            @csrf @method('DELETE')
            <button class="btn secondary" type="submit">Remove signature</button>
        </form>
    @endif
</div>
@endsection
