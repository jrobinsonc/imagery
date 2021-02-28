<?php

namespace JRDev\Imagery;

use JRDev\Imagery\Exceptions\InputException;
use JRDev\Imagery\Exceptions\ConfigException;
use JRDev\Imagery\Image;
use Intervention\Image\ImageManager;
use JRDev\Imagery\Exceptions\SystemException;

class Imagery
{
  /**
   * @var \Intervention\Image\ImageManager
   */
    protected $imageManager;

  /**
   * @var string
   */
    protected $cacheDir;

  /**
   * @var string
   */
    protected $srcDir;

  /**
   * @param string $srcDir
   * @param string $cacheDir
   * @param array<string, mixed> $options
   */
    public function __construct($srcDir, $cacheDir, $options = [])
    {
        $this->srcDir = (string) realpath($srcDir);

        if (! is_dir($this->srcDir) || ! is_readable($this->srcDir)) {
            throw new ConfigException('The source dir does not exist or is not readable.');
        }

        $this->cacheDir = (string) realpath($cacheDir);

        if (! is_dir($this->cacheDir) || ! is_writable($this->cacheDir)) {
            throw new ConfigException('The cache dir does not exist or is not writable.');
        }

        $this->imageManager = new ImageManager(
            $this->parseOptions($options)
        );
    }

    /**
     * @param string $path
     * @return \JRDev\Imagery\Image
     */
    public function load($path)
    {
        return new Image($this->srcDir, $this->cacheDir, $path, $this->imageManager);
    }

  /**
   * @param array<string, mixed> $options
   * @return array<string, mixed>
   */
    protected function parseOptions($options)
    {
        $output = $options;

        if (isset($output['driver']) && $output['driver'] === 'auto') {
            unset($output['driver']);

            foreach (['imagick', 'gd'] as $lib) {
                if (extension_loaded($lib)) {
                    $output['driver'] = $lib;
                    break;
                }
            }
        }

        return $output;
    }
}
