<?php

/*
 * Copyright (C) 2015-2016 Libre Informatique
 *
 * This file is licenced under the GNU GPL v3.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Librinfo\EcommerceBundle\Entity;

use AppBundle\Entity\OuterExtension\LibrinfoEcommerceBundle\AdminUserExtension;
use Blast\OuterExtensionBundle\Entity\Traits\OuterExtensible;
use Sylius\Component\Core\Model\AdminUser as BaseAdminUser;

class AdminUser extends BaseAdminUser
{
    use OuterExtensible, AdminUserExtension;
}