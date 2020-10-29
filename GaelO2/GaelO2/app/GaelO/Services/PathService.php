<?php

namespace App\GaelO\Services;

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
}
