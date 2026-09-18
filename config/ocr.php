<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OCR Feature Enabled
    |--------------------------------------------------------------------------
    |
    | Global switch to enable or disable automated OCR document processing.
    |
    */
    'enabled' => env('OCR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | OCR Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "tesseract", "testing"
    |
    */
    'driver' => env('OCR_DRIVER', 'tesseract'),

    /*
    |--------------------------------------------------------------------------
    | Tesseract Binary Path
    |--------------------------------------------------------------------------
    |
    | Path to the tesseract executable on the host system.
    |
    */
    'binary_path' => env('OCR_BINARY_PATH', 'tesseract'),

    /*
    |--------------------------------------------------------------------------
    | OCR Languages
    |--------------------------------------------------------------------------
    |
    | Languages to pass to the OCR engine (e.g. ['fra', 'eng']).
    |
    */
    'languages' => explode(',', env('OCR_LANGUAGES', 'fra,eng')),

    /*
    |--------------------------------------------------------------------------
    | Timeout in Seconds
    |--------------------------------------------------------------------------
    |
    | Maximum execution time allowed for extracting text from a single file.
    |
    */
    'timeout' => (int) env('OCR_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size (in KB)
    |--------------------------------------------------------------------------
    |
    | Maximum file size eligible for OCR processing (default: 25MB = 25600 KB).
    |
    */
    'max_file_size_kb' => (int) env('OCR_MAX_FILE_SIZE_KB', 25600),

    /*
    |--------------------------------------------------------------------------
    | Supported File Extensions
    |--------------------------------------------------------------------------
    |
    | Extensions compatible with OCR text extraction.
    |
    */
    'supported_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],

    /*
    |--------------------------------------------------------------------------
    | Skipped File Extensions
    |--------------------------------------------------------------------------
    |
    | Extensions that bypass OCR processing without failing (marked as skipped).
    |
    */
    'skipped_extensions' => ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip'],
];
