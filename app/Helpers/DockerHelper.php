<?php

namespace app\Helpers;


use app\Enum\IndentLevelEnum;
use app\Objects\DockerImage;
use app\Objects\Process;

class DockerHelper
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
            TextHelper::messageERROR("[PARAMS] missing a 'imageRepository' 'imageTag'");
            exit(); // END
        }
        // === handle ===
        TextHelper::messageTitle("Keep latest Docker image by repository");
        $listImage = self::getListImages();
        if (count($listImage) === 0) {
            TextHelper::message("Empty images. Do nothing.");
            TextHelper::messageSeparate();
            return; // END
        }
        /** @var DockerImage $image */
        foreach (self::getListImages() as $image) {
            TextHelper::messageSeparate(IndentLevelEnum::ITEM_LINE);
            // case: to check image
            if ($image->getRepository() === $imageRepository) {
                if ($image->getTag() === $imageTag) {
                    TextHelper::message(sprintf("✔ Keep image '%s:%s'", $image->getRepository(), $image->getTag()), IndentLevelEnum::ITEM_LINE);
                    // do nothing | skip this image
                } else {
                    TextHelper::message(sprintf("X Delete image '%s:%s'", $image->getRepository(), $image->getTag()), IndentLevelEnum::ITEM_LINE);
                    (new Process("Delete Docker Image", DirHelper::getWorkingDir(), [
                        sprintf("docker rmi -f %s", $image->getId())
                    ]))->execMultiInWorkDir(true)->printOutput();
                }
                //
                // case: other images
            } else {
                TextHelper::message(sprintf("✔ Keep other image '%s:%s'", $image->getRepository(), $image->getTag()), IndentLevelEnum::ITEM_LINE);
            }
        }
        //
        TextHelper::messageSeparate();
    }

    private static function getListImages(): array
    {
        $list = [];
        $dockerImagesDataArr = (new Process("Get Docker Images Data", DirHelper::getWorkingDir(), [
            "docker images --format \"{{json .}}\""
        ]))->execMultiInWorkDir(true)->getOutput();
        foreach ($dockerImagesDataArr as $imageDataJson) {
            $imageData = json_decode($imageDataJson, true);
            $list[] = new DockerImage(
                $imageData['Repository'], $imageData['Tag'], $imageData['ID'],
                $imageData['CreatedAt'], $imageData['Size']
            );
        }
        return $list;
    }

    /**
     * @return bool
     */
    public static function isDanglingImages():bool
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
        TextHelper::messageTitle("[Docker] Remove dangling images / <none> images");
        $listImage = self::getListImages();
        if (count($listImage) === 0) {
            TextHelper::message("Empty images. Do nothing.");
            TextHelper::messageSeparate();
            return; // END
        }
        /** @var DockerImage $image */
        foreach (self::getListImages() as $image) {
            if ($image->isDanglingImage()) {
                TextHelper::message(sprintf("X Delete dangling image '%s:%s'", $image->getRepository(), $image->getTag()));
                (new Process("Delete Docker Image", DirHelper::getWorkingDir(), [
                    sprintf("docker rmi -f %s", $image->getId())
                ]))->execMultiInWorkDir(true)->printOutput();
            }
        }
        //
        TextHelper::messageSeparate();
    }
}
