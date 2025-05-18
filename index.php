<?php
// filepath: c:\xampp\htdocs\index.php

// Script to handle file requests and check if they exist remotely before serving locally

// Error reporting (comment this in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Allow cross-origin requests for images
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

// Set proper content type for different file types
function setContentType($extension) {
    $contentTypes = [
        'webp' => 'image/webp',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
    ];
      $extension = strtolower($extension);
    return isset($contentTypes[$extension]) ? $contentTypes[$extension] : 'application/octet-stream';
}

// Function to ensure default images exist
function ensureDefaultFilesExist() {
    $baseDir = rtrim(__DIR__, DIRECTORY_SEPARATOR);
    $previewDir = $baseDir . DIRECTORY_SEPARATOR . 'preview';
    $animationsDir = $baseDir . DIRECTORY_SEPARATOR . 'animations';
    
    // Ensure directories exist
    foreach ([$previewDir, $animationsDir] as $dir) {
        if (!is_dir($dir)) {
            $oldUmask = umask(0);
            @mkdir($dir, 0755, true);
            umask($oldUmask);
            @chmod($dir, 0755);
        }
    }
    
    // Check if default preview exists
    $defaultPreviewPath = $previewDir . DIRECTORY_SEPARATOR . 'default.webp';
    if (!file_exists($defaultPreviewPath)) {
        error_log("Default preview not found, trying to create it");
        
        // Try to copy from animations if exists there
        $defaultAnimationPath = $animationsDir . DIRECTORY_SEPARATOR . 'default.webp';
        if (file_exists($defaultAnimationPath)) {
            @copy($defaultAnimationPath, $defaultPreviewPath);
            @chmod($defaultPreviewPath, 0644);
        } else {
            // Try to download a default image if neither exists
            $defaultUrl = 'https://assets.babloresources.com/animmenu/previews/default.webp';
            try {
                $arrContextOptions = [
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 10,
                        'header' => ['User-Agent: Mozilla/5.0']
                    ]
                ];
                $context = stream_context_create($arrContextOptions);
                $content = @file_get_contents($defaultUrl, false, $context);
                
                if ($content !== false) {
                    file_put_contents($defaultPreviewPath, $content);
                    @chmod($defaultPreviewPath, 0644);
                    error_log("Downloaded default preview successfully");
                    
                    // Also save to animations folder as a failsafe
                    if (!file_exists($defaultAnimationPath)) {
                        file_put_contents($defaultAnimationPath, $content);
                        @chmod($defaultAnimationPath, 0644);
                    }
                }
            } catch (Exception $e) {
                error_log("Failed to download default preview: " . $e->getMessage());
            }
        }
    }
}

// Get the URL parameter from the request - this can now be just a filename
$requestedFilename = isset($_GET['url']) ? trim($_GET['url']) : '';
$requestedUrl = '';

// Build the full URL based on the filename pattern
if (!empty($requestedFilename)) {
    // Check if it's a preview file
    if (strpos($requestedFilename, '_preview.') !== false) {        // It's a preview file, extract the emote name
        $filenameWithoutExt = pathinfo($requestedFilename, PATHINFO_FILENAME);
        $emoteName = str_replace('_preview', '', $filenameWithoutExt);
        $extension = pathinfo($requestedFilename, PATHINFO_EXTENSION);
        
        // Construct the URL for preview files
        $requestedUrl = 'https://assets.babloresources.com/animmenu/previews/' . $emoteName . '_preview.' . $extension;
        
    } else {
        // Regular animation file
        $requestedUrl = 'https://assets.babloresources.com/animmenu/animations/' . $requestedFilename;
    }
}

if (empty($requestedUrl)) {
    // Show a simple HTML form with instructions instead of an error
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animation Proxy Service</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .example { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Animation Proxy Service</h1>
    <p>This service requires a file name parameter. Example usage:</p>
    <pre>index.php?url=addict.webp</pre>
    <p>For preview files, add "_preview" before the extension:</p>
    <pre>index.php?url=addict_preview.webp</pre>
    
    <div class="example">
        <h2>Examples:</h2>
        <ul>
            <li><a href="index.php?url=wposh2.webp">View animation: wposh2.webp</a></li>
            <li><a href="index.php?url=wposh2_preview.webp">View preview: wposh2_preview.webp</a></li>
        </ul>
    </div>
</body>
</html>
    <?php
    exit;
}

// Function to check if a remote file exists
function remoteFileExists($url) {
    // Handle SSL verification issues
    $arrContextOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
        'http' => [
            'method' => 'HEAD',
            'timeout' => 5,
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]
    ];
    $context = stream_context_create($arrContextOptions);
    
    // First try with stream_context_create
    $headers = @get_headers($url, 1, $context);
    if ($headers && isset($headers[0]) && strpos($headers[0], '200') !== false) {
        return true;
    }
    
    // If HEAD fails, try with cURL as a backup method
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode == 200;
    }
    
    // Last resort - try file_get_contents with the ignore_errors flag
    try {
        $content = @file_get_contents($url, false, $context);
        return $content !== false;
    } catch (Exception $e) {
        return false;
    }
}

// Function to get file from remote or local
function getFile($url) {
    global $requestedFilename;
    
    error_log("Processing URL: " . $url);
    
    // Ensure default files exist
    ensureDefaultFilesExist();
    
    // Extract filename and determine if it's a preview
    $isPreview = strpos($requestedFilename, '_preview.') !== false;
    $filename = basename($requestedFilename); // Security: prevent directory traversal
    
    // Define local paths
    $baseDir = rtrim(__DIR__, DIRECTORY_SEPARATOR);
    $previewDir = $baseDir . DIRECTORY_SEPARATOR . 'preview';
    $animationsDir = $baseDir . DIRECTORY_SEPARATOR . 'animations';
    
    // Ensure directories exist
    foreach ([$previewDir, $animationsDir] as $dir) {
        if (!is_dir($dir)) {
            $oldUmask = umask(0);
            @mkdir($dir, 0755, true);
            umask($oldUmask);
            @chmod($dir, 0755);
        }
    }
    
    // Set local path based on file type
    $localPath = $isPreview ? 
        $previewDir . DIRECTORY_SEPARATOR . $filename :
        $animationsDir . DIRECTORY_SEPARATOR . $filename;
    
    // First check if file exists locally
    if (file_exists($localPath) && is_readable($localPath)) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $contentType = setContentType($extension);
        
        // Set cache headers
        $lastModified = filemtime($localPath);
        $etag = md5_file($localPath);
        header("ETag: \"$etag\"");
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('Cache-Control: public, max-age=86400');
        
        // Check if client has cached version
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified) {
            header('HTTP/1.0 304 Not Modified');
            exit;
        }
        
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == "\"$etag\"") {
            header('HTTP/1.0 304 Not Modified');
            exit;
        }
        
        header("Content-Type: $contentType");
        header("X-Source: local-file");
        readfile($localPath);
        exit;
    }
    
    // If not found locally, check remote server
    if (remoteFileExists($url)) {
        try {
            $arrContextOptions = [
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'header' => ['User-Agent: Mozilla/5.0']
                ]
            ];
            $context = stream_context_create($arrContextOptions);
            $content = @file_get_contents($url, false, $context);
            
            if ($content !== false && file_put_contents($localPath, $content)) {
                @chmod($localPath, 0644);
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $contentType = setContentType($extension);
                header("Content-Type: $contentType");
                header("X-Source: downloaded-file");
                echo $content;
                exit;
            }
        } catch (Exception $e) {
            error_log("Error downloading file: " . $e->getMessage());
        }
    }
    
    // If we reach here, file wasn't found locally or remotely
    // For preview files, serve default preview
    if ($isPreview) {
        $defaultPreviewPath = $previewDir . DIRECTORY_SEPARATOR . 'default.webp';
        if (file_exists($defaultPreviewPath) && is_readable($defaultPreviewPath)) {
            header("Content-Type: image/webp");
            header("X-Source: default-preview");
            header("Cache-Control: public, max-age=86400");
            readfile($defaultPreviewPath);
            error_log("Serving default preview for: " . $requestedFilename);
            exit;
        }
    }
    
    // If all else fails, return transparent pixel
    header("Content-Type: image/png");
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}

// Process the request
try {
    getFile($requestedUrl);
} catch (Exception $e) {
    error_log("Error processing request: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    echo "Error processing request";
}
?>