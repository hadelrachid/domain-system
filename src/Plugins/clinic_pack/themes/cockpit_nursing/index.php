<?php
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;
use DomainSystem\Plugins\clinic_pack\Views\NursingCockpitView;
(new NursingCockpitView($__data__ ?? (get_defined_vars() + $_SESSION)))->render();
