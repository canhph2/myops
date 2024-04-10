<?php

namespace app\Helpers;


use app\Enum\DockerEnum;
use app\Enum\IconEnum;
use app\Enum\IndentLevelEnum;
use app\Enum\TagEnum;
use app\Enum\UIEnum;
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
                        ->setColor(UIEnum::COLOR_RED)
                        ->message("Delete image '%s:%s'", $image->getRepository(), $image->getTag());
                    TEXT::indent(IndentLevelEnum::SUB_ITEM_LINE)
                        ->setColor(UIEnum::COLOR_RED)
                        ->message("(%s | %s)", $image->getCreatedSince(), $image->getSize());
                    (new Process("Delete Docker Image", DirHelper::getWorkingDir(), [
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

    /**
     * add ENVs into Dockerfile below FROM line. Required: DockerfilePath, secretName
     *
     * @param array $argv
     * @return void
     */
    public static function DockerfileAddEnvs(array $argv): void
    {
        // === param ===
        $DockerfilePath = $argv[2] ?? null;
        $secretName = $argv[3] ?? null;
        // === validate ===
        if (!$DockerfilePath || !$secretName) {
            TEXT::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                ->message("missing a 'DockerfilePath' 'secretName'");
            exit(); // END
        }
        if (!is_file($DockerfilePath)) {
            TEXT::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR])
                ->message("'DockerfilePath' isn't a file");
            exit(); // END
        }
        // === handle ===
        TEXT::tag(TagEnum::DOCKER)->messageTitle("Dockerfile: get '%s' from AWS Secret and add to Dockerfile", $secretName);
        //    get secret
        $envData = json_decode(exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output json", $secretName)));
        $envLines = ["", "# === Generated vars ==="];
        foreach (explode(PHP_EOL, $envData) as $line) {
            $lineTrim = trim($line);
            if ($lineTrim && strlen($lineTrim) > 0) {
                if ($lineTrim[0] !== '#') {  // skip comment line
                    $envLines[] = sprintf("%s %s", DockerEnum::ENV, $line);
                }
            }
        }
        $envLines[] = "# === end Generated vars ===";
        //    add to Docker file
        $DockerfileLines = [];
        foreach (explode(PHP_EOL, file_get_contents($DockerfilePath)) as $line2) {
            $DockerfileLines[] = $line2;
            // insert envs after FROM ... line
            if (substr($line2, 0, 4) === DockerEnum::FROM) {
                $DockerfileLines = array_merge($DockerfileLines, $envLines);
            }
        }
        file_put_contents($DockerfilePath, implode(PHP_EOL, $DockerfileLines));
        //    validate result
        if (STR::contains(file_get_contents($DockerfilePath), DockerEnum::ENV)) {
            Text::tag(TagEnum::SUCCESS)->message("get '%s' from AWS Secret and add to Dockerfile successfully", $secretName);
        } else {
            Text::tag(TagEnum::ERROR)->message("ENV data doesn't exist in Dockerfile");
            exit(1);
        }
        Text::new()->messageSeparate();
    }

    private static function getListImages(): array
    {
        $list = [];
        $dockerImagesDataArr = (new Process("Get Docker Images Data", DirHelper::getWorkingDir(), [
            "docker images --format \"{{json .}}\" || exit 0"
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
                    ->setColor(UIEnum::COLOR_RED)
                    ->message("Delete dangling image '%s:%s'", $image->getRepository(), $image->getTag());
                (new Process("Delete Docker Image", DirHelper::getWorkingDir(), [
                    sprintf("docker rmi -f %s", $image->getId())
                ]))->setOutputParentIndentLevel(IndentLevelEnum::SUB_ITEM_LINE)
                    ->execMultiInWorkDir(true)->printOutput();
            }
        }
        //
        TEXT::new()->messageSeparate();
    }
}
