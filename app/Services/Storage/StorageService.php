<?php
/*
 * StorageService.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of the Firefly III Nordigen importer
 * (https://github.com/firefly-iii/nordigen-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Storage;
use Str;
use UnexpectedValueException;
use Log;
/**
 * Class StorageService
 */
class StorageService
{
    /**
     * @param string $name
     *
     * @return string
     */
    public static function getContent(string $name): string
    {
        $disk = Storage::disk('uploads');
        if ($disk->exists($name)) {
            Log::debug(sprintf('StorageService returns the content of file "%s"', $name));
            return $disk->get($name);
        }
        throw new UnexpectedValueException(sprintf('No such file %s', $name));
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public static function storeContent(string $content): string
    {
        $fileName = Str::random(20);
        $disk     = Storage::disk('uploads');
        $disk->put($fileName, $content);
        Log::debug(sprintf('StorageService saves content into file "%s"', $fileName));

        return $fileName;
    }

}
