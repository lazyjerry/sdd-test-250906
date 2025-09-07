<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>郵件驗證結果 - Laravel JDemo</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .logo {
            color: #fff;
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .success-icon {
            color: #28a745;
            font-size: 4rem;
        }
        .error-icon {
            color: #dc3545;
            font-size: 4rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <!-- Logo -->
                <div class="logo">
                    <i class="bi bi-envelope-check"></i> Laravel JDemo
                </div>
                
                <div class="card">
                    <div class="card-body p-5 text-center">
                        @if($success)
                            <!-- 成功狀態 -->
                            <i class="bi bi-check-circle success-icon mb-4"></i>
                            <h2 class="text-success mb-3">驗證成功！</h2>
                            <p class="text-muted mb-4">{{ $message }}</p>
                            
                            @if($user)
                            <div class="alert alert-success" role="alert">
                                <h6 class="alert-heading">歡迎，{{ $user['username'] }}！</h6>
                                <p class="mb-0">您的電子郵件 <strong>{{ $user['email'] }}</strong> 已成功驗證。</p>
                                <hr>
                                <p class="mb-0"><small>驗證時間：{{ date('Y-m-d H:i:s', strtotime($user['email_verified_at'])) }}</small></p>
                            </div>
                            @endif
                            
                            <div class="d-grid gap-2">
                                <a href="/api/v1/auth/login" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    前往登入
                                </a>
                            </div>
                        @else
                            <!-- 失敗狀態 -->
                            <i class="bi bi-x-circle error-icon mb-4"></i>
                            <h2 class="text-danger mb-3">驗證失敗</h2>
                            <p class="text-muted mb-4">{{ $message }}</p>
                            
                            <div class="alert alert-danger" role="alert">
                                @switch($error_code)
                                    @case('USER_NOT_FOUND')
                                        <h6 class="alert-heading">找不到用戶</h6>
                                        <p class="mb-0">該驗證連結對應的用戶不存在，請確認連結是否正確。</p>
                                        @break
                                    @case('INVALID_VERIFICATION_LINK')
                                        <h6 class="alert-heading">連結無效</h6>
                                        <p class="mb-0">驗證連結已過期或無效，請重新申請驗證郵件。</p>
                                        @break
                                    @case('INVALID_LINK_FORMAT')
                                        <h6 class="alert-heading">連結格式錯誤</h6>
                                        <p class="mb-0">驗證連結格式不正確，請確認連結完整性。</p>
                                        @break
                                    @default
                                        <h6 class="alert-heading">系統錯誤</h6>
                                        <p class="mb-0">系統處理時發生錯誤，請稍後再試或聯繫客服。</p>
                                @endswitch
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="/api/v1/auth/login" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i>
                                    返回登入頁面
                                </a>
                                <button type="button" class="btn btn-primary" onclick="requestNewVerification()">
                                    <i class="bi bi-envelope"></i>
                                    重新發送驗證郵件
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            <i class="bi bi-shield-check"></i>
                            安全的郵件驗證系統
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 重新發送驗證郵件功能（可選）
        function requestNewVerification() {
            alert('重新發送驗證郵件功能需要額外實現，請聯繫客服或重新註冊。');
        }
        
        // 自動跳轉功能（成功時）
        @if($success)
        setTimeout(function() {
            if (confirm('驗證成功！是否要自動跳轉到登入頁面？')) {
                window.location.href = '/api/v1/auth/login';
            }
        }, 3000);
        @endif
    </script>
</body>
</html>
