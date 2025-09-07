<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>密碼重設成功 - Laravel JDemo</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            animation: bounce 1s infinite alternate;
        }
        @keyframes bounce {
            from { transform: translateY(0px); }
            to { transform: translateY(-10px); }
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
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
        .countdown {
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <!-- Logo -->
                <div class="logo">
                    <i class="bi bi-shield-check"></i> Laravel JDemo
                </div>
                
                <div class="card">
                    <div class="card-body text-center p-5">
                        <!-- 成功圖示 -->
                        <div class="success-icon mb-4">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        
                        <!-- 成功訊息 -->
                        <h3 class="text-success mb-3">密碼重設成功！</h3>
                        
                        @if(session('success'))
                            <div class="alert alert-success border-0">
                                <i class="bi bi-info-circle"></i>
                                {{ session('success') }}
                            </div>
                        @endif
                        
                        <p class="text-muted mb-4">
                            您的密碼已成功更新。您現在可以使用新密碼登入系統。
                        </p>
                        
                        <!-- 自動跳轉提示 -->
                        <div class="alert alert-info border-0 mb-4">
                            <i class="bi bi-clock"></i>
                            系統將在 <span class="countdown" id="countdown">10</span> 秒後自動跳轉到 API 文檔頁面
                        </div>
                        
                        <!-- 動作按鈕 -->
                        <div class="d-grid gap-2">
                            <a href="/swagger-ui/" class="btn btn-primary btn-lg">
                                <i class="bi bi-book"></i>
                                立即前往 API 文檔
                            </a>
                            <a href="/" class="btn btn-outline-secondary">
                                <i class="bi bi-house"></i>
                                返回首頁
                            </a>
                        </div>
                        
                        <!-- 使用提示 -->
                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="bi bi-lightbulb"></i>
                                <strong>提示：</strong>建議您使用 Insomnia 或 Postman 等工具來測試 API 功能
                            </small>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            <i class="bi bi-shield-lock"></i>
                            您的帳戶安全是我們的首要任務
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let timeLeft = 10;
            const countdownElement = document.getElementById('countdown');
            
            const timer = setInterval(function() {
                timeLeft--;
                countdownElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    window.location.href = '/swagger-ui/';
                }
            }, 1000);
            
            // 如果用戶點擊其他按鈕，取消自動跳轉
            document.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    clearInterval(timer);
                });
            });
        });
    </script>
</body>
</html>
