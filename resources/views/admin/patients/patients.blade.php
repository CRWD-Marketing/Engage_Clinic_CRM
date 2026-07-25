@extends('admin.layouts.admin-sidebar')

@section('title', 'Patients · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- Patients -->
    <div style="flex: 1; display: flex; min-height: 0; overflow-x: auto; margin: -22px -28px 0 -28px;">
        
        <!-- Left Sidebar - Patient List -->
        <div style="width: 330px; border-right: 1px solid #EBE4DA; background: #FFFDFA; display: flex; flex-direction: column; flex-shrink: 0;">
            <div style="padding: 16px 18px; border-bottom: 1px solid #EBE4DA;">
                <div style="font: 600 18px 'Baloo 2'; color: #16436E;">Patients</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A;">48 active · 5 shown</div>
            </div>
            <div style="flex: 1; overflow-y: auto;">
                <!-- Patient 1 - Active -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 13px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: #F5EFE7;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #C8355F; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">KA</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Khalifa Al Mansoori</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Age 6 · ASD Level 2</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">ABA 20h</span>
                </div>
                
                <!-- Patient 2 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 13px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #24619C; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">SA</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Sara Al Hammadi</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Age 4 · Speech &amp; language delay</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Speech 3h</span>
                </div>
                
                <!-- Patient 3 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 13px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #B97F24; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">OF</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Omar Farooq</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Age 7 · ASD Level 1</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">OT 2h</span>
                </div>
                
                <!-- Patient 4 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 13px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #6E4FA8; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">LH</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Layla Hassan</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Age 5 · ASD Level 2</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">ABA 15h</span>
                </div>
                
                <!-- Patient 5 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 13px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #2E7D5B; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">ZA</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">Zayed Al Nuaimi</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">Age 3 · Early intervention (EIP)</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">EIP 10h</span>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Patient Details -->
        <div style="flex: 1; overflow-y: auto; padding: 24px 28px; display: flex; flex-direction: column; gap: 18px; min-width: 420px; background: #F6F3EE;">
            <!-- Patient Header -->
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: #C8355F; color: white; display: flex; align-items: center; justify-content: center; font: 600 19px 'Baloo 2'; flex-shrink: 0;">KA</div>
                <div style="flex: 1;">
                    <div style="font: 600 22px/1.2 'Baloo 2'; color: #16436E;">Khalifa Al Mansoori</div>
                    <div style="font: 600 13px 'Nunito Sans'; color: #98897A;">Age 6 · ASD Level 2 · enrolled Jan 2025</div>
                </div>
                <div style="text-align: right;">
                    <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Mr. Saif Al Mansoori</div>
                    <div style="font: 600 12px 'Nunito Sans'; color: #98897A;">+971 50 882 1943</div>
                </div>
            </div>
            
            <!-- Tags -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <span style="background: #F9E7EC; color: #C8355F; border-radius: 8px; padding: 5px 12px; font: 800 12px 'Nunito Sans';">ABA 20h/wk + Speech 2h</span>
                <span style="background: #E7EFF7; color: #24619C; border-radius: 8px; padding: 5px 12px; font: 800 12px 'Nunito Sans';">Daman Enhanced</span>
                <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 8px; padding: 5px 12px; font: 800 12px 'Nunito Sans';">Attendance 95%</span>
            </div>
            
            <!-- Main Grid -->
            <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 18px; align-items: start;">
                <!-- Left Column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <!-- Therapy Goals -->
                    <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                        <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 14px;">Therapy goals — current plan</div>
                        <div style="display: flex; flex-direction: column; gap: 13px;">
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">Functional communication (PECS → verbal)</div>
                                    <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">72%</div>
                                </div>
                                <div style="height: 9px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                    <div style="width: 72%; height: 100%; background: #C8355F; border-radius: 5px;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">Transitions without distress</div>
                                    <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">58%</div>
                                </div>
                                <div style="height: 9px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                    <div style="width: 58%; height: 100%; background: #C8355F; border-radius: 5px;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">Toilet training routine</div>
                                    <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">40%</div>
                                </div>
                                <div style="height: 9px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                    <div style="width: 40%; height: 100%; background: #C8355F; border-radius: 5px;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">2-step peer play</div>
                                    <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">65%</div>
                                </div>
                                <div style="height: 9px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                    <div style="width: 65%; height: 100%; background: #C8355F; border-radius: 5px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Latest Session Note -->
                    <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                        <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 10px;">Latest session note</div>
                        <div style="font: 600 13px/1.6 'Nunito Sans'; color: #5A6B7E;">Khalifa independently requested a break using his card twice today — first time without prompting. Tantrum duration during transitions down to under 2 minutes. Recommend introducing the classroom-transition routine with Dad at home this week.</div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <!-- Insurance Authorization -->
                    <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                        <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 10px;">Insurance authorization</div>
                        <div style="font: 600 28px 'Baloo 2'; color: #16436E;">34 <span style="font: 600 14px 'Baloo 2'; color: #98897A;">sessions left of 96</span></div>
                        <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden; margin: 10px 0 8px;">
                            <div style="width: 65%; height: 100%; background: #B97F24; border-radius: 5px;"></div>
                        </div>
                        <div style="font: 600 12px 'Nunito Sans'; color: #98897A;">Daman Enhanced · renews 30 Sep 2026</div>
                    </div>
                    
                    <!-- Care Team -->
                    <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                        <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 10px;">Care team</div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div style="font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; background: #F6F3EE; border-radius: 8px; padding: 8px 12px;">Dr. Noura Al Ali — BCBA Supervisor</div>
                            <div style="font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; background: #F6F3EE; border-radius: 8px; padding: 8px 12px;">Ms. Fatima Zahra — RBT</div>
                            <div style="font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; background: #F6F3EE; border-radius: 8px; padding: 8px 12px;">Ms. Priya Nair — Speech</div>
                        </div>
                    </div>
                    
                    <!-- Upcoming Sessions -->
                    <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                        <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 10px;">Upcoming sessions</div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div style="width: 70px; font: 600 12.5px 'Baloo 2'; color: #16436E;">Today 9:00</div>
                                <span style="background: #F9E7EC; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">ABA</span>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div style="width: 70px; font: 600 12.5px 'Baloo 2'; color: #16436E;">Thu 9:00</div>
                                <span style="background: #F9E7EC; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">ABA</span>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div style="width: 70px; font: 600 12.5px 'Baloo 2'; color: #16436E;">Thu 14:00</div>
                                <span style="background: #E7EFF7; color: #24619C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Speech</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
@endsection