<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Status extends Model
{
  // 我们可在微博模型中，指明一条微博属于一个用户
    public function user(){

      return $this->belongsTo(User::class);

    }
}
