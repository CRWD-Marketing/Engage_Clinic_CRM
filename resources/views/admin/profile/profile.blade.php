@extends('admin.layouts.admin-sidebar')

@section('title', 'My Profile · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <!-- Full width wrapper - matches dashboard structure -->
    <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px;">
        
         <!-- Top Bar - Using class -->
        <div class="topbar-fixed">
            <div style="flex: 1; min-width: 0;">
                <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">My Profile</div>
                <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">Manage your account settings and preferences</div>
            </div>
            <button onclick="window.location.href='{{ route('admin.dashboard') }}'" style="background: #F6F3EE; color: #2B3A4C; border: 1px solid #E2DACE; border-radius: 10px; padding: 10px 18px; font: 700 13px 'Nunito Sans'; cursor: pointer; white-space: nowrap;">← Back to Dashboard</button>
        </div>

        <!-- Main Content Area with padding -->
        <div style="flex: 1; overflow-y: auto; padding: 22px 28px; background: #F6F3EE;">
            
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div style="background: #d4edda; color: #155724; padding: 12px 18px; border-radius: 10px; font-weight: 600; border-left: 4px solid #28a745; margin-bottom: 18px;">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div style="background: #f8d7da; color: #721c24; padding: 12px 18px; border-radius: 10px; font-weight: 600; border-left: 4px solid #dc3545; margin-bottom: 18px;">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Main Grid -->
            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 18px; align-items: start;">
                
                <!-- Left Column: Profile Information -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 24px 28px;">
                    <h3 style="font: 600 18px 'Baloo 2'; color: #16436E; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #F3EDE3;">Profile Information</h3>

                    <!-- Avatar/Photo -->
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 24px;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: #C8355F; color: white; display: flex; align-items: center; justify-content: center; font: 700 32px 'Baloo 2'; flex-shrink: 0;">
                            {{ $user->first_name ? strtoupper(substr($user->first_name, 0, 1)) . ($user->last_name ? strtoupper(substr($user->last_name, 0, 1)) : '') : 'U' }}
                        </div>
                        <div>
                            <div style="font: 700 18px 'Nunito Sans'; color: #2B3A4C;">{{ $user->full_name }}</div>
                            <div style="font: 600 13px 'Nunito Sans'; color: #98897A;">{{ $user->role ?? 'Staff Member' }}</div>
                            <div style="font: 600 13px 'Nunito Sans'; color: #98897A;">{{ $user->department ?? 'No department' }}</div>
                            <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 4px;">Member since {{ $user->created_at->format('F Y') }}</div>
                        </div>
                    </div>

                    <!-- Update Profile Form -->
                    <form method="POST" action="{{ route('admin.profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                            <div>
                                <label style="font: 700 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;">First Name *</label>
                                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" style="width: 100%; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            </div>
                            <div>
                                <label style="font: 700 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;">Last Name *</label>
                                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" style="width: 100%; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            </div>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="font: 700 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;">Middle Name</label>
                            <input type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}" style="width: 100%; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="font: 700 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;">Email Address *</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" style="width: 100%; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="font: 700 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;">Phone Number</label>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" placeholder="+971 50 123 4567" style="width: 100%; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="font: 700 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;">Notes / Bio</label>
                            <textarea name="notes" rows="3" placeholder="Brief description about yourself..." style="width: 100%; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none; resize: vertical;">{{ old('notes', $user->notes) }}</textarea>
                        </div>

                        <button type="submit" style="width: 100%; background: #C8355F; color: white; border: none; border-radius: 10px; padding: 12px; font: 800 14px 'Nunito Sans'; cursor: pointer; transition: background 0.2s;">
                            Update Profile
                        </button>
                    </form>
                </div>

                <!-- Right Column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    
                    <!-- Change Password -->
                    <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 24px 28px;">
                        <h3 style="font: 600 18px 'Baloo 2'; color: #16436E; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #F3EDE3;">Change Password</h3>

                        <form method="POST" action="{{ route('admin.profile.password') }}">
                            @csrf
                            @method('PUT')

                            <div style="margin-bottom: 16px;">
                                <label style="font: 700 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;">Current Password</label>
                                <input type="password" name="current_password" placeholder="Enter current password" style="width: 100%; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label style="font: 700 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;">New Password</label>
                                <input type="password" name="password" placeholder="Enter new password (min 8 characters)" style="width: 100%; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="font: 700 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;">Confirm New Password</label>
                                <input type="password" name="password_confirmation" placeholder="Confirm new password" style="width: 100%; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;">
                            </div>

                            <button type="submit" style="width: 100%; background: #16436E; color: white; border: none; border-radius: 10px; padding: 12px; font: 800 14px 'Nunito Sans'; cursor: pointer; transition: background 0.2s;">
                                Update Password
                            </button>
                        </form>
                    </div>

                    <!-- Account Information -->
                    <div style="background: #FBF3E4; border: 1px solid #EBDCBB; border-radius: 14px; padding: 20px 24px;">
                        <h4 style="font: 600 15px 'Baloo 2'; color: #8A5A10; margin-bottom: 12px;">Account Information</h4>
                        <div style="font: 600 13px 'Nunito Sans'; color: #2B3A4C; display: flex; flex-direction: column; gap: 6px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #98897A;">Role:</span>
                                <span style="font-weight: 800;">{{ $user->role ?? 'N/A' }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #98897A;">Department:</span>
                                <span>{{ $user->department ?? 'N/A' }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #98897A;">Manager:</span>
                                <span>{{ $user->manager ? $user->manager->full_name : 'N/A' }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #98897A;">Start Date:</span>
                                <span>{{ $user->start_date ? $user->start_date->format('F j, Y') : 'N/A' }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #98897A;">Account Created:</span>
                                <span>{{ $user->created_at->format('F j, Y') }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #98897A;">Last Login:</span>
                                <span>{{ $user->last_login_at ? $user->last_login_at->format('F j, Y H:i') : 'Never' }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #98897A;">Status:</span>
                                <span style="color: {{ $user->is_active ? '#1FA855' : '#C8355F' }}; font-weight: 800;">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div style="background: #FFF5F5; border: 1px solid #FCDADA; border-radius: 14px; padding: 20px 24px;">
                        <h4 style="font: 600 15px 'Baloo 2'; color: #C8355F; margin-bottom: 8px;">⚠️ Danger Zone</h4>
                        <p style="font: 600 12px 'Nunito Sans'; color: #6B7A8C; margin-bottom: 12px;">This action cannot be undone. Please be certain.</p>
                        <button onclick="if(confirm('Are you sure you want to logout of all sessions?')) { window.location.href='{{ route('admin.logout') }}'; }" style="background: #C8355F; color: white; border: none; border-radius: 10px; padding: 10px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer;">
                            Logout All Sessions
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection