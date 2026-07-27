@extends('layouts.admin-sidebar')

@section('title', 'Calendar · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- Calendar -->
    <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px;">
        
        <!-- Top Bar -->
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA;">
            <div style="flex: 1;">
                <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Calendar — <span>Wed</span></div>
                <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">Drag a card to another therapist, or use "Move to" · click Edit to modify</div>
            </div>
            <select style="padding: 9px 12px; border: 1px solid #E2DACE; border-radius: 9px; background: #FFFFFF; font: 700 12.5px 'Nunito Sans'; color: #16436E; outline: none;">
                <option>Mon</option>
                <option>Tue</option>
                <option selected>Wed</option>
                <option>Thu</option>
                <option>Fri</option>
            </select>
            <div style="display: flex; gap: 14px; align-items: center; font: 700 12px 'Nunito Sans'; color: #5A6B7E;">
                <span style="display: flex; gap: 6px; align-items: center;">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #C8355F;"></span>ABA
                </span>
                <span style="display: flex; gap: 6px; align-items: center;">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #24619C;"></span>Speech
                </span>
                <span style="display: flex; gap: 6px; align-items: center;">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #B97F24;"></span>OT
                </span>
                <span style="display: flex; gap: 6px; align-items: center;">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #6E4FA8;"></span>Assessment
                </span>
            </div>
            <button style="background: #C8355F; color: white; border: none; border-radius: 10px; padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer;">+ Book session</button>
        </div>
        
        <!-- Calendar Grid -->
        <div style="flex: 1; display: flex; min-height: 0;">
            <div style="flex: 1; overflow: auto; padding: 20px 24px; display: flex; gap: 14px; align-items: flex-start;">
                
                <!-- Dr. Noura Al Ali -->
                <div style="width: 224px; flex-shrink: 0; background: #F6F3EE; border-radius: 14px; padding: 8px;">
                    <div style="background: #16436E; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px;">
                        <div style="font: 600 14px 'Baloo 2'; color: #FFFFFF;">Dr. Noura Al Ali</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #9FB6CC;">BCBA Supervisor · 2 sessions</div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- Session 1 -->
                        <div draggable="true" style="background: #F9E7EC; border-radius: 11px; padding: 10px 13px; display: flex; flex-direction: column; gap: 6px; cursor: grab;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #C8355F;">09:00–10:30</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">90 min</div>
                            </div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Khalifa Al Mansoori</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #8A7D6C;">ABA · Room 1</div>
                            <select style="width: 100%; padding: 5px 6px; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; background: #FFFFFF; font: 700 11px 'Nunito Sans'; color: #16436E; outline: none;">
                                <option value="1" selected>Dr. Noura Al Ali</option>
                                <option value="2">Ms. Fatima Zahra</option>
                                <option value="3">Ms. Hanan Youssef</option>
                                <option value="4">Ms. Priya Nair</option>
                                <option value="5">Mr. James Okafor</option>
                            </select>
                            <button style="background: #FFFFFF; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 4px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                        </div>
                        
                        <!-- Session 2 -->
                        <div draggable="true" style="background: #E3F1E9; border-radius: 11px; padding: 10px 13px; display: flex; flex-direction: column; gap: 6px; cursor: grab;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #2E7D5B;">16:00–16:45</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">45 min</div>
                            </div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Noora (Umm Rashid)</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #8A7D6C;">Parent training · Meeting room</div>
                            <select style="width: 100%; padding: 5px 6px; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; background: #FFFFFF; font: 700 11px 'Nunito Sans'; color: #16436E; outline: none;">
                                <option value="1" selected>Dr. Noura Al Ali</option>
                                <option value="2">Ms. Fatima Zahra</option>
                                <option value="3">Ms. Hanan Youssef</option>
                                <option value="4">Ms. Priya Nair</option>
                                <option value="5">Mr. James Okafor</option>
                            </select>
                            <button style="background: #FFFFFF; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 4px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                        </div>
                    </div>
                </div>
                
                <!-- Ms. Fatima Zahra -->
                <div style="width: 224px; flex-shrink: 0; background: #F6F3EE; border-radius: 14px; padding: 8px;">
                    <div style="background: #16436E; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px;">
                        <div style="font: 600 14px 'Baloo 2'; color: #FFFFFF;">Ms. Fatima Zahra</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #9FB6CC;">RBT · 1 sessions</div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div draggable="true" style="background: #F9E7EC; border-radius: 11px; padding: 10px 13px; display: flex; flex-direction: column; gap: 6px; cursor: grab;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #C8355F;">13:30–15:30</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">120 min</div>
                            </div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Khalifa Al Mansoori</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #8A7D6C;">ABA · Room 1</div>
                            <select style="width: 100%; padding: 5px 6px; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; background: #FFFFFF; font: 700 11px 'Nunito Sans'; color: #16436E; outline: none;">
                                <option value="1">Dr. Noura Al Ali</option>
                                <option value="2" selected>Ms. Fatima Zahra</option>
                                <option value="3">Ms. Hanan Youssef</option>
                                <option value="4">Ms. Priya Nair</option>
                                <option value="5">Mr. James Okafor</option>
                            </select>
                            <button style="background: #FFFFFF; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 4px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                        </div>
                    </div>
                </div>
                
                <!-- Ms. Hanan Youssef -->
                <div style="width: 224px; flex-shrink: 0; background: #F6F3EE; border-radius: 14px; padding: 8px;">
                    <div style="background: #16436E; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px;">
                        <div style="font: 600 14px 'Baloo 2'; color: #FFFFFF;">Ms. Hanan Youssef</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #9FB6CC;">RBT · 1 sessions</div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div draggable="true" style="background: #F9E7EC; border-radius: 11px; padding: 10px 13px; display: flex; flex-direction: column; gap: 6px; cursor: grab;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #C8355F;">14:30–16:30</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">120 min</div>
                            </div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Layla Hassan</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #8A7D6C;">ABA · Room 2</div>
                            <select style="width: 100%; padding: 5px 6px; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; background: #FFFFFF; font: 700 11px 'Nunito Sans'; color: #16436E; outline: none;">
                                <option value="1">Dr. Noura Al Ali</option>
                                <option value="2">Ms. Fatima Zahra</option>
                                <option value="3" selected>Ms. Hanan Youssef</option>
                                <option value="4">Ms. Priya Nair</option>
                                <option value="5">Mr. James Okafor</option>
                            </select>
                            <button style="background: #FFFFFF; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 4px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                        </div>
                    </div>
                </div>
                
                <!-- Ms. Priya Nair -->
                <div style="width: 224px; flex-shrink: 0; background: #F6F3EE; border-radius: 14px; padding: 8px;">
                    <div style="background: #16436E; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px;">
                        <div style="font: 600 14px 'Baloo 2'; color: #FFFFFF;">Ms. Priya Nair</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #9FB6CC;">Speech-Language Pathologist · 1 sessions</div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div draggable="true" style="background: #E7EFF7; border-radius: 11px; padding: 10px 13px; display: flex; flex-direction: column; gap: 6px; cursor: grab;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #24619C;">09:30–10:15</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">45 min</div>
                            </div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Sara Al Hammadi</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #8A7D6C;">Speech · Room 3</div>
                            <select style="width: 100%; padding: 5px 6px; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; background: #FFFFFF; font: 700 11px 'Nunito Sans'; color: #16436E; outline: none;">
                                <option value="1">Dr. Noura Al Ali</option>
                                <option value="2">Ms. Fatima Zahra</option>
                                <option value="3">Ms. Hanan Youssef</option>
                                <option value="4" selected>Ms. Priya Nair</option>
                                <option value="5">Mr. James Okafor</option>
                            </select>
                            <button style="background: #FFFFFF; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 4px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                        </div>
                    </div>
                </div>
                
                <!-- Mr. James Okafor -->
                <div style="width: 224px; flex-shrink: 0; background: #F6F3EE; border-radius: 14px; padding: 8px;">
                    <div style="background: #16436E; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px;">
                        <div style="font: 600 14px 'Baloo 2'; color: #FFFFFF;">Mr. James Okafor</div>
                        <div style="font: 600 11px 'Nunito Sans'; color: #9FB6CC;">Occupational Therapist · 1 sessions</div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div draggable="true" style="background: #F7EEDD; border-radius: 11px; padding: 10px 13px; display: flex; flex-direction: column; gap: 6px; cursor: grab;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <div style="font: 600 13px 'Baloo 2'; color: #B97F24;">15:00–16:00</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A;">60 min</div>
                            </div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">Social skills group (4)</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #8A7D6C;">OT · Sensory gym</div>
                            <select style="width: 100%; padding: 5px 6px; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; background: #FFFFFF; font: 700 11px 'Nunito Sans'; color: #16436E; outline: none;">
                                <option value="1">Dr. Noura Al Ali</option>
                                <option value="2">Ms. Fatima Zahra</option>
                                <option value="3">Ms. Hanan Youssef</option>
                                <option value="4">Ms. Priya Nair</option>
                                <option value="5" selected>Mr. James Okafor</option>
                            </select>
                            <button style="background: #FFFFFF; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 4px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
@endsection