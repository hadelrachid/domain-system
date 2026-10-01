<div class="terminal-container" style="background: #1a1b1e; color: #0lff00; padding: 20px; font-family: monospace; border-radius: 5px; min-height: 400px;">
    <h2><i class="fa fa-terminal"></i> Domain-System Web Terminal</h2>
    <p>Digite <code style="color: #ff0;">php cockpit help</code> para ver os comandos ou qualquer comando bash.</p>

    <div id="terminal-output" style="margin-bottom: 15px; white-space: pre;overflow-y: auto; max-height: 300px;"></div>

    <div style="display: flex;">
        <span style="color: #0lff00; margin-right: 10px;">cockpit></span>
        <input type="text" id="terminal-input" style="flex: 1; background: transparent; border: none; color: #fff; font-family: monospace; outline: none;" autofocus>
    </div>
</div>

<script>
document.getElementById('terminal-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const cmd = this.value;
        this.value = '';
        const outputDiv = document.getElementById('terminal-output');
        outputDiv.innerHTML += '<div><span style="color: #00ff00;">cockpit> </span>' + cmd + '</div>';

        fetch('/admin/terminal/execute', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ command: cmd })
        }).then(r => r.json()).then(data => {
            outputDiv.innerHTML += '<div style="color: #ccc; margin-bottom: 10px;">' + data.output + '</div>';
            outputDiv.scrollTop = outputDiv.scrollHeight;
        }).catch(err => {
            outputDiv.innerHTML += '<div style="color: red; margin-bottom: 10px;">Erro: ' + err + '</div>';
        });
    }
});
</script>
