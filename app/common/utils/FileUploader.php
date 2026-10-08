<?php
namespace app\common\utils;

use think\facade\Filesystem;
use think\file\UploadedFile;

/**
 * 文件上传工具类
 * 处理文件上传相关逻辑
 */
class FileUploader
{
    /**
     * 允许的文件类型
     */
    const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
    const ALLOWED_DOCUMENT_TYPES = ['doc', 'docx', 'pdf', 'txt', 'rtf'];
    const ALLOWED_ALL_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'doc', 'docx', 'pdf', 'txt', 'rtf'];
    
    /**
     * 最大文件大小（5MB）
     */
    const MAX_FILE_SIZE = 5 * 1024 * 1024;
    
    /**
     * 上传文件
     * 
     * @param UploadedFile $file 上传的文件
     * @param string $type 文件类型：image|document|all
     * @param string $subDir 子目录
     * @return array 上传结果
     */
    public static function upload($file, $type = 'image', $subDir = '')
    {
        try {
            // 验证文件
            $validation = self::validateFile($file, $type);
            if (!$validation['success']) {
                return $validation;
            }
            
            // 生成文件名
            $fileName = self::generateFileName($file);
            
            // 构建保存路径
            $savePath = 'uploads';
            if (!empty($subDir)) {
                $savePath .= '/' . trim($subDir, '/');
            }
            
            // 保存文件
            $filePath = Filesystem::disk('public')->putFileAs(
                $savePath,
                $file,
                $fileName
            );
            
            // 构建访问URL
            $url = '/storage/' . $filePath;
            
            return [
                'success' => true,
                'code' => 1,
                'msg' => '上传成功',
                'data' => [
                    'url' => $url,
                    'path' => $filePath,
                    'filename' => $fileName,
                    'original_name' => $file->getOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMime()
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'code' => 0,
                'msg' => '上传失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 验证文件
     * 
     * @param UploadedFile $file 上传的文件
     * @param string $type 文件类型
     * @return array 验证结果
     */
    private static function validateFile($file, $type)
    {
        // 检查文件是否存在
        if (!$file) {
            return [
                'success' => false,
                'code' => 0,
                'msg' => '请选择要上传的文件'
            ];
        }
        
        // 检查文件大小
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return [
                'success' => false,
                'code' => 0,
                'msg' => '文件大小不能超过5MB'
            ];
        }
        
        // 检查文件类型
        $extension = strtolower($file->getOriginalExtension());
        $allowedTypes = self::getAllowedTypes($type);
        
        if (!in_array($extension, $allowedTypes)) {
            return [
                'success' => false,
                'code' => 0,
                'msg' => '不支持的文件类型，仅支持：' . implode(', ', $allowedTypes)
            ];
        }
        
        return [
            'success' => true,
            'code' => 1,
            'msg' => '文件验证通过'
        ];
    }
    
    /**
     * 获取允许的文件类型
     * 
     * @param string $type 文件类型
     * @return array 允许的文件类型数组
     */
    private static function getAllowedTypes($type)
    {
        switch ($type) {
            case 'image':
                return self::ALLOWED_IMAGE_TYPES;
            case 'document':
                return self::ALLOWED_DOCUMENT_TYPES;
            case 'all':
                return self::ALLOWED_ALL_TYPES;
            default:
                return self::ALLOWED_IMAGE_TYPES;
        }
    }
    
    /**
     * 生成文件名
     * 
     * @param UploadedFile $file 上传的文件
     * @return string 生成的文件名
     */
    private static function generateFileName($file)
    {
        $extension = strtolower($file->getOriginalExtension());
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        
        return md5($file->getOriginalName() . $timestamp . $random) . '.' . $extension;
    }
    
    /**
     * 删除文件
     * 
     * @param string $filePath 文件路径
     * @return bool 是否删除成功
     */
    public static function deleteFile($filePath)
    {
        try {
            if (empty($filePath)) {
                return false;
            }
            
            // 移除URL前缀
            $filePath = str_replace('/storage/', '', $filePath);
            
            // 检查文件是否存在
            $fullPath = public_path() . 'storage/' . $filePath;
            if (!file_exists($fullPath)) {
                return false;
            }
            
            // 删除文件
            return unlink($fullPath);
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取文件信息
     * 
     * @param string $filePath 文件路径
     * @return array|null 文件信息
     */
    public static function getFileInfo($filePath)
    {
        try {
            if (empty($filePath)) {
                return null;
            }
            
            // 移除URL前缀
            $filePath = str_replace('/storage/', '', $filePath);
            $fullPath = public_path() . 'storage/' . $filePath;
            
            if (!file_exists($fullPath)) {
                return null;
            }
            
            $fileInfo = [
                'path' => $filePath,
                'url' => '/storage/' . $filePath,
                'size' => filesize($fullPath),
                'mime_type' => mime_content_type($fullPath),
                'modified_time' => filemtime($fullPath),
                'extension' => pathinfo($fullPath, PATHINFO_EXTENSION)
            ];
            
            return $fileInfo;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}
