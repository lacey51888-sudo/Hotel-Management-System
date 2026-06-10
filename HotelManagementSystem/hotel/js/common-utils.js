/**
 * Hotel Management System - Shared Utility Function Library
 * For unified handling of API calls, error handling, user authentication, etc.
 */

// API Base URL
const API_BASE_URL = 'http://localhost/api/';

/**
 * Unified API call function
 * @param {string} endpoint - API endpoint
 * @param {object} options - fetch options
 * @returns {Promise} API response
 */
async function fetchJSON(endpoint, options = {}) {
    try {
        const url = endpoint.startsWith('http') ? endpoint : API_BASE_URL + endpoint;
        const response = await fetch(url, options);
        const text = await response.text();
        
        try {
            const data = JSON.parse(text);
            
            // If response includes performance data, display in console
            if (data.performance) {
                console.log(`⚡ API Performance [${endpoint}]:`, data.performance.formatted);
            }
            
            return data;
        } catch (e) {
            console.error('JSON parse error:', endpoint, text);
            return { ok: false, error: 'invalid_json', details: text };
        }
    } catch (e) {
        console.error('Network error:', endpoint, e);
        return { ok: false, error: 'network_error', details: e.message };
    }
}

/**
 * Get currently logged-in user
 * @returns {object|null} User object or null
 */
function getCurrentUser() {
    try {
        const userStr = localStorage.getItem('hm_user');
        return userStr ? JSON.parse(userStr) : null;
    } catch (e) {
        console.error('Failed to parse user data:', e);
        return null;
    }
}

/**
 * 设置当前登录用户
 * @param {object} user - 用户对象
 */
function setCurrentUser(user) {
    if (user) {
        localStorage.setItem('hm_user', JSON.stringify(user));
        localStorage.setItem('loggedIn', 'true');
    } else {
        localStorage.removeItem('hm_user');
        localStorage.setItem('loggedIn', 'false');
    }
}

/**
 * 检查用户是否已登录
 * @returns {boolean}
 */
function isUserLoggedIn() {
    return localStorage.getItem('loggedIn') === 'true' && getCurrentUser() !== null;
}

/**
 * 退出登录
 */
function logout() {
    localStorage.removeItem('hm_user');
    localStorage.setItem('loggedIn', 'false');
}

/**
 * 检查用户权限
 * @param {string|array} allowedRoles - 允许的角色
 * @returns {boolean}
 */
function checkUserRole(allowedRoles) {
    const user = getCurrentUser();
    if (!user) return false;
    
    const roles = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];
    return roles.includes(user.user_type) || roles.includes(user.role_id);
}

/**
 * 重定向到登录页面
 */
function redirectToLogin() {
    const currentPage = window.location.pathname;
    if (!currentPage.includes('Login_Register.html')) {
        window.location.href = '../Staff/Login_Register.html';
    }
}

/**
 * 格式化日期
 * @param {string|Date} date - 日期
 * @returns {string} 格式化的日期字符串 YYYY-MM-DD
 */
function formatDate(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * 格式化日期时间
 * @param {string|Date} datetime - 日期时间
 * @returns {string} 格式化的日期时间字符串 YYYY-MM-DD HH:MM:SS
 */
function formatDateTime(datetime) {
    const d = new Date(datetime);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

/**
 * 显示加载提示
 * @param {string} elementId - 元素ID
 * @param {string} message - 提示消息
 */
function showLoading(elementId, message = 'Loading...') {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text-secondary);">${message}</div>`;
    }
}

/**
 * Display error message
 * @param {string} elementId - Element ID
 * @param {string} message - Error message
 */
function showError(elementId, message) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `<div style="text-align:center;padding:40px;color:var(--danger);">${message}</div>`;
    }
}

/**
 * Display success message
 * @param {string} message - Success message
 */
function showSuccess(message) {
    alert(message); // Can be replaced with better toast notification
}

/**
 * Handle API errors
 * @param {object} response - API response
 * @param {string} defaultMessage - Default error message
 * @returns {string} Error message
 */
function getErrorMessage(response, defaultMessage = 'Operation failed') {
    if (!response) return 'Network error';
    if (response.error === 'network_error') return 'Network error, please check your connection';
    if (response.error === 'invalid_json') return 'Invalid server response';
    if (response.error) {
        // Convert error code to friendly message
        const errorMessages = {
            'missing_required_fields': 'Please fill in all required fields',
            'invalid_username_length': 'Username must be 3-50 characters',
            'password_too_short': 'Password must be at least 6 characters',
            'username_exists': 'Username already exists',
            'invalid_credentials': 'Invalid username or password',
            'user_not_found': 'User not found',
            'order_not_found': 'Order not found',
            'cannot_cancel': 'This order cannot be cancelled',
            'sold_out': 'Room type is sold out',
            'no_room': 'No available rooms',
            'room_unavailable': 'Selected room is not available',
            'no_order': 'No order history found'
        };
        return errorMessages[response.error] || response.error;
    }
    return defaultMessage;
}

/**
 * 验证邮箱格式
 * @param {string} email - 邮箱地址
 * @returns {boolean}
 */
function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * 验证手机号格式
 * @param {string} phone - 手机号
 * @returns {boolean}
 */
function validatePhone(phone) {
    return /^\d{10,15}$/.test(phone);
}

/**
 * 计算两个日期之间的天数
 * @param {string|Date} startDate - 开始日期
 * @param {string|Date} endDate - 结束日期
 * @returns {number} 天数
 */
function calculateDays(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

/**
 * 更新导航栏active状态
 */
function updateNavActive() {
    document.querySelectorAll('.nav-item').forEach(item => {
        const link = item.querySelector('a');
        if (link && link.href === window.location.href) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

/**
 * 更新导航栏登录状态
 */
function updateNavLoginState() {
    const loggedIn = isUserLoggedIn();
    const loginLink = document.getElementById('login-link');
    const personalLink = document.getElementById('personal-link');
    
    if (loginLink) {
        loginLink.parentElement.style.display = loggedIn ? 'none' : 'block';
    }
    if (personalLink) {
        personalLink.parentElement.style.display = loggedIn ? 'block' : 'none';
    }
}

/**
 * Initialize common page features
 */
function initCommonFeatures() {
    // Initialize login status
    if (localStorage.getItem('loggedIn') === null) {
        localStorage.setItem('loggedIn', 'false');
    }
    
    // Update navigation state
    updateNavActive();
    updateNavLoginState();
    
    // Bind logout event
    const logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
            alert('Logout successful!');
            redirectToLogin();
        });
    }
}

// Auto-initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCommonFeatures);
} else {
    initCommonFeatures();
}
