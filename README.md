# Animation Proxy Service

This script is a proxy service that caches and serves animation and preview files from a remote server locally. It is designed to work on XAMPP.

## Features

- Automatic local caching of files from remote server
- Separate folder structure for preview and animation files
- Default preview file support
- Secure file operations and directory traversal protection
- Cache control and performance optimization

## Important Configuration

Before using the script, you need to replace the remote URLs in the code with your own domain:

1. Open `index.php` in a text editor
2. Find and replace the following URLs:
   - Replace `https://assets.babloresources.com/animmenu/previews/` with `your-domain.com/index.php?url=`
   - Replace `https://assets.babloresources.com/animmenu/animations/` with `your-domain.com/index.php?url=`

For example, if your domain is `example.com`, the URLs should be:
- `https://example.com/index.php?url=` for previews
- `https://example.com/index.php?url=` for animations

## Installation

1. Install XAMPP on your computer (https://www.apachefriends.org/)
2. Copy this script to XAMPP's htdocs folder:
   ```
   C:\xampp\htdocs\index.php
   ```
3. Start the Apache service from XAMPP Control Panel
4. Ensure the following folders are automatically created:
   - `C:\xampp\htdocs\preview`
   - `C:\xampp\htdocs\animations`

## Usage

The script supports the following URL formats:

1. For animation files:
```
http://localhost/index.php?url=emote_name.webp
```

2. For preview files:
```
http://localhost/index.php?url=emote_name_preview.webp
```

### Examples

- View animation:
  ```
  http://localhost/index.php?url=wposh2.webp
  ```

- View preview:
  ```
  http://localhost/index.php?url=wposh2_preview.webp
  ```

## How It Works

1. The script first checks if the file exists in the local folder
2. If not found locally, it downloads from the remote server and saves to the local folder
3. For preview files, if the file is not found, it serves the default preview file (`default.webp`)
4. Appropriate cache headers are set for all files

## Security

- Protection against directory traversal attacks
- File permission checks
- Secure file operations

## Requirements

- PHP 7.0 or higher
- XAMPP (Apache)
- Internet connection (for accessing remote files)

## Troubleshooting

1. If files are not displaying:
   - Make sure XAMPP is running
   - Check folder permissions
   - Check PHP error logs

2. If remote files cannot be downloaded:
   - Check your internet connection
   - Ensure the remote server is accessible
   - Verify that you've correctly configured the remote URLs in the script

## License

This script is open source and free to use. 
