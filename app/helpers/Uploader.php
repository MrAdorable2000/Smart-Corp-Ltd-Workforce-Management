<?php
/**
 * File Upload Helper
 */

class Uploader
{
    private $file;
    private $allowedTypes;
    private $maxSize;
    private $uploadPath;

    public function __construct($fileInput, $allowedTypes = null, $maxSize = null)
    {
        if (!isset($_FILES[$fileInput])) {
            throw new Exception("File input '{$fileInput}' not found");
        }
        $this->file = $_FILES[$fileInput];
        $this->allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
        $this->maxSize = $maxSize ?? MAX_UPLOAD_SIZE;
    }

    public function validate()
    {
        if ($this->file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed with error code: ' . $this->file['error']);
        }
        if ($this->file['size'] > $this->maxSize) {
            throw new Exception('File size exceeds maximum allowed size');
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $this->file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('File type not allowed: ' . $mimeType);
        }
        return true;
    }

    public function upload($subDir = '', $customName = null)
    {
        $this->validate();
        $ext = pathinfo($this->file['name'], PATHINFO_EXTENSION);
        $fileName = $customName ? $customName . '.' . $ext : uniqid('f_', true) . '.' . $ext;
        $targetDir = UPLOAD_PATH . '/' . trim($subDir, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $targetPath = $targetDir . '/' . $fileName;
        if (!move_uploaded_file($this->file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        return trim($subDir, '/') . '/' . $fileName;
    }

    public function getFileName()
    {
        return $this->file['name'];
    }

    public function getSize()
    {
        return $this->file['size'];
    }

    public function getMimeType()
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $this->file['tmp_name']);
        finfo_close($finfo);
        return $mime;
    }
}
