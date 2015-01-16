<?php

/**
 * 文件上传类。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package lib
 */
class TeaUpload {
    /**
     * 上传错误类型：不允许上传。
     */
    const UPLOAD_ERR_TYPE_NOT_ALLOWED = 21;
    
    /**
     * 上传错误类型：文件夹创建失败。
     */
    const UPLOAD_ERR_CREATE_FOLDER_DENIED = 22;
    
    /**
     * 上传错误类型：文件保存失败。
     */
    const UPLOAD_ERR_SAVE_FILE_FAILED = 23;

    /**
     * 上传文件信息数组。
     * @var array
     */
    protected $_fileInfo = array();

    /**
     * 构造函数。
     * @param array $fileInfo 类似$_FILES结构的信息数组，默认为$_FILES.
     */
    public function __construct($fileInfo = array()) {
        if (empty($fileInfo)) {
            $fileInfo = $_FILES;
        }
        $this->_fileInfo = $this->normalizeFileinfo($fileInfo);
    }

    /**
     * 处理上传。
     * @param string $saveFolder 上传文件夹路径，尾部不包含'/'。默认为应用目录下的'public/upload'文件夹。
     * @param array $allowedTypes 限制上传的mime文件类型。
     * @param bool $overwrite 是否覆盖已存在文件。
     * @return $this
     */
    public function upload($saveFolder = null, $allowedTypes = array(), $overwrite = true) {
        if (empty($saveFolder) && defined('APP_PATH')) {
            $saveFolder = APP_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'upload';
        }
        if (empty($allowedTypes)) {
            $allowedTypes = array_values($this->extensionToMimeMap());
        }
        $status = DirectoryHelper::mkdirs($saveFolder);
        foreach ($this->_fileInfo as $key => &$files) {
            if (is_array($files) && !empty($files)) {
                foreach ($files as $k => &$file) {
                    $file['save_name'] = null;
                    $file['save_path'] = null;
                    if (empty($file['error'])) {
                        if (!$status) {
                            $file['error'] = self::UPLOAD_ERR_CREATE_FOLDER_DENIED;
                        }
                        if (is_array($allowedTypes) && !empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
                            $file['error'] = self::UPLOAD_ERR_TYPE_NOT_ALLOWED;
                        }
                    }
                    $mimeToExtMap = array_flip($this->extensionToMimeMap());
                    $uploadSuffix = isset($mimeToExtMap[$file['type']]) ? '.' . $mimeToExtMap[$file['type']] : null;
                    $saveName = date('YmdHis') . '_' . uniqid() . $uploadSuffix;
                    $savePath = $saveFolder . DIRECTORY_SEPARATOR . $saveName;
                    if (file_exists($savePath) && $overwrite) {
                        @unlink($savePath);
                    }
                    if ($file['error'] === 0) {
                        $moveStatus = @move_uploaded_file($file['tmp_name'], $savePath);
                        if ($moveStatus) {
                            $file['save_name'] = $saveName;
                            $file['save_path'] = $savePath;
                        } else {
                            $file['error'] = self::UPLOAD_ERR_SAVE_FILE_FAILED;
                        }
                    }
                    $file['error_msg'] = $this->getErrMsg($file['error']);
                }
                unset($file);
            }
        }
        unset($files);
        return $this;
    }

    /**
     * 获取上传文件信息数组。
     * @return array
     */
    public function getFileInfo() {
        return $this->_fileInfo;
    }

    /**
     * 根据错误代码获取错误信息。
     * @param int $errCode 错误代码。
     * @return string 错误信息。
     */
    public function getErrMsg($errCode) {
        $errMsg = '';
        switch ($errCode) {
            case 0:
                $errMsg = '';
                break;
            case 1:
                $errMsg = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                break;
            case 2:
                $errMsg = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                break;
            case 3:
                $errMsg = 'The uploaded file was only partially uploaded.';
                break;
            case 4:
                $errMsg = 'No file was uploaded.';
                break;
            case 6:
                $errMsg = 'Missing a temporary folder.';
                break;
            case 7:
                $errMsg = 'Failed to write file to disk.';
                break;
            case 8:
                $errMsg = 'A PHP extension stopped the file upload.';
                break;
            case 21:
                $errMsg = 'The type of uploaded file is not allowed.';
                break;
            case 22:
                $errMsg = 'Permission denied to create folder.';
                break;
            case 23:
                $errMsg = 'Permission denied to save file.';
                break;
            default:
                $errMsg = 'Unknown error.';
                break;
        }
        return $errMsg;
    }

    /**
     * 把类似$_FILES结构的信息数组格式化成类似结果集结构数组。
     * @param array $fileInfo 类似$_FILES结构的信息数组。
     * @return array
     */
    public function normalizeFileinfo($fileInfo) {
        $newFileInfo = array();
        $fileInfoKeys = array('name', 'type', 'tmp_name', 'error', 'size');
        foreach ($fileInfo as $key => $info) {
            foreach ($info as $infoKey => $infoVal) {
                if (in_array($infoKey, $fileInfoKeys)) {
                    if (is_array($infoVal)) {
                        foreach ($infoVal as $i => $v) {
                            $newFileInfo[$key][$i][$infoKey] = $v;
                        }
                    } else {
                        $newFileInfo[$key][0][$infoKey] = $infoVal;
                    }
                }
            }
        }
        return $newFileInfo;
    }

    /**
     * 文件后缀对应mime类型映射数组。
     * @return array
     */
    public static function extensionToMimeMap() {
        return array(
            "ez" => "application/andrew-inset",
            "hqx" => "application/mac-binhex40",
            "cpt" => "application/mac-compactpro",
            "doc" => "application/msword",
            "bin" => "application/octet-stream",
            "dms" => "application/octet-stream",
            "lha" => "application/octet-stream",
            "lzh" => "application/octet-stream",
            "exe" => "application/octet-stream",
            "class" => "application/octet-stream",
            "so" => "application/octet-stream",
            "dll" => "application/octet-stream",
            "oda" => "application/oda",
            "pdf" => "application/pdf",
            "ai" => "application/postscript",
            "eps" => "application/postscript",
            "ps" => "application/postscript",
            "smi" => "application/smil",
            "smil" => "application/smil",
            "wbxml" => "application/vnd.wap.wbxml",
            "wmlc" => "application/vnd.wap.wmlc",
            "wmlsc" => "application/vnd.wap.wmlscriptc",
            "bcpio" => "application/x-bcpio",
            "vcd" => "application/x-cdlink",
            "pgn" => "application/x-chess-pgn",
            "cpio" => "application/x-cpio",
            "csh" => "application/x-csh",
            "dcr" => "application/x-director",
            "dir" => "application/x-director",
            "dxr" => "application/x-director",
            "dvi" => "application/x-dvi",
            "spl" => "application/x-futuresplash",
            "gtar" => "application/x-gtar",
            "hdf" => "application/x-hdf",
            "js" => "application/x-javascript",
            "skp" => "application/x-koan",
            "skd" => "application/x-koan",
            "skt" => "application/x-koan",
            "skm" => "application/x-koan",
            "latex" => "application/x-latex",
            "nc" => "application/x-netcdf",
            "cdf" => "application/x-netcdf",
            "sh" => "application/x-sh",
            "shar" => "application/x-shar",
            "swf" => "application/x-shockwave-flash",
            "sit" => "application/x-stuffit",
            "sv4cpio" => "application/x-sv4cpio",
            "sv4crc" => "application/x-sv4crc",
            "tar" => "application/x-tar",
            "tcl" => "application/x-tcl",
            "tex" => "application/x-tex",
            "texinfo" => "application/x-texinfo",
            "texi" => "application/x-texinfo",
            "t" => "application/x-troff",
            "tr" => "application/x-troff",
            "roff" => "application/x-troff",
            "man" => "application/x-troff-man",
            "me" => "application/x-troff-me",
            "ms" => "application/x-troff-ms",
            "ustar" => "application/x-ustar",
            "src" => "application/x-wais-source",
            "xhtml" => "application/xhtml+xml",
            "xht" => "application/xhtml+xml",
            "zip" => "application/zip",
            "au" => "audio/basic",
            "snd" => "audio/basic",
            "mid" => "audio/midi",
            "midi" => "audio/midi",
            "kar" => "audio/midi",
            "mpga" => "audio/mpeg",
            "mp2" => "audio/mpeg",
            "mp3" => "audio/mpeg",
            "aif" => "audio/x-aiff",
            "aiff" => "audio/x-aiff",
            "aifc" => "audio/x-aiff",
            "m3u" => "audio/x-mpegurl",
            "ram" => "audio/x-pn-realaudio",
            "rm" => "audio/x-pn-realaudio",
            "rpm" => "audio/x-pn-realaudio-plugin",
            "ra" => "audio/x-realaudio",
            "wav" => "audio/x-wav",
            "pdb" => "chemical/x-pdb",
            "xyz" => "chemical/x-xyz",
            "bmp" => "image/bmp",
            "gif" => "image/gif",
            "ief" => "image/ief",
            "jpeg" => "image/jpeg",
            "jpg" => "image/jpeg",
            "jpe" => "image/jpeg",
            "png" => "image/png",
            "tiff" => "image/tiff",
            "tif" => "image/tif",
            "djvu" => "image/vnd.djvu",
            "djv" => "image/vnd.djvu",
            "wbmp" => "image/vnd.wap.wbmp",
            "ras" => "image/x-cmu-raster",
            "pnm" => "image/x-portable-anymap",
            "pbm" => "image/x-portable-bitmap",
            "pgm" => "image/x-portable-graymap",
            "ppm" => "image/x-portable-pixmap",
            "rgb" => "image/x-rgb",
            "xbm" => "image/x-xbitmap",
            "xpm" => "image/x-xpixmap",
            "xwd" => "image/x-windowdump",
            "igs" => "model/iges",
            "iges" => "model/iges",
            "msh" => "model/mesh",
            "mesh" => "model/mesh",
            "silo" => "model/mesh",
            "wrl" => "model/vrml",
            "vrml" => "model/vrml",
            "css" => "text/css",
            "html" => "text/html",
            "htm" => "text/html",
            "asc" => "text/plain",
            "txt" => "text/plain",
            "rtx" => "text/richtext",
            "rtf" => "text/rtf",
            "sgml" => "text/sgml",
            "sgm" => "text/sgml",
            "tsv" => "text/tab-seperated-values",
            "wml" => "text/vnd.wap.wml",
            "wmls" => "text/vnd.wap.wmlscript",
            "etx" => "text/x-setext",
            "xml" => "text/xml",
            "xsl" => "text/xml",
            "mpeg" => "video/mpeg",
            "mpg" => "video/mpeg",
            "mpe" => "video/mpeg",
            "qt" => "video/quicktime",
            "mov" => "video/quicktime",
            "mxu" => "video/vnd.mpegurl",
            "avi" => "video/x-msvideo",
            "movie" => "video/x-sgi-movie",
            "ice" => "x-conference-xcooltalk"
        );
    }

}