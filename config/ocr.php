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
    | Poppler pdftoppm Binary Path
    |--------------------------------------------------------------------------
    |
    | Path to the pdftoppm executable for rasterizing PDF pages to images.
    |
    */
    'pdftoppm_path' => env('OCR_PDFTOPPM_PATH', 'pdftoppm'),

    /*
    |--------------------------------------------------------------------------
    | Poppler pdftotext Binary Path
    |--------------------------------------------------------------------------
    |
    | Path to the pdftotext executable for native PDF stream text extraction.
    |
    */
    'pdftotext_path' => env('OCR_PDFTOTEXT_PATH', 'pdftotext'),

    /*
    |--------------------------------------------------------------------------
    | Poppler pdfinfo Binary Path
    |--------------------------------------------------------------------------
    |
    | Path to the pdfinfo executable for inspecting PDF page counts and metadata.
    |
    */
    'pdfinfo_path' => env('OCR_PDFINFO_PATH', 'pdfinfo'),

    /*
    |--------------------------------------------------------------------------
    | PDF Rasterization DPI
    |--------------------------------------------------------------------------
    |
    | Resolution (dots per inch) for converting PDF pages to images.
    | Default is 200 DPI, offering an optimal trade-off between OCR accuracy and memory.
    |
    */
    'dpi' => (int) env('OCR_DPI', 200),

    /*
    |--------------------------------------------------------------------------
    | Maximum Pages per PDF
    |--------------------------------------------------------------------------
    |
    | Maximum number of pages to process per PDF to prevent resource exhaustion.
    |
    */
    'max_pages' => (int) env('OCR_MAX_PAGES', 50),

    /*
    |--------------------------------------------------------------------------
    | Native Extraction Timeout (seconds per page / step)
    |--------------------------------------------------------------------------
    */
    'native_timeout' => (int) env('OCR_NATIVE_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Conversion Timeout (seconds per page rasterization)
    |--------------------------------------------------------------------------
    */
    'conversion_timeout' => (int) env('OCR_CONVERSION_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Minimum Characters for Valid Native Page Text
    |--------------------------------------------------------------------------
    |
    | Pages with fewer significant characters than this threshold will be
    | treated as scanned / image-based and processed via Tesseract OCR.
    |
    */
    'min_native_text_length' => (int) env('OCR_MIN_NATIVE_TEXT_LENGTH', 15),

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
