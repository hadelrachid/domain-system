<?php
$f = "themes/admin/layout.php";
$code = file_get_contents($f);

$osScript = <<<'HTML'
    <!-- OS Notification System -->
    <div id="os-toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 99999;"></div>
    <script>
        window.OS = window.OS || {};
        window.OS.notify = function(message, type = 'success') {
            const container = document.getElementById('os-toast-container');
            if (!container) return;
            
            const toast = document.createElement('div');
            const bg = type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#17a2b8');
            const icon = type === 'success' ? '✅ ' : (type === 'error' ? '❌ ' : 'ℹ️ ');
            
            toast.style = `background: ${bg}; color: white; padding: 16px 24px; border-radius: 6px; margin-bottom: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); opacity: 0; transform: translateX(50px); transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55); font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 10px; border-left: 4px solid rgba(255,255,255,0.3);`;
            toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
            
            container.appendChild(toast);
            
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            });
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(50px)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };
    </script>
</body>
HTML;

$code = str_replace('</body>', $osScript, $code);
file_put_contents($f, $code);
echo "Layout updated!\n";
