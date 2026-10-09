<?php
namespace DomainSystem\Plugins\radio_online;

use DomainSystem\Core\Contracts\DashboardWidgetProviderInterface;
use DomainSystem\Core\Security\IdentityManager;

class RadioWidgetProvider implements DashboardWidgetProviderInterface
{
    private IdentityManager $identity;

    public function __construct(IdentityManager $identity)
    {
        $this->identity = $identity;
    }

    public function getProviderName(): string
    {
        return 'App Rádio Online';
    }

    public function getAvailableWidgets(): array
    {
        return [
            'radio_player' => [
                'title' => 'Rádio Web Station',
                'description' => 'Player interativo com estações online e personalizadas.'
            ]
        ];
    }

    public function renderWidget(string $widgetId): string
    {
        if ($widgetId !== 'radio_player') {
            return '';
        }

        $userId = $_SESSION['user_id'] ?? 0;
        
        if ($this->identity->userCan($userId, 'radio.listen')) {
            $html = '<div id="radio-widget-container" style="background: linear-gradient(135deg, #1f2937, #111827); color: #38bdf8; padding: 20px; border-radius: 12px; text-align: center; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);">';
            
            // Ícone animado
            $html .= '<div style="font-size: 36px; margin-bottom: 10px;">';
            $html .= '<i class="fas fa-broadcast-tower" id="radio-icon" style="color: #64748b; transition: color 0.3s;"></i>';
            $html .= '</div>';
            
            // Combobox de Estações Rápidas
            $html .= '<div style="margin-bottom: 8px;">';
            $html .= '<select id="radio-station-select" style="width: 100%; margin-bottom: 5px; background: #0f172a; color: #38bdf8; border: 1px solid #334155; padding: 8px; border-radius: 4px; outline: none; cursor: pointer; font-size: 13px;">';
            $html .= '<option value="https://stream.live.vc.bbcmedia.co.uk/bbc_world_service">BBC World Service (News)</option>';
            $html .= '<option value="https://icecast.vrtcdn.be/stubru-high.mp3">Studio Brussel (Rock/Alt)</option>';
            $html .= '<option value="https://jazz.streamr.ru/jazz-128.mp3">Smooth Jazz 24/7</option>';
            $html .= '<option value="https://icecast.omroep.nl/radio1-bb-mp3">NPO Radio 1 (Holanda)</option>';
            $html .= '<option value="custom">-- Digitar URL Customizada --</option>';
            $html .= '</select>';
            $html .= '<button id="radio-delete-btn" style="display: none; background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.5); width: 100%; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Excluir Estação Salva"><i class="fas fa-trash"></i> Remover esta rádio da memória</button>';
            $html .= '</div>';
            
            // Input de Texto para URL Personalizada e Botão Salvar
            $html .= '<div id="radio-custom-group" style="margin-bottom: 15px; display: none;">';
            $html .= '<div style="display: flex; gap: 5px;">';
            $html .= '<input type="url" id="radio-custom-url" placeholder="Cole o link HTTPS aqui..." style="flex: 1; min-width: 0; background: #0f172a; color: #fff; border: 1px solid #38bdf8; padding: 8px; border-radius: 4px; outline: none; font-size: 12px;" value="" />';
            $html .= '<button id="radio-save-btn" style="background: #38bdf8; color: #0f172a; border: none; width: 36px; height: 35px; flex-shrink: 0; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;" title="Salvar Estação"><i class="fas fa-plus"></i></button>';
            $html .= '</div>';
            $html .= '</div>';

            // Controles de Play/Pause e Volume
            $html .= '<div style="display: flex; justify-content: center; align-items: center; gap: 15px;">';
            $html .= '<button id="radio-play-btn" style="background: #38bdf8; color: #0f172a; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); transition: transform 0.1s;"><i class="fas fa-play"></i></button>';
            
            $html .= '<div style="display: flex; align-items: center; gap: 5px; color: #94a3b8; font-size: 12px;">';
            $html .= '<i class="fas fa-volume-down"></i>';
            $html .= '<input type="range" id="radio-volume" min="0" max="1" step="0.1" value="0.5" style="width: 80px;">';
            $html .= '<i class="fas fa-volume-up"></i>';
            $html .= '</div>';
            
            $html .= '</div>';
            
            // Texto de Status com Animação (Marquee Digital)
            $html .= '<style>
                @keyframes radioMarquee {
                    0%   { transform: translateX(100%); }
                    100% { transform: translateX(-100%); }
                }
                .radio-display-screen {
                    background: #020617; 
                    color: #10b981; 
                    font-family: monospace; 
                    font-size: 11px; 
                    margin-top: 15px; 
                    padding: 5px; 
                    border-radius: 4px; 
                    border: 1px solid #334155; 
                    overflow: hidden; 
                    position: relative;
                    height: 20px;
                }
                .radio-marquee-text {
                    position: absolute;
                    white-space: nowrap;
                    animation: radioMarquee 7s linear infinite;
                    will-change: transform;
                }
            </style>';
            
            $html .= '<div class="radio-display-screen">';
            $html .= '<div id="radio-status" class="radio-marquee-text">SISTEMA PRONTO. AGUARDANDO SINTONIA.</div>';
            $html .= '</div>';
            
            // Audio tag invisível
            $html .= '<audio id="radio-audio-element" preload="none"></audio>';
            
            $html .= '</div>';
            
            // The magic to make it ALIVE (Client-side JS)
            $html .= '<script>
                (function() {
                    let audio = document.getElementById("radio-audio-element");
                    let btn = document.getElementById("radio-play-btn");
                    let select = document.getElementById("radio-station-select");
                    let customGroup = document.getElementById("radio-custom-group");
                    let customUrlInput = document.getElementById("radio-custom-url");
                    let saveBtn = document.getElementById("radio-save-btn");
                    let volume = document.getElementById("radio-volume");
                    let icon = document.getElementById("radio-icon");
                    let status = document.getElementById("radio-status");
                    let deleteBtn = document.getElementById("radio-delete-btn");
                    
                    let isPlaying = false;

                    function updateDisplay(text) {
                        status.innerText = text;
                        // Reset animation to re-trigger
                        status.style.animation = "none";
                        void status.offsetWidth; 
                        status.style.animation = "radioMarquee 7s linear infinite";
                    }

                    if(audio && btn && select && customUrlInput) {
                        
                        // Carregar estações salvas do LocalStorage
                        let savedStations = JSON.parse(localStorage.getItem("domain_radio_stations") || "[]");
                        savedStations.forEach(url => {
                            let option = document.createElement("option");
                            option.value = url;
                            option.text = "⭐ " + url.replace("https://", "").replace("http://", "").substring(0, 25) + "...";
                            select.insertBefore(option, select.lastElementChild);
                        });

                        // Handler de Volume
                        volume.addEventListener("input", (e) => {
                            audio.volume = e.target.value;
                        });
                        
                        // Lógica de Sincronia entre Combobox e Input Text
                        select.addEventListener("change", () => {
                            if (deleteBtn) {
                                if (savedStations.includes(select.value)) {
                                    deleteBtn.style.display = "block";
                                } else {
                                    deleteBtn.style.display = "none";
                                }
                            }
                            
                            if (select.value === "custom") {
                                customGroup.style.display = "block";
                                customUrlInput.value = "";
                                customUrlInput.focus();
                            } else {
                                customGroup.style.display = "none";
                                customUrlInput.value = select.value;
                            }
                            
                            if(isPlaying) {
                                audio.src = select.value === "custom" ? customUrlInput.value : select.value;
                                if(audio.src) {
                                    audio.play();
                                    updateDisplay("SINTONIZANDO: " + select.options[select.selectedIndex].text.toUpperCase());
                                }
                            }
                        });

                        // Salvar Estação no LocalStorage
                        saveBtn.addEventListener("click", () => {
                            let url = customUrlInput.value.trim();
                            if (url && !savedStations.includes(url)) {
                                savedStations.push(url);
                                localStorage.setItem("domain_radio_stations", JSON.stringify(savedStations));
                                
                                let option = document.createElement("option");
                                option.value = url;
                                option.text = "⭐ " + url.replace("https://", "").replace("http://", "").substring(0, 25) + "...";
                                select.insertBefore(option, select.lastElementChild);
                                select.value = url;
                                customGroup.style.display = "none";
                                updateDisplay("ESTAÇÃO SALVA NA MEMÓRIA!");
                            }
                        });

                        if (deleteBtn) {
                            deleteBtn.addEventListener("click", () => {
                                let url = select.value;
                                if (!url || !savedStations.includes(url)) return;
                                
                                savedStations = savedStations.filter(s => s !== url);
                                localStorage.setItem("domain_radio_stations", JSON.stringify(savedStations));
                                
                                for (let i = 0; i < select.options.length; i++) {
                                    if (select.options[i].value === url) {
                                        select.remove(i);
                                        break;
                                    }
                                }
                                
                                select.selectedIndex = 0;
                                select.dispatchEvent(new Event("change"));
                                updateDisplay("ESTAÇÃO EXCLUÍDA DA MEMÓRIA.");
                            });
                        }

                        btn.addEventListener("click", () => {
                            let targetUrl = (select.value === "custom") ? customUrlInput.value.trim() : select.value;
                            
                            if (isPlaying) {
                                audio.pause();
                                audio.src = ""; // Corta o buffer de download
                                isPlaying = false;
                                btn.innerHTML = \'<i class="fas fa-play"></i>\';
                                icon.style.color = "#64748b";
                                icon.classList.remove("fa-fade");
                                updateDisplay("SISTEMA PARADO.");
                            } else {
                                if (!targetUrl) {
                                    updateDisplay("ERRO: COLOQUE UMA URL VÁLIDA!");
                                    return;
                                }
                                audio.src = targetUrl;
                                audio.volume = volume.value;
                                audio.play();
                                isPlaying = true;
                                btn.innerHTML = \'<i class="fas fa-stop"></i>\';
                                icon.style.color = "#38bdf8";
                                icon.classList.add("fa-fade");
                                let stationName = select.value === "custom" ? "URL CUSTOMIZADA" : select.options[select.selectedIndex].text;
                                updateDisplay("CONECTANDO: " + stationName.toUpperCase());
                            }
                        });
                        
                        audio.addEventListener("playing", () => {
                            updateDisplay("FM STEREO AO VIVO: " + (select.value === "custom" ? "TRANSMISSÃO PERSONALIZADA" : select.options[select.selectedIndex].text.toUpperCase()));
                        });
                        
                        audio.addEventListener("error", () => {
                            updateDisplay("ERRO DE SINAL (VERIFIQUE CORS OU URL)");
                            isPlaying = false;
                            btn.innerHTML = \'<i class="fas fa-play"></i>\';
                            icon.style.color = "#ef4444";
                            icon.classList.remove("fa-fade");
                        });
                    }
                })();
            </script>';
            
            return $html;
        }

        $html = '<div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; font-size: 14px; text-align: center; border: 1px solid #f87171;">';
        $html .= '<i class="fas fa-lock"></i> Você não tem o Privilégio <strong>radio.listen</strong> para ouvir streaming.';
        $html .= '</div>';
        return $html;
    }
}
