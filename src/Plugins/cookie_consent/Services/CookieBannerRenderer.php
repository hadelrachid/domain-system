<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\cookie_consent\Services;

/**
 * Renderizador do Banner de Consentimento de Cookies em conformidade com Google Consent Mode v2, LGPD e GDPR.
 */
class CookieBannerRenderer
{
    public function render(string $baseUrl = ''): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        $privacyUrl = $baseUrl . '/privacidade';
        $termsUrl = $baseUrl . '/termos';

        $bannerHtml = <<<HTML
<!-- Banner de Consentimento de Cookies & Google Consent Mode v2 (RachidD Store / DomainSystem) -->
<div id="rachidd_cookie_banner" role="dialog" aria-modal="true" aria-label="Aviso de Privacidade e Cookies" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; z-index: 999999; background: linear-gradient(180deg, rgba(12, 17, 29, 0.98) 0%, rgba(6, 9, 14, 0.99) 100%); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-top: 1.5px solid rgba(212, 175, 55, 0.45); box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.7); padding: 18px 24px; color: #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13.5px;">
    <div style="max-width: 1220px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 18px;">
        <!-- Texto explicativo e links legais -->
        <div style="flex: 1; min-width: 300px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 18px;">🍪</span>
                <strong style="color: #fff; font-size: 15px; letter-spacing: 0.2px;">Controle de Privacidade &amp; Cookies</strong>
                <span style="font-size: 10.5px; font-weight: 700; color: #F0D060; background: rgba(212, 175, 55, 0.15); border: 1px solid rgba(212, 175, 55, 0.4); padding: 2px 7px; border-radius: 99px; text-transform: uppercase;">
                    LGPD • GDPR • Google Consent v2
                </span>
            </div>
            <p style="margin: 0; line-height: 1.55; color: #94a3b8; font-size: 13px;">
                Utilizamos cookies essenciais para o funcionamento seguro do site e tecnologias semelhantes para personalizar conteúdo, aprimorar sua experiência e analisar o tráfego da loja. Consulte nossa 
                <a href="{$privacyUrl}" target="_blank" rel="noopener noreferrer" style="color: #45f3ff; text-decoration: underline; font-weight: 600;">Política de Privacidade</a> e os 
                <a href="{$termsUrl}" target="_blank" rel="noopener noreferrer" style="color: #45f3ff; text-decoration: underline; font-weight: 600;">Termos de Uso</a> para saber mais.
            </p>
        </div>

        <!-- Botões de Aceite, Recusa e Personalização -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button type="button" id="rachidd_cookie_btn_customize" onclick="window.RachidDCookieConsent.togglePreferencesModal()" style="padding: 10px 16px; border-radius: 8px; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.2); color: #cbd5e1; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s;">
                ⚙️ Personalizar
            </button>
            <button type="button" id="rachidd_cookie_btn_reject" onclick="window.RachidDCookieConsent.rejectAll()" style="padding: 10px 18px; border-radius: 8px; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.2s;">
                ✕ Recusar Opcionais
            </button>
            <button type="button" id="rachidd_cookie_btn_accept" onclick="window.RachidDCookieConsent.acceptAll()" style="padding: 10px 22px; border-radius: 8px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); border: none; color: #0b0f17; font-weight: 800; font-size: 13.5px; cursor: pointer; box-shadow: 0 4px 14px rgba(212, 175, 55, 0.35); transition: all 0.2s;">
                ✓ Aceitar Todos
            </button>
        </div>
    </div>
</div>

<!-- Modal de Preferências Detalhadas de Cookies (LGPD / GDPR) -->
<div id="rachidd_cookie_modal" role="dialog" aria-modal="true" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000000; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(6px); align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #0d121d; border: 1px solid rgba(212, 175, 55, 0.4); border-radius: 14px; max-width: 580px; width: 100%; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.8); color: #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">🛡️</span>
                <strong style="color: #fff; font-size: 17px;">Preferências de Consentimento de Cookies</strong>
            </div>
            <button type="button" onclick="window.RachidDCookieConsent.togglePreferencesModal()" style="background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; padding: 4px;">✕</button>
        </div>

        <p style="font-size: 13px; color: #94a3b8; margin: 0 0 16px 0; line-height: 1.5;">
            Personalize suas preferências de cookies. Os cookies necessários não podem ser desativados, pois garantem a segurança de checkout, login e navegação.
        </p>

        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
            <!-- Categoria: Necessários -->
            <div style="padding: 12px 14px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <div>
                    <strong style="color: #fff; font-size: 13.5px;">1. Cookies Estritamente Necessários</strong>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Sessão, login, carrinho, checkout seguro e prevenção contra fraudes.</div>
                </div>
                <span style="font-size: 11px; font-weight: 700; color: #00d284; background: rgba(0,210,132,0.12); border: 1px solid rgba(0,210,132,0.3); padding: 3px 8px; border-radius: 6px; white-space: nowrap;">Sempre Ativo</span>
            </div>

            <!-- Categoria: Analíticos -->
            <div style="padding: 12px 14px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <div style="flex: 1;">
                    <strong style="color: #fff; font-size: 13.5px;">2. Desempenho &amp; Estatísticas (Analytics)</strong>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Google Analytics e métricas para entender como os visitantes usam a loja.</div>
                </div>
                <input type="checkbox" id="rachidd_pref_analytics" checked style="width: 18px; height: 18px; accent-color: #F0D060; cursor: pointer;">
            </div>

            <!-- Categoria: Publicidade & Marketing -->
            <div style="padding: 12px 14px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <div style="flex: 1;">
                    <strong style="color: #fff; font-size: 13.5px;">3. Publicidade &amp; Google Ads (Marketing)</strong>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Personalização de anúncios relevantes e mensuração de campanhas.</div>
                </div>
                <input type="checkbox" id="rachidd_pref_marketing" checked style="width: 18px; height: 18px; accent-color: #F0D060; cursor: pointer;">
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
            <button type="button" onclick="window.RachidDCookieConsent.saveCustomPreferences()" style="padding: 10px 18px; border-radius: 8px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); border: none; color: #0b0f17; font-weight: 800; font-size: 13px; cursor: pointer;">
                Salvar Minhas Preferências
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    // 1. Google Consent Mode v2 Bootstrap
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = window.gtag || gtag;

    // Define estado padrão bloqueado até o consentimento do visitante (Google Consent Mode v2)
    var savedConsent = null;
    try {
        savedConsent = localStorage.getItem('rachidd_cookie_consent');
    } catch(e) {}

    if (!savedConsent) {
        gtag('consent', 'default', {
            'ad_storage': 'denied',
            'analytics_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
            'wait_for_update': 500
        });
    }

    // 2. Controlador de Consentimento
    window.RachidDCookieConsent = {
        key: 'rachidd_cookie_consent',

        init: function() {
            var consent = this.getConsent();
            if (!consent) {
                this.showBanner();
            } else {
                this.applyConsent(consent.analytics, consent.marketing);
            }
        },

        showBanner: function() {
            var b = document.getElementById('rachidd_cookie_banner');
            if (b) {
                b.style.display = 'block';
            }
        },

        hideBanner: function() {
            var b = document.getElementById('rachidd_cookie_banner');
            if (b) {
                b.style.display = 'none';
            }
        },

        open: function() {
            this.showBanner();
        },

        togglePreferencesModal: function() {
            var m = document.getElementById('rachidd_cookie_modal');
            if (!m) return;
            if (m.style.display === 'none' || !m.style.display) {
                m.style.display = 'flex';
                var cur = this.getConsent();
                if (cur) {
                    var a = document.getElementById('rachidd_pref_analytics');
                    var mk = document.getElementById('rachidd_pref_marketing');
                    if (a) a.checked = !!cur.analytics;
                    if (mk) mk.checked = !!cur.marketing;
                }
            } else {
                m.style.display = 'none';
            }
        },

        acceptAll: function() {
            this.saveAndApply(true, true);
        },

        rejectAll: function() {
            this.saveAndApply(false, false);
        },

        saveCustomPreferences: function() {
            var a = document.getElementById('rachidd_pref_analytics');
            var mk = document.getElementById('rachidd_pref_marketing');
            var allowAnalytics = a ? a.checked : false;
            var allowMarketing = mk ? mk.checked : false;
            this.togglePreferencesModal();
            this.saveAndApply(allowAnalytics, allowMarketing);
        },

        saveAndApply: function(analytics, marketing) {
            var data = {
                status: (analytics && marketing) ? 'accepted' : ((!analytics && !marketing) ? 'rejected' : 'custom'),
                analytics: !!analytics,
                marketing: !!marketing,
                timestamp: new Date().toISOString()
            };

            try {
                localStorage.setItem(this.key, JSON.stringify(data));
            } catch(e) {}

            // Cookie com validade de 1 ano para compatibilidade server-side
            var expires = new Date();
            expires.setFullYear(expires.getFullYear() + 1);
            document.cookie = 'rachidd_cookie_consent=' + encodeURIComponent(data.status) + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';

            this.applyConsent(analytics, marketing);
            this.hideBanner();
        },

        applyConsent: function(analytics, marketing) {
            var updatePayload = {
                'analytics_storage': analytics ? 'granted' : 'denied',
                'ad_storage': marketing ? 'granted' : 'denied',
                'ad_user_data': marketing ? 'granted' : 'denied',
                'ad_personalization': marketing ? 'granted' : 'denied'
            };

            if (typeof window.gtag === 'function') {
                window.gtag('consent', 'update', updatePayload);
            }

            // Notifica listeners no frontend
            window.dispatchEvent(new CustomEvent('rachidd:consent_updated', { detail: updatePayload }));
        },

        getConsent: function() {
            try {
                var raw = localStorage.getItem(this.key);
                if (raw) {
                    return JSON.parse(raw);
                }
            } catch(e) {}
            return null;
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { window.RachidDCookieConsent.init(); });
    } else {
        window.RachidDCookieConsent.init();
    }
})();
</script>
HTML;

        return $bannerHtml;
    }
}
