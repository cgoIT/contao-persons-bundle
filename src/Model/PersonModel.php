<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2025, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Model;

use Codefog\TagsBundle\Model\TagModel;
use Codefog\TagsBundle\Tag;
use Contao\Model;
use Contao\Model\Collection;
use Contao\Model\MetadataTrait;

/**
 * add properties for IDE support.
 *
 * @property string|int                           $id
 * @property string|int                           $tstamp
 * @property string                               $firstName
 * @property string                               $name
 * @property string                               $position
 * @property array<Tag>|Collection<TagModel>|null $tags
 * @property string|null                          $singleSRC
 * @property string|int                           $size
 * @property array<mixed>|null                    $contactInformation
 * @property bool                                 $invisible
 *
 * @method static PersonModel|null findById($id, array<mixed> $opt=array())
 */
class PersonModel extends Model
{
    use MetadataTrait;

    protected static $strTable = 'tl_person';
}
