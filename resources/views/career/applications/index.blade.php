@extends('layouts.admin-sidebar')

@section('title', 'Job Application · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
<style>
.main-content-inner.pt-tight-padding{padding-left:24px;padding-right:24px}.ct-header{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px}.ct-title{font:600 26px 'Baloo 2';color:#16436E}.ct-sub{margin-top:4px;color:#98897A;font:600 13px 'Nunito Sans'}.ct-header-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.ct-filters{display:flex;gap:7px;flex-wrap:wrap}.ct-filter{border:1px solid #E2DACE;background:#fff;color:#5A6B7E;border-radius:999px;padding:7px 12px;text-decoration:none;font:800 11px 'Nunito Sans'}.ct-filter:hover{background:#F6F3EE}.ct-filter.active{background:#2B3A4C;border-color:#2B3A4C;color:#fff}.ct-add-btn{background:#C8355F;color:#fff;border:none;border-radius:8px;padding:8px 14px;font:800 12px 'Nunito Sans';text-decoration:none;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 14px rgba(200,53,95,.28)}.ct-add-btn:hover{background:#A82348}.ct-card{overflow:hidden;overflow-x:auto;border:1px solid #EBE4DA;border-radius:16px;background:#fff;box-shadow:0 2px 10px rgba(22,42,60,.04)}.ct-table{width:100%;min-width:960px;border-collapse:collapse}.ct-table th{padding:13px 20px;border-bottom:1px solid #EBE4DA;background:#FFFDFA;color:#98897A;text-align:left;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;font:800 11px 'Nunito Sans'}.ct-table td{padding:15px 20px;border-bottom:1px solid #F3EDE3;vertical-align:middle;color:#2B3A4C;font:600 13px 'Nunito Sans'}.ct-table tr:last-child td{border:0}.ct-row{cursor:pointer}.ct-row:hover td{background:#FFFDFA}.ct-person{display:flex;align-items:center;gap:11px}.ct-avatar{width:36px;height:36px;flex-shrink:0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font:600 13px 'Baloo 2'}.ct-name{font:800 13.5px 'Nunito Sans'}.ct-secondary{margin-top:1px;color:#98897A;font:600 11.5px 'Nunito Sans'}.ct-message{max-width:270px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#5A6B7E}.ct-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 11px;white-space:nowrap;font:800 11px 'Nunito Sans'}.ct-empty{padding:56px 20px;text-align:center;color:#98897A;font:700 12.5px 'Nunito Sans'}.ct-success{margin-bottom:18px;border:1px solid #BFE9CE;border-radius:10px;padding:12px 16px;background:#E4F6EB;color:#1E8A4C;font:700 13px 'Nunito Sans'}
.ct-modal{display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(22,42,60,.45)}.ct-modal.open{display:flex}.ct-modal-box{width:820px;max-width:100%;max-height:88vh;overflow-y:auto;border-radius:18px;padding:26px 28px;background:#FFFDFA;box-shadow:0 20px 60px rgba(22,42,60,.3)}.ct-modal-head{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;flex-wrap:wrap}.ct-modal-title{flex:1;min-width:160px;color:#16436E;font:600 21px 'Baloo 2'}.ct-modal-sub{margin-top:3px;color:#98897A;font:600 12.5px 'Nunito Sans'}.ct-modal-contact{text-align:right;font:800 13px 'Nunito Sans';color:#2B3A4C}.ct-modal-contact-sub{text-align:right;color:#98897A;font:600 12px 'Nunito Sans'}.ct-close{width:32px;height:32px;border:1px solid #E2DACE;border-radius:9px;background:#fff;color:#5A6B7E;cursor:pointer;font:800 18px/1 'Nunito Sans'}.ct-close:hover{background:#F6F3EE}.ct-chips-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:16px}.ct-details{display:grid;grid-template-columns:1.35fr .9fr;gap:16px}.ct-detail-card{display:flex;flex-direction:column;gap:12px;border:1px solid #EBE4DA;border-radius:14px;padding:18px 20px;background:#fff}.ct-detail-title{color:#16436E;font:600 16px 'Baloo 2'}.ct-field{display:flex;flex-direction:column;gap:4px}.ct-label{color:#98897A;text-transform:uppercase;letter-spacing:.6px;font:700 10.5px 'Nunito Sans'}.ct-input,.ct-select{width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #E2DACE;border-radius:9px;background:#F6F3EE;color:#2B3A4C;font:700 12px 'Nunito Sans'}.ct-input:focus,.ct-select:focus{outline:none;border-color:#C8355F;background:#fff}.ct-action{display:inline-flex;justify-content:center;align-items:center;gap:6px;border:0;border-radius:9px;padding:7px 13px;background:#C8355F;color:#fff;cursor:pointer;text-decoration:none;font:800 11px 'Nunito Sans';box-shadow:0 4px 14px rgba(200,53,95,.28)}.ct-action:hover{background:#A82348}.ct-action-outline{background:#fff;color:#C8355F;border:1px solid #E8B9C6;box-shadow:none}.ct-action-outline:hover{background:#FBEEF1}.ct-action.is-disabled{background:#D9CFC2;cursor:not-allowed;box-shadow:none;pointer-events:none}.ct-delete{margin-top:4px;border:0;padding:3px 0;background:none;color:#B91C1C;cursor:pointer;text-align:left;font:700 12px 'Nunito Sans'}.ct-delete:hover{text-decoration:underline}.ct-field-row{display:flex;flex-direction:column;gap:2px;padding:8px 0;border-bottom:1px solid #F3EDE3}.ct-field-row:last-child{border-bottom:none}.ct-field-label{font:700 10.5px 'Nunito Sans';color:#98897A;text-transform:uppercase;letter-spacing:.6px}.ct-field-value{font:800 13px 'Nunito Sans';color:#2B3A4C}.ct-message-body{font:600 13.5px/1.6 'Nunito Sans';color:#2B3A4C;white-space:pre-wrap}.ct-card-empty{font:600 12.5px/1.5 'Nunito Sans';color:#98897A}.ct-notes-log{display:flex;flex-direction:column;gap:10px;max-height:220px;overflow-y:auto}.ct-note-entry{background:#F6F3EE;border-radius:10px;padding:10px 12px}.ct-note-meta{display:flex;justify-content:space-between;gap:8px;margin-bottom:4px}.ct-note-author{font:800 11.5px 'Nunito Sans';color:#2B3A4C}.ct-note-time{font:600 11px 'Nunito Sans';color:#98897A;white-space:nowrap}.ct-note-body{font:600 12.5px/1.5 'Nunito Sans';color:#2B3A4C;white-space:pre-wrap}.ct-notes-textarea{padding:10px 12px;border:1px solid #E2DACE;border-radius:9px;background:#F6F3EE;font:600 12.5px 'Nunito Sans';color:#2B3A4C;outline:none;resize:vertical;width:100%;box-sizing:border-box}.ct-save-btn{background:#2B3A4C;color:#fff;border:none;border-radius:8px;padding:8px 14px;font:700 11.5px 'Nunito Sans';cursor:pointer;align-self:flex-start}.ct-save-btn:hover{background:#1E2A38}@media(max-width:760px){.ct-details{grid-template-columns:1fr}.ct-modal-box{padding:22px 18px}}
</style>
@php
    $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
    $avatarColor = fn ($seed) => $avatarPalette[crc32((string) $seed) % count($avatarPalette)];
    $statusColors = [
        'new' => ['bg' => '#E7F0FB', 'fg' => '#2563AE'],
        'reviewed' => ['bg' => '#FDF3D6', 'fg' => '#96751B'],
        'interviewing' => ['bg' => '#F1EAFB', 'fg' => '#6E4FA8'],
        'hired' => ['bg' => '#E4F6EB', 'fg' => '#1E8A4C'],
        'rejected' => ['bg' => '#EFEBE3', 'fg' => '#7A6E60'],
    ];
    $applicationUrl = fn ($application) => route('job-applications.index', ['application' => $application->id] + (request('status') ? ['status' => request('status')] : []));
@endphp
<div class="ct-page">
 <div class="ct-header">
   <div>
     <div class="ct-title">Job Application</div>
     <div class="ct-sub">{{ $applications->count() }} {{ Str::plural('applicant', $applications->count()) }} shown &middot; {{ $newCount }} new</div>
   </div>
   <div class="ct-header-actions">
     <div class="ct-filters">
       <a href="{{ route('job-applications.index') }}" class="ct-filter {{ !request('status') || request('status') === 'all' ? 'active' : '' }}">All</a>
       @foreach ($statuses as $key => $label)
         <a href="{{ route('job-applications.index', ['status' => $key]) }}" class="ct-filter {{ request('status') === $key ? 'active' : '' }}">{{ $label }}</a>
       @endforeach
     </div>
     <a href="{{ route('job-postings.index') }}" class="ct-add-btn"><i class="fas fa-plus"></i> Job Posting</a>
   </div>
 </div>
 @if (session('success'))<div class="ct-success">{{ session('success') }}</div>@endif
 <div class="ct-card">
 @if ($applications->isEmpty())
   <div class="ct-empty">
     @if (request('status') && request('status') !== 'all')
       No {{ strtolower($statuses[request('status')] ?? request('status')) }} applications.
     @else
       No applications yet. Submissions from the Careers page will show up here.
     @endif
   </div>
 @else
   <table class="ct-table">
     <thead><tr><th>Applicant</th><th>Job title</th><th>Email &middot; Experience</th><th>Cover letter</th><th>Applied</th><th>Status</th></tr></thead>
     <tbody>
     @foreach ($applications as $application)
       @php $colors = $statusColors[$application->status] ?? $statusColors['rejected']; @endphp
       <tr class="ct-row" onclick="window.location='{{ $applicationUrl($application) }}'">
         <td>
           <div class="ct-person">
             <div class="ct-avatar" style="background: {{ $avatarColor($application->id) }};">{{ strtoupper(substr($application->first_name ?? '?', 0, 1)) }}</div>
             <div>
               <div class="ct-name">{{ $application->full_name }}</div>
               <div class="ct-secondary">Careers page enquiry</div>
             </div>
           </div>
         </td>
         <td>{{ $application->job_title }}</td>
         <td>
           <div>{{ $application->email }}</div>
           <div class="ct-secondary">{{ $application->years_experience ? $application->years_experience.' yrs experience' : 'Experience not stated' }}</div>
         </td>
         <td><div class="ct-message">{{ $application->cover_letter ?: 'No cover letter provided' }}</div></td>
         <td>{{ $application->created_at->format('M j, Y') }}<div class="ct-secondary">{{ $application->created_at->format('H:i') }}</div></td>
         <td><span class="ct-badge" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};">{{ $application->status_label }}</span></td>
       </tr>
     @endforeach
     </tbody>
   </table>
 @endif
 </div>
</div>
@if ($activeApplication)
 @php $colors = $statusColors[$activeApplication->status] ?? $statusColors['rejected']; @endphp
 <div id="appDetailModal" class="ct-modal open" role="dialog" aria-modal="true" aria-labelledby="appDetailTitle">
   <div class="ct-modal-box">
     <div class="ct-modal-head">
       <div class="ct-avatar" style="width:48px;height:48px;background: {{ $avatarColor($activeApplication->id) }}; font-size:17px;">{{ strtoupper(substr($activeApplication->first_name ?? '?', 0, 1)) }}</div>
       <div class="ct-modal-title">
         <div id="appDetailTitle">{{ $activeApplication->full_name }}</div>
         <div class="ct-modal-sub">Applied for {{ $activeApplication->job_title }} &middot; {{ $activeApplication->created_at->diffForHumans() }}</div>
       </div>
       <div>
         <div class="ct-modal-contact">{{ $activeApplication->email }}</div>
         <div class="ct-modal-contact-sub">{{ $activeApplication->years_experience ? $activeApplication->years_experience.' yrs experience' : 'Experience not stated' }}</div>
       </div>
       <button class="ct-close" type="button" onclick="closeApplicationDetail()" aria-label="Close application details">&times;</button>
     </div>

     <div class="ct-chips-row">
       <span class="ct-badge" style="background:#F9E7EC;color:#C8355F;">{{ $activeApplication->job_title }}</span>
       <span class="ct-badge" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};">{{ $activeApplication->status_label }}</span>
       @if ($activeApplication->resume_path)
         <a href="{{ route('job-applications.resume.view', $activeApplication->id) }}" target="_blank" rel="noopener" class="ct-action ct-action-outline"><i class="fas fa-eye"></i> View resume</a>
         <a href="{{ route('job-applications.resume', $activeApplication->id) }}" class="ct-action"><i class="fas fa-download"></i> Download</a>
       @else
         <span class="ct-action is-disabled"><i class="fas fa-file-slash"></i> No resume</span>
       @endif
     </div>

     <div class="ct-details">
       <div style="display:flex; flex-direction:column; gap:16px;">
         <div class="ct-detail-card">
           <div class="ct-detail-title">Cover letter</div>
           @if ($activeApplication->cover_letter)
             <div class="ct-message-body">{{ $activeApplication->cover_letter }}</div>
           @else
             <div class="ct-card-empty">No cover letter provided.</div>
           @endif
         </div>
         <div class="ct-detail-card">
           <div class="ct-detail-title">Internal notes</div>
           @if ($activeApplication->notesLog->isNotEmpty())
             <div class="ct-notes-log">
               @foreach ($activeApplication->notesLog as $note)
                 <div class="ct-note-entry">
                   <div class="ct-note-meta">
                     <span class="ct-note-author">{{ $note->author_name }}</span>
                     <span class="ct-note-time">{{ $note->created_at->diffForHumans() }}</span>
                   </div>
                   <div class="ct-note-body">{{ $note->body }}</div>
                 </div>
               @endforeach
             </div>
           @else
             <div class="ct-card-empty">No notes yet.</div>
           @endif
           <form action="{{ route('job-applications.notes.store', $activeApplication->id) }}" method="POST" style="display:flex; flex-direction:column; gap:10px;">
             @csrf
             <textarea name="body" rows="3" class="ct-notes-textarea" placeholder="Interview feedback, next steps, etc." required></textarea>
             <button type="submit" class="ct-save-btn">Add note</button>
           </form>
         </div>
       </div>

       <div style="display:flex; flex-direction:column; gap:16px;">
         <div class="ct-detail-card">
           <div class="ct-detail-title">Applicant details</div>
           <div>
             <div class="ct-field-row"><span class="ct-field-label">Email</span><span class="ct-field-value">{{ $activeApplication->email }}</span></div>
             <div class="ct-field-row"><span class="ct-field-label">Experience</span><span class="ct-field-value">{{ $activeApplication->years_experience ?: '—' }}</span></div>
             <div class="ct-field-row"><span class="ct-field-label">Applied for</span><span class="ct-field-value">{{ $activeApplication->job_title }}</span></div>
             <div class="ct-field-row"><span class="ct-field-label">Applied</span><span class="ct-field-value">{{ $activeApplication->created_at->format('M j, Y · H:i') }}</span></div>
           </div>
         </div>
         <div class="ct-detail-card">
           <div class="ct-detail-title">Status</div>
           <form action="{{ route('job-applications.update-status', $activeApplication->id) }}" method="POST">
             @csrf
             @method('PATCH')
             <select name="status" onchange="this.form.submit()" class="ct-select">
               @foreach ($statuses as $key => $label)
                 <option value="{{ $key }}" {{ $activeApplication->status === $key ? 'selected' : '' }}>{{ $label }}</option>
               @endforeach
             </select>
           </form>
           <form action="{{ route('job-applications.destroy', $activeApplication->id) }}" method="POST" onsubmit="return confirm('Delete this application? This also removes the stored resume and cannot be undone.');">
             @csrf
             @method('DELETE')
             <button type="submit" class="ct-delete">Delete application</button>
           </form>
         </div>
       </div>
     </div>
   </div>
 </div>
@endif
@endsection
@push('scripts')
<script>
function closeApplicationDetail(){const url=new URL(window.location.href);url.searchParams.delete('application');window.location.href=url.toString()}
document.addEventListener('DOMContentLoaded',function(){
    const modal=document.getElementById('appDetailModal');
    if(modal){
        modal.addEventListener('click',function(e){if(e.target===modal)closeApplicationDetail()});
    }
    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal)closeApplicationDetail()});
});
</script>
@endpush
