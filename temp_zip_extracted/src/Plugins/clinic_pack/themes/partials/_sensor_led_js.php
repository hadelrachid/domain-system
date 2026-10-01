/**
 * SystemSensor - Abstração de Frontend (LEDs de Resposta)
 * Atua como um sensor de respostas do sistema. Renderiza os avisos visuais 
 * dinamicamente na tela sem depender de HTML hardcoded.
 */
class SystemSensor {
    static playSound(type = 'info') {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gainNode = ctx.createGain();
            osc.connect(gainNode);
            gainNode.connect(ctx.destination);
            
            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
                osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.1); // E5
                gainNode.gain.setValueAtTime(0.1, ctx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            } else {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(440, ctx.currentTime); // A4
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
                gainNode.gain.setValueAtTime(0.1, ctx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            }
        } catch(e) {
            console.log('Audio disabled ou não suportado');
        }
    }

    static init() {
        if (!document.getElementById('system-sensor-container')) {
            const container = document.createElement('div');
            container.id = 'system-sensor-container';
            container.style.cssText = `
                position: fixed;
                bottom: 30px;
                right: 30px;
                display: flex;
                flex-direction: column;
                gap: 10px;
                z-index: 999999;
            `;
            document.body.appendChild(container);
        }
    }

    static pulse(type, message) {
        this.init();
        this.playSound(type);
        const container = document.getElementById('system-sensor-container');
        
        const led = document.createElement('div');
        let color = '#64748b'; // default
        let icon = 'fas fa-info-circle';
        
        if (type === 'success') { color = '#10b981'; icon = 'fas fa-check-circle'; }
        if (type === 'error') { color = '#ef4444'; icon = 'fas fa-exclamation-circle'; }
        if (type === 'info') { color = '#3b82f6'; icon = 'fas fa-bell'; }
        if (type === 'warning') { color = '#f59e0b'; icon = 'fas fa-exclamation-triangle'; }

        led.style.cssText = `
            background: #1e293b;
            color: white;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border-left: 4px solid ${color};
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            min-width: 280px;
        `;
        
        led.innerHTML = `<i class="${icon}" style="color: ${color}"></i> <span>${message}</span>`;
        container.appendChild(led);

        // Animate In
        setTimeout(() => {
            led.style.opacity = '1';
            led.style.transform = 'translateX(0)';
        }, 10);

        // Animate Out & Destroy
        setTimeout(() => {
            led.style.opacity = '0';
            led.style.transform = 'translateX(50px)';
            setTimeout(() => led.remove(), 400);
        }, 4000);
    }

    static success(message) { this.pulse('success', message); }
    static error(message) { this.pulse('error', message); }
    static info(message) { this.pulse('info', message); }
    static warning(message) { this.pulse('warning', message); }
}

// Retro-compatibilidade com o antigo showToast e showAlert
window.showToast = function(msg, type = 'success') { SystemSensor.pulse(type, msg); };
window.showAlert = function(type, msg) { 
    // Extrai mensagem se vier com HTML do FontAwesome (ex: showAlert('success', '<i...</i> texto'))
    const temp = document.createElement('div');
    temp.innerHTML = msg;
    const cleanMsg = temp.innerText.trim();
    SystemSensor.pulse(type, cleanMsg || msg); 
};
