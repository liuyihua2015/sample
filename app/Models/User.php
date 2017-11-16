<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\ResetPassword;
use App\Models\Status;
use Auth;

class User extends Authenticatable
{
    use Notifiable;

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = [
      'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email'])));
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }

    //  boot 方法会在用户模型类完成初始化之后进行加载，因此我们对事件的监听需要放在该方法中。
    public static function boot()
    {
        parent::boot();
        static::creating(function ($user) {
            $user->activation_token = str_random(30);
        });
    }
    public function boot1()
    {
        parent::boot();
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    // 在用户模型中，指明一个用户拥有多条微博。
    public function statuses()
    {
        return $this->hasMany(Status::class);
    }


    //获取当前用户发布过的所有微博从数据库中取出，并根据创建时间来倒序排序
    public function feed()
    {
      $user_ids = Auth::user()->followings->pluck('id')->toArray();
      array_push($user_ids, Auth::user()->id);
      return Status::whereIn('user_id', $user_ids)
      ->with('user')
      ->orderBy('created_at', 'desc');
    }

    //粉丝和关注者 多对多关系

    // 获取粉丝关系列表
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'followers_id');
    }
    // 来获取用户关注人列表
    public function followings()
    {
        return $this->belongsToMany(User::class, 'followers', 'followers_id', 'user_id');
    }


    //关注操作
    public function follow($user_ids)
    {
        if (!is_array($user_ids)) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->sync($user_ids, false);
    }

    // 取消关注操作
    public function unfollow($user_ids)
    {
        if (!is_array($user_ids)) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->detach($user_ids);
    }
    // 是否已经关注了：只需要判断用户 B 是否包含在用户 A 的关注人列表上即可。这里我们将用到 contains 方法来做判断。
    public function isFollowing($user_id)
    {
        return $this->followings->contains($user_id);
    }
}
