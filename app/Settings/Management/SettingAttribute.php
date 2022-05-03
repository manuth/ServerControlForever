<?php

namespace Gizmo\ServerControlForever\Settings\Management;

use Attribute;

/**
 * Indicates a setting.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class SettingAttribute
{
}
