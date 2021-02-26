<?php

namespace JRDev\Imagery;

use Drupal\Core\Config\ConfigException;
use JRDev\Imagery\Exceptions\InputException;
use JRDev\Imagery\Image;
use Intervention\Image\ImageManager;
use JRDev\Imagery\Exceptions\SystemException;

class Imagery
{
  /**
   * @var \JRDev\Imagery\Image
   */
  public $image;

  /**
   * @var \Intervention\Image\ImageManager
   */
  public $imageManager;

  /**
   * @var string
   */
  public $cacheDir;

  /**
   * @var string
   */
  public $path;

  /**
   * @param mixed $path
   * @param string $srcDir
   * @param string $cacheDir
   */
  public function __construct($path, $srcDir, $cacheDir)
  {
    if (empty($path)) {
      throw new InputException('The image path should not be empty.');
    }

    if (! is_dir($srcDir)) {
      throw new ConfigException('The source dir does not exist.');
    }

    if (empty($cacheDir)) {
      throw new ConfigException('The cache dir does not exist.');
    }

    $this->imageManager = new ImageManager();
    $this->image = new Image("$srcDir/$path", $this->imageManager);
    $this->path = $path;
    $this->cacheDir = $cacheDir;
  }

  public function getCachePath()
  {
    return "$this->cacheDir/$this->path";
  }

  /**
   * @param array $options
   */
  public function compress($options = [])
  {
    $cachePath = $this->getCachePath();
    $cachePathDir = dirname($cachePath);

    if (! is_dir($cachePathDir) && ! mkdir($cachePathDir, 0755, true)) {
      throw new SystemException('The cache directory could not be created: ' . $cachePathDir);
    }

    if (isset($options['limitColors'])) {
      $this->image->resource->limitColors($options['limitColors'][0], $options['limitColors'][1]);
    }

    $this->image->resource->save($cachePath, $options['quality'] ?? 90);
  }

  public function response()
  {
    header('Content-type: ' . $this->imageManager->make($this->getCachePath())->mime());
    header('X-Imagery: ' . time());
    readfile($this->getCachePath());
  }
}
