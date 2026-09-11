<?php
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;
use DomainSystem\Plugins\clinic_pack\Views\SecretaryCockpitView;
(new SecretaryCockpitView($__data__ ?? (get_defined_vars() + $_SESSION)))->render();
