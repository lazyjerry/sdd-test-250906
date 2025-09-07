<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>密碼重設 - Laravel JDemo</title>
    
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
        .form-control {
            border-radius: 10px;
            border: 2px solid #e1e5e9;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .password-strength {
            font-size: 0.85rem;
            margin-top: 5px;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .logo {
            color: #fff;
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <!-- Logo -->
                <div class="logo">
                    <i class="bi bi-shield-lock"></i> Laravel JDemo
                </div>
                
                <div class="card">
                    <div class="card-header bg-white text-center py-4">
                        <h4 class="mb-0">
                            <i class="bi bi-key text-primary"></i>
                            重設密碼
                        </h4>
                        <p class="text-muted mt-2 mb-0">請輸入您的新密碼</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- 錯誤訊息區域 (動態顯示) -->
                        <div id="alertContainer"></div>

                        <!-- 密碼重設表單 -->
                        <form id="resetForm">
                            @csrf
                            
                            <!-- 隱藏欄位 -->
                            <input type="hidden" name="token" value="{{ $token }}">
                            
                            <!-- Email 欄位 -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope"></i> 電子郵件
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="{{ $email }}" 
                                       required 
                                       readonly>
                            </div>

                            <!-- 新密碼欄位 -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> 新密碼
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           minlength="8"
                                           placeholder="請輸入新密碼">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <!-- 密碼強度指示器 -->
                                <div class="password-strength" id="passwordStrength"></div>
                                <div class="form-text">
                                    <small>
                                        <i class="bi bi-info-circle"></i>
                                        密碼必須至少8個字符，包含大小寫字母、數字和特殊字符
                                    </small>
                                </div>
                            </div>

                            <!-- 確認密碼欄位 -->
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">
                                    <i class="bi bi-lock-fill"></i> 確認新密碼
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation" 
                                           required 
                                           minlength="8"
                                           placeholder="請再次輸入新密碼">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                        <i class="bi bi-eye" id="toggleIconConfirm"></i>
                                    </button>
                                </div>
                                <div class="password-match mt-1" id="passwordMatch"></div>
                            </div>

                            <!-- 提交按鈕 -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="bi bi-check-circle"></i>
                                    重設密碼
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            <i class="bi bi-arrow-left"></i>
                            <a href="/api/v1/auth/login" class="text-decoration-none">返回登入</a>
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
            const passwordInput = document.getElementById('password');
            const passwordConfirmInput = document.getElementById('password_confirmation');
            const strengthIndicator = document.getElementById('passwordStrength');
            const matchIndicator = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');

            // 密碼顯示/隱藏切換
            document.getElementById('togglePassword').addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                const icon = document.getElementById('toggleIcon');
                icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
            });

            document.getElementById('togglePasswordConfirm').addEventListener('click', function() {
                const type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordConfirmInput.setAttribute('type', type);
                const icon = document.getElementById('toggleIconConfirm');
                icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
            });

            // 密碼強度檢查
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);
                
                strengthIndicator.innerHTML = strength.message;
                strengthIndicator.className = `password-strength ${strength.class}`;
                
                checkPasswordMatch();
            });

            // 密碼匹配檢查
            passwordConfirmInput.addEventListener('input', checkPasswordMatch);

            function checkPasswordStrength(password) {
                if (password.length < 8) {
                    return { message: '密碼長度不足', class: 'strength-weak' };
                }

                let score = 0;
                const checks = [
                    /[a-z]/.test(password), // 小寫字母
                    /[A-Z]/.test(password), // 大寫字母
                    /\d/.test(password),    // 數字
                    /[@$!%*?&]/.test(password) // 特殊字符
                ];

                score = checks.filter(check => check).length;

                if (score < 3) {
                    return { message: '<i class="bi bi-shield-exclamation"></i> 密碼強度：弱', class: 'strength-weak' };
                } else if (score === 3) {
                    return { message: '<i class="bi bi-shield-check"></i> 密碼強度：中等', class: 'strength-medium' };
                } else {
                    return { message: '<i class="bi bi-shield-fill-check"></i> 密碼強度：強', class: 'strength-strong' };
                }
            }

            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = passwordConfirmInput.value;

                if (confirmPassword.length === 0) {
                    matchIndicator.innerHTML = '';
                    return;
                }

                if (password === confirmPassword) {
                    matchIndicator.innerHTML = '<i class="bi bi-check-circle text-success"></i> <small class="text-success">密碼匹配</small>';
                } else {
                    matchIndicator.innerHTML = '<i class="bi bi-x-circle text-danger"></i> <small class="text-danger">密碼不匹配</small>';
                }
            }

            // 表單提交處理 (AJAX)
            document.getElementById('resetForm').addEventListener('submit', function(e) {
                e.preventDefault(); // 阻止默認提交
                
                // 顯示載入狀態
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>處理中...';
                submitBtn.disabled = true;
                
                // 清除之前的錯誤訊息
                clearAlerts();
                
                // 收集表單資料
                const formData = new FormData(this);
                const data = {
                    token: formData.get('token'),
                    email: formData.get('email'),
                    password: formData.get('password'),
                    password_confirmation: formData.get('password_confirmation'),
                    _token: formData.get('_token')
                };
                
                // 發送 AJAX 請求
                fetch('{{ route("password.reset.submit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 成功：顯示成功訊息並跳轉
                        showAlert('success', data.message);
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1500);
                    } else {
                        // 失敗：顯示錯誤訊息
                        showAlert('danger', data.message);
                        
                        // 顯示欄位特定錯誤
                        if (data.errors) {
                            showFieldErrors(data.errors);
                        }
                        
                        // 恢復按鈕狀態
                        resetSubmitButton();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', '系統錯誤，請稍後再試');
                    resetSubmitButton();
                });
            });
            
            // 輔助函數：顯示警告訊息
            function showAlert(type, message) {
                const alertContainer = document.getElementById('alertContainer');
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                        <strong>${type === 'success' ? '成功！' : '錯誤：'}</strong> ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                alertContainer.innerHTML = alertHtml;
            }
            
            // 輔助函數：顯示欄位錯誤
            function showFieldErrors(errors) {
                Object.keys(errors).forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        
                        // 移除舊的錯誤訊息
                        const existingError = input.parentNode.querySelector('.invalid-feedback');
                        if (existingError) {
                            existingError.remove();
                        }
                        
                        // 添加新的錯誤訊息
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.textContent = errors[field][0];
                        input.parentNode.appendChild(errorDiv);
                    }
                });
            }
            
            // 輔助函數：清除警告訊息
            function clearAlerts() {
                const alertContainer = document.getElementById('alertContainer');
                alertContainer.innerHTML = '';
                
                // 清除欄位錯誤
                document.querySelectorAll('.is-invalid').forEach(input => {
                    input.classList.remove('is-invalid');
                });
                document.querySelectorAll('.invalid-feedback').forEach(error => {
                    error.remove();
                });
            }
            
            // 輔助函數：重設按鈕狀態
            function resetSubmitButton() {
                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> 重設密碼';
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
