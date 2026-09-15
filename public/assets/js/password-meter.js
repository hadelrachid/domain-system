function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    var svg = btn.querySelector('svg');
    if (input.type === 'password') {
        input.type = 'text';
        if (svg) {
            svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            // Se for ícone fontawesome
            btn.classList.remove('fa-eye');
            btn.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if (svg) {
            svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        } else {
            btn.classList.remove('fa-eye-slash');
            btn.classList.add('fa-eye');
        }
    }
}

function analyzePasswordStrength(pwd, suffix) {
    const container = document.getElementById('pwd-meter-' + suffix);
    const bar = document.getElementById('pwd-bar-' + suffix);
    const text = document.getElementById('pwd-text-' + suffix);
    const hint = document.getElementById('pwd-hint-' + suffix);
    
    if (!container) return; // Segurança caso o meter não exista na tela

    if (!pwd) {
        container.style.display = 'none';
        return;
    }
    container.style.display = 'block';
    
    let score = 0;
    let missing = [];
    
    if (pwd.length >= 8) score += 25; else missing.push('8+ caracteres');
    if (/[A-Z]/.test(pwd)) score += 25; else missing.push('Letra maiúscula');
    if (/[a-z]/.test(pwd)) score += 25; else missing.push('Letra minúscula');
    if (/[0-9]/.test(pwd) && /[^a-zA-Z0-9]/.test(pwd)) score += 25; else missing.push('Número e Símbolo');
    
    bar.style.width = Math.max(10, score) + '%';
    
    if (score < 50) {
        bar.style.background = '#ef4444'; // Vermelho
        text.style.color = '#ef4444';
        text.innerText = 'Ruim';
    } else if (score === 50) {
        bar.style.background = '#f97316'; // Alaranjado
        text.style.color = '#f97316';
        text.innerText = 'Bom';
    } else if (score === 75) {
        bar.style.background = '#3b82f6'; // Azul
        text.style.color = '#3b82f6';
        text.innerText = 'Muito Bom';
    } else {
        bar.style.background = '#10b981'; // Verde
        text.style.color = '#10b981';
        text.innerText = 'Ótimo';
    }
    
    if (missing.length > 0) {
        hint.innerText = 'Falta: ' + missing.join(', ');
    } else {
        hint.innerText = 'Senha atende aos padrões!';
    }
}

function generatePasswordAndAnalyze(inputId, meterSuffix) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+';
    let pwd = '';
    // Forçar requisitos mínimos
    pwd += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 26)];
    pwd += 'abcdefghijklmnopqrstuvwxyz'[Math.floor(Math.random() * 26)];
    pwd += '0123456789'[Math.floor(Math.random() * 10)];
    pwd += '!@#$%^&*()_+'[Math.floor(Math.random() * 12)];
    for (let i = 0; i < 8; i++) {
        pwd += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    // Embaralhar
    pwd = pwd.split('').sort(() => 0.5 - Math.random()).join('');
    
    const input = document.getElementById(inputId);
    input.type = 'text'; // Mostrar para o usuário ver
    input.value = pwd;
    analyzePasswordStrength(pwd, meterSuffix);
}
