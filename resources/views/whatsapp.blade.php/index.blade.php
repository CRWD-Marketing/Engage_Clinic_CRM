@extends('layouts.admin-sidebar')

@section('title', 'WhatsApp · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- WhatsApp Inbox -->
    <div style="flex: 1; display: flex; min-height: 0; overflow-x: auto; margin: -22px -28px 0 -28px;">
        
        <!-- Left Sidebar - Chat List -->
        <div style="width: 300px; border-right: 1px solid #EBE4DA; background: #FFFDFA; display: flex; flex-direction: column; flex-shrink: 0;">
            <div style="padding: 16px 18px; border-bottom: 1px solid #EBE4DA;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #1FA855;"></span>
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E;">WhatsApp Business</div>
                </div>
                <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">+971 2 555 0123 · connected · leads auto-captured</div>
            </div>
            <div style="flex: 1; overflow-y: auto;">
                <!-- Chat 1 - Active -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: #F5EFE7;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #C8355F; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">NU</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; gap: 6px; align-items: baseline;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Noora (Umm Rashid)</div>
                            <div style="font: 600 10.5px 'Nunito Sans'; color: #B0A493;">9:05</div>
                        </div>
                        <div style="font: 600 12px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">And what are the fees for 20 hours per week?</div>
                    </div>
                    <span style="background: #1FA855; color: white; border-radius: 9px; padding: 1px 7px; font: 800 10.5px 'Nunito Sans'; flex-shrink: 0;">2</span>
                </div>
                
                <!-- Chat 2 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #24619C; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">FA</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; gap: 6px; align-items: baseline;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Fatima Al Shamsi</div>
                            <div style="font: 600 10.5px 'Nunito Sans'; color: #B0A493;">Yesterday</div>
                        </div>
                        <div style="font: 600 12px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sunday works for us. Shukran!</div>
                    </div>
                </div>
                
                <!-- Chat 3 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #B97F24; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">RA</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; gap: 6px; align-items: baseline;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Reem Al Falasi</div>
                            <div style="font: 600 10.5px 'Nunito Sans'; color: #B0A493;">Tue</div>
                        </div>
                        <div style="font: 600 12px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Wonderful to hear! Ms. Fatima will share his weekly progress note every Thursday, and parent training starts next Saturday.</div>
                    </div>
                </div>
                
                <!-- Chat 4 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #6E4FA8; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">JO</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; gap: 6px; align-items: baseline;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Jennifer Okoro</div>
                            <div style="font: 600 10.5px 'Nunito Sans'; color: #B0A493;">Mon</div>
                        </div>
                        <div style="font: 600 12px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Great! Any update on the Thiqa pre-authorization?</div>
                    </div>
                    <span style="background: #1FA855; color: white; border-radius: 9px; padding: 1px 7px; font: 800 10.5px 'Nunito Sans'; flex-shrink: 0;">1</span>
                </div>
            </div>
        </div>
        
        <!-- Middle - Chat Messages -->
        <div style="flex: 1; display: flex; flex-direction: column; min-width: 380px; background: #F1EBE1;">
            <!-- Chat Header -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 13px 20px; border-bottom: 1px solid #E4DCCE; background: #FFFDFA;">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: #C8355F; color: white; display: flex; align-items: center; justify-content: center; font: 600 14px 'Baloo 2'; flex-shrink: 0;">NU</div>
                <div style="flex: 1;">
                    <div style="font: 800 14.5px 'Nunito Sans'; color: #2B3A4C;">Noora (Umm Rashid)</div>
                    <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">+971 50 123 4567 · Instagram → WhatsApp</div>
                </div>
                <button style="background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 9px; padding: 8px 14px; font: 800 12px 'Nunito Sans'; cursor: pointer;">Book assessment</button>
            </div>
            
            <!-- Messages -->
            <div style="flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 10px;">
                <!-- Message 1 - Received -->
                <div style="display: flex; justify-content: flex-start;">
                    <div style="max-width: 62%; background: #FFFFFF; border-radius: 14px 14px 14px 4px; padding: 10px 14px; font: 600 13.5px/1.5 'Nunito Sans'; color: #2B3A4C; box-shadow: 0 1px 2px rgba(43,58,76,0.07);">
                        Hello, I saw your center on Instagram. My son Rashid is 5 and was recently diagnosed with autism. Do you offer ABA therapy?
                        <span style="font: 600 10px 'Nunito Sans'; color: #9AA79B; margin-left: 8px; white-space: nowrap;">8:42</span>
                    </div>
                </div>
                
                <!-- Message 2 - Sent -->
                <div style="display: flex; justify-content: flex-end;">
                    <div style="max-width: 62%; background: #DDF3E0; border-radius: 14px 14px 4px 14px; padding: 10px 14px; font: 600 13.5px/1.5 'Nunito Sans'; color: #2B3A4C; box-shadow: 0 1px 2px rgba(43,58,76,0.07);">
                        Good morning Noora! Yes — we run 1:1 ABA programs from 10 to 30 hours per week, supervised by BCBAs. Would you like a free 20-minute consultation this week?
                        <span style="font: 600 10px 'Nunito Sans'; color: #9AA79B; margin-left: 8px; white-space: nowrap;">8:50</span>
                    </div>
                </div>
                
                <!-- Message 3 - Received -->
                <div style="display: flex; justify-content: flex-start;">
                    <div style="max-width: 62%; background: #FFFFFF; border-radius: 14px 14px 14px 4px; padding: 10px 14px; font: 600 13.5px/1.5 'Nunito Sans'; color: #2B3A4C; box-shadow: 0 1px 2px rgba(43,58,76,0.07);">
                        Yes please. Also, do you accept Daman insurance?
                        <span style="font: 600 10px 'Nunito Sans'; color: #9AA79B; margin-left: 8px; white-space: nowrap;">9:04</span>
                    </div>
                </div>
                
                <!-- Message 4 - Received -->
                <div style="display: flex; justify-content: flex-start;">
                    <div style="max-width: 62%; background: #FFFFFF; border-radius: 14px 14px 14px 4px; padding: 10px 14px; font: 600 13.5px/1.5 'Nunito Sans'; color: #2B3A4C; box-shadow: 0 1px 2px rgba(43,58,76,0.07);">
                        And what are the fees for 20 hours per week?
                        <span style="font: 600 10px 'Nunito Sans'; color: #9AA79B; margin-left: 8px; white-space: nowrap;">9:05</span>
                    </div>
                </div>
            </div>
            
            <!-- Message Input -->
            <div style="display: flex; gap: 10px; padding: 14px 20px; background: #FFFDFA; border-top: 1px solid #E4DCCE;">
                <input 
                    type="text" 
                    placeholder="Type a reply…" 
                    style="flex: 1; padding: 11px 16px; border: 1px solid #E2DACE; border-radius: 22px; background: #F6F3EE; font: 600 13.5px 'Nunito Sans'; color: #2B3A4C; outline: none;"
                >
                <button style="background: #1FA855; color: white; border: none; border-radius: 22px; padding: 0 22px; font: 800 13px 'Nunito Sans'; cursor: pointer;">Send</button>
            </div>
        </div>
        
        <!-- Right Sidebar - Family Details -->
        <div style="width: 290px; border-left: 1px solid #EBE4DA; background: #FFFDFA; padding: 20px; display: flex; flex-direction: column; gap: 16px; flex-shrink: 0; overflow-y: auto;">
            <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Family details</div>
            <div style="display: flex; flex-direction: column; gap: 9px;">
                <div>
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Child</div>
                    <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Rashid · 5 yrs</div>
                </div>
                <div>
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Interested in</div>
                    <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">ABA 20h/week</div>
                </div>
                <div>
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Source</div>
                    <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Instagram → WhatsApp</div>
                </div>
                <div>
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">First contact</div>
                    <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Today, 8:42</div>
                </div>
                <div>
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Insurance mentioned</div>
                    <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Daman</div>
                </div>
            </div>
            
            <!-- Convert to Lead Button -->
            <button style="background: #C8355F; color: white; border: none; border-radius: 10px; padding: 12px; font: 800 13px 'Nunito Sans'; cursor: pointer;">Convert to lead</button>
            
            <!-- Auto-capture Info -->
            <div style="background: #F3EDE3; border-radius: 10px; padding: 12px 14px; font: 600 12px/1.5 'Nunito Sans'; color: #5A6B7E;">
                Auto-capture is on: new WhatsApp numbers create a lead card in "New" with the first message attached.
            </div>
        </div>
        
    </div>
@endsection