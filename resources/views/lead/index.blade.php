@extends('layouts.admin-sidebar')

@section('title', 'Leads Pipeline · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- Top Bar -->
    <div style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; margin: -16px -20px 16px -20px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 0;">
            <div style="font: 600 18px/1.2 'Baloo 2'; color: #16436E;">Leads pipeline</div>
            <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">
                <span id="leadCount">{{ $leads->count() }}</span> active enquiries · 
                <span id="totalValue">AED {{ number_format($totalValue ?? 0, 0) }}</span> est. monthly value
            </div>
        </div>
        <button style="background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 8px; padding: 8px 14px; font: 800 12px 'Nunito Sans'; cursor: pointer; white-space: nowrap;">Filter: All sources</button>
        <button onclick="openLeadModal()" style="background: #C8355F; color: white; border: none; border-radius: 8px; padding: 8px 16px; font: 800 12px 'Nunito Sans'; cursor: pointer; white-space: nowrap;">+ New Lead</button>
    </div>

    <!-- Kanban Board -->
    <div style="flex: 1; overflow-x: auto; overflow-y: auto; padding: 12px 0 20px 0; display: flex; gap: 12px; align-items: flex-start; min-height: calc(100vh - 200px);">
        
        <!-- New Stage -->
        <div style="width: 230px; min-width: 200px; flex-shrink: 0; background: #F0EBE5; border-radius: 12px; padding: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; padding: 2px 4px 8px;">
                <div style="font: 600 13px 'Baloo 2'; color: #16436E; flex: 1;">New</div>
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;" id="newCount">{{ $leads->where('status', 'new')->count() }}</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;" id="newLeads">
                @forelse($leads->where('status', 'new') as $lead)
                <div class="lead-card" data-id="{{ $lead->id }}" data-status="{{ $lead->status }}" style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px; transition: all 0.3s ease;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">{{ $lead->child_name ?? 'N/A' }} · {{ $lead->child_age ?? 'N/A' }}</div>
                        <span style="background: #E3F4E9; color: #178A45; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">{{ $lead->source ?? 'N/A' }}</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">{{ $lead->parent_guardian_name ?? 'N/A' }}</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $lead->notes ?? 'No notes' }}</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED {{ number_format($lead->estimated_value_numeric ?? 0, 0) }}/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">{{ $lead->created_at->diffForHumans() }}</div>
                        <div style="display: flex; gap: 3px;">
                            <button onclick="viewLead({{ $lead->id }})" title="View details" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #24619C; font: 800 10px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">👁</button>
                            <button onclick="advanceLead({{ $lead->id }}, '{{ $lead->status }}')" title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">→</button>
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align: center; color: #B0A493; font-size: 12px; padding: 20px 0;">No leads in this stage</div>
                @endforelse
            </div>
        </div>
        
        <!-- Contacted Stage -->
        <div style="width: 230px; min-width: 200px; flex-shrink: 0; background: #F0EBE5; border-radius: 12px; padding: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; padding: 2px 4px 8px;">
                <div style="font: 600 13px 'Baloo 2'; color: #16436E; flex: 1;">Contacted</div>
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;" id="contactedCount">{{ $leads->where('status', 'contacted')->count() }}</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;" id="contactedLeads">
                @forelse($leads->where('status', 'contacted') as $lead)
                <div class="lead-card" data-id="{{ $lead->id }}" data-status="{{ $lead->status }}" style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px; transition: all 0.3s ease;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">{{ $lead->child_name ?? 'N/A' }} · {{ $lead->child_age ?? 'N/A' }}</div>
                        <span style="background: #F7EEDD; color: #B97F24; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">{{ $lead->source ?? 'N/A' }}</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">{{ $lead->parent_guardian_name ?? 'N/A' }}</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $lead->notes ?? 'No notes' }}</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED {{ number_format($lead->estimated_value_numeric ?? 0, 0) }}/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">{{ $lead->created_at->diffForHumans() }}</div>
                        <div style="display: flex; gap: 3px;">
                            <button onclick="viewLead({{ $lead->id }})" title="View details" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #24619C; font: 800 10px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">👁</button>
                            <button onclick="advanceLead({{ $lead->id }}, '{{ $lead->status }}')" title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">→</button>
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align: center; color: #B0A493; font-size: 12px; padding: 20px 0;">No leads in this stage</div>
                @endforelse
            </div>
        </div>
        
        <!-- Assessment Booked Stage -->
        <div style="width: 230px; min-width: 200px; flex-shrink: 0; background: #F0EBE5; border-radius: 12px; padding: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; padding: 2px 4px 8px;">
                <div style="font: 600 13px 'Baloo 2'; color: #16436E; flex: 1;">Assessment Booked</div>
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;" id="assessmentBookedCount">{{ $leads->where('status', 'assessment_booked')->count() }}</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;" id="assessmentBookedLeads">
                @forelse($leads->where('status', 'assessment_booked') as $lead)
                <div class="lead-card" data-id="{{ $lead->id }}" data-status="{{ $lead->status }}" style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px; transition: all 0.3s ease;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">{{ $lead->child_name ?? 'N/A' }} · {{ $lead->child_age ?? 'N/A' }}</div>
                        <span style="background: #E7EFF7; color: #24619C; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">{{ $lead->source ?? 'N/A' }}</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">{{ $lead->parent_guardian_name ?? 'N/A' }}</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $lead->notes ?? 'No notes' }}</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED {{ number_format($lead->estimated_value_numeric ?? 0, 0) }}/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">{{ $lead->created_at->diffForHumans() }}</div>
                        <div style="display: flex; gap: 3px;">
                            <button onclick="viewLead({{ $lead->id }})" title="View details" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #24619C; font: 800 10px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">👁</button>
                            <button onclick="advanceLead({{ $lead->id }}, '{{ $lead->status }}')" title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">→</button>
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align: center; color: #B0A493; font-size: 12px; padding: 20px 0;">No leads in this stage</div>
                @endforelse
            </div>
        </div>
        
        <!-- Assessment Done Stage -->
        <div style="width: 230px; min-width: 200px; flex-shrink: 0; background: #F0EBE5; border-radius: 12px; padding: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; padding: 2px 4px 8px;">
                <div style="font: 600 13px 'Baloo 2'; color: #16436E; flex: 1;">Assessment Done</div>
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;" id="assessmentDoneCount">{{ $leads->where('status', 'assessment_done')->count() }}</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;" id="assessmentDoneLeads">
                @forelse($leads->where('status', 'assessment_done') as $lead)
                <div class="lead-card" data-id="{{ $lead->id }}" data-status="{{ $lead->status }}" style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px; transition: all 0.3s ease;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">{{ $lead->child_name ?? 'N/A' }} · {{ $lead->child_age ?? 'N/A' }}</div>
                        <span style="background: #FAE7F2; color: #C13584; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">{{ $lead->source ?? 'N/A' }}</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">{{ $lead->parent_guardian_name ?? 'N/A' }}</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $lead->notes ?? 'No notes' }}</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED {{ number_format($lead->estimated_value_numeric ?? 0, 0) }}/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">{{ $lead->created_at->diffForHumans() }}</div>
                        <div style="display: flex; gap: 3px;">
                            <button onclick="viewLead({{ $lead->id }})" title="View details" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #24619C; font: 800 10px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">👁</button>
                            <button onclick="advanceLead({{ $lead->id }}, '{{ $lead->status }}')" title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">→</button>
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align: center; color: #B0A493; font-size: 12px; padding: 20px 0;">No leads in this stage</div>
                @endforelse
            </div>
        </div>
        
        <!-- Enrolled Stage -->
        <div style="width: 230px; min-width: 200px; flex-shrink: 0; background: #F0EBE5; border-radius: 12px; padding: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; padding: 2px 4px 8px;">
                <div style="font: 600 13px 'Baloo 2'; color: #16436E; flex: 1;">Enrolled</div>
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;" id="enrolledCount">{{ $leads->where('status', 'enrolled')->count() }}</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;" id="enrolledLeads">
                @forelse($leads->where('status', 'enrolled') as $lead)
                <div class="lead-card" data-id="{{ $lead->id }}" data-status="{{ $lead->status }}" style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px; transition: all 0.3s ease;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">{{ $lead->child_name ?? 'N/A' }} · {{ $lead->child_age ?? 'N/A' }}</div>
                        <span style="background: #EEE9F7; color: #6E4FA8; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">{{ $lead->source ?? 'N/A' }}</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">{{ $lead->parent_guardian_name ?? 'N/A' }}</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $lead->notes ?? 'No notes' }}</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED {{ number_format($lead->estimated_value_numeric ?? 0, 0) }}/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">{{ $lead->created_at->diffForHumans() }}</div>
                        <div style="display: flex; gap: 3px;">
                            <button onclick="viewLead({{ $lead->id }})" title="View details" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #24619C; font: 800 10px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">👁</button>
                            <button onclick="advanceLead({{ $lead->id }}, '{{ $lead->status }}')" title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer; transition: all 0.2s ease;">→</button>
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align: center; color: #B0A493; font-size: 12px; padding: 20px 0;">No leads in this stage</div>
                @endforelse
            </div>
        </div>
        
    </div>

    <!-- View Lead Modal -->
    <div id="viewLeadModal" style="display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.45); align-items: center; justify-content: center; z-index: 9999;">
        <div style="width: 600px; max-height: 88vh; overflow-y: auto; background: #FFFDFA; border-radius: 18px; padding: 26px 28px; display: flex; flex-direction: column; gap: 16px; box-shadow: 0 20px 60px rgba(22,42,60,0.3);">
            
            <!-- Header -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="flex: 1;">
                    <div style="font: 600 20px 'Baloo 2'; color: #16436E;" id="viewLeadTitle">Lead Details</div>
                    <div style="font: 600 12px 'Nunito Sans'; color: #98897A;" id="viewLeadSubtitle">View and update lead information</div>
                </div>
                <button onclick="closeViewLeadModal()" style="width: 32px; height: 32px; border-radius: 9px; border: 1px solid #E2DACE; background: #FFFFFF; color: #5A6B7E; font: 800 15px/1 'Nunito Sans'; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
            </div>
            
            <!-- Lead Details - Editable Fields -->
            <form id="editLeadForm" onsubmit="updateLeadDetails(event)">
                @csrf
                @method('PUT')
                <input type="hidden" id="editLeadId" name="lead_id" value="">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Child's Name</div>
                        <input id="editChildName" name="child_name" type="text" style="width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: all 0.2s ease;">
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Age</div>
                        <input id="editChildAge" name="child_age" type="text" style="width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: all 0.2s ease;">
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Parent / Guardian</div>
                        <input id="editParentName" name="parent_guardian_name" type="text" style="width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: all 0.2s ease;">
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Phone</div>
                        <input id="editPhone" name="phone" type="text" style="width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: all 0.2s ease;">
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Source</div>
                        <select id="editSource" name="source" style="width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: all 0.2s ease;">
                            <option value="Walk-in">Walk-in</option>
                            <option value="Phone call">Phone call</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Website">Website</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Referral">Referral</option>
                            <option value="Google">Google</option>
                            <option value="Event">Event</option>
                        </select>
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Interested In</div>
                        <select id="editInterest" name="interested_in" style="width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: all 0.2s ease;">
                            <option value="ABA therapy">ABA therapy</option>
                            <option value="Speech therapy">Speech therapy</option>
                            <option value="Occupational therapy">Occupational therapy</option>
                            <option value="Diagnostic assessment">Diagnostic assessment</option>
                            <option value="Early intervention">Early intervention</option>
                            <option value="Combined program">Combined program</option>
                        </select>
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Insurance</div>
                        <select id="editInsurance" name="insurance" style="width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: all 0.2s ease;">
                            <option value="Not sure yet">Not sure yet</option>
                            <option value="Daman">Daman</option>
                            <option value="Daman Enhanced">Daman Enhanced</option>
                            <option value="Thiqa">Thiqa</option>
                            <option value="ADNIC">ADNIC</option>
                            <option value="AXA / GIG">AXA / GIG</option>
                            <option value="Self-pay">Self-pay</option>
                        </select>
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Est. Monthly Value (AED)</div>
                        <input id="editValue" name="estimated_value" type="text" placeholder="12,800" style="width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: all 0.2s ease;">
                    </div>
                </div>
                
                <!-- Notes -->
                <div>
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Notes</div>
                    <textarea id="editNotes" name="notes" rows="2" style="width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none; resize: vertical; transition: all 0.2s ease;"></textarea>
                </div>
                
                <!-- Status Update -->
                <div>
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Status</div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <select id="editStatus" name="status" style="flex: 1; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: all 0.2s ease;">
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="assessment_booked">Assessment Booked</option>
                            <option value="assessment_done">Assessment Done</option>
                            <option value="enrolled">Enrolled</option>
                        </select>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: 10px; border-top: 1px solid #F3EDE3; padding-top: 12px;">
                    <button type="submit" style="flex: 1; background: #C8355F; color: white; border: none; border-radius: 8px; padding: 10px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer;">Save Changes</button>
                    <button type="button" onclick="closeViewLeadModal()" style="flex: 0.5; background: #FFFFFF; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 8px; padding: 10px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer;">Cancel</button>
                </div>
                
                <!-- Timestamps -->
                <div style="display: flex; justify-content: space-between; font: 600 11px 'Nunito Sans'; color: #B0A493; border-top: 1px solid #F3EDE3; padding-top: 12px;">
                    <span>Created: <span id="viewCreatedAt">-</span></span>
                    <span>Last updated: <span id="viewUpdatedAt">-</span></span>
                </div>
            </form>
        </div>
    </div>

    <!-- New Lead Modal -->
    <div id="leadModal" style="display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.45); align-items: center; justify-content: center; z-index: 9999;">
        <div style="width: 520px; max-height: 88vh; overflow-y: auto; background: #FFFDFA; border-radius: 18px; padding: 26px 28px; display: flex; flex-direction: column; gap: 14px; box-shadow: 0 20px 60px rgba(22,42,60,0.3);">
            
            <!-- Header -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="flex: 1;">
                    <div style="font: 600 20px 'Baloo 2'; color: #16436E;">New lead — manual entry</div>
                    <div style="font: 600 12px 'Nunito Sans'; color: #98897A;">Walk-in, phone call, or event enquiry</div>
                </div>
                <button onclick="closeLeadModal()" style="width: 32px; height: 32px; border-radius: 9px; border: 1px solid #E2DACE; background: #FFFFFF; color: #5A6B7E; font: 800 15px/1 'Nunito Sans'; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
            </div>
            
            <!-- Form -->
            <form id="leadForm" onsubmit="saveLead(event)">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 110px; gap: 12px;">
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Child's name *</div>
                        <input id="childName" name="child_name" type="text" placeholder="e.g. Hamad" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;" required>
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Age</div>
                        <input id="childAge" name="child_age" type="text" placeholder="5" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Parent / guardian *</div>
                        <input id="parentName" name="parent_guardian_name" type="text" placeholder="e.g. Mrs. Shamma Al Qubaisi" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;" required>
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Phone (WhatsApp)</div>
                        <input id="phoneNumber" name="phone" type="tel" placeholder="+971 5x xxx xxxx" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Source</div>
                        <select id="leadSource" name="source" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            <option value="Walk-in">Walk-in</option>
                            <option value="Phone call">Phone call</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Website">Website</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Referral">Referral</option>
                            <option value="Google">Google</option>
                            <option value="Event">Event</option>
                        </select>
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Interested in</div>
                        <select id="leadInterest" name="interested_in" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            <option value="ABA therapy">ABA therapy</option>
                            <option value="Speech therapy">Speech therapy</option>
                            <option value="Occupational therapy">Occupational therapy</option>
                            <option value="Diagnostic assessment">Diagnostic assessment</option>
                            <option value="Early intervention">Early intervention</option>
                            <option value="Combined program">Combined program</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Insurance</div>
                        <select id="leadInsurance" name="insurance" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            <option value="Not sure yet">Not sure yet</option>
                            <option value="Daman">Daman</option>
                            <option value="Daman Enhanced">Daman Enhanced</option>
                            <option value="Thiqa">Thiqa</option>
                            <option value="ADNIC">ADNIC</option>
                            <option value="AXA / GIG">AXA / GIG</option>
                            <option value="Self-pay">Self-pay</option>
                        </select>
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Est. monthly value (AED)</div>
                        <input id="leadValue" name="estimated_value" type="text" placeholder="12,800" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                    </div>
                </div>
                
                <div style="margin-top: 12px;">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Notes</div>
                    <input id="leadNote" name="notes" type="text" placeholder="e.g. asked about fees and Daman coverage" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 16px;">
                    <button type="submit" style="flex: 1; background: #C8355F; color: white; border: none; border-radius: 10px; padding: 12px 0; font: 800 13.5px 'Nunito Sans'; cursor: pointer;">Save lead</button>
                    <button type="button" onclick="closeLeadModal()" style="width: 120px; background: #FFFFFF; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 10px; padding: 12px 0; font: 800 13.5px 'Nunito Sans'; cursor: pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Status mapping for display
    const statusMap = {
        'new': 'New',
        'contacted': 'Contacted',
        'assessment_booked': 'Assessment Booked',
        'assessment_done': 'Assessment Done',
        'enrolled': 'Enrolled'
    };

    const statusOrder = ['new', 'contacted', 'assessment_booked', 'assessment_done', 'enrolled'];

    const nextStatusMap = {
        'new': 'contacted',
        'contacted': 'assessment_booked',
        'assessment_booked': 'assessment_done',
        'assessment_done': 'enrolled',
        'enrolled': null
    };

    // Get the route URLs from Laravel
    const updateStatusUrl = '{{ route("leads.update-status", ["lead" => "__LEAD_ID__"]) }}';
    const storeLeadUrl = '{{ route("leads.store") }}';
    const viewLeadUrl = '{{ route("leads.show", ["lead" => "__LEAD_ID__"]) }}';
    const updateLeadUrl = '{{ route("leads.update", ["lead" => "__LEAD_ID__"]) }}';

    // View lead details
    function viewLead(leadId) {
        const url = viewLeadUrl.replace('__LEAD_ID__', leadId);
        
        document.getElementById('viewLeadTitle').textContent = 'Loading...';
        document.getElementById('viewLeadModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value || '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const lead = data.lead;
                
                document.getElementById('editLeadId').value = lead.id;
                document.getElementById('editChildName').value = lead.child_name || '';
                document.getElementById('editChildAge').value = lead.child_age || '';
                document.getElementById('editParentName').value = lead.parent_guardian_name || '';
                document.getElementById('editPhone').value = lead.phone || '';
                document.getElementById('editSource').value = lead.source || 'Walk-in';
                document.getElementById('editInterest').value = lead.interested_in || 'ABA therapy';
                document.getElementById('editInsurance').value = lead.insurance || 'Not sure yet';
                document.getElementById('editValue').value = lead.estimated_value ? Number(lead.estimated_value).toLocaleString() : '';
                document.getElementById('editNotes').value = lead.notes || '';
                document.getElementById('editStatus').value = lead.status || 'new';
                
                document.getElementById('viewLeadTitle').textContent = (lead.child_name || 'Lead') + ' · Lead Details';
                document.getElementById('viewLeadSubtitle').textContent = 'ID: #' + lead.id + ' · ' + (statusMap[lead.status] || lead.status);
                document.getElementById('viewCreatedAt').textContent = new Date(lead.created_at).toLocaleString();
                document.getElementById('viewUpdatedAt').textContent = new Date(lead.updated_at).toLocaleString();
            } else {
                showNotification('Error loading lead details.', 'error');
                closeViewLeadModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
            closeViewLeadModal();
        });
    }

    // Update lead details
    function updateLeadDetails(event) {
        event.preventDefault();
        
        const leadId = document.getElementById('editLeadId').value;
        const url = updateLeadUrl.replace('__LEAD_ID__', leadId);
        
        const form = document.getElementById('editLeadForm');
        const formData = new FormData(form);
        formData.append('_method', 'PUT');
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';
        submitBtn.disabled = true;
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value || '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const lead = data.lead;
                
                // Find the card
                let card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
                
                if (card) {
                    const oldStatus = card.dataset.status;
                    const newStatus = lead.status;
                    
                    // If status changed, move the card
                    if (oldStatus !== newStatus) {
                        // Save the card HTML before moving
                        const cardHtml = card.outerHTML;
                        moveLeadCard(leadId, oldStatus, newStatus);
                        updateCounts(oldStatus, newStatus);
                        // Get the new card reference
                        card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
                    }
                    
                    // Update the card content
                    if (card) {
                        // Get all child elements
                        const children = card.children;
                        
                        // First div - contains name and source badge
                        if (children[0]) {
                            const nameContainer = children[0];
                            const nameDiv = nameContainer.querySelector('div:first-child');
                            const sourceBadge = nameContainer.querySelector('span');
                            
                            if (nameDiv) {
                                nameDiv.innerHTML = (lead.child_name || 'N/A') + ' · ' + (lead.child_age || 'N/A');
                            }
                            if (sourceBadge) {
                                sourceBadge.textContent = lead.source || 'N/A';
                                const sourceColors = {
                                    'WhatsApp': { bg: '#E3F4E9', color: '#178A45' },
                                    'Website': { bg: '#E7EFF7', color: '#24619C' },
                                    'Instagram': { bg: '#FAE7F2', color: '#C13584' },
                                    'Referral': { bg: '#F7EEDD', color: '#B97F24' },
                                    'Google': { bg: '#EEE9F7', color: '#6E4FA8' },
                                    'Walk-in': { bg: '#EDEFF1', color: '#5A6B7E' },
                                    'Phone call': { bg: '#E3F1E9', color: '#2E7D5B' },
                                    'Event': { bg: '#F7EEDD', color: '#8A5A10' }
                                };
                                const colors = sourceColors[lead.source] || { bg: '#EDEFF1', color: '#5A6B7E' };
                                sourceBadge.style.background = colors.bg;
                                sourceBadge.style.color = colors.color;
                            }
                        }
                        
                        // Second div - parent name
                        if (children[1]) {
                            children[1].textContent = lead.parent_guardian_name || 'N/A';
                        }
                        
                        // Third div - notes
                        if (children[2]) {
                            children[2].textContent = lead.notes || 'No notes';
                        }
                        
                        // Fourth div - bottom row with value, time, buttons
                        if (children[3]) {
                            const bottomRow = children[3];
                            const bottomChildren = bottomRow.children;
                            
                            // Value div
                            if (bottomChildren[0]) {
                                const numValue = parseFloat(lead.estimated_value) || 0;
                                bottomChildren[0].textContent = 'AED ' + numValue.toLocaleString() + '/mo';
                            }
                            
                            // Time div - update to "just now"
                            if (bottomChildren[1]) {
                                bottomChildren[1].textContent = 'just now';
                            }
                            
                            // Button container
                            if (bottomChildren[2]) {
                                const buttons = bottomChildren[2].querySelectorAll('button');
                                if (buttons[0]) {
                                    buttons[0].setAttribute('onclick', `viewLead(${leadId})`);
                                }
                                if (buttons[1]) {
                                    buttons[1].setAttribute('onclick', `advanceLead(${leadId}, '${lead.status}')`);
                                }
                            }
                        }
                        
                        // Update data attribute
                        card.dataset.status = lead.status;
                    }
                }
                
                showNotification('Lead updated successfully! ✅', 'success');
                setTimeout(() => {
                    closeViewLeadModal();
                }, 1000);
            } else {
                let errorMsg = 'Could not update lead.';
                if (data.errors) {
                    errorMsg = Object.values(data.errors).flat().join(', ');
                }
                showNotification('Error: ' + errorMsg, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    }

    // Advance lead to next stage
    function advanceLead(leadId, currentStatus) {
        const nextStatus = nextStatusMap[currentStatus];
        
        if (!nextStatus) {
            showNotification('This lead is already in the final stage.', 'info');
            return;
        }

        const url = updateStatusUrl.replace('__LEAD_ID__', leadId);

        const card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
        const button = card ? card.querySelector('button:last-child') : null;
        if (button) {
            button.textContent = '⏳';
            button.disabled = true;
        }

        if (card) {
            card.style.opacity = '0.6';
            card.style.transform = 'scale(0.95)';
        }

        fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value || '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: nextStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                moveLeadCard(leadId, currentStatus, nextStatus);
                updateCounts(currentStatus, nextStatus);
                showNotification('Lead moved to ' + statusMap[nextStatus] + ' successfully! ✅', 'success');
            } else {
                showNotification('Error: ' + (data.message || 'Could not update lead status.'), 'error');
                if (card) {
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error. Please check your connection and try again.', 'error');
            if (card) {
                card.style.opacity = '1';
                card.style.transform = 'scale(1)';
            }
        })
        .finally(() => {
            if (button) {
                button.textContent = '→';
                button.disabled = false;
            }
        });
    }

    // Move lead card to the appropriate column
    function moveLeadCard(leadId, fromStatus, toStatus) {
        const card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
        if (!card) return;
        
        const targetContainerId = toStatus + 'Leads';
        const targetContainer = document.getElementById(targetContainerId);
        
        if (!targetContainer) return;
        
        const emptyMsg = targetContainer.querySelector('div[style*="text-align: center"]');
        if (emptyMsg) {
            emptyMsg.remove();
        }
        
        const cardClone = card.cloneNode(true);
        cardClone.dataset.status = toStatus;
        
        const newButton = cardClone.querySelector('button:last-child');
        if (newButton) {
            newButton.setAttribute('onclick', `advanceLead(${leadId}, '${toStatus}')`);
        }
        
        const viewButton = cardClone.querySelector('button:first-child');
        if (viewButton) {
            viewButton.setAttribute('onclick', `viewLead(${leadId})`);
        }
        
        if (card.parentNode) {
            card.parentNode.removeChild(card);
        }
        
        targetContainer.appendChild(cardClone);
        
        void cardClone.offsetWidth;
        cardClone.style.animation = 'fadeIn 0.3s ease';
        cardClone.style.opacity = '1';
        cardClone.style.transform = 'scale(1)';
    }

    // Update stage counts
    function updateCounts(fromStatus, toStatus) {
        const fromCountElement = document.getElementById(fromStatus + 'Count');
        if (fromCountElement) {
            let count = parseInt(fromCountElement.textContent) || 0;
            fromCountElement.textContent = Math.max(0, count - 1);
            fromCountElement.style.transition = 'all 0.3s ease';
            fromCountElement.style.transform = 'scale(0.8)';
            setTimeout(() => {
                fromCountElement.style.transform = 'scale(1)';
            }, 200);
        }
        
        const toCountElement = document.getElementById(toStatus + 'Count');
        if (toCountElement) {
            let count = parseInt(toCountElement.textContent) || 0;
            toCountElement.textContent = count + 1;
            toCountElement.style.transition = 'all 0.3s ease';
            toCountElement.style.transform = 'scale(1.2)';
            setTimeout(() => {
                toCountElement.style.transform = 'scale(1)';
            }, 200);
        }
    }

    // Close view lead modal
    function closeViewLeadModal() {
        document.getElementById('viewLeadModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Show notification
    function showNotification(message, type = 'success') {
        const colors = {
            success: '#d4edda',
            error: '#f8d7da',
            info: '#d1ecf1'
        };
        const textColors = {
            success: '#155724',
            error: '#721c24',
            info: '#0c5460'
        };
        
        const existing = document.querySelectorAll('.custom-notification');
        existing.forEach(el => el.remove());
        
        const notification = document.createElement('div');
        notification.className = 'custom-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[type]};
            color: ${textColors[type]};
            padding: 12px 20px;
            border-radius: 8px;
            font: 600 14px 'Nunito Sans';
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .lead-card {
            transition: all 0.3s ease;
        }
        .lead-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .lead-card button:hover {
            transform: scale(1.1);
        }
        .lead-card button:first-child:hover {
            background: #24619C;
            color: white;
        }
        .lead-card button:last-child:hover {
            background: #C8355F;
            color: white;
        }
        #editLeadForm input:focus,
        #editLeadForm select:focus,
        #editLeadForm textarea:focus {
            border-color: #C8355F;
            box-shadow: 0 0 0 3px rgba(200, 53, 95, 0.1);
        }
    `;
    document.head.appendChild(style);

    // Open lead modal
    function openLeadModal() {
        document.getElementById('leadModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    // Close lead modal
    function closeLeadModal() {
        document.getElementById('leadModal').style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('leadForm').reset();
    }
    
    // Save lead
    function saveLead(event) {
        event.preventDefault();
        
        const form = document.getElementById('leadForm');
        const formData = new FormData(form);
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';
        submitBtn.disabled = true;
        
        fetch(storeLeadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value || '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeLeadModal();
                showNotification('Lead saved successfully! Refreshing...', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                let errorMsg = 'Could not save lead.';
                if (data.errors) {
                    errorMsg = Object.values(data.errors).flat().join(', ');
                }
                showNotification('Error: ' + errorMsg, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    }
    
    // Close modals when clicking outside
    document.addEventListener('DOMContentLoaded', function() {
        const viewModal = document.getElementById('viewLeadModal');
        if (viewModal) {
            viewModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeViewLeadModal();
                }
            });
        }
        
        const leadModal = document.getElementById('leadModal');
        if (leadModal) {
            leadModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeLeadModal();
                }
            });
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeViewLeadModal();
                closeLeadModal();
            }
        });
    });
</script>
@endpush