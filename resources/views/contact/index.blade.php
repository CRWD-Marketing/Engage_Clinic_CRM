@extends('layouts.admin-sidebar')

@section('title', 'Contact Us · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
<style>
.main-content-inner.pt-tight-padding{padding-left:24px;padding-right:24px}.ct-header{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px}.ct-title{font:600 26px 'Baloo 2';color:#16436E}.ct-sub{margin-top:4px;color:#98897A;font:600 13px 'Nunito Sans'}.ct-filters{display:flex;gap:7px;flex-wrap:wrap}.ct-filter{border:1px solid #E2DACE;background:#fff;color:#5A6B7E;border-radius:999px;padding:7px 12px;text-decoration:none;font:800 11px 'Nunito Sans'}.ct-filter:hover{background:#F6F3EE}.ct-filter.active{background:#2B3A4C;border-color:#2B3A4C;color:#fff}.ct-card{overflow:hidden;overflow-x:auto;border:1px solid #EBE4DA;border-radius:16px;background:#fff;box-shadow:0 2px 10px rgba(22,42,60,.04)}.ct-table{width:100%;min-width:960px;border-collapse:collapse}.ct-table th{padding:13px 20px;border-bottom:1px solid #EBE4DA;background:#FFFDFA;color:#98897A;text-align:left;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;font:800 11px 'Nunito Sans'}.ct-table td{padding:15px 20px;border-bottom:1px solid #F3EDE3;vertical-align:middle;color:#2B3A4C;font:600 13px 'Nunito Sans'}.ct-table tr:last-child td{border:0}.ct-row{cursor:pointer}.ct-row:hover td{background:#FFFDFA}.ct-person{display:flex;align-items:center;gap:11px}.ct-avatar{width:36px;height:36px;flex-shrink:0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font:600 13px 'Baloo 2'}.ct-name{font:800 13.5px 'Nunito Sans'}.ct-secondary{margin-top:1px;color:#98897A;font:600 11.5px 'Nunito Sans'}.ct-message{max-width:270px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#5A6B7E}.ct-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 11px;white-space:nowrap;font:800 11px 'Nunito Sans'}.ct-view{display:inline-flex;border:1px solid #E2DACE;border-radius:8px;padding:7px 11px;background:#fff;color:#16436E;text-decoration:none;font:800 11.5px 'Nunito Sans'}.ct-view:hover{background:#F6F3EE}.ct-empty{padding:56px 20px;text-align:center;color:#98897A;font:700 12.5px 'Nunito Sans'}.ct-success{margin-bottom:18px;border:1px solid #BFE9CE;border-radius:10px;padding:12px 16px;background:#E4F6EB;color:#1E8A4C;font:700 13px 'Nunito Sans'}
.ct-modal{display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(22,42,60,.45);backdrop-filter:blur(2px)}.ct-modal.open{display:flex}.ct-modal-box{width:760px;max-width:100%;max-height:88vh;overflow-y:auto;border-radius:18px;border-top:5px solid var(--ct-accent,#C8355F);padding:26px 28px;background:#FFFDFA;box-shadow:0 20px 60px rgba(22,42,60,.3);animation:ctPop .18s ease-out}@keyframes ctPop{from{opacity:0;transform:translateY(6px) scale(.985)}to{opacity:1;transform:none}}.ct-modal-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:22px}.ct-modal-title{flex:1;min-width:0;color:#16436E;font:600 22px 'Baloo 2'}.ct-modal-sub{margin-top:4px;color:#98897A;font:600 12.5px 'Nunito Sans'}.ct-avatar{box-shadow:0 0 0 3px #fff,0 0 0 4px #EBE4DA}.ct-close{width:32px;height:32px;flex-shrink:0;border:1px solid #E2DACE;border-radius:9px;background:#fff;color:#5A6B7E;cursor:pointer;transition:background .12s,color .12s;font:800 18px/1 'Nunito Sans'}.ct-close:hover{background:#2B3A4C;color:#fff;border-color:#2B3A4C}.ct-details{display:grid;grid-template-columns:1.35fr .9fr;gap:16px}.ct-detail-card{display:flex;flex-direction:column;gap:12px;border:1px solid #EBE4DA;border-radius:14px;padding:18px 20px;background:#fff;transition:box-shadow .15s}.ct-detail-card:hover{box-shadow:0 3px 12px rgba(22,42,60,.05)}.ct-detail-title{display:flex;align-items:center;gap:7px;color:#16436E;font:600 16px 'Baloo 2'}.ct-detail-title svg{flex-shrink:0;opacity:.75}.ct-field{display:flex;flex-direction:column;gap:4px}.ct-label{display:flex;align-items:center;gap:5px;color:#98897A;text-transform:uppercase;letter-spacing:.6px;font:700 10.5px 'Nunito Sans'}.ct-input,.ct-select{width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #E2DACE;border-radius:9px;background:#F6F3EE;color:#2B3A4C;font:700 12px 'Nunito Sans';transition:border-color .12s,background .12s}.ct-input:focus,.ct-select:focus{outline:none;border-color:#C8355F;background:#fff}.ct-input::placeholder{color:#B5AB9C;font:600 12px 'Nunito Sans';font-style:italic}.ct-select{cursor:pointer}.ct-slot-card{display:flex;align-items:center;gap:12px;border:1px solid #F3D9E2;border-radius:12px;padding:13px 14px;background:linear-gradient(135deg,#FDF0F4,#FBEAF1)}.ct-slot-icon{display:flex;align-items:center;justify-content:center;width:34px;height:34px;flex-shrink:0;border-radius:10px;background:#fff;color:#C8355F;box-shadow:0 2px 6px rgba(200,53,95,.15)}.ct-slot-text{min-width:0}.ct-slot-date{color:#16436E;font:800 13px 'Nunito Sans'}.ct-slot-sub{margin-top:1px;color:#98897A;font:700 11px 'Nunito Sans'}.ct-action{display:inline-flex;justify-content:center;align-items:center;gap:6px;border:0;border-radius:10px;padding:11px 16px;background:#C8355F;color:#fff;cursor:pointer;text-decoration:none;font:800 12.5px 'Nunito Sans';box-shadow:0 4px 14px rgba(200,53,95,.28);transition:background .12s,transform .12s}.ct-action:hover{background:#A82348;transform:translateY(-1px)}.ct-converted{display:inline-flex;justify-content:center;align-items:center;gap:6px;border-radius:10px;padding:11px 16px;background:#E3F1E9;color:#1F7A4D;text-decoration:none;font:800 12.5px 'Nunito Sans';transition:background .12s}.ct-converted:hover{background:#D3EBDD}.ct-delete{margin-top:4px;border:0;padding:3px 0;background:none;color:#B91C1C;cursor:pointer;text-align:left;font:700 12px 'Nunito Sans'}.ct-delete:hover{text-decoration:underline}.ct-form-error{display:none;color:#B3261E;font:700 12px 'Nunito Sans'}.ct-email-btn{display:inline-flex;justify-content:center;align-items:center;gap:6px;border:0;border-radius:10px;padding:11px 16px;background:#16436E;color:#fff;cursor:pointer;text-decoration:none;font:800 12.5px 'Nunito Sans';transition:background .12s,transform .12s}.ct-email-btn:hover{background:#0F3155;transform:translateY(-1px)}.ct-email-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}.ct-emailed{display:flex;align-items:center;gap:6px;color:#7A8CA0;font:700 11.5px 'Nunito Sans'}.ct-reject-note{display:flex;align-items:flex-start;gap:8px;border-radius:10px;padding:11px 12px;background:#FBEEEE;color:#96322C;font:700 11.5px 'Nunito Sans';line-height:1.4}.ct-reject-note svg{flex-shrink:0;margin-top:1px}.ct-email-modal{display:none;position:fixed;inset:0;z-index:10050;align-items:center;justify-content:center;padding:16px;background:rgba(22,42,60,.55)}.ct-email-modal.open{display:flex}.ct-email-box{width:560px;max-width:100%;max-height:88vh;overflow-y:auto;border-radius:16px;padding:24px 26px;background:#fff;box-shadow:0 20px 60px rgba(22,42,60,.35);animation:ctPop .18s ease-out}.ct-email-error{display:none;margin-bottom:10px;color:#B3261E;font:700 12px 'Nunito Sans'}
/* Contact side panel - mirrors the Leads pipeline's action-panel drawer: a
   read-only info display (no editable form fields), sliding in from the
   right, with dedicated action buttons instead of a status dropdown. */
.cp-overlay{display:none;position:fixed;inset:0;background:rgba(22,42,60,.35);z-index:9999;justify-content:flex-end}.cp-overlay.open{display:flex}.cp-panel{width:460px;max-width:100%;height:100%;background:#FFFDFA;box-shadow:-12px 0 40px rgba(22,42,60,.18);display:flex;flex-direction:column;overflow:hidden;border-top:5px solid var(--ct-accent,#C8355F);box-sizing:border-box;animation:cpSlide .2s ease-out}@keyframes cpSlide{from{transform:translateX(24px);opacity:.4}to{transform:none;opacity:1}}
.cp-head{padding:20px 22px 14px;border-bottom:1px solid #EBE4DA;flex:none}.cp-head-top{display:flex;align-items:center;gap:10px}.cp-name{font:600 22px 'Baloo 2';color:#16436E;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.cp-source-badge{flex-shrink:0;background:#E7F0FB;color:#2563AE;border-radius:999px;padding:4px 10px;font:800 10.5px 'Nunito Sans';white-space:nowrap}.cp-close{width:32px;height:32px;flex-shrink:0;border-radius:9px;border:1px solid #E2DACE;background:#fff;color:#5A6B7E;font:800 15px/1 'Nunito Sans';cursor:pointer;display:flex;align-items:center;justify-content:center}.cp-close:hover{background:#2B3A4C;color:#fff;border-color:#2B3A4C}.cp-meta{display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap}.cp-updated{font:600 11.5px 'Nunito Sans';color:#98897A}
.cp-body{flex:1;overflow-y:auto;padding:18px 22px 22px;display:flex;flex-direction:column;gap:18px}.cp-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px}.cp-stat{background:#F6F3EE;border-radius:10px;padding:10px 12px}.cp-section-label{font:700 10.5px 'Nunito Sans';color:#98897A;text-transform:uppercase;letter-spacing:.6px;display:flex;align-items:center;gap:6px}.cp-section-label svg{opacity:.75}.cp-text{font:600 13px/1.5 'Nunito Sans';color:#2B3A4C;margin-top:6px;white-space:pre-line;word-break:break-word}.cp-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;margin-top:8px}.cp-info-label{font:800 10px 'Nunito Sans';text-transform:uppercase;letter-spacing:.05em;color:#98897A}.cp-info-value{font:700 12.5px 'Nunito Sans';color:#2B3A4C;margin-top:2px;word-break:break-word}
.cp-actions{border-top:1px solid #EBE4DA;padding:16px 22px 20px;display:flex;gap:10px;flex:none;flex-wrap:wrap}.cp-actions form{flex:1;display:flex;min-width:120px}.cp-actions button,.cp-actions a{flex:1;border:none;border-radius:10px;padding:13px 14px;font:800 12.5px 'Nunito Sans';letter-spacing:.2px;cursor:pointer;box-sizing:border-box;text-align:center;text-decoration:none;transition:background .15s,transform .05s;display:flex;align-items:center;justify-content:center;gap:6px}.cp-actions button:active,.cp-actions a:active{transform:translateY(1px)}
.cp-btn-approve{background:#fff;color:#2E7D5B;border:1px solid #CDE9D8 !important}.cp-btn-approve:hover{background:#F1F9F4}.cp-btn-approve.is-active{background:#2E7D5B;color:#fff;border-color:#2E7D5B !important}
.cp-btn-reject{background:#fff;color:#B3261E;border:1px solid #E8CFCC !important}.cp-btn-reject:hover{background:#F9E4E2}.cp-btn-reject.is-active{background:#B3261E;color:#fff;border-color:#B3261E !important}
.cp-btn-email{background:#16436E;color:#fff}.cp-btn-email:hover{background:#0F3255}
.cp-btn-convert{background:#C8355F;color:#fff;box-shadow:0 4px 14px rgba(200,53,95,.28)}.cp-btn-convert:hover{background:#A82348}
.cp-btn-view-lead{background:#E3F1E9;color:#1F7A4D}.cp-btn-view-lead:hover{background:#D3EBDD}
.cp-footnote{width:100%;margin:-4px 0 0;color:#98897A;font:700 11px 'Nunito Sans'}
.cp-links{display:flex;justify-content:space-between;align-items:center}.cp-link{border:0;background:none;color:#98897A;cursor:pointer;font:700 11.5px 'Nunito Sans';text-decoration:underline;padding:0}.cp-link:hover{color:#5A6B7E}.cp-link.danger{color:#B91C1C}.cp-link.danger:hover{color:#8f1616}
@media(max-width:760px){.ct-details{grid-template-columns:1fr}.ct-modal-box{padding:22px 18px}}@media(max-width:480px){.cp-panel{width:100%}}
</style>
@php
    $avatarPalette=['#C8355F','#24619C','#B97F24','#6E4FA8','#1F8FA8','#A8461F','#2E7D5B'];
    $avatarColor=fn($seed)=>$avatarPalette[crc32((string)$seed)%count($avatarPalette)];
    $statusColors=['new'=>['bg'=>'#E7F0FB','fg'=>'#2563AE'],'approved'=>['bg'=>'#E4F6EB','fg'=>'#1E8A4C'],'rejected'=>['bg'=>'#FBE4E4','fg'=>'#B3261E'],'contacted'=>['bg'=>'#FDF3D6','fg'=>'#96751B'],'converted'=>['bg'=>'#E4F6EB','fg'=>'#1E8A4C'],'closed'=>['bg'=>'#EFEBE3','fg'=>'#7A6E60']];
@endphp
<div class="ct-page">
 <div class="ct-header"><div><div class="ct-title">Contacts</div><div class="ct-sub">{{ $contacts->count() }} {{ Str::plural('submission',$contacts->count()) }} shown · {{ $newCount }} new</div></div><div class="ct-filters"><a href="{{ route('contacts.index') }}" class="ct-filter {{ !request('status')||request('status')==='all'?'active':'' }}">All</a>@foreach($statuses as $key=>$label)<a href="{{ route('contacts.index',['status'=>$key]) }}" class="ct-filter {{ request('status')===$key?'active':'' }}">{{ $label }}</a>@endforeach</div></div>
 @if(session('success'))<div class="ct-success">{{ session('success') }}</div>@endif
 <div class="ct-card">
 @if($contacts->isEmpty())<div class="ct-empty">No contact submissions match this view.</div>
 @else <table class="ct-table"><thead><tr><th>Contact</th><th>Child · Service enquiry</th><th>Phone · Email</th><th>Consultation slot</th><th>Received</th><th>Status</th></tr></thead><tbody>
 @foreach($contacts as $contact) @php $colors=$statusColors[$contact->status]??$statusColors['closed'];$parts=preg_split('/\s+/',trim($contact->name??''));$initials=strtoupper(($parts[0][0]??'?').($parts[1][0]??''));$slotLabel=$contact->hasBookingSlot()?$contact->booking_date->format('M j, Y').' · '.$contact->booking_time:null; @endphp
 <tr class="ct-row" data-contact="{{ json_encode(['id'=>$contact->id,'name'=>$contact->name,'phone'=>$contact->phone,'email'=>$contact->email,'child_age'=>$contact->child_age,'interested_in'=>$contact->interested_in,'message'=>$contact->message,'status'=>$contact->status,'status_label'=>$contact->status_label,'booking_date'=>optional($contact->booking_date)->format('M j, Y'),'booking_time'=>$contact->booking_time,'booking_decision'=>$contact->booking_decision,'status_email_sent_at'=>optional($contact->status_email_sent_at)->format('M j, Y · H:i'),'can_send_email'=>$contact->canSendStatusEmail(),'can_convert'=>$contact->canConvertToLead(),'received'=>$contact->created_at->format('M j, Y · H:i'),'avatar'=>$avatarColor($contact->id)], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"><td><div class="ct-person"><div class="ct-avatar" style="background:{{ $avatarColor($contact->id) }}">{{ $initials }}</div><div><div class="ct-name">{{ $contact->name?:'Unknown contact' }}</div><div class="ct-secondary">Website enquiry</div></div></div></td><td><div>{{ $contact->child_age?'Age '.$contact->child_age:'Age not provided' }}</div><div class="ct-secondary">{{ $contact->interested_in?:'No service selected' }}</div></td><td><div>{{ $contact->phone?:'—' }}</div><div class="ct-secondary">{{ $contact->email?:'No email provided' }}</div></td><td>@if($slotLabel)<div>{{ $slotLabel }}</div><div class="ct-secondary">Free 30-min consultation</div>@else<div class="ct-secondary">No slot requested</div>@endif</td><td>{{ $contact->created_at->format('M j, Y') }}<div class="ct-secondary">{{ $contact->created_at->format('H:i') }}</div></td><td><span class="ct-badge" style="background:{{ $colors['bg'] }};color:{{ $colors['fg'] }}">{{ $contact->status_label }}</span></td></tr>
 @endforeach
 </tbody></table>@endif
 </div>
</div>
<div id="ctPanelOverlay" class="cp-overlay" role="dialog" aria-modal="true" aria-labelledby="ctPanelName">
 <div id="ctPanel" class="cp-panel">
  <div class="cp-head">
   <div class="cp-head-top"><div id="ctPanelName" class="cp-name">—</div><span class="cp-source-badge">Website</span><button type="button" class="cp-close" onclick="closeContactPanel()" aria-label="Close contact details">✕</button></div>
   <div class="cp-meta"><span id="ctPanelStatus" class="ct-badge"></span><span id="ctPanelReceived" class="cp-updated"></span></div>
  </div>
  <div class="cp-body">
   <div class="cp-stats">
    <div class="cp-stat"><div class="cp-section-label">Child's age</div><div id="ctPanelAge" class="cp-text" style="margin-top:4px">—</div></div>
    <div class="cp-stat"><div class="cp-section-label">Service enquiry</div><div id="ctPanelInterest" class="cp-text" style="margin-top:4px">—</div></div>
   </div>
   <div><div class="cp-section-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Message</div><div id="ctPanelMessage" class="cp-text">—</div></div>
   <div id="ctPanelSlotWrap" style="display:none"><div class="cp-section-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>Requested consultation slot</div><div class="ct-slot-card" style="margin-top:8px"><div class="ct-slot-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></div><div class="ct-slot-text"><div id="ctPanelSlotDate" class="ct-slot-date"></div><div class="ct-slot-sub">Free 30-min consultation</div></div></div></div>
   <div><div class="cp-section-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Contact details</div>
    <div class="cp-info-grid">
     <div><div class="cp-info-label">Phone</div><div id="ctPanelPhone" class="cp-info-value">—</div></div>
     <div><div class="cp-info-label">Email</div><div id="ctPanelEmail" class="cp-info-value">—</div></div>
     <div><div class="cp-info-label">Channel</div><div class="cp-info-value">Website</div></div>
     <div><div class="cp-info-label">Received</div><div id="ctPanelReceivedFull" class="cp-info-value">—</div></div>
    </div>
   </div>
   <div id="ctPanelEmailedWrap" style="display:none"><div class="cp-section-label">Decision</div><div id="ctPanelEmailedText" class="ct-emailed" style="margin-top:6px"></div></div>
  </div>
  <div class="cp-actions" id="ctPanelActions">
   <form id="ctApproveForm" method="POST" style="display:none">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button type="submit" id="ctApproveBtn" class="cp-btn-approve"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Approve</button></form>
   <form id="ctRejectForm" method="POST" style="display:none">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button type="submit" id="ctRejectBtn" class="cp-btn-reject"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>Reject</button></form>
   <button type="button" id="ctEmailActionBtn" class="cp-btn-email" style="display:none" onclick="ctOpenEmailFromPanel()">Send Email</button>
   <form id="ctConvertForm" method="POST" style="display:none">@csrf<button type="submit" class="cp-btn-convert">Convert to lead</button></form>
   <a id="ctViewLeadLink" href="{{ route('leads.index') }}" class="cp-btn-view-lead" style="display:none">View lead ✓</a>
   <div class="cp-links" style="flex-basis:100%">
    <button type="button" id="ctCloseLink" class="cp-link" style="display:none" onclick="if(confirm('Close this submission without approving or rejecting?'))document.getElementById('ctCloseForm').submit()">Close without action</button>
    @if(auth()->user()->role!=='COORDINATOR')<button type="button" id="ctDeleteLink" class="cp-link danger" onclick="if(confirm('Delete this submission? This cannot be undone.'))document.getElementById('ctDeleteForm').submit()">Delete submission</button>@endif
   </div>
   <form id="ctCloseForm" method="POST" style="display:none">@csrf @method('PATCH')<input type="hidden" name="status" value="closed"></form>
   <form id="ctDeleteForm" method="POST" style="display:none">@csrf @method('DELETE')</form>
  </div>
 </div>
</div>
<div id="ctEmailModal" class="ct-email-modal" role="dialog" aria-modal="true" aria-labelledby="ctEmailTitle"><div class="ct-email-box"><div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px"><div><div id="ctEmailTitle" class="ct-detail-title">Email the family</div><div class="ct-modal-sub" id="ctEmailSub"></div></div><button class="ct-close" type="button" onclick="closeEmailModal()" aria-label="Close">×</button></div><div class="ct-email-error" id="ctEmailError"></div><div class="ct-field" style="margin-bottom:12px"><label class="ct-label" for="ctEmailTo">To</label><input id="ctEmailTo" type="email" class="ct-input"></div><div class="ct-field" style="margin-bottom:12px"><label class="ct-label" for="ctEmailSubject">Subject</label><input id="ctEmailSubject" type="text" class="ct-input"></div><div class="ct-field" style="margin-bottom:16px"><label class="ct-label" for="ctEmailMessage">Message</label><textarea id="ctEmailMessage" class="ct-input" rows="8" style="resize:vertical;font-family:'Nunito Sans'"></textarea></div><button type="button" id="ctEmailSend" class="ct-email-btn" style="width:100%">Send email</button></div></div>
@if($activeContact)
<script>const CT_ACTIVE_CONTACT={!! json_encode(['id'=>$activeContact->id,'name'=>$activeContact->name,'phone'=>$activeContact->phone,'email'=>$activeContact->email,'child_age'=>$activeContact->child_age,'interested_in'=>$activeContact->interested_in,'message'=>$activeContact->message,'status'=>$activeContact->status,'status_label'=>$activeContact->status_label,'booking_date'=>optional($activeContact->booking_date)->format('M j, Y'),'booking_time'=>$activeContact->booking_time,'booking_decision'=>$activeContact->booking_decision,'status_email_sent_at'=>optional($activeContact->status_email_sent_at)->format('M j, Y · H:i'),'can_send_email'=>$activeContact->canSendStatusEmail(),'can_convert'=>$activeContact->canConvertToLead(),'received'=>$activeContact->created_at->format('M j, Y · H:i'),'avatar'=>$avatarColor($activeContact->id)], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};</script>
@endif
@endsection
@push('scripts')
<script>
function closeContactPanel(){
    document.getElementById('ctPanelOverlay')?.classList.remove('open');
    const url=new URL(window.location.href);
    if(url.searchParams.has('contact')){url.searchParams.delete('contact');window.history.replaceState({},'',url.toString())}
}
function closeEmailModal(){document.getElementById('ctEmailModal')?.classList.remove('open')}

const CT_CLINIC_NAME=@json(config('clinic.name'));
const CT_CLINIC_PHONE='+971 50 884 6801';
const CT_CLINIC_EMAIL='info@engagebehavior.com';
const CT_CLINIC_ADDRESS='Office No. 1203, ADCP Commercial Tower-C, Electra Street, Abu Dhabi, UAE';
const CT_SEND_EMAIL_TEMPLATE=@json(route('contacts.send-email',['contact'=>'__CONTACT__']));
let ctEmailTargetId=null, ctCurrentContact=null;

// contact is a plain object with at least {id,name,email,status,booking_date,booking_time} -
// `status` doubles as the decision (approved/rejected) since that's the only
// time this is ever callable (see canSendStatusEmail()).
function openEmailModalFor(contact){
    ctEmailTargetId=contact.id;
    const approved=contact.status==='approved';
    const slot=(contact.booking_date&&contact.booking_time)?`${contact.booking_date} at ${contact.booking_time}`:'your requested slot';
    document.getElementById('ctEmailError').style.display='none';
    document.getElementById('ctEmailSub').textContent=(contact.name||'This family')+' · '+(approved?'approval':'rejection')+' notice';
    document.getElementById('ctEmailTo').value=contact.email||'';
    document.getElementById('ctEmailSubject').value=approved
        ? 'Your free consultation with '+CT_CLINIC_NAME+' is confirmed'
        : 'About your consultation request with '+CT_CLINIC_NAME;
    const ctContactBlock=`Call Us: ${CT_CLINIC_PHONE}\nEmail: ${CT_CLINIC_EMAIL}\nVisit Us: ${CT_CLINIC_ADDRESS}\n\nWarm regards,\n${CT_CLINIC_NAME}`;
    document.getElementById('ctEmailMessage').value=approved
        ? `Dear ${contact.name||'parent'},\n\nThank you for choosing ${CT_CLINIC_NAME}. Your free 30-minute consultation has been confirmed for ${slot}.\n\nOur team looks forward to meeting with you and learning more about your child's needs, so we can better understand how ${CT_CLINIC_NAME} may support your family.\n\nIf you need to make any changes to your consultation, simply reply to this email and our team will be happy to assist.\n\n${ctContactBlock}`
        : `Dear ${contact.name||'parent'},\n\nThank you for your interest in ${CT_CLINIC_NAME} and for taking the time to request a free consultation.\n\nUnfortunately, we're unable to confirm the consultation slot you requested (${slot}). This may simply be due to limited availability at that time, but we'd still love the opportunity to support your family.\n\nPlease reply to this email or reach out to us directly, and our team will be happy to help you find another time that works.\n\n${ctContactBlock}`;
    document.getElementById('ctEmailModal').classList.add('open');
}

function ctOpenEmailFromPanel(){ if(ctCurrentContact) openEmailModalFor(ctCurrentContact); }

document.getElementById('ctEmailSend')?.addEventListener('click',function(){
    const btn=this,error=document.getElementById('ctEmailError'),label=btn.textContent;
    error.style.display='none';
    btn.disabled=true;btn.textContent='Sending…';
    fetch(CT_SEND_EMAIL_TEMPLATE.replace('__CONTACT__',ctEmailTargetId),{
        method:'POST',
        headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content,'Accept':'application/json','Content-Type':'application/json'},
        body:JSON.stringify({to:document.getElementById('ctEmailTo').value,subject:document.getElementById('ctEmailSubject').value,message:document.getElementById('ctEmailMessage').value}),
    })
    .then(async response=>({ok:response.ok,data:await response.json()}))
    .then(result=>{
        if(result.ok){window.location.reload();return}
        error.textContent=result.data.errors?Object.values(result.data.errors).flat().join(', '):(result.data.message||'Could not send the email.');
        error.style.display='block';btn.disabled=false;btn.textContent=label;
    })
    .catch(()=>{error.textContent='Network error. Please try again.';error.style.display='block';btn.disabled=false;btn.textContent=label});
});

document.addEventListener('DOMContentLoaded',function(){
    const overlay=document.getElementById('ctPanelOverlay'),panel=document.getElementById('ctPanel'),emailModal=document.getElementById('ctEmailModal'),
        statusTemplate=@json(route('contacts.update-status',['contact'=>'__CONTACT__'])),
        convertTemplate=@json(route('contacts.convert-to-lead',['contact'=>'__CONTACT__'])),
        deleteTemplate=@json(route('contacts.destroy',['contact'=>'__CONTACT__'])),
        colors=@json($statusColors),
        urlFor=(template,id)=>template.replace('__CONTACT__',id);

    function openContactPanel(contact){
        ctCurrentContact=contact;
        const color=colors[contact.status]||colors.closed,converted=contact.status==='converted';
        panel.style.setProperty('--ct-accent',color.fg);
        document.getElementById('ctPanelName').textContent=contact.name||'Unknown contact';
        document.getElementById('ctPanelReceived').textContent='Received '+contact.received;
        document.getElementById('ctPanelReceivedFull').textContent=contact.received;
        document.getElementById('ctPanelAge').textContent=contact.child_age?('Age '+contact.child_age):'Not provided';
        document.getElementById('ctPanelInterest').textContent=contact.interested_in||'Not provided';
        document.getElementById('ctPanelMessage').textContent=contact.message||'No message provided';
        document.getElementById('ctPanelPhone').textContent=contact.phone||'Not provided';
        document.getElementById('ctPanelEmail').textContent=contact.email||'Not provided';

        const slotWrap=document.getElementById('ctPanelSlotWrap');
        if(contact.booking_date&&contact.booking_time){slotWrap.style.display='';document.getElementById('ctPanelSlotDate').textContent=contact.booking_date+' · '+contact.booking_time}
        else{slotWrap.style.display='none'}

        const badge=document.getElementById('ctPanelStatus');
        badge.textContent=contact.status_label;badge.style.background=color.bg;badge.style.color=color.fg;

        // Approve/Reject stay available (and swappable) right up until the
        // decision is emailed - after that (contacted) or once converted,
        // the decision is locked in and these buttons disappear entirely.
        const decidable=['new','approved','rejected'].includes(contact.status);
        const approveForm=document.getElementById('ctApproveForm'),rejectForm=document.getElementById('ctRejectForm'),
            approveBtn=document.getElementById('ctApproveBtn'),rejectBtn=document.getElementById('ctRejectBtn');
        approveForm.style.display=decidable?'':'none';
        rejectForm.style.display=decidable?'':'none';
        approveForm.action=urlFor(statusTemplate,contact.id);
        rejectForm.action=urlFor(statusTemplate,contact.id);
        approveBtn.classList.toggle('is-active',contact.status==='approved');
        rejectBtn.classList.toggle('is-active',contact.status==='rejected');

        const emailBtn=document.getElementById('ctEmailActionBtn'),emailedWrap=document.getElementById('ctPanelEmailedWrap'),emailedText=document.getElementById('ctPanelEmailedText');
        emailBtn.style.display=contact.can_send_email?'':'none';
        if(contact.status_email_sent_at){
            const decisionLabel=contact.booking_decision==='approved'?'Approved':(contact.booking_decision==='rejected'?'Rejected':'Decision');
            emailedText.textContent=decisionLabel+' · emailed '+contact.status_email_sent_at;
            emailedWrap.style.display='';
        }else{emailedWrap.style.display='none'}

        const convertForm=document.getElementById('ctConvertForm'),leadLink=document.getElementById('ctViewLeadLink');
        convertForm.style.display=contact.can_convert?'':'none';
        convertForm.action=urlFor(convertTemplate,contact.id);
        leadLink.style.display=converted?'':'none';

        document.getElementById('ctCloseLink').style.display=(contact.status==='new')?'':'none';
        document.getElementById('ctCloseForm').action=urlFor(statusTemplate,contact.id);
        const deleteForm=document.getElementById('ctDeleteForm');if(deleteForm)deleteForm.action=urlFor(deleteTemplate,contact.id);

        overlay.classList.add('open');
    }

    document.querySelectorAll('.ct-row[data-contact]').forEach(row=>row.addEventListener('click',()=>{
        openContactPanel(JSON.parse(row.dataset.contact));
    }));

    if(typeof CT_ACTIVE_CONTACT!=='undefined')openContactPanel(CT_ACTIVE_CONTACT);

    if(overlay)overlay.addEventListener('click',e=>{if(e.target===overlay)closeContactPanel()});
    if(emailModal)emailModal.addEventListener('click',function(e){if(e.target===emailModal)closeEmailModal()});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){if(emailModal?.classList.contains('open'))closeEmailModal();else if(overlay?.classList.contains('open'))closeContactPanel()}});
});
</script>
@endpush
