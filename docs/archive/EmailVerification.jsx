import React, { useEffect, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";

const EmailVerification = () => {
	const [status, setStatus] = useState("loading");
	const [message, setMessage] = useState("");
	const location = useLocation();
	const navigate = useNavigate();

	useEffect(() => {
		const verifyEmail = async () => {
			try {
				// 從 URL 參數中提取驗證信息
				const urlParams = new URLSearchParams(location.search);
				const verificationData = {
					id: urlParams.get("id"),
					hash: urlParams.get("hash"),
					expires: urlParams.get("expires"),
					signature: urlParams.get("signature"),
				};

				// 檢查必要參數是否存在
				if (!verificationData.id || !verificationData.hash || !verificationData.expires || !verificationData.signature) {
					throw new Error("驗證參數不完整");
				}

				// 調用後端 API 進行驗證
				const response = await fetch(`${process.env.REACT_APP_API_URL}/api/v1/auth/verify-email`, {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
						Accept: "application/json",
					},
					body: JSON.stringify(verificationData),
				});

				const data = await response.json();

				if (response.ok && data.status === "success") {
					setStatus("success");
					setMessage(data.message || "您的電子郵件已成功驗證。");

					// 3秒後自動跳轉到登入頁面
					setTimeout(() => {
						navigate("/login", {
							state: { message: "電子郵件驗證成功，請登入您的帳戶。" },
						});
					}, 3000);
				} else {
					throw new Error(data.message || "驗證失敗");
				}
			} catch (error) {
				setStatus("error");
				setMessage(error.message);
			}
		};

		verifyEmail();
	}, [location.search, navigate]);

	const renderContent = () => {
		switch (status) {
			case "loading":
				return (
					<div className="text-center">
						<div
							className="spinner-border text-primary mb-3"
							role="status"
						>
							<span className="visually-hidden">Loading...</span>
						</div>
						<p className="text-muted">正在驗證您的電子郵件...</p>
					</div>
				);

			case "success":
				return (
					<div className="text-center text-success">
						<div className="mb-3">
							<i
								className="bi bi-check-circle-fill"
								style={{ fontSize: "3rem" }}
							></i>
						</div>
						<h2>驗證成功！</h2>
						<p>{message}</p>
						<p className="text-muted">3秒後將自動跳轉到登入頁面...</p>
						<button
							className="btn btn-primary mt-3"
							onClick={() => navigate("/login")}
						>
							立即前往登入
						</button>
					</div>
				);

			case "error":
				return (
					<div className="text-center text-danger">
						<div className="mb-3">
							<i
								className="bi bi-exclamation-circle-fill"
								style={{ fontSize: "3rem" }}
							></i>
						</div>
						<h2>驗證失敗</h2>
						<p>{message}</p>
						<div className="mt-3">
							<p>
								<strong>可能的原因：</strong>
							</p>
							<ul className="list-unstyled">
								<li>• 驗證連結已過期</li>
								<li>• 驗證連結無效</li>
								<li>• 電子郵件已經被驗證過</li>
							</ul>
						</div>
						<div className="mt-4">
							<button
								className="btn btn-secondary me-2"
								onClick={() => navigate("/register")}
							>
								重新註冊
							</button>
							<button
								className="btn btn-outline-secondary"
								onClick={() => (window.location.href = "mailto:support@example.com")}
							>
								聯絡客服
							</button>
						</div>
					</div>
				);

			default:
				return null;
		}
	};

	return (
		<div className="container">
			<div className="row justify-content-center">
				<div className="col-md-6">
					<div className="card">
						<div className="card-body">
							<h1 className="card-title text-center mb-4">電子郵件驗證</h1>
							{renderContent()}
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default EmailVerification;
