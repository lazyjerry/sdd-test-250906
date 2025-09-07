<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>密碼重設測試 - Laravel JDemo</title>
    
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
        .logo {
            color: #fff;
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .btn-custom {
            border-radius: 25px;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .code-block {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Logo -->
        <div class="logo">
            <i class="bi bi-gear"></i> Laravel JDemo - 密碼重設測試
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-tools"></i>
                            密碼重設功能測試頁面
                        </h4>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>說明：</strong>這個頁面用於測試密碼重設功能的完整流程
                        </div>
                        
                        <!-- 步驟說明 -->
                        <h5><i class="bi bi-list-ol"></i> 測試步驟</h5>
                        
                        <div class="accordion" id="testSteps">
                            <!-- 步驟 1 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                                        <i class="bi bi-1-circle me-2"></i>
                                        發送忘記密碼請求
                                    </button>
                                </h2>
                                <div id="step1" class="accordion-collapse collapse show" data-bs-parent="#testSteps">
                                    <div class="accordion-body">
                                        <p>使用 API 發送忘記密碼請求：</p>
                                        <div class="code-block mb-3">
POST /api/v1/auth/forgot-password
Content-Type: application/json

{
    "email": "test@example.com"
}
                                        </div>
                                        <button class="btn btn-primary btn-custom" onclick="sendForgotPassword()">
                                            <i class="bi bi-send"></i> 發送忘記密碼請求
                                        </button>
                                        <div id="forgotResult" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 步驟 2 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                                        <i class="bi bi-2-circle me-2"></i>
                                        模擬郵件中的重設連結
                                    </button>
                                </h2>
                                <div id="step2" class="accordion-collapse collapse" data-bs-parent="#testSteps">
                                    <div class="accordion-body">
                                        <p>通常用戶會收到包含重設連結的郵件。以下是範例連結格式：</p>
                                        <div class="code-block mb-3">
http://localhost/password/reset/sample_token_123456?email=test@example.com
                                        </div>
                                        <p class="text-warning">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <strong>注意：</strong>在實際應用中，token 是由系統生成的隨機字串
                                        </p>
                                        <a href="/password/reset/sample_token_123456?email=test@example.com" 
                                           class="btn btn-warning btn-custom" target="_blank">
                                            <i class="bi bi-link-45deg"></i> 開啟密碼重設頁面 (測試連結)
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 步驟 3 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                                        <i class="bi bi-3-circle me-2"></i>
                                        填寫密碼重設表單
                                    </button>
                                </h2>
                                <div id="step3" class="accordion-collapse collapse" data-bs-parent="#testSteps">
                                    <div class="accordion-body">
                                        <p>在密碼重設頁面中，用戶需要：</p>
                                        <ul>
                                            <li>確認電子郵件地址（預填且只讀）</li>
                                            <li>輸入新密碼（至少8個字符，包含大小寫字母、數字和特殊字符）</li>
                                            <li>確認新密碼</li>
                                            <li>提交表單</li>
                                        </ul>
                                        <p>表單提交後會調用以下 API：</p>
                                        <div class="code-block">
POST /api/v1/auth/reset-password
Content-Type: application/json

{
    "token": "reset_token",
    "email": "test@example.com",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 步驟 4 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step4">
                                        <i class="bi bi-4-circle me-2"></i>
                                        密碼重設完成
                                    </button>
                                </h2>
                                <div id="step4" class="accordion-collapse collapse" data-bs-parent="#testSteps">
                                    <div class="accordion-body">
                                        <p>密碼重設成功後，用戶會被重定向到成功頁面，顯示：</p>
                                        <ul>
                                            <li><i class="bi bi-check-circle text-success"></i> 密碼重設成功訊息</li>
                                            <li><i class="bi bi-clock text-info"></i> 10秒倒數計時</li>
                                            <li><i class="bi bi-link text-primary"></i> 自動跳轉到 API 文檔</li>
                                            <li><i class="bi bi-book text-secondary"></i> 手動前往按鈕</li>
                                        </ul>
                                        <a href="/password/reset-success" class="btn btn-success btn-custom" target="_blank">
                                            <i class="bi bi-check-circle"></i> 查看成功頁面
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 功能特色 -->
                        <div class="mt-5">
                            <h5><i class="bi bi-star"></i> 功能特色</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-shield-check text-success"></i> 即時密碼強度檢查</li>
                                        <li><i class="bi bi-eye text-info"></i> 密碼顯示/隱藏切換</li>
                                        <li><i class="bi bi-check-circle text-success"></i> 密碼匹配驗證</li>
                                        <li><i class="bi bi-phone text-warning"></i> 響應式設計</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-bootstrap text-primary"></i> Bootstrap 5 樣式</li>
                                        <li><i class="bi bi-exclamation-triangle text-danger"></i> 完整錯誤處理</li>
                                        <li><i class="bi bi-arrow-clockwise text-info"></i> 自動跳轉功能</li>
                                        <li><i class="bi bi-lock text-secondary"></i> CSRF 保護</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 相關連結 -->
                        <div class="mt-4">
                            <h5><i class="bi bi-link-45deg"></i> 相關連結</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="/swagger-ui/" class="btn btn-outline-primary btn-sm">API 文檔</a>
                                <a href="/api/v1/auth/login" class="btn btn-outline-secondary btn-sm">API 登入</a>
                                <a href="/" class="btn btn-outline-info btn-sm">首頁</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        async function sendForgotPassword() {
            const resultDiv = document.getElementById('forgotResult');
            resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>發送中...';
            
            try {
                const response = await fetch('/api/v1/auth/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: 'test@example.com'
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <strong>成功！</strong> ${data.message || '忘記密碼郵件已發送'}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>錯誤：</strong> ${data.message || '發送失敗'}
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-x-circle"></i>
                        <strong>網路錯誤：</strong> ${error.message}
                    </div>
                `;
            }
        }
    </script>
</body>
</html>
