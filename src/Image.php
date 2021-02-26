<?php

namespace JRDev\Imagery;

use JRDev\Imagery\Exceptions\FileNotFoundException;

class Image
{
  /**
   * @var string
   */
  public $path;

  /**
   * @var \Intervention\Image\Image
   */
  public $resource;

  // /**
  //  * @var resource
  //  */
  // protected $tempFile;

  /**
   * @param string $path
   * @throws FileNotFoundException
   * @return void
   */
  public function __construct($path)
  {
    $this->path = urldecode($path);

    if (! file_exists($this->path) || ! is_readable($this->path)) {
      throw new FileNotFoundException('The image doesn\'t exist');
    }
  }

  // /**
  //  * @return void
  //  */
  // public function __destruct()
  // {
  //   $this->closeResources();
  // }

  // /**
  //  * Close used resources.
  //  *
  //  * @return void
  //  */
  // protected function closeResources()
  // {
  //   $resources = [$this->tempFile, $this->srcFile];

  //   foreach ($resources as $resource) {
  //     if (is_resource($resource)) {
  //       fclose($resource);
  //     }
  //   }
  // }

  // /**
  //  * @return resource
  //  */
  // public function getTempFile()
  // {
  //   if ($this->tempFile === null) {
  //     $this->tempFile = tmpfile();
  //   }

  //   if ($this->tempFile === false) {
  //     throw new SystemException('The temporary file could not be created.');
  //   }

  //   return $this->tempFile;
  // }

  // /**
  //  * @return resource
  //  */
  // public function getSrcFile()
  // {
  //   if ($this->srcFile === null) {
  //     $this->srcFile = fopen($this->uri, 'r');
  //   }

  //   if ($this->srcFile === false) {
  //     throw new SystemException('The source file is not readable.');
  //   }

  //   return $this->srcFile;
  // }
}
