<?php

namespace JRDev\Imagery;

use JRDev\Imagery\Exceptions\FileNotFoundException;
use JRDev\Imagery\Exceptions\SystemException;

class Image
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $srcDir;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var \Intervention\Image\Image
     */
    protected $lib;

    /**
     * @var array<string, mixed>
     */
    protected $data;

    /**
     * @param string $srcDir
     * @param string $cacheDir
     * @param string $path
     * @param \Intervention\Image\ImageManager $imageManager
     * @throws \JRDev\Imagery\Exceptions\FileNotFoundException
     * @return void
     */
    public function __construct($srcDir, $cacheDir, $path, &$imageManager)
    {
        $this->srcDir = $srcDir;
        $this->cacheDir = $cacheDir;
        $this->path = $path;

        $this->parseImage();

        $this->lib = $imageManager->make($this->data['fullPath']);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->lib->destroy();
    }

    /**
     * @param string $varName
     * @return mixed
     */
    public function __get($varName)
    {
        return $this->data[$varName] ?? null;
    }

    /**
     * @return void
     */
    protected function parseImage()
    {
        $this->data['fullPath'] = "{$this->srcDir}/{$this->path}";

        if (! file_exists($this->data['fullPath']) || ! is_readable($this->data['fullPath'])) {
            throw new FileNotFoundException('Image doesn\'t exist or is not readable: ' . $this->path);
        }

        $this->data['cachePath'] = "{$this->cacheDir}/{$this->path}";

        $info = pathinfo($this->data['fullPath']);

        $this->data['extension'] = $info['extension'] ?? '';
    }

    /**
     * @param array<string, mixed> $options
     * @return void
     */
    public function process($options = [])
    {
        $cachePathDir = dirname($this->data['cachePath']);

        if (! is_dir($cachePathDir) && ! mkdir($cachePathDir, 0755, true)) {
            throw new SystemException('Directory could not be created: ' . $cachePathDir);
        }

        $filters = [
            'limitColors',
            'crop',
            'widen',
            'heighten',
            'fit',
            'resize',
        ];

        foreach ($filters as $key) {
            if (empty($options[$key])) {
                continue;
            }

            $data = $options[$key];

            if (! is_array($data)) {
                $data = [$data];
            }

            call_user_func_array([$this->lib, $key], $data);
        }

        $quality = $options['quality'] ?? 100;

        $this->lib->save($this->data['cachePath'], $quality);
    }

    /**
     * @param array<string, string> $headers
     * @return void
     */
    public function response($headers = [])
    {
        // Date when the image was generated.
        $headers['X-Imagery'] = date('c');
        $headers['Content-type'] = $this->lib->mime();
        $headers['Content-Length'] = filesize($this->data['cachePath']);

        foreach ($headers as $key => $value) {
            header(sprintf('%s: %s', $key, $value));
        }

        readfile($this->data['cachePath']);
    }
}
