<?php namespace DomainSystem\SystemApps\settings\Contracts; interface SettingRepositoryInterface { public function getAll(): array; public function upsert(string $key, string $value): void; }
