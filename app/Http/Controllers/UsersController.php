<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Auth;
use Mail;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', [
       'except' => ['show', 'create', 'store','index','confirmEmail']
    ]);
        // 只让未登录用户访问注册页面：
        $this->middleware('guest', [
      'only' => ['create']
    ]);
    }


    public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
        $statuses = $user->statuses()->orderBy('created_at','desc')->paginate(30);
        return view('users.show', compact('user','statuses'));
    }
    public function store(Request $request)
    {
        //验证
        $this->validate($request, [
      'name' => 'required|max:50',
      'email' => 'required|email|unique:users|max:255',
      'password' => 'required|confirmed|min:6'
    ]);


        //创建用户对象,并保存到数据库
        $user = User::create([
      'name' => $request->name,
      'email' => $request->email,
      'password' => bcrypt($request->password),

    ]);

        // // Auth::login($user);
        // //临时数据保存，之后使用session()->get('success') 获取数据
        // session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');
        // //用户模型 User::create() 创建成功后会返回一个用户对象，并包含新注册用户的所有信息。我们将新注册用户的所有信息赋值给变量 $user，并通过路由跳转来进行数据绑定。
        // return redirect()->route('users.show', [$user]);

        //发送注册邮件，返回首页
        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        return redirect('/');
    }

    //编辑
    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }
    //更新
    public function update(User $user, Request $request)
    {
        $this->validate($request, [
      'name' => 'required|max:50',
      'password' => 'nullable|confirmed|min:6'
    ]);

        $this->authorize('update', $user);

        $date = [];
        $date['name'] = $request->name;
        if ($request->password) {
            $date['password'] =  bcrypt($request->password);
        }
        $user->update($date);

        session()->flash('success', '个人资料更新成功!');

        return redirect()->route('users.show', $user->id);
    }
    //个人页面
    public function index()
    {
        $users = User::paginate(10);
        return view('users.index', compact('users'));
    }
    public function destroy(User $user)
    {
        // 删除授权策略 destroy 我们已经在上面创建了，这里我们在用户控制器中使用 authorize 方法来对删除操作进行授权验证即可。在删除动作的授权中，我们规定只有当前用户为管理员，且被删除用户不是自己时，授权才能通过。
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }

    //发送邮件
    public function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        $from = '529732278@qq.com';
        $name = 'liuyihua';
        $to = $user->email;
        $subject = "感谢注册 Sample 应用！请确认你的邮箱。";

        Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });
    }

    //邮件激活成功
    public function confirmEmail($token)
    {
        $user = user::where('activation_token', $token)->firstOrFail();
        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', [$user]);
    }

    public function followings(User $user)
    {
        $users = $user->followings()->paginate(30);
        $title = '关注的人';
        return view('users.show_follow', compact('users', 'title'));
    }

    public function followers(User $user)
    {
        $users = $user->followers()->paginate(30);
        $title = '粉丝';
        return view('users.show_follow', compact('users', 'title'));
    }
}
