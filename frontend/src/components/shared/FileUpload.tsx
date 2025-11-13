'use client';

import { useState } from 'react';
import { api } from '@/lib/api';

interface FileUploadProps {
  onUploadComplete: (urls: string[]) => void;
  maxFiles?: number;
  acceptedTypes?: string;
  relatedId: string;
  uploadType: string;
}

export default function FileUpload({
  onUploadComplete,
  maxFiles = 5,
  acceptedTypes = 'image/*',
  relatedId,
  uploadType,
}: FileUploadProps) {
  const [files, setFiles] = useState<File[]>([]);
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState<Record<string, number>>({});
  const [errors, setErrors] = useState<string[]>([]);

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const selectedFiles = Array.from(e.target.files);
      const validFiles: File[] = [];
      const newErrors: string[] = [];

      selectedFiles.forEach((file) => {
        // Check file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
          newErrors.push(`${file.name} is too large (max 5MB)`);
          return;
        }

        // Check file type
        if (!file.type.startsWith('image/')) {
          newErrors.push(`${file.name} is not an image`);
          return;
        }

        validFiles.push(file);
      });

      // Limit number of files
      if (files.length + validFiles.length > maxFiles) {
        newErrors.push(`Maximum ${maxFiles} files allowed`);
        setErrors(newErrors);
        return;
      }

      setFiles([...files, ...validFiles]);
      setErrors(newErrors);
    }
  };

  const removeFile = (index: number) => {
    setFiles(files.filter((_, i) => i !== index));
  };

  const uploadFiles = async () => {
    if (files.length === 0) {
      onUploadComplete([]);
      return;
    }

    try {
      setUploading(true);
      const uploadedUrls: string[] = [];

      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        setUploadProgress({ ...uploadProgress, [file.name]: 0 });

        try {
          const response = await api.scStaff.uploadFile(file, uploadType || 'temp');
          
          if (response.success) {
            uploadedUrls.push(response.data.url);
            setUploadProgress({ ...uploadProgress, [file.name]: 100 });
          } else {
            setErrors([...errors, `Failed to upload ${file.name}`]);
          }
        } catch (error) {
          console.error(`Error uploading ${file.name}:`, error);
          setErrors([...errors, `Failed to upload ${file.name}`]);
        }
      }

      onUploadComplete(uploadedUrls);
    } catch (error) {
      console.error('Upload error:', error);
      setErrors(['Upload failed. Please try again.']);
    } finally {
      setUploading(false);
    }
  };

  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  };

  return (
    <div className="space-y-4">
      {/* File Input */}
      <div>
        <label className="block text-gray-700 font-medium mb-2">
          Upload Images
        </label>
        <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors">
          <input
            type="file"
            onChange={handleFileSelect}
            accept={acceptedTypes}
            multiple
            disabled={uploading || files.length >= maxFiles}
            className="hidden"
            id="file-upload"
          />
          <label
            htmlFor="file-upload"
            className={`cursor-pointer ${uploading || files.length >= maxFiles ? 'opacity-50 cursor-not-allowed' : ''}`}
          >
            <svg className="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p className="text-gray-600 font-medium">
              Click to upload or drag and drop
            </p>
            <p className="text-sm text-gray-500 mt-1">
              PNG, JPG, GIF up to 5MB (Max {maxFiles} files)
            </p>
          </label>
        </div>
      </div>

      {/* Errors */}
      {errors.length > 0 && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="flex items-start gap-3">
            <svg className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div className="flex-1">
              {errors.map((error, index) => (
                <p key={index} className="text-sm text-red-800">{error}</p>
              ))}
            </div>
            <button
              onClick={() => setErrors([])}
              className="text-red-600 hover:text-red-700"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      )}

      {/* Selected Files List */}
      {files.length > 0 && (
        <div className="space-y-2">
          <p className="text-sm font-medium text-gray-700">
            Selected files ({files.length}/{maxFiles})
          </p>
          {files.map((file, index) => (
            <div key={index} className="flex items-center justify-between bg-gray-50 rounded-lg p-3">
              <div className="flex items-center gap-3 flex-1 min-w-0">
                <svg className="w-8 h-8 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 truncate">{file.name}</p>
                  <p className="text-xs text-gray-500">{formatFileSize(file.size)}</p>
                </div>
              </div>
              {!uploading && (
                <button
                  onClick={() => removeFile(index)}
                  className="text-red-600 hover:text-red-700 ml-3"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Upload Button */}
      {files.length > 0 && (
        <button
          onClick={uploadFiles}
          disabled={uploading}
          className="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white py-3 px-6 rounded-lg font-semibold transition-colors"
        >
          {uploading ? (
            <span className="flex items-center justify-center gap-2">
              <svg className="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Uploading...
            </span>
          ) : (
            `Upload ${files.length} file${files.length > 1 ? 's' : ''}`
          )}
        </button>
      )}
    </div>
  );
}
