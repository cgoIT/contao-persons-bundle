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

use Contao\CoreBundle\File\ModelMetadataTrait;
use Contao\Model;

/**
 * add properties for IDE support.
 *
 * @property string|int  $id
 * @property string|int  $tstamp
 * @property string      $firstName
 * @property string      $name
 * @property string|null $singleSRC
 * @property string|int  $size
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property bool        $invisible
 *
 * @method static PersonModel|null findById($id, array $opt=array())
 */
class PersonModel extends Model
{
    use ModelMetadataTrait;

    protected static $strTable = 'tl_person';
}
