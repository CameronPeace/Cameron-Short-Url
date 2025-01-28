<?php

namespace App\Traits;

trait HandlesCsv
{

    /**
     * Defaults to 50mb
     */
    protected $maxFileSize = 50 * 1024 * 1024;

    /**
     * Validate the file is not larger than desired.
     *
     * @param string $filePath
     *
     * @return bool
     */
    public function isValidSize(string $filePath)
    {

        $fileSize = filesize($filePath);

        if ($fileSize > $this->getMaxFileSize()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the file at the path provided is a csv file.
     *
     * @param string $filePath
     *
     * @return bool
     */
    public function isCsv(string $filePath)
    {
        if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'csv') {
            return false;
        }

        return true;
    }

    /**
     * Check if the file exists at the path provided.s
     *
     * @param string $filePath
     *
     * @return bool
     */
    public function fileExists(string $filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        return true;
    }

    /**
     * Set the max file size for a csv.
     *
     * @param int $fileSize
     *
     */
    public function setMaxFileSize(int $fileSize)
    {
        $this->maxFileSize = $fileSize;
    }

    /**
     * Get the sex max file size.
     *
     * @return int
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }
}
