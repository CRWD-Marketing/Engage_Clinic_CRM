@extends('admin.layouts.admin-sidebar')

@section('title', 'Therapists · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- Therapists & Schedules -->
    <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px;">
        
        <!-- Top Bar -->
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; flex-shrink: 0;">
            <div style="flex: 1; min-width: 0;">
                <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Therapists &amp; schedules</div>
                <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">Weekly recurring schedule · add, modify or close sessions</div>
            </div>
            <button style="background: #C8355F; color: white; border: none; border-radius: 10px; padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer; flex-shrink: 0;">+ Add session</button>
        </div>
        
        <!-- Main Content -->
        <div style="flex: 1; display: flex; min-height: 0; overflow: hidden;">
            
            <!-- Left Sidebar - Therapist List -->
            <div style="width: 280px; min-width: 200px; border-right: 1px solid #EBE4DA; background: #FFFDFA; display: flex; flex-direction: column; flex-shrink: 0; overflow-y: auto;">
                <div style="padding: 16px 18px; border-bottom: 1px solid #EBE4DA; flex-shrink: 0;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Therapists</div>
                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">5 active</div>
                </div>
                
                <!-- Therapist 1 - Active -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: #F5EFE7;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #C8355F; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">DN</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Dr. Noura Al Ali</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">BCBA Supervisor</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 8px; padding: 2px 8px; font: 800 10px 'Nunito Sans'; white-space: nowrap; flex-shrink: 0;">6</span>
                </div>
                
                <!-- Therapist 2 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #24619C; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">MF</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Ms. Fatima Zahra</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">RBT</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 8px; padding: 2px 8px; font: 800 10px 'Nunito Sans'; white-space: nowrap; flex-shrink: 0;">6</span>
                </div>
                
                <!-- Therapist 3 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #6E4FA8; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">MH</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Ms. Hanan Youssef</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">RBT</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 8px; padding: 2px 8px; font: 800 10px 'Nunito Sans'; white-space: nowrap; flex-shrink: 0;">4</span>
                </div>
                
                <!-- Therapist 4 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #B97F24; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">MP</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Ms. Priya Nair</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Speech-Language Pathologist</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 8px; padding: 2px 8px; font: 800 10px 'Nunito Sans'; white-space: nowrap; flex-shrink: 0;">5</span>
                </div>
                
                <!-- Therapist 5 -->
                <div style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: transparent;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #2E7D5B; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">MJ</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Mr. James Okafor</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Occupational Therapist</div>
                    </div>
                    <span style="background: #F3EDE3; color: #8A7D6C; border-radius: 8px; padding: 2px 8px; font: 800 10px 'Nunito Sans'; white-space: nowrap; flex-shrink: 0;">4</span>
                </div>
            </div>
            
            <!-- Right Side - Weekly Schedule -->
            <div style="flex: 1; overflow: auto; padding: 20px 24px; display: flex; gap: 14px; align-items: flex-start; min-width: 0;">
                
                <!-- Monday -->
                <div style="width: 196px; min-width: 150px; flex-shrink: 0;">
                    <div style="background: #F0EBE5; border-radius: 10px; padding: 8px 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <div style="font: 600 13.5px 'Baloo 2'; color: #16436E; flex: 1;">Mon</div>
                        <span style="font: 800 11.5px 'Nunito Sans'; color: #8A7D6C;">2</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="background: #F9E7EC; border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #C8355F;">09:00–10:30</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">90 min</div>
                            </div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Khalifa Al Mansoori</div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="background: #FFFDFA; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans';">ABA</span>
                            </div>
                            <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Room 1</div>
                            <div style="display: flex; gap: 6px; border-top: 1px solid rgba(43,58,76,0.08); padding-top: 7px; margin-top: 2px;">
                                <button style="flex: 1; background: #FFFDFA; border: 1px solid #E2DACE; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                                <button style="flex: 1; background: #F9E4E2; border: none; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #B3261E; cursor: pointer;">Close</button>
                            </div>
                        </div>
                        <div style="background: #EDEFF1; border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #5A6B7E;">14:00–15:00</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">60 min</div>
                            </div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">RBT team</div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="background: #FFFDFA; color: #5A6B7E; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans';">Supervision</span>
                            </div>
                            <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Room 1</div>
                            <div style="display: flex; gap: 6px; border-top: 1px solid rgba(43,58,76,0.08); padding-top: 7px; margin-top: 2px;">
                                <button style="flex: 1; background: #FFFDFA; border: 1px solid #E2DACE; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                                <button style="flex: 1; background: #F9E4E2; border: none; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #B3261E; cursor: pointer;">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tuesday -->
                <div style="width: 196px; min-width: 150px; flex-shrink: 0;">
                    <div style="background: #F0EBE5; border-radius: 10px; padding: 8px 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <div style="font: 600 13.5px 'Baloo 2'; color: #16436E; flex: 1;">Tue</div>
                        <span style="font: 800 11.5px 'Nunito Sans'; color: #8A7D6C;">1</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="background: #EEE9F7; border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #6E4FA8;">10:00–12:00</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">120 min</div>
                            </div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">New assessments</div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="background: #FFFDFA; color: #6E4FA8; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans';">Assessment</span>
                            </div>
                            <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Assessment suite</div>
                            <div style="display: flex; gap: 6px; border-top: 1px solid rgba(43,58,76,0.08); padding-top: 7px; margin-top: 2px;">
                                <button style="flex: 1; background: #FFFDFA; border: 1px solid #E2DACE; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                                <button style="flex: 1; background: #F9E4E2; border: none; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #B3261E; cursor: pointer;">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Wednesday -->
                <div style="width: 196px; min-width: 150px; flex-shrink: 0;">
                    <div style="background: #F0EBE5; border-radius: 10px; padding: 8px 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <div style="font: 600 13.5px 'Baloo 2'; color: #16436E; flex: 1;">Wed</div>
                        <span style="font: 800 11.5px 'Nunito Sans'; color: #8A7D6C;">2</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="background: #F9E7EC; border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #C8355F;">09:00–10:30</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">90 min</div>
                            </div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Khalifa Al Mansoori</div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="background: #FFFDFA; color: #C8355F; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans';">ABA</span>
                            </div>
                            <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Room 1</div>
                            <div style="display: flex; gap: 6px; border-top: 1px solid rgba(43,58,76,0.08); padding-top: 7px; margin-top: 2px;">
                                <button style="flex: 1; background: #FFFDFA; border: 1px solid #E2DACE; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                                <button style="flex: 1; background: #F9E4E2; border: none; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #B3261E; cursor: pointer;">Close</button>
                            </div>
                        </div>
                        <div style="background: #E3F1E9; border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #2E7D5B;">16:00–16:45</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">45 min</div>
                            </div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Noora (Umm Rashid)</div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="background: #FFFDFA; color: #2E7D5B; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans';">Parent training</span>
                            </div>
                            <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Meeting room</div>
                            <div style="display: flex; gap: 6px; border-top: 1px solid rgba(43,58,76,0.08); padding-top: 7px; margin-top: 2px;">
                                <button style="flex: 1; background: #FFFDFA; border: 1px solid #E2DACE; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                                <button style="flex: 1; background: #F9E4E2; border: none; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #B3261E; cursor: pointer;">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Thursday -->
                <div style="width: 196px; min-width: 150px; flex-shrink: 0;">
                    <div style="background: #F0EBE5; border-radius: 10px; padding: 8px 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <div style="font: 600 13.5px 'Baloo 2'; color: #16436E; flex: 1;">Thu</div>
                        <span style="font: 800 11.5px 'Nunito Sans'; color: #8A7D6C;">1</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="background: #EDEFF1; border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #5A6B7E;">11:00–13:00</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">120 min</div>
                            </div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Program reviews</div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="background: #FFFDFA; color: #5A6B7E; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans';">Supervision</span>
                            </div>
                            <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Room 1</div>
                            <div style="display: flex; gap: 6px; border-top: 1px solid rgba(43,58,76,0.08); padding-top: 7px; margin-top: 2px;">
                                <button style="flex: 1; background: #FFFDFA; border: 1px solid #E2DACE; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                                <button style="flex: 1; background: #F9E4E2; border: none; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #B3261E; cursor: pointer;">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Friday -->
                <div style="width: 196px; min-width: 150px; flex-shrink: 0;">
                    <div style="background: #F0EBE5; border-radius: 10px; padding: 8px 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <div style="font: 600 13.5px 'Baloo 2'; color: #16436E; flex: 1;">Fri</div>
                        <span style="font: 800 11.5px 'Nunito Sans'; color: #8A7D6C;">1</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="background: #F3F0EA; border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px; opacity: 0.75;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #98897A;">09:00–10:00</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">60 min</div>
                            </div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; text-decoration: line-through; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Layla Hassan</div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="background: #EAE4DA; color: #98897A; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans';">ABA</span>
                                <span style="background: #F9E4E2; color: #B3261E; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans';">Closed</span>
                            </div>
                            <div style="font: 600 11px 'Nunito Sans'; color: #98897A;">Room 2</div>
                            <div style="display: flex; gap: 6px; border-top: 1px solid rgba(43,58,76,0.08); padding-top: 7px; margin-top: 2px;">
                                <button style="flex: 1; background: #FFFDFA; border: 1px solid #E2DACE; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                                <button style="flex: 1; background: #E3F1E9; border: none; border-radius: 7px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #2E7D5B; cursor: pointer;">Reopen</button>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
@endsection