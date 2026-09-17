@extends('layouts.admin')

@section('page_title', 'Website Media')

@section('admin_content')
<div class="media-page">
    <div class="media-hero">
        <div>
            <div class="media-breadcrumb">School website <span>/</span> Content library</div>
            <h1>Website Media</h1>
            <p>Manage the photographs and videos that represent your school online.</p>
        </div>
        <div class="media-hero-stat">
            <strong>{{ $media->total() }}</strong>
            <span>stored items</span>
        </div>
    </div>

    @if(session('success'))
        <div class="media-alert success" role="status"><div class="alert-icon">✓</div><div><strong>Saved successfully</strong><span>{{ session('success') }}</span></div></div>
    @endif
    @if($errors->any())
        <div class="media-alert danger" role="alert"><div class="alert-icon">!</div><div><strong>Please review the upload</strong><span>{{ $errors->first() }}</span></div></div>
    @endif

    <section class="media-upload-card">
        <div class="section-heading">
            <div><span class="media-eyebrow">Add content</span><h2>Upload a school photo or video</h2><p>Use clear, school-appropriate media for classrooms, CBC learning, sports, events, facilities and community activities.</p></div>
            <div class="upload-security"><span>✓</span> Up to 50 MB per file</div>
        </div>
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="media-form" id="media-upload-form">
            @csrf
            <div class="media-form-grid">
                <label><span>Media type</span><select name="type" id="media-type" required><option value="image">Photo / image</option><option value="video">Video</option></select></label>
                <label><span>Title <em>optional</em></span><input type="text" name="title" maxlength="160" placeholder="e.g. Grade 6 science practical"></label>
                <label class="full"><span>Caption <em>optional</em></span><textarea name="caption" rows="3" maxlength="1000" placeholder="Describe the activity, learning moment or school event..."></textarea></label>
                <div class="full upload-zone" id="upload-zone" tabindex="0" role="button" aria-label="Choose a media file">
                    <div class="upload-zone-icon">↑</div><div class="upload-zone-copy"><strong>Drag and drop your file here</strong><span>or <u>browse from this computer</u></span><small id="file-help">Images: JPG, PNG, WebP, GIF and other server-supported image formats.</small></div>
                    <input type="file" name="media" id="media-file" accept="image/*" required>
                </div>
                <div class="full selected-file" id="selected-file" hidden><div class="selected-file-icon" id="selected-file-icon">IMG</div><div class="selected-file-copy"><strong id="selected-file-name">No file selected</strong><span id="selected-file-meta"></span></div><button type="button" id="clear-file" aria-label="Remove selected file">×</button></div>
                <label class="media-check full"><input type="checkbox" name="published" value="1" checked><span><strong>Publish immediately</strong><small>Make this media available on the public school website after upload.</small></span></label>
            </div>
            <div class="media-form-actions"><button class="media-button primary" type="submit" id="upload-button"><span>Upload to school website</span><b>→</b></button></div>
        </form>
    </section>

    <section class="media-library-head">
        <div><span class="media-eyebrow">Content library</span><h2>School media library</h2><p>Review, reorder and control which media appears on the homepage.</p></div>
        <div class="library-summary"><span class="summary-dot"></span><strong>{{ $media->total() }}</strong> {{ $media->total() === 1 ? 'item' : 'items' }}</div>
    </section>

    @if($media->count())
        <div class="media-library">
            @foreach($media as $item)
                @php
                    /* Use a relative public-storage URL so localhost/127.0.0.1 or a production host cannot mismatch. */
                    $mediaUrl = '/storage/' . ltrim($item->path, '/');
                    $extension = strtoupper(pathinfo($item->path, PATHINFO_EXTENSION));
                    $sizeBytes = null;
                    try {
                        if (Storage::disk('public')->exists($item->path)) {
                            $sizeBytes = Storage::disk('public')->size($item->path);
                        }
                    } catch (\Throwable $e) {
                        $sizeBytes = null;
                    }
                    $sizeMb = $sizeBytes !== null ? number_format($sizeBytes / 1048576, 1) . ' MB' : null;
                @endphp
                <article class="media-card">
                    <div class="media-preview {{ $item->type === 'video' ? 'is-video' : '' }}">
                        @if($item->type === 'image')
                            <img src="{{ $mediaUrl }}" alt="{{ $item->title ?: 'School photo' }}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.hidden=false;">
                            <div class="media-load-error" hidden>Image unavailable</div>
                        @else
                            <video controls preload="metadata" playsinline><source src="{{ $mediaUrl }}" type="{{ $item->mime_type }}">Your browser does not support this video.</video>
                            <span class="video-badge">▶ VIDEO</span>
                        @endif
                        <div class="preview-overlay"><span>{{ $extension ?: strtoupper($item->type) }}</span></div>
                    </div>
                    <div class="media-card-body">
                        <div class="media-card-top"><span class="media-type-icon {{ $item->type }}">{{ $item->type === 'image' ? 'IMG' : 'VID' }}</span><span class="media-status {{ $item->published ? 'published' : 'hidden' }}"><i></i>{{ $item->published ? 'Published' : 'Hidden' }}</span></div>
                        <h3>{{ $item->title ?: 'Untitled school media' }}</h3>
                        @if($item->caption)<p class="media-caption">{{ $item->caption }}</p>@endif
                        <div class="media-meta">@if($sizeMb)<span>◉ {{ $sizeMb }}</span>@endif<span>↕ Order {{ $item->sort_order }}</span></div>
                        <form method="POST" action="{{ route('admin.media.update', $item->id) }}" class="media-edit-form">
                            @csrf @method('PUT')
                            <label><span>Title</span><input type="text" name="title" value="{{ $item->title }}" maxlength="160" placeholder="Title"></label>
                            <label><span>Caption</span><textarea name="caption" rows="2" maxlength="1000" placeholder="Caption">{{ $item->caption }}</textarea></label>
                            <div class="media-edit-row"><label><span>Display order</span><input type="number" name="sort_order" min="0" value="{{ $item->sort_order }}"></label><label class="media-check compact"><input type="checkbox" name="published" value="1" {{ $item->published ? 'checked' : '' }}><span>Show on homepage</span></label></div>
                            <button class="media-button save" type="submit">Save changes <b>✓</b></button>
                        </form>
                        <form method="POST" action="{{ route('admin.media.destroy', $item->id) }}" class="delete-form" onsubmit="return confirm('Remove this media from the school website? This cannot be undone.');">@csrf @method('DELETE')<button class="media-delete" type="submit">Delete media</button></form>
                    </div>
                </article>
            @endforeach
        </div>
        @if($media->hasPages())<div class="media-pagination">{{ $media->links() }}</div>@endif
    @else
        <div class="media-empty"><div class="media-empty-icon">▧</div><h3>Your media library is empty</h3><p>Upload the school's first photograph or video using the form above.</p></div>
    @endif
</div>

<style>
.media-page{--blue:#1769df;--blue-dark:#1057bb;--ink:#17243a;--muted:#718197;--line:#e2e9f1;max-width:1440px}.media-hero{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px;padding:4px 0}.media-breadcrumb{font-size:10px;font-weight:800;color:#8a98aa;text-transform:uppercase;letter-spacing:.08em;margin-bottom:7px}.media-breadcrumb span{padding:0 5px;color:#c2ccd7}.media-hero h1{margin:0;color:var(--ink);font-size:27px;letter-spacing:-.035em}.media-hero p{margin:5px 0 0;color:var(--muted);font-size:12px}.media-hero-stat{min-width:105px;padding:11px 15px;border:1px solid #dce8f7;background:#f7faff;border-radius:13px;text-align:center}.media-hero-stat strong{display:block;color:var(--blue);font-size:19px}.media-hero-stat span{display:block;color:#72839a;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;margin-top:2px}.media-alert{display:flex;gap:11px;align-items:center;padding:12px 14px;border-radius:12px;margin-bottom:17px;border:1px solid}.media-alert.success{background:#effaf5;border-color:#ccebdc;color:#126b48}.media-alert.danger{background:#fff6f5;border-color:#f0d0cc;color:#a52b21}.alert-icon{width:25px;height:25px;display:grid;place-items:center;border-radius:8px;background:rgba(255,255,255,.7);font-weight:900}.media-alert strong,.media-alert span{display:block}.media-alert strong{font-size:11px}.media-alert span{font-size:11px;margin-top:2px}.media-upload-card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:24px;box-shadow:0 10px 28px rgba(24,45,75,.055);margin-bottom:31px}.section-heading{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:21px}.media-eyebrow{display:block;color:var(--blue);font-size:9px;font-weight:900;letter-spacing:.14em;text-transform:uppercase}.section-heading h2,.media-library-head h2{margin:5px 0 6px;color:var(--ink);font-size:20px;letter-spacing:-.025em}.section-heading p,.media-library-head p{margin:0;color:var(--muted);font-size:11px;line-height:1.6}.upload-security{white-space:nowrap;border:1px solid #dcebdc;background:#f3faf5;color:#22704f;border-radius:999px;padding:7px 10px;font-size:9px;font-weight:800}.upload-security span{margin-right:4px}.media-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.media-form-grid label{display:block}.media-form-grid label.full{grid-column:1/-1}.media-form-grid label>span,.media-edit-form label>span{display:block;font-size:10px;font-weight:850;color:#45566d;margin-bottom:6px}.media-form-grid label>span em{font-style:normal;color:#a0abba;font-weight:600;margin-left:4px}.media-form-grid input,.media-form-grid select,.media-form-grid textarea,.media-edit-form input,.media-edit-form textarea{width:100%;box-sizing:border-box;border:1px solid #d8e2ed;background:#fff;border-radius:10px;padding:10px 11px;color:#26384f;font:inherit;font-size:12px;outline:none;transition:.16s}.media-form-grid input:focus,.media-form-grid select:focus,.media-form-grid textarea:focus,.media-edit-form input:focus,.media-edit-form textarea:focus{border-color:#74a9ee;box-shadow:0 0 0 3px rgba(23,105,223,.08)}.upload-zone{min-height:104px;border:1.5px dashed #b9cee8;background:#f8fbff;border-radius:13px;display:flex;align-items:center;justify-content:center;gap:14px;text-align:center;cursor:pointer;position:relative;transition:.18s}.upload-zone:hover,.upload-zone.dragging{border-color:#4d91e9;background:#f1f7ff}.upload-zone input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}.upload-zone-icon{width:42px;height:42px;border-radius:12px;background:#e5f0ff;color:var(--blue);display:grid;place-items:center;font-size:22px;font-weight:700}.upload-zone-copy{text-align:left}.upload-zone-copy strong,.upload-zone-copy span,.upload-zone-copy small{display:block}.upload-zone-copy strong{font-size:12px;color:#29415f}.upload-zone-copy span{font-size:10px;color:#748399;margin-top:3px}.upload-zone-copy u{color:var(--blue);font-weight:800;text-decoration:none}.upload-zone-copy small{font-size:9px;color:#97a3b2;margin-top:7px;max-width:520px}.selected-file{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #dce6f1;border-radius:10px;background:#fbfdff}.selected-file-icon{width:34px;height:34px;display:grid;place-items:center;border-radius:9px;background:#eaf2ff;color:var(--blue);font-size:8px;font-weight:900}.selected-file-copy{min-width:0;flex:1}.selected-file-copy strong,.selected-file-copy span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.selected-file-copy strong{font-size:11px;color:#29415f}.selected-file-copy span{font-size:9px;color:#8593a5;margin-top:2px}.selected-file button{border:0;background:#eef2f6;color:#607087;width:27px;height:27px;border-radius:8px;font-size:18px;cursor:pointer}.media-check{display:flex!important;align-items:center;gap:9px}.media-check input{width:auto!important;accent-color:var(--blue)}.media-check span{margin:0!important;color:#40536b!important;font-size:11px!important}.media-check span strong,.media-check span small{display:block}.media-check span small{color:#8795a7;font-size:9px;margin-top:2px}.media-form-actions{display:flex;justify-content:flex-end;margin-top:16px}.media-button{border:1px solid #d4dfeb;background:#fff;color:#38516c;border-radius:9px;padding:9px 13px;font-weight:800;font-size:10px;cursor:pointer;transition:.16s}.media-button:hover{background:#f4f8fd;transform:translateY(-1px)}.media-button.primary{display:flex;align-items:center;gap:12px;background:var(--blue);border-color:var(--blue);color:#fff;box-shadow:0 7px 16px rgba(23,105,223,.2)}.media-button.primary:hover{background:var(--blue-dark)}.media-button.primary b{font-size:14px}.media-library-head{display:flex;justify-content:space-between;align-items:flex-end;gap:15px;margin-bottom:15px}.library-summary{display:flex;align-items:center;gap:5px;background:#fff;border:1px solid var(--line);border-radius:999px;padding:7px 11px;color:#728197;font-size:9px;font-weight:700}.library-summary strong{color:#233c5a}.summary-dot{width:6px;height:6px;border-radius:50%;background:#2db57b}.media-library{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:17px}.media-card{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 7px 22px rgba(24,45,75,.045);transition:.18s}.media-card:hover{box-shadow:0 12px 30px rgba(24,45,75,.09);transform:translateY(-2px)}.media-preview{height:195px;background:#10233a;position:relative;overflow:hidden}.media-preview img,.media-preview video{display:block;width:100%;height:100%;object-fit:cover}.media-preview video{background:#0d1d31}.media-load-error{height:100%;display:grid;place-items:center;color:#d9e7f8;font-size:11px;background:#18304d}.preview-overlay{position:absolute;left:9px;bottom:9px;pointer-events:none}.preview-overlay span{background:rgba(10,29,52,.82);color:#fff;padding:4px 6px;border-radius:5px;font-size:8px;font-weight:900;letter-spacing:.08em}.video-badge{position:absolute;top:9px;right:9px;background:rgba(7,29,58,.9);color:#fff;padding:5px 7px;border-radius:6px;font-size:8px;font-weight:900;letter-spacing:.08em}.media-card-body{padding:14px}.media-card-top{display:flex;justify-content:space-between;gap:8px;align-items:center}.media-type-icon{font-size:8px;font-weight:900;letter-spacing:.08em;border-radius:5px;padding:4px 6px}.media-type-icon.image{background:#edf4ff;color:#1769df}.media-type-icon.video{background:#f1edff;color:#6a4bc4}.media-status{font-size:8px;font-weight:850;border-radius:999px;padding:4px 7px}.media-status i{display:inline-block;width:5px;height:5px;border-radius:50%;margin-right:4px;background:currentColor}.media-status.published{background:#eaf8f1;color:#17734e}.media-status.hidden{background:#f2f4f7;color:#69788b}.media-card h3{margin:9px 0 4px;color:#20334b;font-size:14px;line-height:1.35}.media-caption{margin:0 0 9px;color:#748399;font-size:10px;line-height:1.5;min-height:15px}.media-meta{display:flex;gap:10px;flex-wrap:wrap;padding-bottom:11px;color:#94a0af;font-size:8px;font-weight:700}.media-edit-form{border-top:1px solid #edf1f5;padding-top:12px}.media-edit-form label{display:block}.media-edit-form input,.media-edit-form textarea{margin-bottom:7px;padding:8px 9px;font-size:10px}.media-edit-row{display:flex;align-items:flex-end;gap:9px;margin-bottom:8px}.media-edit-row>label:first-child{width:100px}.media-edit-row>label:last-child{flex:1}.media-edit-row input{margin:0}.media-check.compact{height:31px;align-items:center}.media-check.compact span{font-size:9px!important;white-space:nowrap}.media-edit-form>.media-button{width:100%}.media-button.save{background:#f7faff;border-color:#d8e6f7;color:#205b9f}.delete-form{margin:0}.media-delete{border:0;background:none;color:#b42318;font-size:9px;font-weight:800;cursor:pointer;padding:9px 0 0}.media-delete:hover{text-decoration:underline}.media-pagination{margin-top:20px}.media-empty{text-align:center;background:#fff;border:1px dashed #d7e1ec;border-radius:16px;padding:45px 20px;color:#748399}.media-empty-icon{margin:0 auto 10px;width:44px;height:44px;border-radius:12px;background:#edf4ff;color:#1769df;display:grid;place-items:center;font-size:22px}.media-empty h3{margin:0 0 5px;color:#20334b;font-size:15px}.media-empty p{margin:0;font-size:11px}@media(max-width:1100px){.media-library{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.media-hero,.section-heading,.media-library-head{align-items:flex-start;flex-direction:column}.media-hero-stat{width:auto}.media-form-grid{grid-template-columns:1fr}.media-form-grid label.full{grid-column:auto}.media-library{grid-template-columns:1fr}.media-upload-card{padding:18px}.upload-zone{min-height:130px}.upload-zone-copy{text-align:center}.upload-zone-icon{display:none}.media-form-actions{justify-content:stretch}.media-button.primary{width:100%;justify-content:center}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = document.getElementById('media-type'); const file = document.getElementById('media-file'); const zone = document.getElementById('upload-zone'); const selected = document.getElementById('selected-file'); const name = document.getElementById('selected-file-name'); const meta = document.getElementById('selected-file-meta'); const icon = document.getElementById('selected-file-icon'); const clear = document.getElementById('clear-file'); const help = document.getElementById('file-help'); const form = document.getElementById('media-upload-form'); const button = document.getElementById('upload-button');
    function updateAccept(){if(!type||!file)return;file.accept=type.value==='video'?'video/*':'image/*';if(help)help.textContent=type.value==='video'?'Videos: MP4, WebM and other formats supported by the server.':'Images: JPG, PNG, WebP, GIF and other supported image formats.';}
    function formatBytes(bytes){if(!bytes)return'0 B';const units=['B','KB','MB','GB'];const index=Math.floor(Math.log(bytes)/Math.log(1024));return(bytes/Math.pow(1024,index)).toFixed(index>1?1:0)+' '+units[index];}
    function showFile(f){if(!f)return;name.textContent=f.name;meta.textContent=formatBytes(f.size)+' · '+(f.type||'Media file');icon.textContent=f.type&&f.type.startsWith('video/')?'VID':'IMG';selected.hidden=false;}
    function clearFile(){file.value='';selected.hidden=true;}
    if(type){type.addEventListener('change',updateAccept);updateAccept();} if(file)file.addEventListener('change',function(){showFile(this.files[0]);});
    if(zone){['dragenter','dragover'].forEach(function(n){zone.addEventListener(n,function(e){e.preventDefault();zone.classList.add('dragging');});});['dragleave','drop'].forEach(function(n){zone.addEventListener(n,function(e){e.preventDefault();zone.classList.remove('dragging');});});zone.addEventListener('drop',function(e){const dropped=e.dataTransfer.files[0];if(!dropped)return;try{const transfer=new DataTransfer();transfer.items.add(dropped);file.files=transfer.files;}catch(err){}showFile(dropped);});}
    if(clear)clear.addEventListener('click',clearFile); if(form&&button)form.addEventListener('submit',function(){button.disabled=true;button.style.opacity='.7';button.querySelector('span').textContent='Uploading…';});
});
</script>
@endsection
