/**
 * Cliente HTTP de la API Pop Estate.
 *
 * La UI web usa sesión Symfony.
 * Este cliente existe solo para llamadas que realmente necesiten JWT.
 *
 * Importante:
 * - No usa localhost.
 * - No usa una URL de backend configurable en JavaScript.
 * - Usa same-origin.
 * - El token vive en sessionStorage y no en localStorage.
 */
(() => {
    'use strict';

    const API_BASE = '/api';
    const TOKEN_KEY = 'pop_estate_api_token';

    const getToken = () => sessionStorage.getItem(TOKEN_KEY);

    const clearToken = () => sessionStorage.removeItem(TOKEN_KEY);

    const setToken = (token) => {
        if (token) {
            sessionStorage.setItem(TOKEN_KEY, token);
        } else {
            clearToken();
        }
    };

    async function login(username, password) {
        const response = await fetch('/api/login_check', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ username, password })
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok || !payload.token) {
            throw new Error(
                payload.message ||
                payload.error ||
                'No fue posible autenticar al usuario.'
            );
        }

        setToken(payload.token);
        return payload;
    }

    async function request(path, options = {}) {
        const headers = new Headers(options.headers || {});
        headers.set('Accept', 'application/json');

        if (options.body && !headers.has('Content-Type')) {
            headers.set('Content-Type', 'application/json');
        }

        const token = getToken();

        if (token) {
            headers.set('Authorization', `Bearer ${token}`);
        }

        const response = await fetch(`${API_BASE}${path}`, {
            ...options,
            headers,
            credentials: 'same-origin'
        });

        if (response.status === 401) {
            clearToken();
            throw new Error('La sesión API expiró o no está autorizada.');
        }

        const contentType = response.headers.get('content-type') || '';
        const payload = contentType.includes('application/json')
            ? await response.json()
            : await response.text();

        if (!response.ok) {
            throw new Error(
                typeof payload === 'object'
                    ? (payload.message || payload.error || 'Error de API.')
                    : 'Error de API.'
            );
        }

        return payload;
    }

    window.PopEstateAPI = Object.freeze({
        login,
        request,
        getToken,
        clearToken
    });
})();
