<?php

namespace app\model;

use think\Model;

/**
 * @mixin think\Model
 */
class User extends Model
{
    protected $name = 'user';
    protected $pk = 'user_id';

    protected $autoWriteTimestamp = true;
}
