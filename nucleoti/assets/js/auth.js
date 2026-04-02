// auth.js - Gerenciamento de autenticacao

function setCurrentUser(user) {
    localStorage.setItem('currentUser', JSON.stringify(user));
}

function getCurrentUser() {
    const userStr = localStorage.getItem('currentUser');
    return userStr ? JSON.parse(userStr) : null;
}

function clearCurrentUser() {
    localStorage.removeItem('currentUser');
}

function isAuthenticated() {
    return getCurrentUser() !== null;
}

function hasRole(role) {
    const user = getCurrentUser();
    return user && user.role === role;
}

function logout() {
    clearCurrentUser();
    window.location.href = 'index.html';
}

function redirectToDashboard(user) {
    const currentUser = user || getCurrentUser();
    if (!currentUser) {
        window.location.href = 'index.html';
        return;
    }
    switch(currentUser.role) {
        case 'admin':
            window.location.href = 'dashboard-admin.html';
            break;
        case 'lojista':
            window.location.href = 'dashboard-lojista.html';
            break;
        case 'arquiteto':
            window.location.href = 'dashboard-arquiteto.html';
            break;
        default:
            window.location.href = 'index.html';
    }
}

function requireAuth(requiredRole) {
    const user = getCurrentUser();
    if (!user) {
        window.location.href = 'index.html';
        return null;
    }
    if (requiredRole && user.role !== requiredRole) {
        redirectToDashboard(user);
        return null;
    }
    return user;
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/')) {
        const user = getCurrentUser();
        if (user) {
            redirectToDashboard(user);
        }
    }
});
