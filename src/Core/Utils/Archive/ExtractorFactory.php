<?php

namespace DomainSystem\Core\Utils\Archive;

use Exception;

class ExtractorFactory
{
    public static function create(): ExtractorInterface
    {
        if (class_exists('ZipArchive')) {
            return new ZipArchiveExtractor();
        }

        if (class_exists('PharData')) {
            return new PharDataExtractor();
        }

        throw new Exception("Nenhum descompactador disponível no sistema (ZipArchive ou PharData).");
    }
}
