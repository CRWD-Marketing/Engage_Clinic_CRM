@extends('admin.layouts.admin-sidebar')

@section('title', 'Leads Pipeline · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- Top Bar -->
    <div style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; margin: -16px -20px 16px -20px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 0;">
            <div style="font: 600 18px/1.2 'Baloo 2'; color: #16436E;">Leads pipeline</div>
            <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">10 active enquiries · AED 105,600 est. monthly value</div>
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
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;">3</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Lead Card 1 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Rashid · 5</div>
                        <span style="background: #E3F4E9; color: #178A45; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">WhatsApp</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Noora (Umm Rashid)</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Asking about ABA fees &amp; Daman coverage</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 12,800/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">2h ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
                
                <!-- Lead Card 2 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Maya · 3</div>
                        <span style="background: #E7EFF7; color: #24619C; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">Website</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Sarah Thompson</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Early intervention enquiry — form submitted</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 9,600/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">5h ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
                
                <!-- Lead Card 3 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Adam · 6</div>
                        <span style="background: #FAE7F2; color: #C13584; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">Instagram</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Khaled Al Marzouqi</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">DM asking about speech + OT combined</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 7,400/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">1d ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contacted Stage -->
        <div style="width: 230px; min-width: 200px; flex-shrink: 0; background: #F0EBE5; border-radius: 12px; padding: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; padding: 2px 4px 8px;">
                <div style="font: 600 13px 'Baloo 2'; color: #16436E; flex: 1;">Contacted</div>
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;">2</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Lead Card 4 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Salem · 4</div>
                        <span style="background: #F7EEDD; color: #B97F24; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">Referral</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Fatima Al Shamsi</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Referred by Dr. Haddad (NMC) — intro call done</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 12,800/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">1d ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
                
                <!-- Lead Card 5 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Ethan · 7</div>
                        <span style="background: #EEE9F7; color: #6E4FA8; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">Google</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Priya Menon</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Brochure + price list sent on WhatsApp</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 6,200/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">2d ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Assessment Booked Stage -->
        <div style="width: 230px; min-width: 200px; flex-shrink: 0; background: #F0EBE5; border-radius: 12px; padding: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; padding: 2px 4px 8px;">
                <div style="font: 600 13px 'Baloo 2'; color: #16436E; flex: 1;">Assessment Booked</div>
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;">2</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Lead Card 6 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Mohammed · 5</div>
                        <span style="background: #E3F4E9; color: #178A45; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">WhatsApp</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Huda Bin Saeed</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Assessment today 11:00 with Dr. Noura</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 12,800/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">3d ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
                
                <!-- Lead Card 7 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Lina · 4</div>
                        <span style="background: #E7EFF7; color: #24619C; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">Website</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Omar Haddad</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">ADOS-2 assessment booked for Sun 12 Jul</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 9,600/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">4d ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Assessment Done Stage -->
        <div style="width: 230px; min-width: 200px; flex-shrink: 0; background: #F0EBE5; border-radius: 12px; padding: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; padding: 2px 4px 8px;">
                <div style="font: 600 13px 'Baloo 2'; color: #16436E; flex: 1;">Assessment Done</div>
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;">1</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Lead Card 8 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Yousef · 6</div>
                        <span style="background: #F7EEDD; color: #B97F24; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">Referral</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Amna Al Dhaheri</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Report shared — family reviewing 15h ABA plan</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 11,400/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">1w ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enrolled Stage -->
        <div style="width: 230px; min-width: 200px; flex-shrink: 0; background: #F0EBE5; border-radius: 12px; padding: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; padding: 2px 4px 8px;">
                <div style="font: 600 13px 'Baloo 2'; color: #16436E; flex: 1;">Enrolled</div>
                <span style="background: #FFFFFF; border-radius: 6px; padding: 1px 7px; font: 800 10px 'Nunito Sans'; color: #8A7D6C;">2</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Lead Card 9 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Ali · 5</div>
                        <span style="background: #E3F4E9; color: #178A45; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">WhatsApp</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Reem Al Falasi</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Started 20h ABA + speech on Monday</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 14,200/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">1w ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
                
                <!-- Lead Card 10 -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; font-size: 12px;">Grace · 4</div>
                        <span style="background: #FAE7F2; color: #C13584; border-radius: 5px; padding: 2px 7px; font: 800 9px 'Nunito Sans'; white-space: nowrap;">Instagram</span>
                    </div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Jennifer Okoro</div>
                    <div style="font: 600 11px/1.4 'Nunito Sans'; color: #5A6B7E; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Contract signed — Thiqa pre-auth pending</div>
                    <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid #F3EDE3; padding-top: 6px;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #16436E; flex: 1;">AED 8,800/mo</div>
                        <div style="font: 600 10px 'Nunito Sans'; color: #B0A493;">2w ago</div>
                        <button title="Move to next stage" style="width: 22px; height: 22px; border-radius: 6px; border: 1px solid #E2DACE; background: #FFFDFA; color: #C8355F; font: 800 12px/1 'Nunito Sans'; cursor: pointer;">→</button>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- New Lead Modal -->
    <div id="leadModal" style="display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.45); display: none; align-items: center; justify-content: center; z-index: 9999;">
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
                <!-- Child Name & Age -->
                <div style="display: grid; grid-template-columns: 1fr 110px; gap: 12px;">
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Child's name *</div>
                        <input id="childName" type="text" placeholder="e.g. Hamad" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Age</div>
                        <input id="childAge" type="text" placeholder="5" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                    </div>
                </div>
                
                <!-- Parent & Phone -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Parent / guardian *</div>
                        <input id="parentName" type="text" placeholder="e.g. Mrs. Shamma Al Qubaisi" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Phone (WhatsApp)</div>
                        <input id="phoneNumber" type="tel" placeholder="+971 5x xxx xxxx" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                    </div>
                </div>
                
                <!-- Source & Interested In -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Source</div>
                        <select id="leadSource" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            <option>Walk-in</option>
                            <option>Phone call</option>
                            <option>WhatsApp</option>
                            <option>Website</option>
                            <option>Instagram</option>
                            <option>Referral</option>
                            <option>Google</option>
                            <option>Event</option>
                        </select>
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Interested in</div>
                        <select id="leadInterest" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            <option>ABA therapy</option>
                            <option>Speech therapy</option>
                            <option>Occupational therapy</option>
                            <option>Diagnostic assessment</option>
                            <option>Early intervention</option>
                            <option>Combined program</option>
                        </select>
                    </div>
                </div>
                
                <!-- Insurance & Est. Value -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Insurance</div>
                        <select id="leadInsurance" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            <option>Not sure yet</option>
                            <option>Daman</option>
                            <option>Daman Enhanced</option>
                            <option>Thiqa</option>
                            <option>ADNIC</option>
                            <option>AXA / GIG</option>
                            <option>Self-pay</option>
                        </select>
                    </div>
                    <div>
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Est. monthly value (AED)</div>
                        <input id="leadValue" type="text" placeholder="12,800" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                    </div>
                </div>
                
                <!-- Notes -->
                <div style="margin-top: 12px;">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px;">Notes</div>
                    <input id="leadNote" type="text" placeholder="e.g. asked about fees and Daman coverage" style="width: 100%; padding: 10px 13px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 10px; margin-top: 16px;">
                    <button type="submit" style="flex: 1; background: #C8355F; color: white; border: none; border-radius: 10px; padding: 12px 0; font: 800 13.5px 'Nunito Sans'; cursor: pointer;">Save lead</button>
                    <button type="button" onclick="closeLeadModal()" style="width: 120px; background: #FFFFFF; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 10px; padding: 12px 0; font: 800 13.5px 'Nunito Sans'; cursor: pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

<script>
    // Open modal
    function openLeadModal() {
        document.getElementById('leadModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    // Close modal
    function closeLeadModal() {
        document.getElementById('leadModal').style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('leadForm').reset();
    }
    
    // Save lead
    function saveLead(event) {
        event.preventDefault();
        
        // Get form values
        const childName = document.getElementById('childName').value;
        const childAge = document.getElementById('childAge').value;
        const parentName = document.getElementById('parentName').value;
        const phoneNumber = document.getElementById('phoneNumber').value;
        const leadSource = document.getElementById('leadSource').value;
        const leadInterest = document.getElementById('leadInterest').value;
        const leadInsurance = document.getElementById('leadInsurance').value;
        const leadValue = document.getElementById('leadValue').value;
        const leadNote = document.getElementById('leadNote').value;
        
        // Basic validation
        if (!childName || !parentName) {
            alert('Please fill in Child\'s name and Parent/guardian fields.');
            return;
        }
        
        // Here you would typically send this data to your backend
        console.log({
            childName,
            childAge,
            parentName,
            phoneNumber,
            leadSource,
            leadInterest,
            leadInsurance,
            leadValue,
            leadNote
        });
        
        // Show success message
        alert('Lead saved successfully!');
        
        // Close modal
        closeLeadModal();
    }
    
    // Close modal when clicking outside
    document.getElementById('leadModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLeadModal();
        }
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLeadModal();
        }
    });
</script>