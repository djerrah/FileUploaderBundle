<?php

namespace Djerrah\FileUploaderBundle\Controller;

use Djerrah\FileUploaderBundle\Entity\File;
use Djerrah\FileUploaderBundle\Lib\UploadHandler\UploadHandler as UploadHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;

/**
 * Class UploaderController
 *
 * @package Back\AdminBundle\Controller
 */
class UploaderController extends Controller
{

    public function indexAction($folder)
    {
        $object = $this->getUser();
        //$this->get('kernel')->getRootDir() ;
        $templatedownload = file_get_contents(
            dirname(__DIR__) . '/Resources/views/Uploader/Index/templatedownload.html.twig'
        );

        $data = [
            'object'           => $object,
            'folder'           => $folder,
            'templatedownload' => $templatedownload,
        ];

        return $this->render(
            "DjerrahFileUploaderBundle:Uploader:index.html.twig",
            $data
        );
    }

    public function uploaderAction($folder)
    {

        #$em = $this->getDoctrine()->getManager();

        //throw new AccessDeniedException();

        $object = $this->getUser();
        //$this->get('kernel')->getRootDir() ;
        $templateupload   = file_get_contents(
            dirname(__DIR__) . '/Resources/views/Uploader/Uploader/templateUpload.html.twig'
        );
        $templatedownload = file_get_contents(
            dirname(__DIR__) . '/Resources/views/Uploader/Uploader/templatedownload.html.twig'
        );

        $acceptFileTypes = $this->getAcceptFileTypeByFolder($folder);

        $data = [
            'object'           => $object,
            'folder'           => $folder,
            'acceptFileTypes'  => $acceptFileTypes,
            'templateupload'   => $templateupload,
            'templatedownload' => $templatedownload,
        ];

        return $this->render(
            "DjerrahFileUploaderBundle:Uploader:uploader.html.twig",
            $data
        );
    }

    public function audioAction()
    {
        return [];
    }

    public function videoAction($slug)
    {
        $em     = $this->getDoctrine()->getManager();
        $object = $em->getRepository(File::class)->findOneBy(['slug' => $slug]);


        $message  = "";
        $videoUrl = "";
        if ($object) {

            $videoUrl = $object->getUrl();
            $file     = __dir__ . "/../../../../web$videoUrl";
        }

        $data = [
            'video'   => $object,
            'message' => $message,
            'file'    => $file,
        ];


        return $this->render(
            "DjerrahFileUploaderBundle:Uploader:video.html.twig",
            $data
        );

    }

    public function uploadAction(Request $request, $folder, $id)
    {

        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        $path_files         = dirname(
                $this->get('kernel')->getRootDir()
            ) . '/web/uploads/user_' . $id . '/files/' . $folder;
        $path               = dirname(
                $this->get('kernel')->getRootDir()
            ) . '/web/uploads/user_' . $id . '/files/' . $folder . '/';
        $facebookPartageUrl = ''; //$this->generateUrl('reseaux_socieaux_facebook_partage', ['slug'=> '__slug__']);
        $youtubePartageUrl  = ''; //$this->generateUrl('reseaux_socieaux_youtube_partage', ['slug'=> '__slug__']);
        $voirVideoUrl       = ''; //$this->generateUrl('uploader_lecteur_video', ['slug'=> '__slug__']);

        $options = [
            'script_url'         => $this->generateUrl(
                'uploader_upload_php',
                ['folder' => $folder, 'id' => $id]
            ),
            'upload_dir'         => $path,
            'upload_url'         => '/uploads/user_' . $id . '/files/' . $folder . '/',
            'accept_file_types'  => $this->getAcceptFileTypeByFolder($folder),
            'default_pdf_icon'   => '/bundles/djerrahfileuploader/jqueryuploader/img/pdf_icon_large.png',
            'default_excel_icon' => '/bundles/djerrahfileuploader/jqueryuploader/img/xls_icon_large.jpg',
            'default_doc_icon'   => '/bundles/djerrahfileuploader/jqueryuploader/img/doc_icon_large.jpg',
            'default_csv_icon'   => '/bundles/djerrahfileuploader/jqueryuploader/img/csv_icon.png',
            'default_xml_icon'   => '/bundles/djerrahfileuploader/jqueryuploader/img/xml_icon.png',
            'default_icon'       => '/bundles/djerrahfileuploader/jqueryuploader/img/document-icon.png',
            'facebookPartageUrl' => $facebookPartageUrl,
            'youtubePartageUrl'  => $youtubePartageUrl,
            'voirVideoUrl'       => $voirVideoUrl,
            'folder'             => $folder,
        ];


        if (!is_dir($path_files)) {
            exec("mkdir -m 777 $path_files");
        }
        exec("chmod 777  $path_files/*");
        if (!is_dir($path)) {
            exec("mkdir -m 777 $path; chmod 777 $path");
        }

        $upload_handler = new UploadHandler($request, $em, $user, $options);

        return $upload_handler->getResponse();
    }

    /**
     * @param $folder
     *
     * @return string
     */
    private function getAcceptFileTypeByFolder($folder)
    {
        $accept_file_types = "*";

        switch ($folder) {

            case 'pictures':
                $accept_file_types = '/\.(gif|jpe?g|png)$/i';
                break;

            case 'docs':
                $accept_file_types = '/\.(pdf|doc|docx|csv)$/i';
                break;

            case 'videos':
                $accept_file_types = '/\.(avi|mp4|MP4)$/i';
                break;

            case 'music':
                $accept_file_types = '/\.(mp3|mpa|mpga|wav)$/i';
                break;

            default:
                $accept_file_types = '/\.(gif|jpe?g|png)$/i';
                break;
        }

        return $accept_file_types;
    }
}
