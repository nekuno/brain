<?php

namespace ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\Exception\UrlChangedException;
use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Model\Link\Audio;
use Model\Link\Image;
use Model\Link\Link;
use Model\Link\Video;
use Model\Token\TokensManager;

class TumblrPostProcessor extends AbstractTumblrProcessor
{
    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $token = $preprocessedLink->getToken();
        $firstLink = $preprocessedLink->getFirstLink();
        if (!$postId = $firstLink->getId()) {
            $postId = TumblrUrlParser::getPostId($firstLink->getUrl());
        }
        if (!$blogId = $preprocessedLink->getResourceItemId()) {
            $blogId = TumblrUrlParser::getBlogId($firstLink->getUrl());
        }

        $response = $this->resourceOwner->requestPost($blogId, $postId, $token);
        $post = isset($response['response']['posts'][0]) ? $response['response']['posts'][0] : null;

        if (isset($post['video_type']) && $post['video_type'] === 'youtube' && isset($post['permalink_url'])) {
            $preprocessedLink->setSource(TokensManager::GOOGLE);
            $preprocessedLink->setType(YoutubeUrlParser::VIDEO_URL);
            throw new UrlChangedException($firstLink->getUrl(), $post['permalink_url']);
        }
        if (isset($post['audio_type']) && $post['audio_type'] === 'spotify' && isset($post['audio_source_url'])) {
            $preprocessedLink->setSource(TokensManager::SPOTIFY);
            $preprocessedLink->setType(null);
            throw new UrlChangedException($firstLink->getUrl(), $post['audio_source_url']);
        }

        return $post;
    }

    public function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::addTags($preprocessedLink, $data);

        $link = $preprocessedLink->getFirstLink();

        if (isset($data['tags']) && is_array($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                $link->addTag($this->buildTag($tag));
            }
        }
    }

    protected function buildTag($tagName)
    {
        $tag = array();
        $tag['name'] = $tagName;

        return $tag;
    }

    protected function hydrateAudioLink(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::hydrateLink($preprocessedLink, $data);
        $link = $preprocessedLink->getFirstLink();
        $audio = Audio::buildFromLink($link);
        $title = isset($data['track_name']) ? $data['track_name'] : $data['source_title'];
        $audio->setTitle($title);
        $audio->setDescription($data['album'] . ' : ' . $data['artist']);
        $audio->setEmbedType('tumblr');
        $audio->setEmbedId($data['player']);

        $preprocessedLink->setFirstLink($audio);
    }

    protected function hydrateLinkLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();
        parent::hydrateLink($preprocessedLink, $data);
        $newLink = Link::buildFromLink($link);
        $newLink->setTitle($data['title']);
        $newLink->setDescription(strip_tags($data['description']) ?: strip_tags($data['excerpt']));

        $preprocessedLink->setFirstLink($newLink);

        if (isset($data['url']) && $data['url'] !== $link->getUrl()) {
            $this->changeUrl($preprocessedLink, $data['url'], $data);
        }
    }

    protected function hydratePhotoLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();
        parent::hydrateLink($preprocessedLink, $data);
        $newLink = $this->completePhotoLink($link, $data);

        $preprocessedLink->setFirstLink($newLink);

        if (isset($data['link_url']) && $data['link_url'] !== $link->getUrl()) {
            $this->changeUrl($preprocessedLink, $data['link_url'], $data);
        }
    }

    public function hydrateVideoLink(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::hydrateLink($preprocessedLink, $data);
        $link = $preprocessedLink->getFirstLink();
        $video = $this->completeVideoLink($link, $data);

        $preprocessedLink->setFirstLink($video);
    }

    private function changeUrl(PreprocessedLink $preprocessedLink, $url, $data)
    {
        $link = $preprocessedLink->getFirstLink();
        $preprocessedLink->setSource(null);
        $preprocessedLink->setType(null);
        $preprocessedLink->setResourceItemId(null);
        if (isset($data['photos'][0]['original_size']['url'])) {
            $link->setThumbnail($data['photos'][0]['original_size']['url']);
        }
        $link->setAdditionalLabels(array());
        throw new UrlChangedException($link->getUrl(), $url);
    }

    protected function getAudioImages(PreprocessedLink $preprocessedLink, array $data)
    {
        if (isset($data['album_art'])) {
            return array(new ProcessingImage($data['album_art']));
        }

        return parent::getImages($preprocessedLink, $data);
    }

    protected function getLinkImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $images = array();
        if (isset($data['photos'])) {
            foreach ($data['photos'] as $photo) {
                $originalPhoto = isset($photo['original_size']) ? $photo['original_size'] : null;
                if (isset($originalPhoto['url'])) {
                    $images[] = $this->buildSquareImage($originalPhoto['url'], ProcessingImage::LABEL_LARGE, $originalPhoto['width']);
                }
                $smallerPhotos = isset($photo['alt_sizes']) ? $photo['alt_sizes'] : array();
                foreach ($smallerPhotos as $smallerPhoto) {
                    if (isset($smallerPhoto['url'])) {
                        $sizeLabel = $smallerPhoto['width'] >= 128 ? ProcessingImage::LABEL_MEDIUM : ProcessingImage::LABEL_SMALL;
                        $images[] = $this->buildSquareImage($smallerPhoto['url'], $sizeLabel, $smallerPhoto['width']);
                        if (count($images) >= 3) {
                            break;
                        }
                    }
                }

            }

            if (count($images) > 0) {
                return $images;
            }
        }

        return parent::getImages($preprocessedLink, $data);
    }

    protected function getPhotoImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $images = array();
        if (isset($data['photos'])) {
            foreach ($data['photos'] as $photo) {
                $thumbnails = isset($photo['alt_sizes']) ? $photo['alt_sizes'] : array();
                foreach ($thumbnails as $thumbnail) {
                    if (isset($thumbnail['url'])) {
                        $sizeLabel = $thumbnail['width'] >= 512 ? ProcessingImage::LABEL_LARGE :
                            $thumbnail['width'] >= 128 ? ProcessingImage::LABEL_MEDIUM : ProcessingImage::LABEL_SMALL;
                        $images[] = $this->buildSquareImage($thumbnail['url'], $sizeLabel, $thumbnail['width']);
                        if (count($images) >= 3) {
                            break;
                        }
                    }
                }

            }

            if (count($images) > 0) {
                return $images;
            }
        }

        return parent::getImages($preprocessedLink, $data);
    }

    protected function addAudioTags(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::addTags($preprocessedLink, $data);

        $link = $preprocessedLink->getFirstLink();

        if (isset($data['artist'])) {
            $link->addTag($this->buildArtistTag($data['artist']));
        }
        if (isset($data['album'])) {
            $link->addTag($this->buildAlbumTag($data['album']));
        }
        if (isset($data['track_name'])) {
            $link->addTag($this->buildSongTag($data['track_name']));
        }
    }

    private function buildArtistTag($artist)
    {
        $tag = array();
        $tag['name'] = $artist;
        $tag['additionalLabels'][] = 'Artist';

        return $tag;
    }

    private function buildAlbumTag($album)
    {
        $tag = array();
        $tag['name'] = $album;
        $tag['additionalLabels'][] = 'Album';

        return $tag;
    }

    private function buildSongTag($trackName) {
        $tag = array();
        $tag['name'] = $trackName;
        $tag['additionalLabels'][] = 'Song';

        return $tag;
    }

    private function completePhotoLink(Link $link, $data)
    {
        if (isset($data['summary']) && $data['summary']) {
            $caption = $data['summary'];
        } elseif (isset($data['caption']) && $data['caption']) {
            $caption = strip_tags($data['caption']);
        } else {
            $caption = null;
        }

        if ($caption) {
            $newLink = Link::buildFromLink($link);
            if ($newLinePos = strpos($caption, "\n")) {
                $title = substr($caption, 0, $newLinePos);
                $description = strlen($caption) > $newLinePos + 1 ? substr($caption, $newLinePos + 1) : null;
            } elseif ($dotPos = strpos($caption, '.')) {
                $title = substr($caption, 0, $dotPos);
                $description = strlen($caption) > $dotPos + 1 ? substr($caption, $dotPos + 1) : null;
            } else {
                $title = $caption;
                $description = null;
            }
            $newLink->setTitle($title);
            $newLink->setDescription($description);
        } else {
            $newLink = Image::buildFromLink($link);
        }

        return $newLink;
    }

    private function completeVideoLink(Link $link, $data)
    {
        if (isset($data['summary']) && $data['summary']) {
            $caption = $data['summary'];
        } elseif (isset($data['caption']) && $data['caption']) {
            $caption = strip_tags($data['caption']);
        } else {
            $caption = null;
        }

        $newLink = Video::buildFromLink($link);
        if ($caption) {
            if ($newLinePos = strpos($caption, "\n")) {
                $title = substr($caption, 0, $newLinePos);
                $description = strlen($caption) > $newLinePos + 1 ? substr($caption, $newLinePos + 1) : null;
            } elseif ($dotPos = strpos($caption, '.')) {
                $title = substr($caption, 0, $dotPos);
                $description = strlen($caption) > $dotPos + 1 ? substr($caption, $dotPos + 1) : null;
            } else {
                $title = $caption;
                $description = null;
            }
            $newLink->setTitle($title);
            $newLink->setDescription($description);
        }

        $newLink->setEmbedType('tumblr');
        $newLink->setEmbedId($data['player'][0]['embed_code']);

        return $newLink;
    }
}