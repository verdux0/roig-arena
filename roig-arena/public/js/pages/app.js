document.addEventListener('DOMContentLoaded', () => {
    const loginNavLink = document.getElementById('loginNavLink');
    const userNavLink = document.getElementById('userNavLink');

    if (!loginNavLink || !userNavLink) {
        return;
    }

    try {
        const rawUser = localStorage.getItem('sanctum_user');

        if (!rawUser) {
            return;
        }

        const user = JSON.parse(rawUser);
        const userName = (user?.nombre ?? '').trim();
        const userApellido = (user?.apellido ?? '').trim();
        const displayName = `${userName} ${userApellido}`.trim() || (user?.email ?? '').trim();

        if (!displayName) {
            return;
        }

        userNavLink.textContent = displayName;
        userNavLink.hidden = false;
        loginNavLink.hidden = true;
    } catch (error) {
        localStorage.removeItem('sanctum_user');
    }
});
