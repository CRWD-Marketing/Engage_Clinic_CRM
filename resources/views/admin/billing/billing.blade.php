@extends('admin.layouts.admin-sidebar')

@section('title', 'Billing · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- Billing & Insurance -->
    <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px;">
        
        <!-- Top Bar -->
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA;">
            <div style="flex: 1;">
                <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Billing &amp; insurance</div>
                <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">July 2026 · Daman, Thiqa &amp; ADNIC claims</div>
            </div>
            <button style="background: #C8355F; color: white; border: none; border-radius: 10px; padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer;">+ New invoice</button>
        </div>
        
        <!-- Main Content -->
        <div style="flex: 1; overflow-y: auto; padding: 22px 28px; display: flex; flex-direction: column; gap: 18px;">
            
            <!-- Stats Cards -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Invoiced · MTD</div>
                    <div style="font: 600 26px 'Baloo 2'; color: #16436E;">AED 486,200</div>
                </div>
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Collected</div>
                    <div style="font: 600 26px 'Baloo 2'; color: #2E7D5B;">AED 402,750</div>
                </div>
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Outstanding claims</div>
                    <div style="font: 600 26px 'Baloo 2'; color: #B97F24;">AED 73,450</div>
                </div>
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Avg. claim cycle</div>
                    <div style="font: 600 26px 'Baloo 2'; color: #16436E;">18 days</div>
                </div>
            </div>
            
            <!-- Main Grid -->
            <div style="display: grid; grid-template-columns: 1.7fr 1fr; gap: 18px; align-items: start;">
                
                <!-- Recent Claims Table -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; padding: 16px 20px 8px;">Recent claims</div>
                    <div style="display: grid; grid-template-columns: 1fr 62px 74px 76px; gap: 8px; padding: 8px 20px; font: 700 10px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 1px solid #F3EDE3;">
                        <div>Patient</div>
                        <div>Insurer</div>
                        <div style="text-align: right;">Amount</div>
                        <div>Status</div>
                    </div>
                    
                    <!-- Claim Row 1 -->
                    <div style="display: grid; grid-template-columns: 1fr 62px 74px 76px; gap: 8px; padding: 11px 20px; border-bottom: 1px solid #F3EDE3; align-items: center;">
                        <div style="min-width: 0;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Khalifa Al Mansoori</div>
                            <div style="font: 700 11px 'Nunito Sans'; color: #98897A;">CLM-2090 · Jul 2026</div>
                        </div>
                        <div style="font: 700 11.5px 'Nunito Sans'; color: #5A6B7E;">Daman</div>
                        <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E; text-align: right;">AED 25,600</div>
                        <div>
                            <span style="background: #E7EFF7; color: #24619C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Submitted</span>
                        </div>
                    </div>
                    
                    <!-- Claim Row 2 -->
                    <div style="display: grid; grid-template-columns: 1fr 62px 74px 76px; gap: 8px; padding: 11px 20px; border-bottom: 1px solid #F3EDE3; align-items: center;">
                        <div style="min-width: 0;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Omar Farooq</div>
                            <div style="font: 700 11px 'Nunito Sans'; color: #98897A;">CLM-2088 · Jun 2026</div>
                        </div>
                        <div style="font: 700 11.5px 'Nunito Sans'; color: #5A6B7E;">ADNIC</div>
                        <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E; text-align: right;">AED 3,900</div>
                        <div>
                            <span style="background: #F7EEDD; color: #B97F24; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Pending info</span>
                        </div>
                    </div>
                    
                    <!-- Claim Row 3 -->
                    <div style="display: grid; grid-template-columns: 1fr 62px 74px 76px; gap: 8px; padding: 11px 20px; border-bottom: 1px solid #F3EDE3; align-items: center;">
                        <div style="min-width: 0;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sara Al Hammadi</div>
                            <div style="font: 700 11px 'Nunito Sans'; color: #98897A;">CLM-2086 · Jun 2026</div>
                        </div>
                        <div style="font: 700 11.5px 'Nunito Sans'; color: #5A6B7E;">Thiqa</div>
                        <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E; text-align: right;">AED 4,800</div>
                        <div>
                            <span style="background: #E7EFF7; color: #24619C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Submitted</span>
                        </div>
                    </div>
                    
                    <!-- Claim Row 4 -->
                    <div style="display: grid; grid-template-columns: 1fr 62px 74px 76px; gap: 8px; padding: 11px 20px; border-bottom: 1px solid #F3EDE3; align-items: center;">
                        <div style="min-width: 0;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Khalifa Al Mansoori</div>
                            <div style="font: 700 11px 'Nunito Sans'; color: #98897A;">CLM-2081 · Jun 2026</div>
                        </div>
                        <div style="font: 700 11.5px 'Nunito Sans'; color: #5A6B7E;">Daman</div>
                        <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E; text-align: right;">AED 25,600</div>
                        <div>
                            <span style="background: #E3F1E9; color: #2E7D5B; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Paid</span>
                        </div>
                    </div>
                    
                    <!-- Claim Row 5 -->
                    <div style="display: grid; grid-template-columns: 1fr 62px 74px 76px; gap: 8px; padding: 11px 20px; border-bottom: 1px solid #F3EDE3; align-items: center;">
                        <div style="min-width: 0;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Zayed Al Nuaimi</div>
                            <div style="font: 700 11px 'Nunito Sans'; color: #98897A;">CLM-2079 · Jun 2026</div>
                        </div>
                        <div style="font: 700 11.5px 'Nunito Sans'; color: #5A6B7E;">Daman</div>
                        <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E; text-align: right;">AED 12,800</div>
                        <div>
                            <span style="background: #E3F1E9; color: #2E7D5B; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Paid</span>
                        </div>
                    </div>
                    
                    <!-- Claim Row 6 -->
                    <div style="display: grid; grid-template-columns: 1fr 62px 74px 76px; gap: 8px; padding: 11px 20px; align-items: center;">
                        <div style="min-width: 0;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Grace Okoro</div>
                            <div style="font: 700 11px 'Nunito Sans'; color: #98897A;">CLM-2074 · May 2026</div>
                        </div>
                        <div style="font: 700 11.5px 'Nunito Sans'; color: #5A6B7E;">Thiqa</div>
                        <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E; text-align: right;">AED 8,800</div>
                        <div>
                            <span style="background: #F9E4E2; color: #B3261E; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">Rejected</span>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue by Payer -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 14px;">Revenue by payer · MTD</div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">Daman</div>
                                <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">46%</div>
                            </div>
                            <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                <div style="width: 46%; height: 100%; background: #C8355F; border-radius: 5px;"></div>
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">Thiqa</div>
                                <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">31%</div>
                            </div>
                            <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                <div style="width: 31%; height: 100%; background: #16436E; border-radius: 5px;"></div>
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">Self-pay</div>
                                <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">15%</div>
                            </div>
                            <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                <div style="width: 15%; height: 100%; background: #B97F24; border-radius: 5px;"></div>
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">ADNIC</div>
                                <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">8%</div>
                            </div>
                            <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                <div style="width: 8%; height: 100%; background: #6E4FA8; border-radius: 5px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alert -->
                    <div style="background: #FBF3E4; border: 1px solid #EBDCBB; border-radius: 10px; padding: 10px 14px; margin-top: 16px; font: 600 12px/1.5 'Nunito Sans'; color: #8A5A10;">
                        Grace Okoro's Thiqa claim (CLM-2074) was rejected — resubmit with updated CPT codes before 15 Jul.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection