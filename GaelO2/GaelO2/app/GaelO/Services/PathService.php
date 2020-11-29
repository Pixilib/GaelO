<?php

namespace App\GaelO\Services;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PathService {

    public static function getFileInPathGenerator(String $path) {

		if (is_dir($path)) {
			// Create recursive directory iterator
			$files=new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path),
				RecursiveIteratorIterator::LEAVES_ONLY
				);

			foreach ($files as $name => $file) {
				// Skip directories (they would be added automatically)
				if (!$file->isDir()) {
					// Get real and relative path for current file
					yield $file;
				}
			}
		}
    }


    public static function recursive_directory_delete(string $directory) {
        $it=new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
        $it=new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            if ($file->isDir()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir($directory);
    }

    public static function getPathAsFileArray($directory) : array {
        $rii=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        $files=[];

        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }
            $files[]=$file->getPathname();
        }

        return $files;

    }

    public static function getZipUncompressedSize(string $filename) {
        $size=0;
        $resource=zip_open($filename);
        while ($dir_resource=zip_read($resource)) {
            $size+=zip_entry_filesize($dir_resource);
        }
        zip_close($resource);

        return $size;
    }

}
