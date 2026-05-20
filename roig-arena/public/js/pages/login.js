document.addEventListener('DOMContentLoaded', () => {
	const form = document.getElementById('loginForm');

	if (!form) {
		return;
	}

	const submitButton = document.getElementById('loginSubmit');
	const alertBox = document.getElementById('loginAlert');
	const fieldErrors = new Map(
		Array.from(form.querySelectorAll('[data-error-for]')).map(element => [element.dataset.errorFor, element])
	);

	const clearMessages = () => {
		alertBox.hidden = true;
		alertBox.textContent = '';

		fieldErrors.forEach(element => {
			element.textContent = '';
		});
	};

	const setBusy = isBusy => {
		submitButton.disabled = isBusy;
		submitButton.textContent = isBusy ? 'Entrando...' : 'Entrar';
	};

	const getRedirectTo = () => {
		const fallback = '/';
		const redirectTo = form.dataset.redirectTo || fallback;

		try {
			const url = new URL(redirectTo, window.location.origin);
			return `${url.pathname}${url.search}${url.hash}` || fallback;
		} catch {
			return fallback;
		}
	};

	const showFieldErrors = errors => {
		Object.entries(errors).forEach(([fieldName, messages]) => {
			const target = fieldErrors.get(fieldName);
			if (target) {
				target.textContent = Array.isArray(messages) ? messages[0] : String(messages);
			}
		});
	};

	form.addEventListener('submit', async event => {
		event.preventDefault();
		clearMessages();
		setBusy(true);

		const formData = new FormData(form);
		const payload = {
			email: String(formData.get('email') || '').trim(),
			password: String(formData.get('password') || '')
		};

		try {
			const response = await fetch(form.action, {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
				},
				credentials: 'same-origin',
				body: JSON.stringify(payload)
			});

			const data = await response.json().catch(() => ({}));

			if (!response.ok) {
				if (response.status === 422 && data.errors) {
					showFieldErrors(data.errors);
				} else {
					alertBox.hidden = false;
					alertBox.textContent = data.message || 'No se pudo iniciar sesión.';
				}

				setBusy(false);
				return;
			}

			if (data.token) {
				localStorage.setItem('sanctum_token', data.token);
			} else {
				localStorage.removeItem('sanctum_token');
			}

			if (data.user) {
				localStorage.setItem('sanctum_user', JSON.stringify(data.user));
			} else {
				localStorage.removeItem('sanctum_user');
			}

			window.location.href = getRedirectTo();
		} catch (error) {
			alertBox.hidden = false;
			alertBox.textContent = 'Error de red al iniciar sesión.';
			console.error('Error iniciando sesión:', error);
			setBusy(false);
		}
	});
});
