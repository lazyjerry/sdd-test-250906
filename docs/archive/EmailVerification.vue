<template>
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-6">
				<div class="card">
					<div class="card-body">
						<h1 class="card-title text-center mb-4">電子郵件驗證</h1>

						<!-- Loading State -->
						<div
							v-if="status === 'loading'"
							class="text-center"
						>
							<div
								class="spinner-border text-primary mb-3"
								role="status"
							>
								<span class="visually-hidden">Loading...</span>
							</div>
							<p class="text-muted">正在驗證您的電子郵件...</p>
						</div>

						<!-- Success State -->
						<div
							v-else-if="status === 'success'"
							class="text-center text-success"
						>
							<div class="mb-3">
								<i
									class="bi bi-check-circle-fill"
									style="font-size: 3rem"
								></i>
							</div>
							<h2>驗證成功！</h2>
							<p>{{ message }}</p>
							<p class="text-muted">{{ countdown }}秒後將自動跳轉到登入頁面...</p>
							<button
								class="btn btn-primary mt-3"
								@click="goToLogin"
							>
								立即前往登入
							</button>
						</div>

						<!-- Error State -->
						<div
							v-else-if="status === 'error'"
							class="text-center text-danger"
						>
							<div class="mb-3">
								<i
									class="bi bi-exclamation-circle-fill"
									style="font-size: 3rem"
								></i>
							</div>
							<h2>驗證失敗</h2>
							<p>{{ message }}</p>
							<div class="mt-3">
								<p><strong>可能的原因：</strong></p>
								<ul class="list-unstyled">
									<li>• 驗證連結已過期</li>
									<li>• 驗證連結無效</li>
									<li>• 電子郵件已經被驗證過</li>
								</ul>
							</div>
							<div class="mt-4">
								<button
									class="btn btn-secondary me-2"
									@click="$router.push('/register')"
								>
									重新註冊
								</button>
								<button
									class="btn btn-outline-secondary"
									@click="contactSupport"
								>
									聯絡客服
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

export default {
	name: "EmailVerification",
	setup() {
		const status = ref("loading");
		const message = ref("");
		const countdown = ref(3);
		const route = useRoute();
		const router = useRouter();

		const verifyEmail = async () => {
			try {
				// 從 URL 參數中提取驗證信息
				const verificationData = {
					id: route.query.id,
					hash: route.query.hash,
					expires: route.query.expires,
					signature: route.query.signature,
				};

				// 檢查必要參數是否存在
				if (!verificationData.id || !verificationData.hash || !verificationData.expires || !verificationData.signature) {
					throw new Error("驗證參數不完整");
				}

				// 調用後端 API 進行驗證
				const response = await fetch(`${import.meta.env.VITE_API_URL}/api/v1/auth/verify-email`, {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
						Accept: "application/json",
					},
					body: JSON.stringify(verificationData),
				});

				const data = await response.json();

				if (response.ok && data.status === "success") {
					status.value = "success";
					message.value = data.message || "您的電子郵件已成功驗證。";

					// 開始倒數計時
					startCountdown();
				} else {
					throw new Error(data.message || "驗證失敗");
				}
			} catch (error) {
				status.value = "error";
				message.value = error.message;
			}
		};

		const startCountdown = () => {
			const timer = setInterval(() => {
				countdown.value--;
				if (countdown.value <= 0) {
					clearInterval(timer);
					goToLogin();
				}
			}, 1000);
		};

		const goToLogin = () => {
			router.push({
				path: "/login",
				query: { message: "電子郵件驗證成功，請登入您的帳戶。" },
			});
		};

		const contactSupport = () => {
			window.location.href = "mailto:support@example.com";
		};

		onMounted(() => {
			verifyEmail();
		});

		return {
			status,
			message,
			countdown,
			goToLogin,
			contactSupport,
		};
	},
};
</script>

<style scoped>
.spinner-border {
	width: 3rem;
	height: 3rem;
}
</style>
