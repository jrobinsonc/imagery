<?php

namespace JRDev\Imagery;

use JRDev\Imagery\Exceptions\FileNotFoundException;
use JRDev\Imagery\Exceptions\SystemException;
use JRDev\Imagery\Exceptions\InputException;

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
        if (is_null($this->lib)) {
            return;
        }

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
        $ds = DIRECTORY_SEPARATOR;

        $this->data['fullPath'] = sprintf(
            '%s%s%s',
            rtrim($this->srcDir, $ds),
            $ds,
            ltrim($this->path, $ds)
        );

        if (! file_exists($this->data['fullPath']) || ! is_readable($this->data['fullPath'])) {
            throw new FileNotFoundException('Image doesn\'t exist or is not readable: ' . $this->path);
        }

        $this->data['cachePath'] = sprintf(
            '%s%s%s',
            rtrim($this->cacheDir, $ds),
            $ds,
            ltrim($this->path, $ds)
        );

        $info = pathinfo($this->data['fullPath']);

        $this->data['extension'] = $info['extension'] ?? '';
    }

    protected function parseFilterCustomArg($optionName, $array)
    {
        if (! in_array($optionName, $array)) {
            return null;
        }

        switch ($optionName) {
            case 'upsize':
                return function($constraint){ $constraint->upsize(); };
                break;
        }

        return null;
    }

    /**
     * Builds the arguments needed to call the method of the image manipulation library.
     *
     * @param string $filterName
     * @param array $params
     * @return array
     */
    protected function buildFilterArgs($filterName, $params)
    {
        $cbParams = [];

        switch ($filterName) {
            case 'widen': // http://image.intervention.io/api/widen
                // width
                $cbParams[0] = (int) $params[0] ?? 0;

                // callback
                $cbParams[1] = $this->parseFilterCustomArg('upsize', $params[1] ?? []);
                break;

            case 'heighten': // http://image.intervention.io/api/heighten
                // height
                $cbParams[0] = (int) $params[0] ?? 0;

                // callback
                $cbParams[1] = $this->parseFilterCustomArg('upsize', $params[1] ?? []);
                break;

            case 'limitColors': // http://image.intervention.io/api/limitColors
                // count
                $cbParams[0] = (int) $params[0] ?? 0;

                // matte
                $cbParams[1] = $params[1] ?? null;
                break;

            default:
                throw new InputException('Unknown filter: ' . $filterName);
        }

        return $cbParams;
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
            'resize',
            'widen',
            'heighten',
            'fit',
            'crop',
            'limitColors',
        ];

        foreach ($filters as $filterName) {
            if (empty($options[$filterName])) {
                continue;
            }

            $filterMethodCb = [$this->lib, $filterName];
            $filterMethodArgs = $this->buildFilterArgs($filterName, $options[$filterName]);

            call_user_func_array($filterMethodCb, $filterMethodArgs);
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
