<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageTreatmentService
{
    private static ?ImageTreatmentService $instance = null;
    private ImageManager $manager;

    // Private constructor prevents direct instantiation
    private function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserializing
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): ImageTreatmentService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Treat the image: read, downscale if needed, and encode to WebP with custom quality.
     *
     * @param mixed $blob
     * @param int $quality
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     * @return \Intervention\Image\EncodedImage
     */
    public function treat($blob, int $quality = 80, ?int $maxWidth = 1200, ?int $maxHeight = 1200)
    {
        $image = $this->manager->read($blob);
        
        if ($maxWidth !== null || $maxHeight !== null) {
            $image->scaleDown(width: $maxWidth, height: $maxHeight);
        }
        
        return $image->toWebp($quality);
    }
}
