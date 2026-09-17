@extends('layouts.admin')

@section('page_title', 'Website Media')

@section('admin_content')
<div class="admin-page-head">
    <div>
        <h1>Website Media</h1>
        <p>Upload and manage the photographs and videos displayed on the public school website.</p>
    </div>
</div>

@if(session('success'))
    <div class="media-alert success">
        <strong>Done</strong>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="media-alert danger">
        <strong>Please review the upload</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<section class="media-upload-card">
    <div class="media-upload-copy">
        <span class="media-eyebrow">School gallery</span>
        <h2>Add a new photo or video</h2>
        <p>Share classrooms, learning activities, sports, events, facilities and other moments from school life.</p>
    </div>

    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="media-form">
        @csrf

        <div class="media-form-grid">
            <label>
                <span>Media type</span>
                <select name="type" id="media-type" required>
                    <option value="image">Image</option>
                    <option value="video">Video</option>
                </select>
            </label>

            <label>
                <span>Title</span>
                <input type="text" name="title" maxlength="160" placeholder="e.g. Science laboratory session">
            </label>

            <label class="full">
                <span>Caption</span>
                <textarea name="caption" rows="3" maxlength="1000" placeholder="Briefly describe this school moment..."></textarea>
            </label>

            <label class="full media-file-field">
                <span>Select file</span>
                <input type="file" name="media" id="media-file" accept="image/*" required>
                <small>Images: JPG, PNG, WebP, GIF and other supported image formats. Videos: MP4, WebM and other formats supported by the server. Maximum upload size: 50 MB.</small>
            </label>

            <label class="media-check full">
                <input type="checkbox" name="published" value="1" checked>
                <span>Publish immediately on the homepage</span>
            </label>
        </div>

        <div class="media-form-actions">
            <button class="media-button primary" type="submit">Upload to school website</button>
        </div>
    </form>
</section>

<section class="media-library-head">
    <div>
        <span class="media-eyebrow">Published & stored media</span>
        <h2>School media library</h2>
    </div>
    <span class="media-count">{{ $media->total() }} items</span>
</section>

@if($media->count())
<div class="media-library">
    @foreach($media as $item)
        <article class="media-card">
            <div class="media-preview">
                @if($item->type === 'image')
                    <img src="{{ Storage::disk('public')->url($item->path) }}" alt="{{ $item->title ?: 'School photo' }}" loading="lazy">
                @else
                    <video controls preload="metadata">
                        <source src="{{ Storage::disk('public')->url($item->path) }}" type="{{ $item->mime_type }}">
                        Your browser does not support this video.
                    </video>
                    <span class="video-label">VIDEO</span>
                @endif
            </div>

            <div class="media-card-body">
                <div class="media-card-top">
                    <span class="media-type">{{ strtoupper($item->type) }}</span>
                    <span class="media-status {{ $item->published ? 'published' : 'hidden' }}">
                        {{ $item->published ? 'Published' : 'Hidden' }}
                    </span>
                </div>

                <h3>{{ $item->title ?: 'Untitled school media' }}</h3>

                @if($item->caption)
                    <p>{{ $item->caption }}</p>
                @endif

                <form method="POST" action="{{ route('admin.media.update', $item->id) }}" class="media-edit-form">
                    @csrf
                    @method('PUT')

                    <input type="text" name="title" value="{{ $item->title }}" maxlength="160" placeholder="Title">
                    <textarea name="caption" rows="2" maxlength="1000" placeholder="Caption">{{ $item->caption }}</textarea>

                    <div class="media-edit-row">
                        <label>
                            <span>Order</span>
                            <input type="number" name="sort_order" min="0" value="{{ $item->sort_order }}">
                        </label>

                        <label class="media-check">
                            <input type="checkbox" name="published" value="1" {{ $item->published ? 'checked' : '' }}>
                            <span>Show on homepage</span>
                        </label>
                    </div>

                    <button class="media-button" type="submit">Save changes</button>
                </form>

                <form method="POST" action="{{ route('admin.media.destroy', $item->id) }}" onsubmit="return confirm('Remove this media from the school website? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button class="media-delete" type="submit">Delete media</button>
                </form>
            </div>
        </article>
    @endforeach
</div>

<div class="media-pagination">
    {{ $media->links() }}
</div>
@else
    <div class="media-empty">
        <div class="media-empty-icon">▧</div>
        <h3>No school media yet</h3>
        <p>Upload the school's first photograph or video above. Published items will appear on the homepage.</p>
    </div>
@endif

<style>
.media-alert{display:flex;gap:10px;align-items:flex-start;padding:13px 15px;border-radius:12px;margin-bottom:18px;border:1px solid}.media-alert.success{background:#effaf5;border-color:#c9ead9;color:#126b48}.media-alert.danger{background:#fff6f5;border-color:#f2d0cc;color:#a52b21}.media-alert strong{font-size:12px}.media-alert span{font-size:12px}
.media-upload-card{background:#fff;border:1px solid #e3eaf2;border-radius:18px;padding:24px;box-shadow:0 10px 28px rgba(24,45,75,.055);margin-bottom:30px}.media-upload-copy{margin-bottom:20px}.media-eyebrow{display:block;color:#1769df;font-size:10px;font-weight:900;letter-spacing:.12em;text-transform:uppercase}.media-upload-card h2,.media-library-head h2{margin:5px 0 7px;color:#17243a;font-size:21px;letter-spacing:-.025em}.media-upload-card p{margin:0;color:#748399;font-size:13px;line-height:1.6}
.media-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.media-form-grid label{display:block}.media-form-grid label.full{grid-column:1/-1}.media-form-grid label>span,.media-edit-form label>span{display:block;font-size:11px;font-weight:850;color:#45566d;margin-bottom:6px}.media-form-grid input,.media-form-grid select,.media-form-grid textarea,.media-edit-form input,.media-edit-form textarea{width:100%;box-sizing:border-box;border:1px solid #d8e2ed;background:#fff;border-radius:10px;padding:10px 11px;color:#26384f;font:inherit;font-size:12px;outline:none}.media-form-grid input:focus,.media-form-grid select:focus,.media-form-grid textarea:focus,.media-edit-form input:focus,.media-edit-form textarea:focus{border-color:#74a9ee;box-shadow:0 0 0 3px rgba(23,105,223,.08)}.media-file-field input{padding:8px}.media-file-field small{display:block;margin-top:6px;color:#8794a6;font-size:10px;line-height:1.5}.media-check{display:flex!important;align-items:center;gap:8px}.media-check input{width:auto!important}.media-check span{margin:0!important}.media-form-actions{display:flex;justify-content:flex-end;margin-top:17px}.media-button{border:1px solid #d4dfeb;background:#fff;color:#38516c;border-radius:9px;padding:9px 13px;font-weight:800;font-size:11px;cursor:pointer}.media-button:hover{background:#f4f8fd}.media-button.primary{background:#1769df;border-color:#1769df;color:#fff;box-shadow:0 7px 16px rgba(23,105,223,.2)}.media-button.primary:hover{background:#1057bb}
.media-library-head{display:flex;justify-content:space-between;align-items:end;gap:15px;margin-bottom:15px}.media-count{background:#edf4ff;color:#1769d8;border:1px solid #d8e7fb;border-radius:999px;padding:5px 10px;font-size:10px;font-weight:850}
.media-library{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.media-card{background:#fff;border:1px solid #e3eaf2;border-radius:16px;overflow:hidden;box-shadow:0 7px 22px rgba(24,45,75,.045)}.media-preview{height:190px;background:#10233a;position:relative;overflow:hidden}.media-preview img,.media-preview video{display:block;width:100%;height:100%;object-fit:cover}.video-label{position:absolute;top:10px;right:10px;background:rgba(7,29,58,.85);color:#fff;padding:5px 7px;border-radius:6px;font-size:9px;font-weight:900;letter-spacing:.08em}.media-card-body{padding:14px}.media-card-top{display:flex;justify-content:space-between;gap:8px;align-items:center}.media-type{font-size:9px;font-weight:900;letter-spacing:.1em;color:#6d7e92}.media-status{font-size:9px;font-weight:850;border-radius:999px;padding:4px 7px}.media-status.published{background:#eaf8f1;color:#17734e}.media-status.hidden{background:#f2f4f7;color:#69788b}.media-card h3{margin:9px 0 5px;color:#20334b;font-size:14px}.media-card-body>p{margin:0 0 12px;color:#748399;font-size:11px;line-height:1.5}.media-edit-form{border-top:1px solid #edf1f5;margin-top:12px;padding-top:12px}.media-edit-form input,.media-edit-form textarea{margin-bottom:7px}.media-edit-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}.media-edit-row label:first-child{width:80px}.media-edit-row label:last-child{flex:1}.media-edit-row input{margin:0}.media-edit-row .media-check{height:34px}.media-edit-form>.media-button{width:100%}.media-delete{border:0;background:none;color:#b42318;font-size:10px;font-weight:800;cursor:pointer;padding:9px 0 0}.media-pagination{margin-top:20px}.media-empty{text-align:center;background:#fff;border:1px dashed #d7e1ec;border-radius:16px;padding:45px 20px;color:#748399}.media-empty-icon{margin:0 auto 10px;width:44px;height:44px;border-radius:12px;background:#edf4ff;color:#1769df;display:grid;place-items:center;font-size:22px}.media-empty h3{margin:0 0 5px;color:#20334b}.media-empty p{margin:0;font-size:12px}
@media(max-width:1050px){.media-library{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.media-form-grid{grid-template-columns:1fr}.media-form-grid label.full{grid-column:auto}.media-library{grid-template-columns:1fr}.media-library-head{align-items:flex-start}.media-upload-card{padding:18px}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = document.getElementById('media-type');
    const file = document.getElementById('media-file');

    function updateAccept() {
        if (!type || !file) return;
        file.accept = type.value === 'video' ? 'video/*' : 'image/*';
    }

    if (type) {
        type.addEventListener('change', updateAccept);
        updateAccept();
    }
});
</script>
@endsection
