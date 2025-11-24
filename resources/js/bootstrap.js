import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Intercepteur pour s'assurer que le token CSRF est toujours à jour
axios.interceptors.request.use(config => {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token.content;
    }
    
    return config;
}, error => {
    return Promise.reject(error);
});

// Gestion automatique de l'erreur 419
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            console.error('CSRF token mismatch - reloading page...');
            // Recharger la page pour obtenir un nouveau token
            window.location.reload();
        }
        return Promise.reject(error);
    }
);