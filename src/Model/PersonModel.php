<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle.
 *
 * (c) Carsten Götzinger
 *
 * @license LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Model;

use Codefog\TagsBundle\Tag;
use Contao\CoreBundle\File\ModelMetadataTrait;
use Contao\Model;
use Contao\Model\Collection;

/**
 * add properties for IDE support.
 *
 * @property string|int                 $id
 * @property string|int                 $tstamp
 * @property string                     $firstName
 * @property string                     $name
 * @property string                     $position
 * @property array<Tag>|Collection|null $tags
 * @property string|null                $singleSRC
 * @property string|int                 $size
 * @property array<mixed>|null          $contactInformation
 * @property bool                       $invisible
 *
 * @method static PersonModel|null findById($id, array $opt=array())
 */
class PersonModel extends Model
{
    use ModelMetadataTrait;

    protected static $strTable = 'tl_person';
}
