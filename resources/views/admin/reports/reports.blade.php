@extends('admin.layouts.admin-sidebar')

@section('title', 'Reports · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- Reports & Analytics -->
    <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px;">
        
        <!-- Top Bar -->
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA;">
            <div style="flex: 1;">
                <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Reports &amp; analytics</div>
                <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">January – July 2026</div>
            </div>
            <button style="background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 10px; padding: 10px 16px; font: 800 13px 'Nunito Sans'; cursor: pointer;">Export PDF</button>
        </div>
        
        <!-- Main Content - Grid -->
        <div style="flex: 1; overflow-y: auto; padding: 22px 28px; display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-content: start;">
            
            <!-- Revenue by Month Chart -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Revenue by month</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">AED thousands</div>
                <div style="display: flex; gap: 14px; align-items: flex-end; height: 150px;">
                    <!-- Jan -->
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #98897A;">402</div>
                        <div style="width: 100%; height: 96px; background: #EBD3DB; border-radius: 7px 7px 3px 3px;"></div>
                        <div style="font: 700 11px 'Nunito Sans'; color: #8A7D6C;">Jan</div>
                    </div>
                    <!-- Feb -->
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #98897A;">418</div>
                        <div style="width: 100%; height: 100px; background: #EBD3DB; border-radius: 7px 7px 3px 3px;"></div>
                        <div style="font: 700 11px 'Nunito Sans'; color: #8A7D6C;">Feb</div>
                    </div>
                    <!-- Mar -->
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #98897A;">445</div>
                        <div style="width: 100%; height: 107px; background: #EBD3DB; border-radius: 7px 7px 3px 3px;"></div>
                        <div style="font: 700 11px 'Nunito Sans'; color: #8A7D6C;">Mar</div>
                    </div>
                    <!-- Apr -->
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #98897A;">431</div>
                        <div style="width: 100%; height: 103px; background: #EBD3DB; border-radius: 7px 7px 3px 3px;"></div>
                        <div style="font: 700 11px 'Nunito Sans'; color: #8A7D6C;">Apr</div>
                    </div>
                    <!-- May -->
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #98897A;">468</div>
                        <div style="width: 100%; height: 112px; background: #EBD3DB; border-radius: 7px 7px 3px 3px;"></div>
                        <div style="font: 700 11px 'Nunito Sans'; color: #8A7D6C;">May</div>
                    </div>
                    <!-- Jun -->
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #98897A;">502</div>
                        <div style="width: 100%; height: 120px; background: #C8355F; border-radius: 7px 7px 3px 3px;"></div>
                        <div style="font: 700 11px 'Nunito Sans'; color: #8A7D6C;">Jun</div>
                    </div>
                    <!-- Jul -->
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end;">
                        <div style="font: 800 11px 'Nunito Sans'; color: #98897A;">486</div>
                        <div style="width: 100%; height: 116px; background: #F0B9C9; border-radius: 7px 7px 3px 3px;"></div>
                        <div style="font: 700 11px 'Nunito Sans'; color: #8A7D6C;">Jul*</div>
                    </div>
                </div>
            </div>
            
            <!-- Lead Conversion Funnel -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Lead conversion funnel</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">Last 90 days · 26% lead → enrolled</div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 130px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">Leads captured</div>
                        <div style="flex: 1;">
                            <div style="width: 100%; height: 26px; background: #16436E; border-radius: 7px; display: flex; align-items: center; justify-content: flex-end; padding: 0 10px; font: 800 12px 'Nunito Sans'; color: #FFFFFF;">58</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 130px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">Contacted</div>
                        <div style="flex: 1;">
                            <div style="width: 71%; height: 26px; background: #3A6A96; border-radius: 7px; display: flex; align-items: center; justify-content: flex-end; padding: 0 10px; font: 800 12px 'Nunito Sans'; color: #FFFFFF;">41</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 130px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">Assessment done</div>
                        <div style="flex: 1;">
                            <div style="width: 41%; height: 26px; background: #7396B8; border-radius: 7px; display: flex; align-items: center; justify-content: flex-end; padding: 0 10px; font: 800 12px 'Nunito Sans'; color: #FFFFFF;">24</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 130px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">Enrolled</div>
                        <div style="flex: 1;">
                            <div style="width: 26%; height: 26px; background: #C8355F; border-radius: 7px; display: flex; align-items: center; justify-content: flex-end; padding: 0 10px; font: 800 12px 'Nunito Sans'; color: #FFFFFF;">15</div>
                        </div>
                    </div>
                </div>
                <div style="font: 600 12px/1.5 'Nunito Sans'; color: #98897A; margin-top: 14px;">
                    WhatsApp leads convert best (34%) — Instagram lowest (14%). Median time from first message to enrollment: 19 days.
                </div>
            </div>
            
            <!-- Lead Sources Pie Chart -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px; display: flex; gap: 22px; align-items: center;">
                <div style="width: 140px; height: 140px; border-radius: 50%; background: conic-gradient(#1FA855 0deg, #1FA855 38%, #C13584 38%, #C13584 60%, #24619C 60%, #24619C 78%, #B97F24 78%, #B97F24 92%, #6E4FA8 92%, #6E4FA8 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <div style="width: 84px; height: 84px; border-radius: 50%; background: #FFFFFF; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <div style="font: 600 20px 'Baloo 2'; color: #16436E;">58</div>
                        <div style="font: 700 10px 'Nunito Sans'; color: #98897A;">LEADS · 90d</div>
                    </div>
                </div>
                <div style="flex: 1;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 10px;">Lead sources</div>
                    <div style="display: flex; flex-direction: column; gap: 7px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; border-radius: 3px; background: #1FA855;"></span>
                            <span style="flex: 1;">WhatsApp</span>
                            <span style="color: #16436E;">38%</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; border-radius: 3px; background: #C13584;"></span>
                            <span style="flex: 1;">Instagram</span>
                            <span style="color: #16436E;">22%</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; border-radius: 3px; background: #24619C;"></span>
                            <span style="flex: 1;">Website</span>
                            <span style="color: #16436E;">18%</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; border-radius: 3px; background: #B97F24;"></span>
                            <span style="flex: 1;">Referral</span>
                            <span style="color: #16436E;">14%</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; border-radius: 3px; background: #6E4FA8;"></span>
                            <span style="flex: 1;">Google Ads</span>
                            <span style="color: #16436E;">8%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Therapy Hours Delivered -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Therapy hours delivered · June</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">1,000 clinical hours</div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">ABA (1:1)</div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">640h</div>
                        </div>
                        <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                            <div style="width: 64%; height: 100%; background: #C8355F; border-radius: 5px;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">Speech therapy</div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">180h</div>
                        </div>
                        <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                            <div style="width: 18%; height: 100%; background: #24619C; border-radius: 5px;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">Occupational therapy</div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">120h</div>
                        </div>
                        <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                            <div style="width: 12%; height: 100%; background: #B97F24; border-radius: 5px;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">Assessments</div>
                            <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">60h</div>
                        </div>
                        <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                            <div style="width: 6%; height: 100%; background: #6E4FA8; border-radius: 5px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection