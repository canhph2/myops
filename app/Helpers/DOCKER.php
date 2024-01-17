<?php

namespace app\Helpers;


use app\Enum\IconEnum;
use app\Enum\IndentLevelEnum;
use app\Enum\TagEnum;
use app\Objects\DockerImage;
use app\Objects\Process;

/**
 * this is a Docker helper
 */
class DOCKER
{
    public static function isDockerInstalled(): bool
    {
        $dockerPath = trim(exec("which docker"));
        return !(!$dockerPath || STR::contains($dockerPath, 'not found'));
    }

    /**
     * keep image by repository name with specific tag, use for keep latest image
     *
     * @param array $argv
     * @return void
     */
    public static function keepImageBy(array $argv): void
    {
        // === param ===
        $imageRepository = $argv[2] ?? null;
        $imageTag = $argv[3] ?? null;
        // === validate ===
        if (!$imageRepository || !$imageTag) {
            TEXT::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                ->message("missing a 'imageRepository' 'imageTag'");
            exit(); // END
        }
        // === handle ===
        TEXT::tag(TagEnum::DOCKER)->messageTitle("Keep latest Docker image by repository");
        $listImage = self::getListImages();
        if (count($listImage) === 0) {
            TEXT::new()->message("Empty images. Do nothing.")->messageSeparate();
            return; // END
        }
        /** @var DockerImage $image */
        foreach (self::getListImages() as $image) {
            TEXT::indent(IndentLevelEnum::ITEM_LINE)->messageSeparate();
            // case: to check image
            if ($image->getRepository() === $imageRepository) {
                if ($image->getTag() === $imageTag) {
                    TEXT::indent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::CHECK)
                        ->message("Keep image '%s:%s'", $image->getRepository(), $image->getTag());
                    TEXT::indent(IndentLevelEnum::SUB_ITEM_LINE)
                        ->message("(%s | %s)", $image->getCreatedSince(), $image->getSize());
                    // do nothing | skip this image
                } else {
                    TEXT::indent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::X)
                        ->message("Delete image '%s:%s'", $image->getRepository(), $image->getTag());
                    TEXT::indent(IndentLevelEnum::SUB_ITEM_LINE)
                        ->message("(%s | %s)", $image->getCreatedSince(), $image->getSize());
                    (new Process("Delete Docker Image", DIR::getWorkingDir(), [
                        sprintf("docker rmi -f %s", $image->getId())
                    ]))->setOutputParentIndentLevel(IndentLevelEnum::SUB_ITEM_LINE)
                        ->execMultiInWorkDir(true)->printOutput();
                }
                //
                // case: other images
            } else {
                TEXT::indent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::CHECK)
                    ->message("Keep other image '%s:%s'", $image->getRepository(), $image->getTag());
                TEXT::indent(IndentLevelEnum::SUB_ITEM_LINE)
                    ->message("(%s | %s)", $image->getCreatedSince(), $image->getSize());
            }
        }
        //
        TEXT::new()->messageSeparate();
    }

    private static function getListImages(): array
    {
        $list = [];
        $dockerImagesDataArr = (new Process("Get Docker Images Data", DIR::getWorkingDir(), [
            "docker images --format \"{{json .}}\""
        ]))->execMultiInWorkDir(true)->getOutput();
        foreach ($dockerImagesDataArr as $imageDataJson) {
            $imageData = json_decode($imageDataJson, true);
            $list[] = new DockerImage(
                $imageData['Repository'], $imageData['Tag'], $imageData['ID'],
                $imageData['CreatedAt'], $imageData['CreatedSince'], $imageData['Size']
            );
        }
        return $list;
    }

    /**
     * @return bool
     */
    public static function isDanglingImages(): bool
    {
        /** @var DockerImage $image */
        foreach (self::getListImages() as $image) {
            if ($image->isDanglingImage()) return true;
        }
        return false;
    }

    /**
     * remove dangling image / <none> image
     * @return void
     */
    public static function removeDanglingImages(): void
    {
        // === handle ===
        TEXT::tag(TagEnum::DOCKER)->messageTitle("Remove dangling images / <none> images");
        $listImage = self::getListImages();
        if (count($listImage) === 0) {
            TEXT::new()->message("Empty images. Do nothing.")->messageSeparate();
            return; // END
        }
        /** @var DockerImage $image */
        foreach (self::getListImages() as $image) {
            if ($image->isDanglingImage()) {
                TEXT::indent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::X)
                    ->message("Delete dangling image '%s:%s'", $image->getRepository(), $image->getTag());
                (new Process("Delete Docker Image", DIR::getWorkingDir(), [
                    sprintf("docker rmi -f %s", $image->getId())
                ]))->setOutputParentIndentLevel(IndentLevelEnum::SUB_ITEM_LINE)
                    ->execMultiInWorkDir(true)->printOutput();
            }
        }
        //
        TEXT::new()->messageSeparate();
    }
}
