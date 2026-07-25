<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login · Engage Clinic</title>
    <!-- Google Font & Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f6f3ee 0%, #ede8e0 100%);
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }

        /* Decorative elements */
        .login-container::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(37,99,235,0.05) 0%, transparent 70%);
            border-radius: 50%;
        }

        .login-container::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(37,99,235,0.03) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .login-wrapper {
            display: flex;
            max-width: 1100px;
            width: 100%;
            background: white;
            border-radius: 2rem;
            box-shadow: 0 25px 60px -15px rgba(0,0,0,0.15);
            overflow: hidden;
            animation: fadeIn 0.6s ease-in-out;
            position: relative;
            z-index: 1;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        /* Left Side - Admin Branding */
        .login-image {
            flex: 1;
            background: linear-gradient(135deg, #0b1121 0%, #1a2332 50%, #0b1121 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
            min-height: 550px;
        }
        
        .login-image::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 70% 30%, rgba(37,99,235,0.08) 0%, transparent 60%);
            animation: float 15s ease-in-out infinite;
        }
        
        .login-image::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -30%;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle at 30% 70%, rgba(37,99,235,0.05) 0%, transparent 60%);
            animation: float 20s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-2%, 2%) rotate(2deg); }
        }
        
        .login-image-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .login-image .shield-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: block;
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .login-image .admin-badge {
            display: inline-block;
            background: rgba(37,99,235,0.2);
            border: 1px solid rgba(37,99,235,0.3);
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #60a5fa;
            margin-bottom: 0.75rem;
        }
        
        .login-image h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }
        
        .login-image .welcome-text {
            font-size: 1.5rem;
            font-weight: 300;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        
        .login-image .sub-text {
            opacity: 0.5;
            font-size: 0.85rem;
            max-width: 300px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .login-image .security-badge {
            margin-top: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.4);
        }
        
        /* Right Side - Admin Login Form */
        .login-form-side {
            flex: 1;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 550px;
            background: white;
        }
        
        .login-form-side .brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .login-form-side .brand span {
            font-size: 1.5rem;
        }
        
        .login-form-side .brand h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1F2937;
        }
        
        .login-form-side .brand .badge {
            font-size: 0.6rem;
            background: #dbeafe;
            color: #2563eb;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-left: 0.25rem;
        }
        
        .login-form-side .welcome-text {
            margin-bottom: 2rem;
        }
        
        .login-form-side .welcome-text h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 0.25rem;
        }
        
        .login-form-side .welcome-text h1 .lock-icon {
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .login-form-side .welcome-text p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .input-field {
            width: 100%;
            background: #f8fafc;
            border: 2px solid #e8ecf1;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #1F2937;
            transition: all 0.15s;
        }
        .input-field::placeholder {
            color: #9ca3af;
        }
        .input-field:focus {
            outline: none;
            border-color: #2563eb;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.08);
        }
        .input-field.error {
            border-color: #ef4444;
        }
        
        .input-icon-wrapper {
            position: relative;
        }
        
        .input-icon-wrapper .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.4;
            font-size: 1rem;
        }
        
        .input-icon-wrapper .input-field {
            padding-left: 2.75rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transition: all 0.3s;
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: white;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -5px rgba(37,99,235,0.3);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-primary .btn-icon {
            margin-right: 0.5rem;
        }
        
        .forgot-link {
            color: #6b7280;
            font-size: 0.875rem;
            transition: all 0.15s;
            text-decoration: none;
        }
        .forgot-link:hover {
            color: #2563eb;
        }
        
        /* Admin notice */
        .admin-notice {
            margin-top: 1.5rem;
            padding: 0.75rem 1rem;
            background: #f0f4ff;
            border: 1px solid #dbeafe;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-notice .notice-icon {
            font-size: 1rem;
            flex-shrink: 0;
        }
        
        .admin-notice .notice-text {
            font-size: 0.7rem;
            color: #4b5563;
            line-height: 1.4;
        }
        
        .admin-notice .notice-text strong {
            color: #1F2937;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 420px;
            }
            
            .login-image {
                min-height: 250px;
                padding: 2rem;
            }
            
            .login-image .shield-icon {
                font-size: 3rem;
                margin-bottom: 0.75rem;
            }
            
            .login-image h2 {
                font-size: 1.75rem;
            }
            
            .login-image .welcome-text {
                font-size: 1.1rem;
            }
            
            .login-image .security-badge {
                margin-top: 1rem;
                padding: 0.35rem 0.75rem;
                font-size: 0.6rem;
            }
            
            .login-form-side {
                padding: 2rem 1.5rem;
                min-height: auto;
            }
            
            .login-form-side .welcome-text h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-wrapper">
            <!-- Left Side - Admin Branding -->
            <div class="login-image">
                <div class="login-image-content">
                    <span class="shield-icon">🛡️</span>
                    <div class="admin-badge">🔐 Administrator</div>
                    <h2>Engage Clinic</h2>
                    <div class="welcome-text">WELCOME ADMIN</div>
                    <p class="sub-text">Secure access to the clinic management portal</p>
                    <div class="security-badge">
                        <span>🔒</span>
                        <span>256-bit encrypted · Authorized personnel only</span>
                    </div>
                </div>
            </div>

            <!-- Right Side - Admin Login Form -->
            <div class="login-form-side">
                <div class="brand">
                    <span>🧠</span>
                    <h3>Engage Clinic <span class="badge">Admin</span></h3>
                </div>

                <div class="welcome-text">
                    <h1><span class="lock-icon">🔐</span>Admin Login</h1>
                    <p>Enter your credentials to access the dashboard</p>
                </div>

                <!-- Login Form -->
                <form action="{{ route('admin.login') }}" method="POST">
                    @csrf
                    
                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">📧</span>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="input-field @error('email') error @enderror" 
                                placeholder="admin@gmail.com"
                                value="{{ old('email') }}"
                                required
                                autofocus
                            >
                        </div>
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">🔑</span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="input-field @error('password') error @enderror" 
                                placeholder="Enter your password"
                                required
                            >
                        </div>
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-[#2563EB] focus:ring-[#2563EB] mr-2">
                            Remember me
                        </label>
                        <a href="#" class="forgot-link" onclick="alert('Please contact your system administrator to reset your password.'); return false;">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary">
                        <span class="btn-icon">→</span>
                        Sign In to Admin Portal
                    </button>
                </form>

                <!-- Admin Notice -->
                <div class="admin-notice">
                    <span class="notice-icon">ℹ️</span>
                    <span class="notice-text">
                        <strong>Authorized access only.</strong> This portal is restricted to clinic administrators. All activities are logged for security purposes.
                    </span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>