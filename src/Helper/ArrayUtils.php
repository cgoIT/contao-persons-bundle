<?php

declare(strict_types=1);

namespace Cgoit\PersonsBundle\Helper;

class ArrayUtils
{
    /**
     * @param array<mixed> $arrCurrent
     * @param array<mixed> $newEntry
     *
     * @return array<mixed>|null
     */
    public static function insertInAssociativeArray(array $arrCurrent, mixed $afterKey, array $newEntry): array|null
    {
        if (!\is_array($arrCurrent)) {
            return null;
        }

        if (!\is_array($newEntry)) {
            return $arrCurrent;
        }

        $key = array_search($afterKey, array_keys($arrCurrent), true);

        return array_merge(
            \array_slice($arrCurrent, 0, $key + 1, true),
            $newEntry,
            \array_slice(
                $arrCurrent,
                $key + 1,
                null,
                true,
            ),
        );
    }

    /**
     * @param array<mixed> $arrCurrent
     */
    public static function countByKeyAndValue(array $arrCurrent, string $key, mixed $val): int
    {
        $count = 0;

        foreach ($arrCurrent as $entry) {
            $checkVal = null;

            if (\is_array($entry) && isset($entry[$key])) {
                $checkVal = $entry[$key];
            } elseif (\is_object($entry) && property_exists($entry, $key)) {
                $checkVal = $entry->$key;
            } else {
                $checkVal = $entry;
            }

            if ($checkVal === $val) {
                ++$count;
            }
        }

        return $count;
    }
}
