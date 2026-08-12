@extends('layouts.coordinator-sidebar')

@section('title', 'Dashboard · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- Top Bar -->
    <div style="display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; margin: -22px -28px 18px -28px;">
        <div style="flex: 1; min-width: 0;">
            <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Good morning, {{ Auth::user()->name }}</div>
            <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">{{ date('l, j F Y') }} · Khalifa City, Abu Dhabi</div>
        </div>
        <input 
            type="text" 
            placeholder="Search families, leads, appointments…" 
            style="width: 260px; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 10px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;"
        >
        <button style="background: #C8355F; color: white; border: none; border-radius: 10px; padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer;">+ New Lead</button>
    </div>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 18px;">
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">New leads · week</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $newLeadsCount ?? '—' }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #2E7D5B;">▲ {{ $newLeadsDelta ?? '—' }}% vs last week</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Sessions today</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $sessionsTodayCount ?? '—' }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #8A7D6C;">{{ $roomsInUseCount ?? '—' }} rooms · {{ $activeTherapistsCount ?? '—' }} therapists</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Intake calls pending</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $pendingIntakeCallsCount ?? '—' }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">{{ $overdueIntakeCallsCount ?? '—' }} overdue</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">No-shows · week</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $noShowsCount ?? '—' }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">{{ $noShowFollowUpsNeeded ?? '—' }} need follow-up</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Waitlist</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $waitlistCount ?? '—' }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">{{ $openingsThisWeek ?? '—' }} openings this week</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Attendance · 30d</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $attendanceRate ?? '—' }}%</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #2E7D5B;">▲ {{ $attendanceDelta ?? '—' }} pts</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div style="display: grid; grid-template-columns: 1.55fr 1fr; gap: 18px; align-items: start;">
        <!-- Left Column -->
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <!-- Today's Schedule -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden;">
                <div style="display: flex; align-items: baseline; gap: 10px; padding: 16px 20px 10px;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">Today's schedule</div>
                    <button style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0;">Open calendar →</button>
                </div>
                <div style="display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                    <div style="width: 48px; font: 600 14px 'Baloo 2'; color: #16436E;">8:00</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Zayed Al Nuaimi</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Ms. Fatima Zahra · Room 2</div>
                    </div>
                    <span style="background: #F9E7EC; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">ABA</span>
                    <span style="background: #F3EDE3; color: #98897A; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Completed</span>
                </div>
                <div style="display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                    <div style="width: 48px; font: 600 14px 'Baloo 2'; color: #16436E;">9:00</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Khalifa Al Mansoori</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Dr. Noura Al Ali · Room 1</div>
                    </div>
                    <span style="background: #F9E7EC; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">ABA</span>
                    <span style="background: #F9E7EC; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">In Session</span>
                </div>
                <div style="display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                    <div style="width: 48px; font: 600 14px 'Baloo 2'; color: #16436E;">9:30</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Sara Al Hammadi</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Ms. Priya Nair · Room 3</div>
                    </div>
                    <span style="background: #E7EFF7; color: #24619C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Speech</span>
                    <span style="background: #F9E7EC; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">In Session</span>
                </div>
                <div style="display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                    <div style="width: 48px; font: 600 14px 'Baloo 2'; color: #16436E;">10:00</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Omar Farooq</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Mr. James Okafor · Sensory gym</div>
                    </div>
                    <span style="background: #F7EEDD; color: #B97F24; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">OT</span>
                    <span style="background: #E3F1E9; color: #2E7D5B; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Checked-in</span>
                </div>
                <div style="display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                    <div style="width: 48px; font: 600 14px 'Baloo 2'; color: #16436E;">10:30</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Layla Hassan</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Ms. Hanan Youssef · Room 2</div>
                    </div>
                    <span style="background: #F9E7EC; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">ABA</span>
                    <span style="background: #EEF0F2; color: #6B7A8C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Upcoming</span>
                </div>
                <div style="display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                    <div style="width: 48px; font: 600 14px 'Baloo 2'; color: #16436E;">11:00</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Mohammed Bin Saeed</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Dr. Noura Al Ali · Assessment suite</div>
                    </div>
                    <span style="background: #EEE9F7; color: #6E4FA8; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Assessment</span>
                    <span style="background: #EEF0F2; color: #6B7A8C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Upcoming</span>
                </div>
                <div style="display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                    <div style="width: 48px; font: 600 14px 'Baloo 2'; color: #16436E;">11:30</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Aisha Rahman</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Ms. Priya Nair · Room 3</div>
                    </div>
                    <span style="background: #E7EFF7; color: #24619C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Speech</span>
                    <span style="background: #EEF0F2; color: #6B7A8C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Upcoming</span>
                </div>
                <div style="display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                    <div style="width: 48px; font: 600 14px 'Baloo 2'; color: #16436E;">13:00</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Hamdan Al Ketbi</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Mr. James Okafor · Sensory gym</div>
                    </div>
                    <span style="background: #F7EEDD; color: #B97F24; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">OT</span>
                    <span style="background: #EEF0F2; color: #6B7A8C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Upcoming</span>
                </div>
            </div>

            <!-- Intake Pipeline -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 16px 20px;">
                <div style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">Intake pipeline — awaiting scheduling</div>
                    <button style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0;">View leads →</button>
                </div>
                <div style="display: flex; flex-direction: column; gap: 9px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-top: 1px solid #F3EDE3;">
                        <div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Noora (Umm Rashid)</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Intake form complete · requested ABA</div>
                        </div>
                        <span style="background: #F7EEDD; color: #B97F24; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Awaiting call</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-top: 1px solid #F3EDE3;">
                        <div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Fatima Al Shamsi</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Confirmed Sunday · needs room assignment</div>
                        </div>
                        <span style="background: #E3F1E9; color: #2E7D5B; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Ready to book</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-top: 1px solid #F3EDE3;">
                        <div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Reem Al Falasi</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Parent training starts Saturday · notify Ms. Fatima</div>
                        </div>
                        <span style="background: #F9E3EA; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Follow up</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <!-- WhatsApp Inbox -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden;">
                <div style="display: flex; align-items: center; gap: 8px; padding: 16px 20px 10px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #1FA855;"></span>
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">WhatsApp inbox</div>
                    <button style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0;">Open →</button>
                </div>
                <div style="display: flex; gap: 11px; align-items: center; padding: 11px 20px; border-top: 1px solid #F3EDE3; cursor: pointer;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #C8355F; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">NU</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Noora (Umm Rashid)</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">And what are the fees for 20 hours per week?</div>
                    </div>
                    <span style="background: #1FA855; color: white; border-radius: 9px; padding: 1px 7px; font: 800 10.5px 'Nunito Sans'; flex-shrink: 0;">2</span>
                </div>
                <div style="display: flex; gap: 11px; align-items: center; padding: 11px 20px; border-top: 1px solid #F3EDE3; cursor: pointer;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #24619C; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">FA</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Fatima Al Shamsi</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sunday works for us. Shukran!</div>
                    </div>
                </div>
                <div style="display: flex; gap: 11px; align-items: center; padding: 11px 20px; border-top: 1px solid #F3EDE3; cursor: pointer;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #B97F24; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">RA</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Reem Al Falasi</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Wonderful to hear! Ms. Fatima will share his weekly progress note every Thursday, and parent training starts next Saturday.</div>
                    </div>
                </div>
            </div>

            <!-- No-show Follow-ups -->
            <div style="background: #FBF3E4; border: 1px solid #EBDCBB; border-radius: 14px; padding: 16px 20px;">
                <div style="font: 600 15px 'Baloo 2'; color: #8A5A10; margin-bottom: 8px;">No-shows needing follow-up</div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between; gap: 10px;">
                        <div style="font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">Grace Okoro</div>
                        <div style="font: 600 12px 'Nunito Sans'; color: #8A5A10;">Missed OT · Tue 9:00 · reschedule needed</div>
                    </div>
                    <div style="display: flex; justify-content: space-between; gap: 10px;">
                        <div style="font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">Ethan Menon</div>
                        <div style="font: 600 12px 'Nunito Sans'; color: #8A5A10;">Missed Speech · Mon 10:30 · 2nd no-show</div>
                    </div>
                </div>
            </div>

            <!-- Waitlist -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 16px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 10px;">Waitlist — next up</div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="font: 600 13px 'Baloo 2'; color: #C8355F; width: 20px;">1</div>
                        <div style="flex: 1;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Yousef Al Dhaheri · 6</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">ABA 15h/wk · waiting 2 wks</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="font: 600 13px 'Baloo 2'; color: #C8355F; width: 20px;">2</div>
                        <div style="flex: 1;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Lina Haddad · 4</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Speech 3h/wk · waiting 3 wks</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="font: 600 13px 'Baloo 2'; color: #C8355F; width: 20px;">3</div>
                        <div style="flex: 1;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Ethan Menon · 7</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">OT 2h/wk · waiting 4 wks</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection