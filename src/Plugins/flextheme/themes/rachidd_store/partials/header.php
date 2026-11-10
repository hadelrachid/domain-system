<?php
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
?>
<!-- Top Utility Bar (Marketplace Internacional) -->
<div style="background: #06090e; border-bottom: 1px solid rgba(212, 175, 55, 0.15); font-size: 12px; color: #94a3b8; padding: 7px 0;">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <span style="color: #F0D060; font-weight: 600;">✨ O Gênio das Ofertas Internacionais</span>
            <span style="opacity: 0.5;">|</span>
            <span>🚚 Envio rastreado para todo o Brasil e Mundo</span>
        </div>
        <div style="display: flex; align-items: center; gap: 16px;">
            <!-- Seletor Rápido de Idioma / Moeda -->
            <div style="display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.05); padding: 3px 10px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); cursor: pointer;" title="Alterar Região e Moeda">
                <span>🌐</span>
                <span style="color: #fff; font-weight: 600;">BRL (R$) / PT</span>
                <span style="font-size: 10px; opacity: 0.6;">▼</span>
            </div>
            <a href="<?= $baseUrl ?>/loja" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#cbd5e1'">Rastrear Pedido</a>
            <a href="<?= $baseUrl ?>/admin/dropship" style="color: #D4AF37; text-decoration: none; font-weight: 600;">Painel Seller</a>
        </div>
    </div>
</div>

<!-- Main Header -->
<header style="padding: 16px 0; border-bottom: 1px solid rgba(212, 175, 55, 0.2); position: sticky; top: 0; z-index: 1000; background: rgba(11, 15, 23, 0.95); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; gap: 24px;">
        
        <!-- Logo Lâmpada de Aladim -->
        <a href="<?= $baseUrl ?>/" style="display: flex; align-items: center; gap: 12px; text-decoration: none; flex-shrink: 0;">
            <img src="<?= $baseUrl ?>/assets/img/site-home/logo-lampada.svg" alt="RachidD Lâmpada de Aladim" style="height: 52px; width: auto; filter: drop-shadow(0 0 12px rgba(212, 175, 55, 0.5)); transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
            <div>
                <div style="font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px; color: #fff; line-height: 1.1;">
                    Rachid<span style="background: linear-gradient(135deg, #FFE082 0%, #D4AF37 50%, #B8962E 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">D.com</span>
                </div>
                <div style="font-size: 10px; color: #D4AF37; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px;">
                    Marketplace & Dropship
                </div>
            </div>
        </a>

        <!-- Global Marketplace Search Bar -->
        <form action="<?= $baseUrl ?>/loja" method="GET" style="flex: 1; max-width: 580px; display: flex; position: relative;">
            <input type="text" name="q" placeholder="O que o Gênio pode encontrar para você hoje?..." style="width: 100%; background: rgba(0,0,0,0.5); border: 2px solid rgba(212, 175, 55, 0.35); border-right: none; color: #fff; padding: 12px 18px; border-radius: 8px 0 0 8px; font-size: 14px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#D4AF37'" onblur="this.style.borderColor='rgba(212,175,55,0.35)'">
            <button type="submit" style="background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); border: none; color: #0b0f17; padding: 0 24px; border-radius: 0 8px 8px 0; font-size: 14px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                <span>🔍</span>
                <span>Buscar</span>
            </button>
        </form>

        <!-- Right Quick Actions -->
        <div style="display: flex; align-items: center; gap: 16px; flex-shrink: 0;">
            <a href="<?= $baseUrl ?>/#desejos" style="display: flex; align-items: center; gap: 8px; color: #F0D060; text-decoration: none; font-size: 13px; font-weight: 700; background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3); padding: 7px 14px; border-radius: 20px; transition: all 0.2s;" onmouseover="this.style.background='rgba(212,175,55,0.2)'" onmouseout="this.style.background='rgba(212,175,55,0.1)'">
                <span>✨</span>
                <span>Desejos do Dia</span>
            </a>
            <a href="<?= $baseUrl ?>/loja" style="display: flex; align-items: center; gap: 8px; color: #fff; text-decoration: none; font-size: 13px; font-weight: 700; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; padding: 8px 18px; border-radius: 20px; box-shadow: 0 4px 14px rgba(212, 175, 55, 0.35); transition: transform 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <span>🛍️</span>
                <span>Ver Loja</span>
            </a>
        </div>
    </div>

    <!-- Category Navigation Strip -->
    <nav style="margin-top: 14px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06);">
        <div class="container" style="display: flex; gap: 20px; overflow-x: auto; scrollbar-width: none; font-size: 13px; font-weight: 600; white-space: nowrap;">
            <a href="<?= $baseUrl ?>/loja" style="color: #F0D060; text-decoration: none;">🔥 Super Ofertas</a>
            <a href="<?= $baseUrl ?>/loja?cat=Tecnologia" style="color: #cbd5e1; text-decoration: none;">Tecnologia & Gadgets</a>
            <a href="<?= $baseUrl ?>/loja?cat=Smartwatches" style="color: #cbd5e1; text-decoration: none;">Smartwatches & Áudio</a>
            <a href="<?= $baseUrl ?>/loja?cat=Casa" style="color: #cbd5e1; text-decoration: none;">Casa Inteligente</a>
            <a href="<?= $baseUrl ?>/loja?cat=Acessorios" style="color: #cbd5e1; text-decoration: none;">Acessórios Virais</a>
            <a href="<?= $baseUrl ?>/loja?cat=Beleza" style="color: #cbd5e1; text-decoration: none;">Saúde & Cuidados</a>
            <a href="<?= $baseUrl ?>/loja" style="color: #cbd5e1; text-decoration: none;">Mais Vendidos</a>
        </div>
    </nav>
</header>
