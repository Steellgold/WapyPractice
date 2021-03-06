<?php

namespace UnknowG\WapyPractice\resources;

use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\BinaryStream;
use UnknowG\WapyPractice\Wapy;

class LoadResources
{
    public static function getSize($bytes) : ?array
    {
        switch(strlen($bytes)){
            case 64*64*4:
                $l = 64;
                $L = 64;
                return [$l, $L];
            case 64*32*4:
                $l = 64;
                $L = 32;
                return [$l, $L];
            case 128*128*4:
                $l = 128;
                $L = 128;
                return [$l, $L];
            default :
                return null;
        }
    }

    public static function getHeadBYTEStoIMG($bytes)
    {
        $size = self::getSize($bytes);
        $l = $size[0] / 8;
        $L = $size[1] / 8;
        $img = self::BYTEStoIMG($bytes);
        $crop = @imagecrop($img, ['x' => $L, 'y' => $L, 'width' => $L, 'height' => $l]);
        @imagedestroy($img);
        return $crop;
    }

    public static function PNGtoBYTES(string $image): string
    {
        $path = Wapy::getResourcesPath() . "/image/npc/" . $image . ".png";
        $img = @imagecreatefrompng($path);
        $bytes = '';

        $L = (int) @getimagesize($path)[0];
        $l = (int) @getimagesize($path)[1];

        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < $L; $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        return $bytes;
    }

    public static function BYTEStoIMG(string $bytes)
    {
        $size = self::getSize($bytes);
        $l = $size[0];
        $L = $size[1];

        $img = @imagecreatetruecolor($l, $L);
        @imagealphablending($img, false);
        @imagesavealpha($img, true);

        $stream = new BinaryStream($bytes);
        for($y = 0; $y < $L; ++$y){
            for($x = 0; $x < $l; ++$x){
                $r = $stream->getByte();
                $g = $stream->getByte();
                $b = $stream->getByte();
                $a = 127 - (int) floor($stream->getByte() / 2);
                $colour = @imagecolorallocatealpha($img, $r, $g, $b, $a);
                @imagesetpixel($img, $x, $y, $colour);
            }
        }
        return $img;
    }

    public static function getGeometry(string $name): string
    {
        $geometry = file_get_contents(Wapy::getResourcesPath() . "/geometry/npc/" . $name . ".json");
        return $geometry;
    }

    public static function overlayingSkin(string $image, string $bytes)
    {
        $L = (int) self::getSize($bytes)[0];
        $l = (int) self::getSize($bytes)[1];

        $img = self::BYTEStoIMG($bytes);

        $path = Wapy::getResourcesPath() . "/image/npc/" . $image .".png";

        $skin = @imagecreatefrompng($path);

        $bytes = "";

        for ($y = 0; $y < $l; $y++) {

            for ($x = 0; $x < $L; $x++) {

                $rgba = @imagecolorat($skin, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;

                if ($g == 255 and $r == 0 and $b == 27 and $a == 254) { //IDK for 254

                    $rgba = @imagecolorat($img, $x, $y);
                    $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                    $r = ($rgba >> 16) & 0xff;
                    $g = ($rgba >> 8) & 0xff;
                    $b = $rgba & 0xff;

                }

                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);

            }

        }

        @imagedestroy($skin);
        @imagedestroy($img);

        return $bytes;
    }

    public static function getSkinTag() : NamedTag
    {
        $skin = str_repeat("\x00", 8192);
        return new CompoundTag("Skin", [
            new StringTag("Name", "Standard_Custom"),
            new ByteArrayTag("Data", $skin)
        ]);
        /*$skin =
            new CompoundTag
            (
                "Skin",
                [
                    "Data" => new StringTag("Data", ),
                    "SkinId" => new StringTag("Name", "SkinTag")
                ]
            );
        return $skin;*/
    }

    public static function getSkinNameWhitSize(int $l, int $L, string $img) : string{
        return $img . $l . "x" . $L;
    }

    public static function getFile(string $image) : bool{
        return file_exists(Wapy::getResourcesPath() . "/image/npc/" . $image .".png");
    }
}
