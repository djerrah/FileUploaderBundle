<?php
/**
 * jQuery File Upload Plugin PHP Class 7.1.4
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */


namespace Djerrah\FileUploaderBundle\Lib\UploadHandler;

use Djerrah\FileUploaderBundle\Entity\File;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \imagick;
use \stdClass;
use \ImagickPixel;


/**
 * Class UploadHandler
 *
 * @package Djerrah\FileUploaderBundle\Lib\UploadHandler
 */
class UploadHandler
{
    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var null
     */
    protected $options;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var $user
     */
    private $user;

    /**
     * @var array|null
     */
    protected $error_messages = [
        1                     => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2                     => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3                     => 'The uploaded file was only partially uploaded',
        4                     => 'No file was uploaded',
        6                     => 'Missing a temporary folder',
        7                     => 'Failed to write file to disk',
        8                     => 'A PHP extension stopped the file upload',
        'post_max_size'       => 'The uploaded file exceeds the post_max_size directive in php.ini',
        'max_file_size'       => 'File is too big',
        'min_file_size'       => 'File is too small',
        'accept_file_types'   => 'Filetype not allowed',
        'max_number_of_files' => 'Maximum number of files exceeded',
        'max_width'           => 'Image exceeds maximum width',
        'min_width'           => 'Image requires a minimum width',
        'max_height'          => 'Image exceeds maximum height',
        'min_height'          => 'Image requires a minimum height',
        'abort'               => 'File upload aborted',
        'image_resize'        => 'Failed to resize image'
    ];

    /**
     * @var array
     */
    protected $imageObjects = [];

    /**
     * @param Request       $request
     * @param EntityManager $em
     * @param User          $user
     * @param null          $options
     * @param bool          $initialize
     * @param null          $error_messages
     */
    function __construct(
        Request $request,
        EntityManager $em,
         $user,
        $options = null,
        $initialize = true,
        $error_messages = null
    ) {
        $this->em      = $em;
        $this->user    = $user;
        $this->request = $request;

        $this->options = [
            'script_url'                       => $this->getFullUrl() . '/',
            'upload_dir'                       => dirname($this->getServerVar('SCRIPT_FILENAME')),
            'upload_url'                       => $this->getFullUrl(),
            'user_dirs'                        => false,
            'mkdir_mode'                       => 0755,
            'param_name'                       => 'files',
            'default_pdf_icon'                 => 'img/pdf_icon_large.png',
            'default_excel_icon'               => 'img/xls_icon_large.jpg',
            'default_doc_icon'                 => 'img/doc_icon_large.jpg',
            'default_icon'                     => 'img/document-icon.png',
            // Set the following option to 'POST', if your server does not support
            // DELETE requests. This is a parameter sent to the client:
            'delete_type'                      => 'DELETE',
            'access_control_allow_origin'      => '*',
            'access_control_allow_credentials' => false,
            'access_control_allow_methods'     => ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            'access_control_allow_headers'     => ['Content-Type', 'Content-Range', 'Content-Disposition'],
            // Enable to provide file downloads via GET requests to the PHP script:
            //     1. Set to 1 to download files via readfile method through PHP
            //     2. Set to 2 to send a X-Sendfile header for lighttpd/Apache
            //     3. Set to 3 to send a X-Accel-Redirect header for nginx
            // If set to 2 or 3, adjust the upload_url option to the base path of
            // the redirect parameter, e.g. '/files/'.
            'download_via_php'                 => false,
            // Read files in chunks to avoid memory limits when download_via_php
            // is enabled, set to 0 to disable chunked reading of files:
            'readfile_chunk_size'              => 10 * 1024 * 1024, // 10 MiB
            // Defines which files can be displayed inline when downloaded:
            'inline_file_types'                => '/\.(gif|jpe?g|png)$/i',
            // Defines which files (based on their names) are accepted for upload:
            'accept_file_types'                => '/.+$/i',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size'                    => null,
            'min_file_size'                    => 1,
            // The maximum number of files for the upload directory:
            'max_number_of_files'              => null,
            // Defines which files are handled as image files:
            'image_file_types'                 => '/\.(gif|jpe?g|png)$/i',
            // Image resolution restrictions:
            'max_width'                        => null,
            'max_height'                       => null,
            'min_width'                        => 1,
            'min_height'                       => 1,
            // Set the following option to false to enable resumable uploads:
            'discard_aborted_uploads'          => true,
            // Set to 0 to use the GD library to scale and orient images,
            // set to 1 to use imagick (if installed, falls back to GD),
            // set to 2 to use the ImageMagick convert binary directly:
            'image_library'                    => 1,
            // Uncomment the following to define an array of resource limits
            // for imagick:
            /*
            'imagick_resource_limits' => array(
                imagick::RESOURCETYPE_MAP => 32,
                imagick::RESOURCETYPE_MEMORY => 32
            ),
            */
            // Command or path for to the ImageMagick convert binary:
            'convert_bin'                      => 'convert',
            // Uncomment the following to add parameters in front of each
            // ImageMagick convert call (the limit constraints seem only
            // to have an effect if put in front):
            /*
            'convert_params' => '-limit memory 32MiB -limit map 32MiB',
            */
            // Command or path for to the ImageMagick identify binary:
            'identify_bin'                     => 'identify',
            'image_versions'                   => [
                // The empty image version key defines options for the original image:
                ''          => [
                    // Automatically rotate images based on EXIF meta data:
                    'auto_orient' => true
                ],
                // Uncomment the following to create medium sized images:
                /*
                'medium' => array(
                    'max_width' => 800,
                    'max_height' => 600
                ),
                */
                'thumbnail' => [
                    // Uncomment the following to use a defined directory for the thumbnails
                    // instead of a subdirectory based on the version identifier.
                    // Make sure that this directory doesn't allow execution of files if you
                    // don't pose any restrictions on the type of uploaded files, e.g. by
                    // copying the .htaccess file from the files directory for Apache:
                    //'upload_dir' => dirname($this->getServerVar('SCRIPT_FILENAME')).'/thumb/',
                    //'upload_url' => $this->getFullUrl().'/thumb/',
                    // Uncomment the following to force the max
                    // dimensions and e.g. create square thumbnails:
                    //'crop' => true,
                    'max_width'  => 80,
                    'max_height' => 80
                ]
            ]
        ];
        if ($options) {
            $this->options = $options + $this->options;
        }
        if ($error_messages) {
            $this->error_messages = $error_messages + $this->error_messages;
        }
        if ($initialize) {
            // $this->initialize();
        }
    }

    /**
     *
     */
    public function getResponse()
    {
        switch ($this->request->getMethod()) {
            case 'OPTIONS':
            case 'HEAD':
                $response = $this->head();
                break;
            case 'GET':
                $response = $this->get();
                break;
            case 'PATCH':
            case 'PUT':
            case 'POST':
                $response = $this->post();
                break;
            case 'DELETE':
                $response = $this->delete();
                break;
            default:
                $response = new Response('', 200, ['HTTP/1.1 405 Method Not Allowed']);
        }

        return $response;
    }

    /**
     * @return string
     */
    protected function getFullUrl()
    {
        $https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0;

        return
            ($https ? 'https://' : 'http://') .
            (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] . '@' : '') .
            (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] .
                ($https && $_SERVER['SERVER_PORT'] === 443 ||
                $_SERVER['SERVER_PORT'] === 80 ? '' : ':' . $_SERVER['SERVER_PORT']))) .
            substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
    }

    /**
     * @return string
     */
    protected function getUserId()
    {
        @session_start();

        return session_id();
    }

    /**
     * @return string
     */
    protected function get_user_path()
    {
        if ($this->options['user_dirs']) {
            return $this->getUserId() . '/';
        }

        return '';
    }

    /**
     * @param null $file_name
     * @param null $version
     *
     * @return string
     */
    protected function getUploadPath($file_name = null, $version = null)
    {
        $file_name = $file_name ? $file_name : '';
        if (empty($version)) {
            $version_path = '';
        } else {
            $version_dir = @$this->options['image_versions'][$version]['upload_dir'];
            if ($version_dir) {
                return $version_dir . $this->get_user_path() . $file_name;
            }
            $version_path = $version . '/';
        }

        return $this->options['upload_dir'] . $this->get_user_path()
        . $version_path . $file_name;
    }

    /**
     * @param $url
     *
     * @return string
     */
    protected function getQuerySeparator($url)
    {
        return strpos($url, '?') === false ? '?' : '&';
    }

    /**
     * @param      $file_name
     * @param null $version
     * @param bool $direct
     *
     * @return string
     */
    protected function getDownloadUrl($file_name, $version = null, $direct = false)
    {
        if (!$direct && $this->options['download_via_php']) {
            $url = $this->options['script_url'] . $this->getQuerySeparator($this->options['script_url']) . $this->getSingularParamName() . '=' . rawurlencode($file_name);
            if ($version) {
                $url .= '&version=' . rawurlencode($version);
            }

            return $url . '&download=1';
        }

        if (empty($version)) {
            $version_path = '';
        } else {
            $version_url = @$this->options['image_versions'][$version]['upload_url'];
            if ($version_url) {
                return $version_url . $this->get_user_path() . rawurlencode($file_name);
            }
            $version_path = rawurlencode($version) . '/';
        }

        return $this->options['upload_url'] . $this->get_user_path()
        . $version_path . rawurlencode($file_name);
    }

    /**
     * @param $file
     */
    protected function setAdditionalFileProperties($file)
    {
        $file->deleteUrl  = $this->options['script_url'] . $this->getQuerySeparator($this->options['script_url']) . $this->getSingularParamName() . '=' . rawurlencode($file->name);
        $file->deleteType = $this->options['delete_type'];
        if ($file->deleteType !== 'DELETE') {
            $file->deleteUrl .= '&_method=DELETE';
        }
        if ($this->options['access_control_allow_credentials']) {
            $file->deleteWithCredentials = true;
        }
    }

    /**
     * @param $size
     *
     * @return float
     */
    protected function fixIntegerOverflow($size)
    {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }

        return $size;
    }

    /**
     * @param      $file_path
     * @param bool $clear_stat_cache
     *
     * @return float
     */
    protected function getFileSize($file_path, $clear_stat_cache = false)
    {
        if ($clear_stat_cache) {
            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                clearstatcache(true, $file_path);
            } else {
                clearstatcache();
            }
        }

        return $this->fixIntegerOverflow(filesize($file_path));
    }

    /**
     * @param $file_name
     *
     * @return bool
     */
    protected function isValidFileObject($file_name)
    {
        $file_path = $this->getUploadPath($file_name);
        if (is_file($file_path) && $file_name[0] !== '.') {
            return true;
        }

        return false;
    }

    /**
     * @param $file_name
     *
     * @return null|stdClass
     */
    protected function getFileObject($file_name)
    {
        if ($this->isValidFileObject($file_name)) {
            $file       = new stdClass();
            $file->name = $file_name;
            $file->size = $this->getFileSize(
                $this->getUploadPath($file_name)
            );
            $file->url  = $this->getDownloadUrl($file->name);

            $this->setMiniature($file);

            foreach ($this->options['image_versions'] as $version => $options) {
                if (!empty($version)) {
                    if (is_file($this->getUploadPath($file_name, $version))) {
                        $file->{$version . 'Url'} = $this->getDownloadUrl($file->name, $version);
                    }
                }
            }
            $this->setBddProperties($file);

            $this->setAdditionalFileProperties($file);

            return $file;
        }

        return null;
    }

    /**
     * @param string $iteration_method
     *
     * @return array
     */
    protected function getFileObjects($iteration_method = 'getFileObject')
    {
        $upload_dir = $this->getUploadPath();
        if (!is_dir($upload_dir)) {
            return [];
        }

        return array_values(array_filter(array_map([$this, $iteration_method], scandir($upload_dir))));
    }

    /**
     * @return int
     */
    protected function countFileObjects()
    {
        return count($this->getFileObjects('isValidFileObject'));
    }

    /**
     * @param $error
     *
     * @return mixed
     */
    protected function getErrorMessage($error)
    {
        return array_key_exists($error, $this->error_messages) ? $this->error_messages[$error] : $error;
    }

    /**
     * @param $val
     *
     * @return float
     */
    function getConfigBytes($val)
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $this->fixIntegerOverflow($val);
    }

    /**
     * @param $uploaded_file
     * @param $file
     * @param $error
     * @param $index
     *
     * @return bool
     */
    protected function validate($uploaded_file, $file, $error, $index)
    {
        if ($error) {
            $file->error = $this->getErrorMessage($error);

            return false;
        }
        $content_length = $this->fixIntegerOverflow(intval($this->getServerVar('CONTENT_LENGTH')));
        $post_max_size  = $this->getConfigBytes(ini_get('post_max_size'));
        if ($post_max_size && ($content_length > $post_max_size)) {
            $file->error = $this->getErrorMessage('post_max_size');

            return false;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            $file->error = $this->getErrorMessage('accept_file_types');

            return false;
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = $this->getFileSize($uploaded_file);
        } else {
            $file_size = $content_length;
        }
        if ($this->options['max_file_size'] && (
                $file_size > $this->options['max_file_size'] ||
                $file->size > $this->options['max_file_size'])
        ) {
            $file->error = $this->getErrorMessage('max_file_size');

            return false;
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']
        ) {
            $file->error = $this->getErrorMessage('min_file_size');

            return false;
        }
        if (is_int($this->options['max_number_of_files']) &&
            ($this->countFileObjects() >= $this->options['max_number_of_files']) &&
            // Ignore additional chunks of existing files:
            !is_file($this->getUploadPath($file->name))
        ) {
            $file->error = $this->getErrorMessage('max_number_of_files');

            return false;
        }
        $max_width  = @$this->options['max_width'];
        $max_height = @$this->options['max_height'];
        $min_width  = @$this->options['min_width'];
        $min_height = @$this->options['min_height'];

        if (getimagesize($uploaded_file) && ($max_width || $max_height || $min_width || $min_height)) {
            list($img_width, $img_height) = $this->getImageSize($uploaded_file);
        }

        if (!empty($img_width)) {
            if ($max_width && $img_width > $max_width) {
                $file->error = $this->getErrorMessage('max_width');

                return false;
            }
            if ($max_height && $img_height > $max_height) {
                $file->error = $this->getErrorMessage('max_height');

                return false;
            }
            if ($min_width && $img_width < $min_width) {
                $file->error = $this->getErrorMessage('min_width');

                return false;
            }
            if ($min_height && $img_height < $min_height) {
                $file->error = $this->getErrorMessage('min_height');

                return false;
            }
        }

        return true;
    }

    /**
     * @param $matches
     *
     * @return string
     */
    protected function upcountNameCallback($matches)
    {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext   = isset($matches[2]) ? $matches[2] : '';

        return ' (' . $index . ')' . $ext;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function upcountName($name)
    {
        return preg_replace_callback('/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/', [$this, 'upcountNameCallback'], $name, 1);
    }

    /**
     * @param $file_path
     * @param $name
     * @param $size
     * @param $type
     * @param $error
     * @param $index
     * @param $content_range
     *
     * @return mixed
     */
    protected function getUniqueFilename($file_path, $name, $size, $type, $error, $index, $content_range) {
        while (is_dir($this->getUploadPath($name))) {
            $name = $this->upcountName($name);
        }
        // Keep an existing filename if this is part of a chunked upload:
        $uploaded_bytes = $this->fixIntegerOverflow(intval($content_range[1]));
        while (is_file($this->getUploadPath($name))) {
            if ($uploaded_bytes === $this->getFileSize($this->getUploadPath($name))) {
                break;
            }
            $name = $this->upcountName($name);
        }

        return $name;
    }

    /**
     * @param $file_path
     * @param $name
     * @param $size
     * @param $type
     * @param $error
     * @param $index
     * @param $content_range
     *
     * @return mixed|string
     */
    protected function trimFileName($file_path, $name, $size, $type, $error, $index, $content_range)
    {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $name = trim(basename(stripslashes($name)), ".\x00..\x20");
        // Use a timestamp for empty filenames:
        if (!$name) {
            $name = str_replace('.', '-', microtime(true));
        }
        // Add missing file extension for known image types:
        if (strpos($name, '.') === false && preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches))
        {
            $name .= '.' . $matches[1];
        }
        if (function_exists('exif_imagetype')) {
            switch (@exif_imagetype($file_path)) {
                case IMAGETYPE_JPEG:
                    $extensions = ['jpg', 'jpeg'];
                    break;
                case IMAGETYPE_PNG:
                    $extensions = ['png'];
                    break;
                case IMAGETYPE_GIF:
                    $extensions = ['gif'];
                    break;
            }
            // Adjust incorrect image file extensions:
            if (!empty($extensions)) {
                $parts    = explode('.', $name);
                $extIndex = count($parts) - 1;
                $ext      = strtolower(@$parts[$extIndex]);
                if (!in_array($ext, $extensions)) {
                    $parts[$extIndex] = $extensions[0];
                    $name             = implode('.', $parts);
                }
            }
        }

        return $name;
    }

    /**
     * @param $file_path
     * @param $name
     * @param $size
     * @param $type
     * @param $error
     * @param $index
     * @param $content_range
     *
     * @return mixed
     */
    protected function getFileName($file_path, $name, $size, $type, $error, $index, $content_range) {

        $trimFileName = $this->trimFileName($file_path, $name, $size, $type, $error, $index, $content_range);
        return $this->getUniqueFilename($file_path, $trimFileName, $size, $type, $error, $index, $content_range);
    }

    protected function handleFormData($file, $index)
    {
        // Handle form data, e.g. $_REQUEST['description'][$index]
    }

    protected function getScaledImageFilePaths($file_name, $version)
    {
        $file_path = $this->getUploadPath($file_name);
        if (!empty($version)) {
            $version_dir = $this->getUploadPath(null, $version);
            if (!is_dir($version_dir)) {
                mkdir($version_dir, $this->options['mkdir_mode'], true);
            }
            $new_file_path = $version_dir . '/' . $file_name;
        } else {
            $new_file_path = $file_path;
        }

        return [$file_path, $new_file_path];
    }

    /**
     * @param      $file_path
     * @param      $func
     * @param bool $no_cache
     *
     * @return mixed
     */
    protected function gdGetImageObject($file_path, $func, $no_cache = false)
    {
        if (empty($this->imageObjects[$file_path]) || $no_cache) {
            $this->gdDestroyImageObject($file_path);
            $this->imageObjects[$file_path] = $func($file_path);
        }

        return $this->imageObjects[$file_path];
    }

    /**
     * @param $file_path
     * @param $image
     */
    protected function gdSetImageObject($file_path, $image)
    {
        $this->gdDestroyImageObject($file_path);
        $this->imageObjects[$file_path] = $image;
    }

    /**
     * @param $file_path
     *
     * @return bool
     */
    protected function gdDestroyImageObject($file_path)
    {
        $image = @$this->imageObjects[$file_path];

        return $image && imagedestroy($image);
    }

    /**
     * @param $image
     * @param $mode
     *
     * @return bool|resource
     */
    protected function gdImageflip($image, $mode)
    {
        if (function_exists('imageflip')) {
            return imageflip($image, $mode);
        }
        $new_width  = $src_width = imagesx($image);
        $new_height = $src_height = imagesy($image);
        $new_img    = imagecreatetruecolor($new_width, $new_height);
        $src_x      = 0;
        $src_y      = 0;
        switch ($mode) {
            case '1': // flip on the horizontal axis
                $src_y      = $new_height - 1;
                $src_height = -$new_height;
                break;
            case '2': // flip on the vertical axis
                $src_x     = $new_width - 1;
                $src_width = -$new_width;
                break;
            case '3': // flip on both axes
                $src_y      = $new_height - 1;
                $src_height = -$new_height;
                $src_x      = $new_width - 1;
                $src_width  = -$new_width;
                break;
            default:
                return $image;
        }
        imagecopyresampled($new_img, $image, 0, 0, $src_x, $src_y, $new_width, $new_height, $src_width, $src_height);

        return $new_img;
    }

    /**
     * @param $file_path
     * @param $src_img
     *
     * @return bool
     */
    protected function gdOrientImage($file_path, $src_img)
    {
        if (!function_exists('exif_read_data')) {
            return false;
        }
        $exif = @exif_read_data($file_path);
        if ($exif === false) {
            return false;
        }
        $orientation = intval(@$exif['Orientation']);
        if ($orientation < 2 || $orientation > 8) {
            return false;
        }
        switch ($orientation) {
            case 2:
                $new_img = $this->gdImageflip($src_img, defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2);
                break;
            case 3:
                $new_img = imagerotate($src_img, 180, 0);
                break;
            case 4:
                $new_img = $this->gdImageflip($src_img, defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1);
                break;
            case 5:
                $tmp_img = $this->gdImageflip($src_img, defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1);
                $new_img = imagerotate($tmp_img, 270, 0);
                imagedestroy($tmp_img);
                break;
            case 6:
                $new_img = imagerotate($src_img, 270, 0);
                break;
            case 7:
                $tmp_img = $this->gdImageflip($src_img, defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2);
                $new_img = imagerotate($tmp_img, 270, 0);
                imagedestroy($tmp_img);
                break;
            case 8:
                $new_img = imagerotate($src_img, 90, 0);
                break;
            default:
                return false;
        }
        $this->gdSetImageObject($file_path, $new_img);

        return true;
    }

    /**
     * @param $file_name
     * @param $version
     * @param $options
     *
     * @return bool
     */
    protected function gdCreateScaledImage($file_name, $version, $options)
    {
        if (!function_exists('imagecreatetruecolor')) {
            error_log('Function not found: imagecreatetruecolor');

            return false;
        }
        list($file_path, $new_file_path) = $this->getScaledImageFilePaths($file_name, $version);
        $type = strtolower(substr(strrchr($file_name, '.'), 1));
        switch ($type) {
            case 'jpg':
            case 'jpeg':
                $src_func      = 'imagecreatefromjpeg';
                $write_func    = 'imagejpeg';
                $image_quality = isset($options['jpeg_quality']) ?
                    $options['jpeg_quality'] : 75;
                break;
            case 'gif':
                $src_func      = 'imagecreatefromgif';
                $write_func    = 'imagegif';
                $image_quality = null;
                break;
            case 'png':
                $src_func      = 'imagecreatefrompng';
                $write_func    = 'imagepng';
                $image_quality = isset($options['png_quality']) ?
                    $options['png_quality'] : 9;
                break;
            default:
                return false;
        }
        $src_img        = $this->gdGetImageObject($file_path, $src_func, !empty($options['no_cache']));
        $image_oriented = false;
        if (!empty($options['auto_orient']) && $this->gdOrientImage($file_path, $src_img)) {
            $image_oriented = true;
            $src_img        = $this->gdGetImageObject($file_path, $src_func);
        }
        $max_width  = $img_width = imagesx($src_img);
        $max_height = $img_height = imagesy($src_img);
        if (!empty($options['max_width'])) {
            $max_width = $options['max_width'];
        }
        if (!empty($options['max_height'])) {
            $max_height = $options['max_height'];
        }
        $scale = min(
            $max_width / $img_width,
            $max_height / $img_height
        );
        if ($scale >= 1) {
            if ($image_oriented) {
                return $write_func($src_img, $new_file_path, $image_quality);
            }
            if ($file_path !== $new_file_path) {
                return copy($file_path, $new_file_path);
            }

            return true;
        }
        if (empty($options['crop'])) {
            $new_width  = $img_width * $scale;
            $new_height = $img_height * $scale;
            $dst_x      = 0;
            $dst_y      = 0;
            $new_img    = imagecreatetruecolor($new_width, $new_height);
        } else {
            if (($img_width / $img_height) >= ($max_width / $max_height)) {
                $new_width  = $img_width / ($img_height / $max_height);
                $new_height = $max_height;
            } else {
                $new_width  = $max_width;
                $new_height = $img_height / ($img_width / $max_width);
            }
            $dst_x   = 0 - ($new_width - $max_width) / 2;
            $dst_y   = 0 - ($new_height - $max_height) / 2;
            $new_img = imagecreatetruecolor($max_width, $max_height);
        }
        // Handle transparency in GIF and PNG images:
        switch ($type) {
            case 'gif':
            case 'png':
                imagecolortransparent($new_img, imagecolorallocate($new_img, 0, 0, 0));
            case 'png':
                imagealphablending($new_img, false);
                imagesavealpha($new_img, true);
                break;
        }
        $success = imagecopyresampled($new_img, $src_img, $dst_x, $dst_y, 0, 0, $new_width, $new_height, $img_width, $img_height) && $write_func($new_img, $new_file_path, $image_quality);
        $this->gdSetImageObject($file_path, $new_img);

        return $success;
    }

    /**
     * @param      $file_path
     * @param bool $no_cache
     *
     * @return mixed
     */
    protected function imagickGetImageObject($file_path, $no_cache = false)
    {
        if (empty($this->imageObjects[$file_path]) || $no_cache) {
            $this->imagickDestroyImageObject($file_path);
            $image = new Imagick();
            if (!empty($this->options['imagick_resource_limits'])) {
                foreach ($this->options['imagick_resource_limits'] as $type => $limit) {
                    $image->setResourceLimit($type, $limit);
                }
            }
            $image->readImage($file_path);
            $this->imageObjects[$file_path] = $image;
        }

        return $this->imageObjects[$file_path];
    }

    /**
     * @param $file_path
     * @param $image
     */
    protected function imagickSetImageObject($file_path, $image)
    {
        $this->imagickDestroyImageObject($file_path);
        $this->imageObjects[$file_path] = $image;
    }

    /**
     * @param $file_path
     *
     * @return bool
     */
    protected function imagickDestroyImageObject($file_path)
    {
        $image = @$this->imageObjects[$file_path];

        return $image && $image->destroy();
    }

    /**
     * @param $image
     *
     * @return bool
     */
    protected function imagickOrientImage($image)
    {
        $orientation = $image->getImageOrientation();
        $background  = new ImagickPixel('none');
        switch ($orientation) {
            case imagick::ORIENTATION_TOPRIGHT: // 2
                $image->flopImage(); // horizontal flop around y-axis
                break;
            case imagick::ORIENTATION_BOTTOMRIGHT: // 3
                $image->rotateImage($background, 180);
                break;
            case imagick::ORIENTATION_BOTTOMLEFT: // 4
                $image->flipImage(); // vertical flip around x-axis
                break;
            case imagick::ORIENTATION_LEFTTOP: // 5
                $image->flopImage(); // horizontal flop around y-axis
                $image->rotateImage($background, 270);
                break;
            case imagick::ORIENTATION_RIGHTTOP: // 6
                $image->rotateImage($background, 90);
                break;
            case imagick::ORIENTATION_RIGHTBOTTOM: // 7
                $image->flipImage(); // vertical flip around x-axis
                $image->rotateImage($background, 270);
                break;
            case imagick::ORIENTATION_LEFTBOTTOM: // 8
                $image->rotateImage($background, 270);
                break;
            default:
                return false;
        }
        $image->setImageOrientation(imagick::ORIENTATION_TOPLEFT); // 1
        return true;
    }

    /**
     * @param $file_name
     * @param $version
     * @param $options
     *
     * @return bool
     */
    protected function imagickCreateScaledImage($file_name, $version, $options)
    {
        list($file_path, $new_file_path) = $this->getScaledImageFilePaths($file_name, $version);
        $image = $this->imagickGetImageObject($file_path, !empty($options['no_cache']));
        if ($image->getImageFormat() === 'GIF') {
            // Handle animated GIFs:
            $images = $image->coalesceImages();
            foreach ($images as $frame) {
                $image = $frame;
                $this->imagickSetImageObject($file_name, $image);
                break;
            }
        }
        $image_oriented = false;
        if (!empty($options['auto_orient'])) {
            $image_oriented = $this->imagickOrientImage($image);
        }
        $new_width  = $max_width = $img_width = $image->getImageWidth();
        $new_height = $max_height = $img_height = $image->getImageHeight();
        if (!empty($options['max_width'])) {
            $new_width = $max_width = $options['max_width'];
        }
        if (!empty($options['max_height'])) {
            $new_height = $max_height = $options['max_height'];
        }
        if (!($image_oriented || $max_width < $img_width || $max_height < $img_height)) {
            if ($file_path !== $new_file_path) {
                return copy($file_path, $new_file_path);
            }

            return true;
        }
        $crop = !empty($options['crop']);
        if ($crop) {
            $x = 0;
            $y = 0;
            if (($img_width / $img_height) >= ($max_width / $max_height)) {
                $new_width = 0; // Enables proportional scaling based on max_height
                $x         = ($img_width / ($img_height / $max_height) - $max_width) / 2;
            } else {
                $new_height = 0; // Enables proportional scaling based on max_width
                $y          = ($img_height / ($img_width / $max_width) - $max_height) / 2;
            }
        }
        $success = $image->resizeImage(
            $new_width,
            $new_height,
            isset($options['filter']) ? $options['filter'] : imagick::FILTER_LANCZOS,
            isset($options['blur']) ? $options['blur'] : 1,
            $new_width && $new_height // fit image into constraints if not to be cropped
        );
        if ($success && $crop) {
            $success = $image->cropImage($max_width, $max_height, $x, $y);
            if ($success) {
                $success = $image->setImagePage($max_width, $max_height, 0, 0);
            }
        }
        $type = strtolower(substr(strrchr($file_name, '.'), 1));
        switch ($type) {
            case 'jpg':
            case 'jpeg':
                if (!empty($options['jpeg_quality'])) {
                    $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                    $image->setImageCompressionQuality($options['jpeg_quality']);
                }
                break;
        }
        if (!empty($options['strip'])) {
            $image->stripImage();
        }

        return $success && $image->writeImage($new_file_path);
    }

    /**
     * @param $file_name
     * @param $version
     * @param $options
     *
     * @return bool
     */
    protected function imageMagickCreateScaledImage($file_name, $version, $options)
    {
        list($file_path, $new_file_path) =
            $this->getScaledImageFilePaths($file_name, $version);
        $resize = @$options['max_width']
            . (empty($options['max_height']) ? '' : 'x' . $options['max_height']);
        if (!$resize && empty($options['auto_orient'])) {
            if ($file_path !== $new_file_path) {
                return copy($file_path, $new_file_path);
            }

            return true;
        }
        $cmd = $this->options['convert_bin'];
        if (!empty($this->options['convert_params'])) {
            $cmd .= ' ' . $this->options['convert_params'];
        }
        $cmd .= ' ' . escapeshellarg($file_path);
        if (!empty($options['auto_orient'])) {
            $cmd .= ' -auto-orient';
        }
        if ($resize) {
            // Handle animated GIFs:
            $cmd .= ' -coalesce';
            if (empty($options['crop'])) {
                $cmd .= ' -resize ' . escapeshellarg($resize . '>');
            } else {
                $cmd .= ' -resize ' . escapeshellarg($resize . '^');
                $cmd .= ' -gravity center';
                $cmd .= ' -crop ' . escapeshellarg($resize . '+0+0');
            }
            // Make sure the page dimensions are correct (fixes offsets of animated GIFs):
            $cmd .= ' +repage';
        }
        if (!empty($options['convert_params'])) {
            $cmd .= ' ' . $options['convert_params'];
        }
        $cmd .= ' ' . escapeshellarg($new_file_path);
        exec($cmd, $output, $error);
        if ($error) {
            error_log(implode('\n', $output));

            return false;
        }

        return true;
    }

    /**
     * @param $file_path
     *
     * @return array|bool
     */
    protected function getImageSize($file_path)
    {
        if ($this->options['image_library']) {
            if (extension_loaded('imagick')) {
                $image = new Imagick();
                try {
                    if (@$image->pingImage($file_path)) {
                        $dimensions = [$image->getImageWidth(), $image->getImageHeight()];
                        $image->destroy();

                        return $dimensions;
                    }

                    return false;
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                }
            }
            if ($this->options['image_library'] === 2) {
                $cmd = $this->options['identify_bin'];
                $cmd .= ' -ping ' . escapeshellarg($file_path);
                exec($cmd, $output, $error);
                if (!$error && !empty($output)) {
                    // image.jpg JPEG 1920x1080 1920x1080+0+0 8-bit sRGB 465KB 0.000u 0:00.000
                    $infos      = preg_split('/\s+/', $output[0]);
                    $dimensions = preg_split('/x/', $infos[2]);

                    return $dimensions;
                }

                return false;
            }
        }
        if (!function_exists('getimagesize')) {
            error_log('Function not found: getimagesize');

            return false;
        }

        return @getimagesize($file_path);
    }

    /**
     * @param $file_name
     * @param $version
     * @param $options
     *
     * @return bool
     */
    protected function createScaledImage($file_name, $version, $options)
    {
        if ($this->options['image_library'] === 2) {
            return $this->imageMagickCreateScaledImage($file_name, $version, $options);
        }
        if ($this->options['image_library'] && extension_loaded('imagick')) {
            return $this->imagickCreateScaledImage($file_name, $version, $options);
        }

        return $this->gdCreateScaledImage($file_name, $version, $options);
    }

    /**
     * @param $file_path
     *
     * @return bool
     */
    protected function destroyImageObject($file_path)
    {
        if ($this->options['image_library'] && extension_loaded('imagick')) {
            return $this->imagickDestroyImageObject($file_path);
        }
    }

    /**
     * @param $file_path
     *
     * @return bool|int
     */
    protected function isValidImageFile($file_path)
    {
        if (!preg_match($this->options['image_file_types'], $file_path)) {
            return false;
        }
        if (function_exists('exif_imagetype')) {
            return @exif_imagetype($file_path);
        }
        $image_info = $this->getImageSize($file_path);

        return $image_info && $image_info[0] && $image_info[1];
    }

    /**
     * @param $file_path
     * @param $file
     */
    protected function handleImageFile($file_path, $file)
    {
        $failed_versions = [];
        foreach ($this->options['image_versions'] as $version => $options) {
            if ($this->createScaledImage($file->name, $version, $options)) {
                if (!empty($version)) {
                    $file->{$version . 'Url'} = $this->getDownloadUrl($file->name, $version);
                } else {
                    $file->size = $this->getFileSize($file_path, true);
                }
            } else {
                $failed_versions[] = $version ? $version : 'original';
            }
        }
        if (count($failed_versions)) {
            $file->error = $this->getErrorMessage('image_resize') . ' (' . implode($failed_versions, ', ') . ')';
        }
        // Free memory:
        $this->destroyImageObject($file_path);
    }

    /**
     * @param      $uploaded_file
     * @param      $name
     * @param      $description
     * @param      $size
     * @param      $type
     * @param      $error
     * @param null $index
     * @param null $content_range
     *
     * @return stdClass
     */
    protected function handleFileUpload($uploaded_file, $name, $description, $size, $type, $error, $index = null, $content_range = null)
    {
        $file              = new stdClass();
        $file->existe      = $this->deletefileifexiste($name);
        $file->name        = $this->getFileName($uploaded_file, $name, $size, $type, $error, $index, $content_range);
        $file->description = $description;
        $file->size        = $this->fixIntegerOverflow(intval($size));
        $file->type        = $type;

        $this->setMiniature($file);

        if ($this->validate($uploaded_file, $file, $error, $index)) {
            $this->handleFormData($file, $index);
            $upload_dir = $this->getUploadPath();
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, $this->options['mkdir_mode'], true);
            }
            $file_path   = $this->getUploadPath($file->name);
            $append_file = $content_range && is_file($file_path) &&
                $file->size > $this->getFileSize($file_path);
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            $file_size = $this->getFileSize($file_path, $append_file);
            if ($file_size === $file->size) {
                $file->url = $this->getDownloadUrl($file->name);
                if ($this->isValidImageFile($file_path)) {
                    $this->handleImageFile($file_path, $file);
                }
                //insert dans la base de données bdd

                $this->insertIntoBdd($file, $file_path);
                $this->setBddProperties($file);
                //end
            } else {
                $file->size = $file_size;
                if (!$content_range && $this->options['discard_aborted_uploads']) {
                    unlink($file_path);
                    $file->error = $this->getErrorMessage('abort');
                }
            }
            $this->setAdditionalFileProperties($file);
        }

        return $file;
    }

    /**
     * @param $file_path
     *
     * @return float|int
     */
    protected function readfile($file_path)
    {
        $file_size  = $this->getFileSize($file_path);
        $chunk_size = $this->options['readfile_chunk_size'];
        if ($chunk_size && $file_size > $chunk_size) {
            $handle = fopen($file_path, 'rb');
            while (!feof($handle)) {
                echo fread($handle, $chunk_size);
                ob_flush();
                flush();
            }
            fclose($handle);

            return $file_size;
        }

        return readfile($file_path);
    }

    /**
     * @param $str
     */
    protected function body($str)
    {
        echo $str;
    }

    /**
     * @param $str
     */
    protected function setHeader($str)
    {
        $this->headers[] = $str;
        /*header($str);*/
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    protected function getServerVar($id)
    {
        return $this->request->server->get($id, '');
    }

    /**
     * @param      $content
     * @param bool $print_response
     *
     * @return JsonResponse|void
     */
    protected function generateResponse($content, $print_response = true)
    {
        $data = '';
        if ($print_response) {
            $json     = json_encode($content);
            $redirect = isset($_REQUEST['redirect']) ? stripslashes($_REQUEST['redirect']) : null;
            if ($redirect) {
                $this->setHeader('Location: ' . sprintf($redirect, rawurlencode($json)));

                return;
            }
            $this->head();
            if ($this->getServerVar('HTTP_CONTENT_RANGE')) {
                $files = isset($content[$this->options['param_name']]) ?
                    $content[$this->options['param_name']] : null;
                if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
                    $this->setHeader('Range: 0-' . ($this->fixIntegerOverflow(intval($files[0]->size)) - 1));
                }
            }
            //$this->body($json);
            $data = $content;
        }

        return new JsonResponse($data, 200, array_values($this->headers));
    }

    /**
     * @return null|string
     */
    protected function getVersionParam()
    {
        return isset($_GET['version']) ? basename(stripslashes($_GET['version'])) : null;
    }

    /**
     * @return string
     */
    protected function getSingularParamName()
    {
        return substr($this->options['param_name'], 0, -1);
    }

    /**
     * @return null|string
     */
    protected function getFileNameParam()
    {
        $name = $this->getSingularParamName();

        return isset($_GET[$name]) ? basename(stripslashes($_GET[$name])) : null;
    }

    /**
     * @param bool $paramName
     *
     * @return array
     */
    protected function getFileNamesParams($paramName = false)
    {

        $paramName = (!$paramName) ? $this->options['param_name'] : $paramName;

        $params = (array)$this->request->query->get($paramName, []);

        foreach ($params as $key => $value) {
            $params[$key] = basename(stripslashes($value));
        }

        return $params;
    }

    /**
     * @param $file_path
     *
     * @return string
     */
    protected function getFileType($file_path)
    {
        switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                return strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        }
    }

    /**
     *
     */
    protected function download()
    {
        switch ($this->options['download_via_php']) {
            case 1:
                $redirect_header = null;
                break;
            case 2:
                $redirect_header = 'X-Sendfile';
                break;
            case 3:
                $redirect_header = 'X-Accel-Redirect';
                break;
            default:
                return $this->setHeader('HTTP/1.1 403 Forbidden');
        }
        $file_name = $this->getFileNameParam();
        if (!$this->isValidFileObject($file_name)) {
            return $this->setHeader('HTTP/1.1 404 Not Found');
        }
        if ($redirect_header) {
            return $this->setHeader($redirect_header . ': ' . $this->getDownloadUrl($file_name, $this->getVersionParam(), true));
        }
        $file_path = $this->getUploadPath($file_name, $this->getVersionParam());
        // Prevent browsers from MIME-sniffing the content-type:
        $this->setHeader('X-Content-Type-Options: nosniff');
        if (!preg_match($this->options['inline_file_types'], $file_name)) {
            $this->setHeader('Content-Type: application/octet-stream');
            $this->setHeader('Content-Disposition: attachment; filename="' . $file_name . '"');
        } else {
            $this->setHeader('Content-Type: ' . $this->getFileType($file_path));
            $this->setHeader('Content-Disposition: inline; filename="' . $file_name . '"');
        }
        $this->setHeader('Content-Length: ' . $this->getFileSize($file_path));
        $this->setHeader('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file_path)));
        $this->readfile($file_path);
    }

    /**
     * @return JsonResponse|void
     */
    protected function sendContentTypeHeader()
    {
        $this->setHeader('Vary: Accept');
        if (strpos($this->getServerVar('HTTP_ACCEPT'), 'application/json') !== false) {
            $this->setHeader('Content-type: application/json');
        } else {
            $this->setHeader('Content-type: text/plain');
        }

        return $this->generateResponse('', false);
    }

    /**
     *
     */
    protected function sendAccessControlHeaders()
    {
        $this->setHeader('Access-Control-Allow-Origin: ' . $this->options['access_control_allow_origin']);
        $this->setHeader('Access-Control-Allow-Credentials: ' . ($this->options['access_control_allow_credentials'] ? 'true' : 'false'));
        $this->setHeader('Access-Control-Allow-Methods: ' . implode(', ', $this->options['access_control_allow_methods']));
        $this->setHeader('Access-Control-Allow-Headers: ' . implode(', ', $this->options['access_control_allow_headers']));
    }

    /**
     *
     */
    public function head()
    {
        $this->setHeader('Pragma: no-cache');
        $this->setHeader('Cache-Control: no-store, no-cache, must-revalidate');
        $this->setHeader('Content-Disposition: inline; filename="files.json"');
        // Prevent Internet Explorer from MIME-sniffing the content-type:
        $this->setHeader('X-Content-Type-Options: nosniff');
        if ($this->options['access_control_allow_origin']) {
            $this->sendAccessControlHeaders();
        }
        $this->sendContentTypeHeader();
    }

    /**
     * @param bool $print_response
     *
     * @return JsonResponse|void
     */
    public function get($print_response = true)
    {
        if ($print_response && isset($_GET['download'])) {
            return $this->download();
        }

        $file_name = $this->getFileNameParam();

        if ($file_name) {
            $response = [$this->getSingularParamName() => $this->getFileObject($file_name)];
        } else {
            $response = [$this->options['param_name'] => $this->getFileObjects()];
        }

        return $this->generateResponse($response, $print_response);
    }

    /**
     * @param bool $print_response
     *
     * @return JsonResponse|void
     */
    public function post($print_response = true)
    {
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            return $this->delete($print_response);
        }
        $descriptions = isset($_REQUEST['descriptions']) ? $_REQUEST['descriptions'] : [];

        $upload = isset($_FILES[$this->options['param_name']]) ?
            $_FILES[$this->options['param_name']] : null;
        // Parse the Content-Disposition header, if available:
        $file_name = $this->getServerVar('HTTP_CONTENT_DISPOSITION') ?
            rawurldecode(preg_replace('/(^[^"]+")|("$)/', '', $this->getServerVar('HTTP_CONTENT_DISPOSITION'))) : null;
        // Parse the Content-Range header, which has the following form:
        // Content-Range: bytes 0-524287/2000000
        $content_range = $this->getServerVar('HTTP_CONTENT_RANGE') ?
            preg_split('/[^0-9]+/', $this->getServerVar('HTTP_CONTENT_RANGE')) : null;
        $size          = $content_range ? $content_range[3] : null;
        $files         = [];
        if ($upload && is_array($upload['tmp_name'])) {
            // param_name is an array identifier like "files[]",
            // $_FILES is a multi-dimensional array:
            foreach ($upload['tmp_name'] as $index => $value) {
                $name    = $file_name ? $file_name : $upload['name'][$index];
                $files[] = $this->handleFileUpload(
                    $upload['tmp_name'][$index],
                    $file_name ? $file_name : $upload['name'][$index],
                    trim(isset($descriptions[$name]) ? $descriptions[$name] : ""),
                    $size ? $size : $upload['size'][$index],
                    $upload['type'][$index],
                    $upload['error'][$index],
                    $index,
                    $content_range
                );
            }
        } else {
            $name = $file_name ? $file_name : (isset($upload['name']) ? $upload['name'] : null);
            // param_name is a single object identifier like "file",
            // $_FILES is a one-dimensional array:
            $files[] = $this->handleFileUpload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                $file_name ? $file_name : (isset($upload['name']) ?
                    $upload['name'] : null),
                trim(isset($descriptions[$name]) ? $descriptions[$name] : ""),
                $size ? $size : (isset($upload['size']) ?
                    $upload['size'] : $this->getServerVar('CONTENT_LENGTH')),
                isset($upload['type']) ?
                    $upload['type'] : $this->getServerVar('CONTENT_TYPE'),
                isset($upload['error']) ? $upload['error'] : null,
                null,
                $content_range
            );
        }

        return $this->generateResponse(
            [$this->options['param_name'] => $files],
            $print_response
        );
    }

    /**
     * @param bool $print_response
     *
     * @return JsonResponse|void
     */
    public function delete($print_response = true)
    {
        $file_names = $this->getFileNamesParams('file');

        if (empty($file_names)) {
            $file_names = [$this->getFileNameParam()];
        }
        $response = [];
        foreach ($file_names as $file_name) {
            $file_path = $this->getUploadPath($file_name);
            $this->deleteFromBdd($file_name);

            $success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
            if ($success) {
                foreach ($this->options['image_versions'] as $version => $options) {

                    if (!empty($version)) {
                        $file = $this->getUploadPath($file_name, $version);

                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                }

                $this->deleteFromBdd($file_name);
                $response[$file_name] = $success;
            }
        }

        return $this->generateResponse($response, $print_response);
    }

    /**
     * @param $file
     * @param $file_path
     */
    protected function insertIntoBdd($file, $file_path)
    {
        $name      = html_entity_decode($file->name, null, "UTF-8");
        $uploadDir = $this->options['upload_url'];

        if ($uploadDir[0] == '/') {
            $uploadDir = substr($uploadDir, 1);
        }

        $object = false;

        if (!$file->existe) {//INSERT
            $object = new File($this->options['upload_url']);
        } else {//UPDATE
            $object = $this->em->getRepository(File::class)->findOneBy(
                ['name' => $name, 'url' => $uploadDir . $name]
            ); //,'createdBy'=>$this->user]);
        }

        if ($object) {
            $object
                ->setName($file->name)
                ->setUrl($uploadDir . $file->name)
                ->setDescription($file->description)
                ->setType($file->type)
                ->setSize($file->size);

            $this->em->persist($object);
            $this->em->flush();
        }

    }

    /**
     * @param $file_name
     */
    protected function deleteFromBdd($file_name)
    {
        $name = html_entity_decode($file_name, null, "UTF-8");

        $uploadDir = trim($this->options['upload_url'], '/');

        $url = $uploadDir . DIRECTORY_SEPARATOR . $name;

        $object = $this->em->getRepository(File::class)->findOneBy(
            ['name' => $name, 'url' => $url]
        ); //, 'createdBy'=>$this->user]);
        if ($object) {
            $this->em->remove($object);
        }
        $this->em->flush();
    }


    /**
     * @param $file
     */
    protected function setBddProperties($file)
    {
        $uploadDir = $this->options['upload_url'];

        if ($uploadDir[0] == '/') {
            $uploadDir = substr($uploadDir, 1);
        }

        $name = html_entity_decode($file->name, null, "UTF-8");
        $name = html_entity_decode($file->name, null, "UTF-8");
        $url  = $uploadDir . $name;

        $object = $this->em->getRepository(File::class)->findOneBy(
            ['url' => $url, 'name' => $name]
        ); //, 'createdBy'=>$this->user]);
        if ($object) {
            $file->id   = $object->getId();
            $file->slug = $object->getSlug();
            //$file->user_uplod=$object->getCreatedBy()->getUsername();
            $file->description       = $object->getDescription();
            $file->created_at        = $object->getCreatedAt()->format('D d M Y');
            $file->updated_at        = $object->getUpdatedAt()->format('D d M Y');
            $file->partageFaxebook   = str_replace(
                '__slug__',
                $object->getSlug(),
                $this->options['facebookPartageUrl']
            );
            $file->youtubePartageUrl = str_replace('__slug__', $object->getSlug(), $this->options['youtubePartageUrl']);

            if ($this->options['folder'] == "videos") {
                $file->voirVideoUrl = str_replace('__slug__', $object->getSlug(), $this->options['voirVideoUrl']);
            }
        }
        $file->type = $this->getFileType($file->url);


        if ($this->options['folder'] == "pictures") {
            $file->hasSlider = true;
        } else {
            $file->hasSlider = false;
        }
    }

    /**
     * @param $name
     *
     * @return bool
     */
    protected function deletefileifexiste($name)
    {
        $name = html_entity_decode($name, null, "UTF-8");

        $file_name = $name;
        $file_path = $this->getUploadPath($file_name);
        $success   = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
        if ($success) {
            foreach ($this->options['image_versions'] as $version => $options) {
                if (!empty($version)) {
                    $file = $this->getUploadPath($file_name, $version);
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param $file
     */
    protected function setMiniature($file)
    {
        $file_name = $file->name;
        $ext       = $this->getFileType($this->getUploadPath($file_name));
        if (in_array($ext, ['pdf'])) {
            $file->{'thumbnailUrl'} = $this->options['default_pdf_icon'];
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            $file->{'thumbnailUrl'} = $this->options['default_excel_icon'];
        } elseif (in_array($ext, ['csv'])) {
            $file->{'thumbnailUrl'} = $this->options['default_csv_icon'];
        } elseif (in_array($ext, ['xml'])) {
            $file->{'thumbnailUrl'} = $this->options['default_xml_icon'];
        } elseif (in_array($ext, ['docx', 'doc'])) {
            $file->{'thumbnailUrl'} = $this->options['default_doc_icon'];
        } else {
            $file->{'thumbnailUrl'} = $this->options['default_icon'];
        }
    }
}
