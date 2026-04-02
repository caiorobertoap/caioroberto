// api.js - Funcoes para comunicacao com o backend

const API_BASE = window.location.origin + '/nucleo/api';

async function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include'
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    const url = endpoint.startsWith('http') ? endpoint : `${API_BASE}/${endpoint}`;
    
    const response = await fetch(url, options);
    const result = await response.json();
    
    if (!response.ok) {
        throw new Error(result.error || 'Erro na requisicao');
    }
    
    return result;
}

// Upload com FormData (para logo)
async function apiUpload(endpoint, formData) {
    const url = `${API_BASE}/${endpoint}`;
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'include',
        body: formData
    });
    const result = await response.json();
    if (!response.ok) {
        throw new Error(result.error || 'Erro no upload');
    }
    return result;
}

const API = {
    auth: {
        login: (email, password) => apiRequest('login.php', 'POST', { email, password }),
        registerLojista: (data) => apiRequest('register.php', 'POST', { ...data, role: 'lojista' }),
        registerArquiteto: (data) => apiRequest('register.php', 'POST', { ...data, role: 'arquiteto' }),
        me: () => apiRequest('stats.php'),
        myDetails: () => apiRequest('my_details.php')
    },
    offices: {
        list: () => apiRequest('offices.php'),
        create: (data) => apiRequest('offices.php', 'POST', data)
    },
    campaigns: {
        list: () => apiRequest('campaigns.php'),
        create: (data) => apiRequest('campaigns.php', 'POST', data),
        action: (campaign_id, action) => apiRequest('campaigns.php', 'PUT', { campaign_id, action }),
        analysis: (campaignId) => apiRequest(`campaign_analysis.php?campaign_id=${campaignId}`)
    },
    points: {
        create: (data) => apiRequest('points.php', 'POST', data)
    },
    rankings: {
        arquitetos: () => apiRequest('rankings.php?type=arquitetos'),
        lojistas: () => apiRequest('rankings.php?type=lojistas'),
        escritorios: () => apiRequest('rankings.php?type=offices')
    },
    stats: {
        dashboard: () => apiRequest('stats.php')
    },
    birthdays: {
        check: () => apiRequest('check_birthdays.php')
    },
    reports: {
        get: (filters = {}) => {
            const params = new URLSearchParams();
            if (filters.campaign_id) params.append('campaign_id', filters.campaign_id);
            if (filters.lojista_id) params.append('lojista_id', filters.lojista_id);
            if (filters.office_id) params.append('office_id', filters.office_id);
            if (filters.month) params.append('month', filters.month);
            if (filters.year) params.append('year', filters.year);
            return apiRequest(`reports.php?${params.toString()}`);
        }
    },
    logo: {
        upload: (formData) => apiUpload('upload_logo.php', formData),
        remove: () => apiRequest('upload_logo.php', 'DELETE')
    },
    users: {
        arquitetos: () => apiRequest('rankings.php?type=arquitetos'),
        lojistas: () => apiRequest('rankings.php?type=lojistas')
    }
};

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR').format(value);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('pt-BR');
}

function showError(message) {
    alert(message);
}

function showSuccess(message) {
    alert(message);
}
