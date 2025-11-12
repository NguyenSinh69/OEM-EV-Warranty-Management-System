# File Upload Service

Microservice for handling file uploads in the OEM EV Warranty Management System.

## Features

- Single and multiple file uploads
- File validation (size, type, security)
- Support for images and documents
- Organized storage by category
- RESTful API endpoints

## Configuration

- **Port**: 8006
- **Max File Size**: 5MB
- **Allowed Types**: JPG, PNG, GIF, PDF, DOC, DOCX
- **Upload Directory**: `/uploads/`

## API Endpoints

### Health Check
```
GET /api/upload/health
```

### Upload Single File
```
POST /api/upload/file
Content-Type: multipart/form-data

Parameters:
- file: File to upload
- category: Storage category (claims, vehicles, temp)
```

### Upload Multiple Files
```
POST /api/upload/files
Content-Type: multipart/form-data

Parameters:
- files[]: Array of files to upload
- category: Storage category
```

### Get File
```
GET /api/upload/file/{category}/{filename}
```

### Delete File
```
DELETE /api/upload/file/{category}/{filename}
```

### List Files
```
GET /api/upload/files?category={category}
```

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "filename": "image_1234567890_abc123.jpg",
    "original_name": "image.jpg",
    "size": 102400,
    "mime_type": "image/jpeg",
    "category": "claims",
    "url": "/api/upload/file/claims/image_1234567890_abc123.jpg",
    "uploaded_at": "2024-11-12 10:30:00"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "File validation failed",
  "errors": [
    "File size exceeds maximum allowed size (5MB)"
  ]
}
```

## Storage Structure

```
uploads/
├── claims/      # Warranty claim images
├── vehicles/    # Vehicle documents
└── temp/        # Temporary uploads
```

## Security Features

- File type validation (extension + MIME type)
- File size limits
- Directory traversal prevention
- Unique filename generation
- Sanitized file paths

## Usage Example

### JavaScript/Fetch
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('category', 'claims');

const response = await fetch('http://localhost:8006/api/upload/file', {
  method: 'POST',
  body: formData
});

const result = await response.json();
console.log(result.data.url);
```

### cURL
```bash
curl -X POST \
  -F "file=@/path/to/image.jpg" \
  -F "category=claims" \
  http://localhost:8006/api/upload/file
```

## Docker

Build and run:
```bash
docker build -t file-upload-service .
docker run -p 8006:80 -v $(pwd)/uploads:/var/www/uploads file-upload-service
```

## Notes

- Uploaded files are stored persistently in the `uploads/` directory
- Use volume mount in production to persist files across container restarts
- Implement authentication/authorization for production use
- Consider implementing virus scanning for enhanced security
