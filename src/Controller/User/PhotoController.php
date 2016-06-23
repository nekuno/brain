<?php

namespace Controller\User;

use Model\Exception\ValidationException;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class PhotoController
 * @package Controller
 */
class PhotoController
{

    public function getAllAction(Application $app, User $user)
    {

        $manager = $app['users.photo.manager'];

        $photos = $manager->getAll($user->getId());

        return $app->json($photos);
    }

    public function getAction(Application $app, $id)
    {

        $manager = $app['users.photo.manager'];

        $photos = $manager->getAll($id);

        return $app->json($photos);
    }

    public function postAction(Application $app, Request $request, User $user)
    {

        $manager = $app['users.photo.manager'];

        if ($request->request->has('base64')) {
            $base64 = $request->request->get('base64');
            $file = base64_decode($base64);
        } else {
            if ($request->request->has('url')) {
                $url = $request->request->get('url');
                if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                    throw new ValidationException(array('photo' => array('Invalid "url" provided')));
                }
                $file = file_get_contents($url);
                if (!$file) {
                    throw new ValidationException(array('photo' => array('Unable to get photo from "url"')));
                }
            } else {
                throw new ValidationException(array('photo' => array('Invalid photo provided, param "base64" or "url" must be provided')));
            }
        }

        $photo = $manager->create($user->getId(), $file);

        return $app->json($photo, 201);
    }

    public function postProfileAction(Application $app, Request $request, User $user, $id)
    {

        $photo = $app['users.photo.manager']->getById($id);

        if ($photo->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Photo not allowed');
        }

        $base = $app['social_web_dir'] . '/user/images/';

        $oldPicture = $base . $user->getPicture();

        $extension = $photo->getExtension();
        $new = $user->getUsernameCanonical() . '_' . time() . $extension;

        if (!is_readable($photo->getFullPath())) {
            throw new \RuntimeException(sprintf('Source image "%s" does not exists', $photo->getFullPath()));
        }

        $dest = $base . $new;
        $file = file_get_contents($photo->getFullPath());
        $size = getimagesizefromstring($file);
	    $width = $size[0];
	    $height = $size[1];
        $xPercent = $request->request->get('x', 0);
        $yPercent = $request->request->get('y', 0);
        $widthPercent = $request->request->get('width', 100);
        $heightPercent = $request->request->get('height', 100);
		$x = $width * $xPercent/100;
		$y = $height * $yPercent/100;
	    $widthCrop = $width * $widthPercent/100;
	    $heightCrop = $height * $heightPercent/100;
        $image = imagecreatefromstring($file);
        $crop = imagecrop($image, array('x' => $x, 'y' => $y, 'width' => $widthCrop, 'height' => $heightCrop));

        switch ($size['mime']) {
            case 'image/png':
                imagepng($crop, $dest);
                break;
            case 'image/jpeg':
                imagejpeg($crop, $dest);
                break;
            case 'image/gif':
                imagegif($crop, $dest);
                break;
            default:
                throw new ValidationException(array('photo' => array('Invalid mimetype')));
                break;
        }

        $data = array(
            'userId' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'picture' => $new,
        );
        $user = $app['users.manager']->update($data);

        if (file_exists($oldPicture)) {
            unlink($oldPicture);
        }

        return $app->json($user);

    }

    public function deleteAction(Application $app, User $user, $id)
    {

        $manager = $app['users.photo.manager'];

        $photo = $manager->getById($id);

        if ($photo->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Photo not allowed');
        }

        $success = $manager->remove($id);

        if ($success && is_writable($photo->getFullPath())) {
            unlink($photo->getFullPath());
        }

        return $app->json($photo);
    }

    public function validateAction(Request $request, Application $app)
    {

    }

}
